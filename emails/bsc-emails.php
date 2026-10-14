<?php
/**
 * BSC-053: Central BSC email dispatcher.
 * Handles branded transactional emails for all order status transitions.
 * Loaded by inc/woocommerce.php.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Status → template map.
 * Keys are WooCommerce status slugs (without 'wc-' prefix).
 */
function bsc_get_email_template_map(): array {
	return array(
		'processing' => 'bsc-order-confirmed.php',
		'preparing'  => 'bsc-order-preparing.php',
		'shipped'    => 'bsc-order-shipped.php',   // also triggered by bsc_save_tracking
		'completed'  => 'bsc-order-delivered.php',
		'cancelled'  => 'bsc-order-cancelled.php',
	);
}

function bsc_get_order_email_recipient( WC_Order $order ): string {
	$to = sanitize_email( (string) $order->get_billing_email() );

	if ( $to ) {
		return $to;
	}

	$user = $order->get_user();
	return $user ? sanitize_email( (string) $user->user_email ) : '';
}

/**
 * Build and send a BSC-branded order email.
 *
 * @param int    $order_id WC order ID.
 * @param string $status   WC status slug (without 'wc-').
 * @param array  $extra    Extra variables passed to the template (e.g. tracking_code, tracking_link).
 * @return bool  True if wp_mail() succeeded.
 */
function bsc_send_order_email( int $order_id, string $status, array $extra = array() ): bool {
	$result = BSC_DB_Lock::run( 'order-email:' . $order_id, static function () use ( $order_id, $status, $extra ) {
		return bsc_deliver_order_email( $order_id, $status, $extra );
	} );
	return ! is_wp_error( $result ) && true === $result;
}

function bsc_deliver_order_email( int $order_id, string $status, array $extra = array() ): bool {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return false;
	}
	$order->read_meta_data( true );

	$map      = bsc_get_email_template_map();
	$template = $map[ $status ] ?? null;
	if ( ! $template ) {
		return false;
	}

	$template_path = get_template_directory() . '/emails/' . $template;
	if ( ! file_exists( $template_path ) ) {
		error_log( "BSC email template not found: $template_path" );
		return false;
	}

	$to = bsc_get_order_email_recipient( $order );
	if ( empty( $to ) ) {
		return false;
	}

	$email_type = 'order-' . sanitize_key( $status );
	$dedupe_key = md5( wp_json_encode( array( $status, $extra['tracking_code'] ?? '', $extra['tracking_link'] ?? '' ) ) );

	if ( empty( $extra['force'] ) && function_exists( 'bsc_order_email_was_sent' ) && bsc_order_email_was_sent( $order, $email_type, $dedupe_key ) ) {
		return true;
	}

	// Build subject per status
	$subjects = array(
		'processing' => sprintf( '¡Tu pago fue recibido! Pedido #%s — Bubble Skin Care', $order->get_order_number() ),
		'preparing'  => sprintf( '¡Estamos preparando tu pedido #%s! — Bubble Skin Care', $order->get_order_number() ),
		'shipped'    => sprintf( 'Tu pedido #%s está en camino 🚚 — Bubble Skin Care', $order->get_order_number() ),
		'completed'  => sprintf( '¡Tu pedido #%s fue entregado! — Bubble Skin Care', $order->get_order_number() ),
		'cancelled'  => sprintf( 'Tu pedido #%s fue cancelado — Bubble Skin Care', $order->get_order_number() ),
	);
	$subject  = $subjects[ $status ] ?? 'Actualización de tu pedido — Bubble Skin Care';

	// Render template
	$tracking_code = $extra['tracking_code'] ?? '';
	$tracking_link = $extra['tracking_link'] ?? '';

	ob_start();
	include $template_path;
	$body = ob_get_clean();

	$headers = function_exists( 'bsc_get_email_headers' )
		? bsc_get_email_headers()
		: array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Bubble Skin Care <noreply@bubblesskincare.com>',
		);

	$sent = wp_mail( $to, $subject, $body, $headers );

	if ( function_exists( 'bsc_append_order_email_log' ) ) {
		bsc_append_order_email_log(
			$order,
			$email_type,
			$sent,
			array(
				'to'         => $to,
				'subject'    => $subject,
				'template'   => $template,
				'dedupe_key' => $dedupe_key,
			)
		);
	}

	if ( ! $sent ) {
		error_log( "BSC email failed for order #$order_id status $status" );
	}

	return $sent;
}

// ── Hook: fire BSC email on every order status change ─────────────────────
add_action( 'woocommerce_order_status_changed', 'bsc_handle_order_status_email', 10, 3 );

