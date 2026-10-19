<?php
/**
 * Wompi Portal safety adapter retained across third-party plugin updates.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'http_request_args', 'bsc_wompi_secure_http', PHP_INT_MAX, 2 );
add_action( 'woocommerce_api_wc_wompi', 'bsc_wompi_secure_webhook', -100 );

require_once __DIR__ . '/wompi-recovery.php';

/**
 * Validate a transaction identifier before using it in API paths, jobs or logs.
 *
 * @param mixed $id Candidate identifier.
 * @return bool
 */
function bsc_wompi_valid_transaction_id( $id ): bool {
	return is_string( $id ) && 1 === preg_match( '/^[a-zA-Z0-9_-]{1,200}$/D', $id );
}

/**
 * Get the active gateway environment.
 *
 * @return string
 */
function bsc_wompi_environment(): string {
	return class_exists( 'Wompi_Portal_Pagos_Main' ) && 'yes' === Wompi_Portal_Pagos_Main::get_setting( 'testmode' ) ? 'test' : 'prod';
}

/**
 * Log technical fields only, never payloads, keys, billing data or exception messages.
 *
 * @param string $level Log severity.
 * @param string $reason Technical reason code.
 * @param array  $context Allowlisted technical fields.
 */
function bsc_wompi_log( string $level, string $reason, array $context = array() ): void {
	$safe = array(
		'source' => 'bsc-wompi',
		'reason' => sanitize_key( $reason ),
	);
	foreach ( array( 'order_id', 'transaction_id', 'http_status', 'attempt', 'status', 'environment' ) as $key ) {
		if ( isset( $context[ $key ] ) && is_scalar( $context[ $key ] ) ) {
			$safe[ $key ] = substr( sanitize_text_field( (string) $context[ $key ] ), 0, 200 );
		}
	}
	try {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->log( $level, 'Wompi: ' . $safe['reason'], $safe );
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational fallback when WooCommerce logging is unavailable.
			error_log( 'BSC: Wompi ' . wp_json_encode( $safe ) );
		}
	} catch ( Throwable $error ) {
		// Logging must never turn a committed payment into a failed callback.
		return;
	}
}

/**
 * Distinguish permanent validation errors from retryable processing failures.
 *
 * @param WP_Error $error Reconciliation failure.
 * @return int
 */
function bsc_wompi_error_http_status( WP_Error $error ): int {
	$permanent = array( 'invalid_transaction', 'invalid_order', 'transaction_mismatch', 'invalid_transaction_status' );
	return in_array( $error->get_error_code(), $permanent, true ) ? 400 : 503;
}

/**
 * Require TLS verification and disallow redirects on Wompi API requests.
 *
 * @param array  $args HTTP request arguments.
 * @param string $url Destination URL.
 * @return array
 */
function bsc_wompi_secure_http( array $args, string $url ): array {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( in_array( $host, array( 'production.wompi.co', 'sandbox.wompi.co', 'api.wompi.co' ), true ) ) {
		$args['sslverify']   = true;
		$args['redirection'] = 0;
	}
	return $args;
}

/**
 * Get the event secret for the active environment.
 *
 * @return string
 */
function bsc_wompi_event_secret(): string {
	if ( ! class_exists( 'Wompi_Portal_Pagos_Main' ) ) {
		return '';
	}
	$test = 'yes' === Wompi_Portal_Pagos_Main::get_setting( 'testmode' );
	return trim( (string) Wompi_Portal_Pagos_Main::get_setting( $test ? 'test_event_secret_key' : 'event_secret_key', '' ) );
}

/**
 * Validate the dynamic properties and checksum of a transaction event.
 *
 * @param object $event Decoded raw event.
 * @param string $secret Environment-specific event secret.
 * @param string $environment Expected environment.
 * @return bool
 */
