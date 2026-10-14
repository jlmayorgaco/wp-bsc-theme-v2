<?php
/** Wompi Portal safety adapter: retained across third-party plugin updates. */
defined( 'ABSPATH' ) || exit;

add_filter( 'http_request_args', 'bsc_wompi_secure_http', PHP_INT_MAX, 2 );
add_action( 'woocommerce_api_wc_wompi', 'bsc_wompi_secure_webhook', -100 );

function bsc_wompi_secure_http( array $args, string $url ): array {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( in_array( $host, array( 'production.wompi.co', 'sandbox.wompi.co', 'api.wompi.co' ), true ) ) {
		$args['sslverify'] = true;
		$args['redirection'] = 0;
	}
	return $args;
}

function bsc_wompi_event_secret(): string {
	if ( ! class_exists( 'Wompi_Portal_Pagos_Main' ) ) {
		return '';
	}
	$test = 'yes' === Wompi_Portal_Pagos_Main::get_setting( 'testmode' );
	return trim( (string) Wompi_Portal_Pagos_Main::get_setting( $test ? 'test_event_secret_key' : 'event_secret_key', '' ) );
}

function bsc_wompi_valid_event( object $event, string $secret, string $environment ): bool {
	if ( '' === $secret || 'transaction.updated' !== ( $event->event ?? '' ) || $environment !== ( $event->environment ?? '' ) ) {
		return false;
	}
	$properties = $event->signature->properties ?? null;
	$checksum = $event->signature->checksum ?? null;
	if ( ! is_array( $properties ) || ! $properties || count( $properties ) > 32 || ! is_string( $checksum ) || ! preg_match( '/^[a-f0-9]{64}$/iD', $checksum ) || ! is_int( $event->timestamp ?? null ) || $event->timestamp <= 0 ) {
		return false;
	}
	$value = '';
	foreach ( $properties as $property ) {
		if ( ! is_string( $property ) || strlen( $property ) > 256 || ! preg_match( '/^[a-zA-Z0-9_]+(?:\.[a-zA-Z0-9_]+)*$/D', $property ) ) {
			return false;
		}
		$part = $event->data ?? null;
		foreach ( explode( '.', $property ) as $key ) {
			if ( ! is_object( $part ) || ! property_exists( $part, $key ) ) {
				return false;
			}
			$part = $part->{$key};
		}
		if ( ! is_scalar( $part ) || strlen( (string) $part ) > 4096 ) {
			return false;
		}
		$value .= (string) $part;
	}
	return hash_equals( hash( 'sha256', $value . $event->timestamp . $secret ), strtolower( $checksum ) );
}

/** Select a transition without regressing a fulfilled or previously paid order. */
function bsc_wompi_payment_action( string $status, bool $paid, string $accepted_id, string $incoming_id, string $incoming_status ): string {
	$fulfilled = in_array( $status, array( 'preparing', 'shipped', 'completed', 'refunded' ), true );
	if ( 'APPROVED' === $incoming_status ) {
		return $paid || $fulfilled || ( '' !== $accepted_id && $accepted_id !== $incoming_id ) ? 'ignore' : 'approve';
	}
	if ( $paid || $fulfilled || '' !== $accepted_id ) {
		return 'ignore';
	}
	if ( in_array( $incoming_status, array( 'DECLINED', 'ERROR', 'VOIDED' ), true ) ) {
		return in_array( $status, array( 'pending', 'on-hold' ), true ) ? 'fail' : 'ignore';
	}
	return 'ignore'; // Pending and unknown events never mark an order failed.
}