function bsc_handle_order_status_email( int $order_id, string $old_status, string $new_status ): void {
	$map = bsc_get_email_template_map();
	if ( ! isset( $map[ $new_status ] ) ) {
		return;
	}

	// shipped is handled by bsc_save_tracking to include tracking data
	if ( $new_status === 'shipped' ) {
		return;
	}

	// Auto-archiving a cancelled order → no customer email
	if ( $new_status === 'completed' && $old_status === 'cancelled' ) {
		return;
	}

	if ( 'completed' === $new_status && in_array( $old_status, array( 'pending', 'on-hold', 'failed' ), true ) ) {
		bsc_queue_order_email( $order_id, 'processing' );
		return;
	}

	bsc_queue_order_email( $order_id, $new_status );
}

// Payment-complete fallback for gateways that do not produce a status transition.
add_action( 'woocommerce_payment_complete', 'bsc_handle_payment_complete_order_email', 20, 2 );
add_action( 'woocommerce_payment_complete_order_status_processing', 'bsc_handle_payment_complete_order_email', 20, 2 );
add_action( 'woocommerce_payment_complete_order_status_completed', 'bsc_handle_payment_complete_order_email', 20, 2 );

function bsc_handle_payment_complete_order_email( int $order_id, string $transaction_id = '' ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->has_status( array( 'processing', 'preparing', 'shipped', 'completed' ) ) ) {
		return;
	}

	bsc_queue_order_email( $order_id, 'processing' );
}

// Disable duplicate WooCommerce native emails for covered statuses.
// Keep WooCommerce's operational new-order notification enabled.
add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
add_filter( 'woocommerce_defer_transactional_emails', '__return_true' );
add_filter( 'woocommerce_email_enabled_customer_completed_order', '__return_false' );

/** Queue transactional notifications without blocking payment callbacks. */
function bsc_queue_order_email( int $order_id, string $status, array $extra = array() ): bool {
	if ( ! isset( bsc_get_email_template_map()[ $status ] ) ) {
		return false;
	}
	$args = array( $order_id, $status, $extra, 0 );
	if ( function_exists( 'as_enqueue_async_action' ) ) {
		if ( as_has_scheduled_action( 'bsc_order_email_job', $args, 'bsc-email' ) ) {
			return true;
		}
		if ( as_enqueue_async_action( 'bsc_order_email_job', $args, 'bsc-email', true ) ) { return true; }
	}
	$queued = wp_next_scheduled( 'bsc_order_email_job', $args ) || (bool) wp_schedule_single_event( time() + 10, 'bsc_order_email_job', $args );
	if ( ! $queued ) {
		$order = wc_get_order( $order_id );
		if ( $order ) { $order->update_meta_data( '_bsc_email_failed_' . sanitize_key( $status ), array( 'error' => 'queue_unavailable' ) ); $order->save_meta_data(); $order->add_order_note( 'No se pudo programar el email. Revisar la cola y reenviar desde el pedido.' ); }
	}
	return (bool) $queued;
}

function bsc_order_email_job( int $order_id, string $status, array $extra = array(), int $attempt = 0 ): void {
	if ( bsc_send_order_email( $order_id, $status, $extra ) ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->delete_meta_data( '_bsc_email_failed_' . sanitize_key( $status ) );
			$order->save_meta_data();
		}
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$order->update_meta_data( '_bsc_email_failed_' . sanitize_key( $status ), array( 'attempts' => $attempt + 1, 'last_attempt' => current_time( 'mysql' ) ) );
	$order->save_meta_data();
	if ( $attempt >= 4 ) {
		$order->add_order_note( 'El email de ' . sanitize_key( $status ) . ' falló tras 5 intentos. Revisar transporte y reenviar desde el pedido.' );
		throw new RuntimeException( 'BSC transactional email exhausted retries for order ' . $order_id );
	}
	$args = array( $order_id, $status, $extra, $attempt + 1 );
	$next = time() + min( 3600, 60 * ( 2 ** $attempt ) );
	if ( function_exists( 'as_schedule_single_action' ) && as_schedule_single_action( $next, 'bsc_order_email_job', $args, 'bsc-email', true ) ) { return; }
	if ( ! wp_schedule_single_event( $next, 'bsc_order_email_job', $args ) && ! wp_next_scheduled( 'bsc_order_email_job', $args ) ) {
		$order->add_order_note( 'No se pudo programar el reintento del email; requiere revisión.' );
		throw new RuntimeException( 'BSC email retry queue unavailable for order ' . $order_id );
	}
}
add_action( 'bsc_order_email_job', 'bsc_order_email_job', 10, 4 );