function bsc_wompi_valid_event( object $event, string $secret, string $environment ): bool {
	if ( '' === $secret || 'transaction.updated' !== ( $event->event ?? '' ) || ( $event->environment ?? '' ) !== $environment ) {
		return false;
	}
	$properties = $event->signature->properties ?? null;
	$checksum   = $event->signature->checksum ?? null;
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

/**
 * Select a transition without regressing a fulfilled or previously paid order.
 *
 * @param string $status Current order status.
 * @param bool   $paid Whether WooCommerce considers the order paid.
 * @param string $accepted_id Previously recognized transaction ID.
 * @param string $incoming_id Authoritative transaction ID.
 * @param string $incoming_status Authoritative transaction status.
 * @return string
 */
function bsc_wompi_payment_action( string $status, bool $paid, string $accepted_id, string $incoming_id, string $incoming_status ): string {
	$fulfilled = in_array( $status, array( 'preparing', 'shipped', 'completed', 'refunded' ), true );
	if ( 'APPROVED' === $incoming_status ) {
		if ( '' !== $accepted_id && $accepted_id !== $incoming_id ) {
			return 'review';
		}
		return $paid || $fulfilled ? 'record' : 'approve';
	}
	if ( $paid || $fulfilled || '' !== $accepted_id ) {
		return 'ignore';
	}
	if ( in_array( $incoming_status, array( 'DECLINED', 'ERROR', 'VOIDED' ), true ) ) {
		return in_array( $status, array( 'pending', 'on-hold' ), true ) ? 'fail' : 'ignore';
	}
	return 'ignore'; // Pending and unknown events never mark an order failed.
}

/**
 * Reconcile an authoritative API transaction, never a raw event transaction.
 *
 * @param object $transaction Transaction returned by the private API.
 * @param string $order_key Optional key verified by the browser return.
 * @return bool|WP_Error
 */
function bsc_wompi_reconcile_transaction( object $transaction, string $order_key = '' ) {
	$reference = is_scalar( $transaction->reference ?? null ) ? (string) $transaction->reference : '';
	$id        = $transaction->id ?? null;
	if ( ! ctype_digit( $reference ) || (int) $reference <= 0 || (string) (int) $reference !== $reference || ! bsc_wompi_valid_transaction_id( $id ) ) {
		return new WP_Error( 'invalid_transaction', 'Transacción inválida.' );
	}
	$status = $transaction->status ?? null;
	if ( ! in_array( $status, array( 'APPROVED', 'PENDING', 'DECLINED', 'ERROR', 'VOIDED' ), true ) ) {
		return new WP_Error( 'invalid_transaction_status', 'Estado de transacción inválido.' );
	}
	return BSC_DB_Lock::run(
		'payment-order:' . $reference,
		static function () use ( $reference, $id, $transaction, $order_key ) {
			$order = wc_get_order( (int) $reference );
			if ( ! $order instanceof WC_Order || 'wompi' !== $order->get_payment_method() || ( '' !== $order_key && ! hash_equals( $order->get_order_key(), $order_key ) ) ) {
				return new WP_Error( 'invalid_order', 'Pedido inválido.' );
			}
			$order->read_meta_data( true );
			if ( ! bsc_wompi_transaction_matches_order( $transaction, $order, false ) ) {
				return new WP_Error( 'transaction_mismatch', 'El monto o la moneda no coinciden.' );
			}
			$status     = (string) ( $transaction->status ?? '' );
			$event_key  = hash( 'sha256', $id . ':' . $status );
			$seen       = array_values( array_filter( (array) $order->get_meta( '_bsc_wompi_events', true ), 'is_string' ) );
			$accepted   = (string) $order->get_meta( '_bsc_wompi_approved_id', true );
			$paid       = $order->is_paid() || ( (bool) $order->get_date_paid() && 'pending' !== $order->get_status() );
			$fulfilled  = in_array( $order->get_status(), array( 'preparing', 'shipped', 'completed', 'refunded' ), true );
			$recognized = '' !== $accepted ? $accepted : ( ( $paid || $fulfilled ) ? $order->get_transaction_id() : '' );
			$action     = bsc_wompi_payment_action( $order->get_status(), $paid, $recognized, $id, $status );
			// Old versions could mark an APPROVED event handled without persisting its payment.
			if ( in_array( $event_key, $seen, true ) && ( 'APPROVED' !== $status || ( $accepted === $id && $order->get_transaction_id() === $id && ( $paid || $fulfilled ) ) || ( 'review' === $action && 'multiple_approved_transactions' === $order->get_meta( '_bsc_payment_review_required', true ) ) ) ) {
				return true;
			}
			$note = '';
			if ( 'approve' === $action ) {
				// Keep the ID before payment hooks (points require it), but never record success on failure.
				if ( ! bsc_update_order_from_wompi_transaction( $order, $transaction ) || true !== $order->payment_complete( $id ) ) {
					return new WP_Error( 'bsc_payment_incomplete', 'No se pudo completar el pago.' );
				}
				$order = wc_get_order( (int) $reference );
				if ( ! $order instanceof WC_Order || $order->get_transaction_id() !== $id || ! $order->get_date_paid() || in_array( $order->get_status(), array( 'pending', 'failed', 'cancelled' ), true ) ) {
					return new WP_Error( 'bsc_payment_incomplete', 'El pago no quedó guardado.' );
				}
				$order->read_meta_data( true );
			} elseif ( 'record' === $action ) {
				// Attach verified evidence without replaying payment hooks or regressing fulfillment.
				if ( ! bsc_update_order_from_wompi_transaction( $order, $transaction ) ) {
					return new WP_Error( 'bsc_payment_incomplete', 'La transacción no quedó guardada.' );
				}
			} elseif ( 'fail' === $action ) {
				// A failed attempt is retryable; never overwrite a recognized payment.
				if ( ! $order->update_status( 'failed', 'Intento Wompi no aprobado: ' . $id ) ) {
					return new WP_Error( 'bsc_payment_incomplete', 'El estado del intento no quedó guardado.' );
				}
			} elseif ( 'review' === $action ) {
				$order->update_meta_data( '_bsc_payment_review_required', 'multiple_approved_transactions' );
				$note = 'Wompi reportó otra transacción aprobada: ' . $id . '. Revisar conciliación; se conservó el pago registrado.';
			} elseif ( 'VOIDED' === $status && ( $paid || '' !== $accepted ) && $recognized === $id ) {
				// Voids of a settled payment require reconciliation, not an automatic stock return.
				$order->update_meta_data( '_bsc_payment_review_required', 'voided' );
				$note = 'Wompi reportó una anulación de pago. Revisar conciliación antes de modificar entrega o reembolso.';
			}
			if ( in_array( $action, array( 'approve', 'record' ), true ) ) {
				$order->update_meta_data( '_bsc_wompi_approved_id', $id );
				if ( $accepted !== $id ) {
					$note = 'Pago Wompi verificado. Transacción: ' . $id;
				}
			}
			$seen[] = $event_key;
			$order->update_meta_data( '_bsc_wompi_events', array_slice( array_values( array_unique( $seen ) ), -50 ) );
			if ( ! $order->save() ) {
				return new WP_Error( 'bsc_payment_save_failed', 'La conciliación no quedó guardada.' );
			}
			$persisted = wc_get_order( (int) $reference );
			if ( ! $persisted instanceof WC_Order ) {
				return new WP_Error( 'bsc_payment_save_failed', 'No se pudo verificar la conciliación.' );
			}
			$persisted->read_meta_data( true );
			if ( ! in_array( $event_key, (array) $persisted->get_meta( '_bsc_wompi_events', true ), true ) || ( in_array( $action, array( 'approve', 'record' ), true ) && ( $persisted->get_transaction_id() !== $id || $persisted->get_meta( '_bsc_wompi_approved_id', true ) !== $id ) ) ) {
				return new WP_Error( 'bsc_payment_save_failed', 'La conciliación no quedó guardada.' );
			}
			if ( '' !== $note ) {
				$persisted->add_order_note( $note );
			}
			return true;
		}
	);
}

/**
 * Log the reason for an HTTP outcome.
 *
 * @param int    $code HTTP status.
 * @param string $reason Technical reason code.
 * @param array  $context Technical context.
 * @return int
 */
function bsc_wompi_webhook_response( int $code, string $reason, array $context = array() ): int {
	$context['http_status'] = $code;
	bsc_wompi_log( $code >= 500 ? 'error' : ( $code >= 400 ? 'warning' : 'info' ), $reason, $context );
	return $code;
}

/**
 * Process a raw request separately from emitting/exiting so the HTTP outcome is testable.
 *
 * @param string $method HTTP method.
 * @param string $body Unmodified request body.
 * @return int
 */
function bsc_wompi_process_webhook( string $method, string $body ): int {
	$id      = '';
	$context = array();
	try {
		if ( 'POST' !== strtoupper( $method ) ) {
			return bsc_wompi_webhook_response( 400, 'invalid_method' );
		}
		$event = strlen( $body ) <= 262144 ? json_decode( $body, false, 32 ) : null;
		if ( ! is_object( $event ) ) {
			return bsc_wompi_webhook_response( 400, 'invalid_body' );
		}
		$secret = bsc_wompi_event_secret();
		if ( '' === $secret ) {
			return bsc_wompi_webhook_response( 503, 'missing_event_secret' );
		}
		$environment = bsc_wompi_environment();
		if ( 'transaction.updated' !== ( $event->event ?? '' ) ) {
			return bsc_wompi_webhook_response( 400, 'invalid_event_type' );
		}
		if ( ( $event->environment ?? '' ) !== $environment ) {
			return bsc_wompi_webhook_response( 400, 'environment_mismatch' );
		}
		if ( ! bsc_wompi_valid_event( $event, $secret, $environment ) ) {
			return bsc_wompi_webhook_response( 400, 'invalid_signature' );
		}
		$id = $event->data->transaction->id ?? null;
		if ( ! bsc_wompi_valid_transaction_id( $id ) ) {
			$id = '';
			return bsc_wompi_webhook_response( 400, 'invalid_transaction' );
		}
		$context['transaction_id'] = $id;
		bsc_wompi_log( 'info', 'webhook_received', $context );
		$transaction = bsc_get_wompi_transaction_for_return( $id );
		if ( ! $transaction ) {
			// Only the signed ID is trusted here: an event's reference need not be signed.
			bsc_wompi_schedule_reconciliation( $id );
			return bsc_wompi_webhook_response( 503, 'api_unavailable', $context );
		}
		$result = bsc_wompi_reconcile_transaction( $transaction );
		if ( is_wp_error( $result ) ) {
			$code = bsc_wompi_error_http_status( $result );
			if ( 503 === $code ) {
				bsc_wompi_schedule_reconciliation( $id );
			}
			return bsc_wompi_webhook_response( $code, $result->get_error_code(), $context );
		}
		$context['order_id'] = (int) $transaction->reference;
		$context['status']   = $transaction->status;
		if ( 'PENDING' === $transaction->status && ! bsc_wompi_schedule_reconciliation( $id, (int) $transaction->reference ) ) {
			return bsc_wompi_webhook_response( 503, 'retry_schedule_failed', $context );
		}
		return bsc_wompi_webhook_response( 200, 'webhook_reconciled', $context );
	} catch ( Throwable $error ) {
		if ( bsc_wompi_valid_transaction_id( $id ) ) {
			bsc_wompi_schedule_reconciliation( $id );
		}
		return bsc_wompi_webhook_response( 503, 'webhook_processing_failed', $context );
	}
}

/** The early callback owns the response; the unsafe vendor handler cannot run afterward. */
function bsc_wompi_secure_webhook(): void {
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
	// Preserve raw JSON for signature verification, with a bounded read.
	$body = 'POST' === strtoupper( $method ) ? file_get_contents( 'php://input', false, null, 0, 262145 ) : '';
	$code = bsc_wompi_process_webhook( $method, is_string( $body ) ? $body : '' );
	status_header( $code );
	nocache_headers();
	exit;
}