/** Authoritative API transaction only. Caller must not pass the raw event transaction. */
function bsc_wompi_reconcile_transaction( object $transaction, string $order_key = '' ) {
	$reference = (string) ( $transaction->reference ?? '' );
	$id = (string) ( $transaction->id ?? '' );
	if ( ! ctype_digit( $reference ) || (int) $reference <= 0 || '' === $id ) {
		return new WP_Error( 'invalid_transaction', 'Transacción inválida.' );
	}
	return BSC_DB_Lock::run( 'payment-order:' . $reference, static function () use ( $reference, $id, $transaction, $order_key ) {
		$order = wc_get_order( (int) $reference );
		if ( ! $order instanceof WC_Order || 'wompi' !== $order->get_payment_method() || ( '' !== $order_key && ! hash_equals( $order->get_order_key(), $order_key ) ) ) {
			return new WP_Error( 'invalid_order', 'Pedido inválido.' );
		}
		$order->read_meta_data( true );
		$expected = (int) round( (float) $order->get_total() * 100 );
		if ( ! is_numeric( $transaction->amount_in_cents ?? null ) || (float) $transaction->amount_in_cents !== (float) $expected || ( $transaction->currency ?? '' ) !== $order->get_currency() ) {
			return new WP_Error( 'transaction_mismatch', 'El monto o la moneda no coinciden.' );
		}
		$status = (string) ( $transaction->status ?? '' );
		$event_key = hash( 'sha256', $id . ':' . $status );
		$seen = (array) $order->get_meta( '_bsc_wompi_events', true );
		if ( in_array( $event_key, $seen, true ) ) {
			return true;
		}
		$accepted = (string) $order->get_meta( '_bsc_wompi_approved_id', true );
		$paid = $order->is_paid() || (bool) $order->get_date_paid();
		$action = bsc_wompi_payment_action( $order->get_status(), $paid, $accepted, $id, $status );
		if ( 'approve' === $action ) {
			bsc_update_order_from_wompi_transaction( $order, $transaction ); // WC CRUD, including HPOS.
			$order->payment_complete( $id );
			$order->update_meta_data( '_bsc_wompi_approved_id', $id );
			$order->add_order_note( 'Pago Wompi verificado. Transacción: ' . sanitize_text_field( $id ) );
		} elseif ( 'fail' === $action ) {
			// A failed attempt is retryable; never overwrite a recognized payment.
			$order->update_status( 'failed', 'Intento Wompi no aprobado: ' . sanitize_text_field( $id ) );
		} elseif ( 'VOIDED' === $status && $paid ) {
			// Voids of a settled payment require reconciliation, not an automatic stock return.
			$order->update_meta_data( '_bsc_payment_review_required', 'voided' );
			$order->add_order_note( 'Wompi reportó una anulación de pago. Revisar conciliación antes de modificar entrega o reembolso.' );
		}
		$seen[] = $event_key;
		$order->update_meta_data( '_bsc_wompi_events', array_slice( $seen, -50 ) );
		$order->save();
		return true;
	} );
}

/** The early callback owns the response; the unsafe vendor handler cannot run afterward. */
function bsc_wompi_secure_webhook(): void {
	if ( ! class_exists( 'Wompi_Portal_Pagos_Main' ) ) {
		return;
	}
	$code = 400;
	try {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		// Raw JSON must not be unslashed or passed through a text sanitizer before decoding/signature verification.
		$body = 'post' === $method ? file_get_contents( 'php://input', false, null, 0, 262145 ) : '';
		$event = is_string( $body ) && strlen( $body ) <= 262144 ? json_decode( $body, false, 32 ) : null;
		$secret = bsc_wompi_event_secret();
		$environment = 'yes' === Wompi_Portal_Pagos_Main::get_setting( 'testmode' ) ? 'test' : 'prod';
		if ( '' === $secret ) {
			$code = 503;
		} elseif ( is_object( $event ) && bsc_wompi_valid_event( $event, $secret, $environment ) ) {
			$id = $event->data->transaction->id ?? null;
			if ( is_string( $id ) && strlen( $id ) <= 200 ) {
				$transaction = bsc_get_wompi_transaction_for_return( $id );
				if ( ! $transaction || (string) ( $transaction->id ?? '' ) !== $id ) {
					$code = 503; // Retry when the authoritative API cannot be verified.
				} else {
					$result = bsc_wompi_reconcile_transaction( $transaction );
					$code = is_wp_error( $result ) ? ( 'bsc_lock_timeout' === $result->get_error_code() ? 503 : 400 ) : 200;
				}
			}
		}
	} catch ( Throwable $error ) {
		$code = 503;
		error_log( 'BSC: Wompi reconciliation failed; event will be retried.' );
	}
	status_header( $code );
	nocache_headers();
	exit;
}
