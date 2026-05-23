<?php
/**
 * BSC-053: Central BSC email dispatcher.
 * Handles branded transactional emails for all order status transitions.
 * Loaded by inc/woocommerce.php.
 */
defined('ABSPATH') || exit;

/**
 * Status → template map.
 * Keys are WooCommerce status slugs (without 'wc-' prefix).
 */
function bsc_get_email_template_map(): array {
    return [
        'processing' => 'bsc-order-confirmed.php',
        'preparing'  => 'bsc-order-preparing.php',
        'shipped'    => 'bsc-order-shipped.php',   // also triggered by bsc_save_tracking
        'completed'  => 'bsc-order-delivered.php',
        'cancelled'  => 'bsc-order-cancelled.php',
    ];
}

/**
 * Build and send a BSC-branded order email.
 *
 * @param int    $order_id WC order ID.
 * @param string $status   WC status slug (without 'wc-').
 * @param array  $extra    Extra variables passed to the template (e.g. tracking_code, tracking_link).
 * @return bool  True if wp_mail() succeeded.
 */
function bsc_send_order_email( int $order_id, string $status, array $extra = [] ): bool {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return false;
    }

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

    $to = $order->get_billing_email();
    if ( empty( $to ) ) {
        return false;
    }

    $email_type = 'order-' . sanitize_key( $status );
    $dedupe_key = md5( wp_json_encode( [ $status, $extra['tracking_code'] ?? '', $extra['tracking_link'] ?? '' ] ) );

    if ( empty( $extra['force'] ) && function_exists( 'bsc_order_email_was_sent' ) && bsc_order_email_was_sent( $order, $email_type, $dedupe_key ) ) {
        return true;
    }

    // Build subject per status
    $subjects = [
        'processing' => sprintf( '¡Tu pago fue recibido! Pedido #%s — Bubble Skin Care', $order->get_order_number() ),
        'preparing'  => sprintf( '¡Estamos preparando tu pedido #%s! — Bubble Skin Care', $order->get_order_number() ),
        'shipped'    => sprintf( 'Tu pedido #%s está en camino 🚚 — Bubble Skin Care', $order->get_order_number() ),
        'completed'  => sprintf( '¡Tu pedido #%s fue entregado! — Bubble Skin Care', $order->get_order_number() ),
        'cancelled'  => sprintf( 'Tu pedido #%s fue cancelado — Bubble Skin Care', $order->get_order_number() ),
    ];
    $subject = $subjects[ $status ] ?? 'Actualización de tu pedido — Bubble Skin Care';

    // Render template
    $tracking_code = $extra['tracking_code'] ?? '';
    $tracking_link = $extra['tracking_link'] ?? '';

    ob_start();
    include $template_path;
    $body = ob_get_clean();

    $headers = function_exists( 'bsc_get_email_headers' )
        ? bsc_get_email_headers()
        : [
            'Content-Type: text/html; charset=UTF-8',
            'From: Bubble Skin Care <noreply@bubbleskincare.co>',
        ];

    $sent = wp_mail( $to, $subject, $body, $headers );

    if ( function_exists( 'bsc_append_order_email_log' ) ) {
        bsc_append_order_email_log(
            $order,
            $email_type,
            $sent,
            [
                'to'         => $to,
                'subject'    => $subject,
                'template'   => $template,
                'dedupe_key' => $dedupe_key,
            ]
        );
    }

    if ( ! $sent ) {
        error_log( "BSC email failed for order #$order_id status $status to $to" );
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

    bsc_send_order_email( $order_id, $new_status );
}

// ── Disable duplicate WooCommerce native emails for covered statuses ───────
add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
add_filter( 'woocommerce_email_enabled_customer_completed_order',  '__return_false' );
