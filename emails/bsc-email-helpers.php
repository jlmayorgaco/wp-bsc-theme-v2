<?php
/**
 * BSC-082: shared helpers for BSC branded emails.
 */
defined( 'ABSPATH' ) || exit;

function bsc_get_email_from_name(): string {
	$name = sanitize_text_field( (string) get_option( 'bsc_email_from_name', 'Bubble Skin Care' ) );
	return $name !== '' ? $name : 'Bubble Skin Care';
}

function bsc_get_email_from_address(): string {
	$configured = sanitize_email( (string) get_option( 'bsc_email_from_address', '' ) );
	if ( $configured !== '' ) {
		return $configured;
	}

	if ( defined( 'BSC_CONTACT_EMAIL' ) && is_email( BSC_CONTACT_EMAIL ) ) {
		return BSC_CONTACT_EMAIL;
	}

	return sanitize_email( (string) get_option( 'admin_email' ) );
}

function bsc_get_email_headers( array $extra_headers = array() ): array {
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: %s <%s>', bsc_get_email_from_name(), bsc_get_email_from_address() ),
	);

	return array_merge( $headers, $extra_headers );
}

function bsc_get_email_whatsapp_url(): string {
	if ( function_exists( 'bsc_get_whatsapp_url' ) ) {
		return (string) bsc_get_whatsapp_url( 'general' );
	}

	$number = preg_replace( '/[^0-9]/', '', (string) get_option( 'bsc_whatsapp_number', '573156922859' ) );

	return 'https://wa.me/' . ( $number !== '' ? $number : '573156922859' );
}

function bsc_render_email_template( string $template, array $vars = array() ): string {
	$template_path = get_template_directory() . '/emails/' . ltrim( $template, '/\\' );
	if ( ! file_exists( $template_path ) ) {
		error_log( sprintf( 'BSC email template not found: %s', $template_path ) );
		return '';
	}

	extract( $vars, EXTR_SKIP );

	ob_start();
	include $template_path;
	return (string) ob_get_clean();
}

function bsc_send_email_from_template( string $to, string $subject, string $template, array $vars = array(), array $headers = array() ): bool {
	$to = sanitize_email( $to );
	if ( $to === '' ) {
		return false;
	}

	$body = bsc_render_email_template( $template, $vars );
	if ( $body === '' ) {
		return false;
	}

	$sent = wp_mail( $to, $subject, $body, $headers ?: bsc_get_email_headers() );

	if ( isset( $vars['order'] ) && $vars['order'] instanceof WC_Order ) {
		bsc_append_order_email_log(
			$vars['order'],
			pathinfo( $template, PATHINFO_FILENAME ),
			$sent,
			array(
				'to'       => $to,
				'subject'  => $subject,
				'template' => $template,
			)
		);
	}

	if ( ! $sent ) {
		error_log( sprintf( 'BSC email failed for %s using template %s', $to, $template ) );
	}

	return $sent;
}

function bsc_get_order_email_log( WC_Order $order ): array {
	$log = $order->get_meta( '_bsc_email_log', true );
	return is_array( $log ) ? array_values( $log ) : array();
}

function bsc_order_email_was_sent( WC_Order $order, string $type, string $dedupe_key = '' ): bool {
	$type = sanitize_key( $type );

	foreach ( bsc_get_order_email_log( $order ) as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		if (
			(string) ( $entry['type'] ?? '' ) === $type
			&& (string) ( $entry['status'] ?? '' ) === 'sent'
			&& ( $dedupe_key === '' || (string) ( $entry['dedupe_key'] ?? '' ) === $dedupe_key )
		) {
			return true;
		}
	}

	return false;
}

function bsc_append_order_email_log( WC_Order $order, string $type, bool $sent, array $context = array() ): void {
	$type = sanitize_key( $type );

	if ( $type === '' ) {
		$type = 'email';
	}

	$log   = bsc_get_order_email_log( $order );
	$log[] = array(
		'type'       => $type,
		'status'     => $sent ? 'sent' : 'failed',
		'sent_at'    => current_time( 'mysql' ),
		'to'         => sanitize_email( (string) ( $context['to'] ?? '' ) ),
		'subject'    => sanitize_text_field( (string) ( $context['subject'] ?? '' ) ),
		'template'   => sanitize_text_field( (string) ( $context['template'] ?? '' ) ),
		'dedupe_key' => sanitize_text_field( (string) ( $context['dedupe_key'] ?? '' ) ),
		'user_id'    => get_current_user_id(),
	);

	$order->update_meta_data( '_bsc_email_log', array_slice( $log, -50 ) );
	$order->save_meta_data();
}

function bsc_get_recent_order_email_log_rows( int $limit = 20 ): array {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array();
	}

	$orders = wc_get_orders(
		array(
			'limit'        => 50,
			'orderby'      => 'modified',
			'order'        => 'DESC',
			'meta_key'     => '_bsc_email_log',
			'meta_compare' => 'EXISTS',
		)
	);
	$rows   = array();

	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}

		foreach ( bsc_get_order_email_log( $order ) as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$rows[] = array(
				'order_id'     => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'type'         => (string) ( $entry['type'] ?? '' ),
				'status'       => (string) ( $entry['status'] ?? '' ),
				'sent_at'      => (string) ( $entry['sent_at'] ?? '' ),
				'to'           => (string) ( $entry['to'] ?? '' ),
				'subject'      => (string) ( $entry['subject'] ?? '' ),
			);
		}
	}

	usort(
		$rows,
		static fn( array $a, array $b ): int => strcmp( $b['sent_at'], $a['sent_at'] )
	);

	return array_slice( $rows, 0, max( 1, $limit ) );
}

function bsc_get_email_template_manifest(): array {
	return array(
		array(
			'slug'    => 'welcome',
			'label'   => 'Usuario nuevo',
			'trigger' => 'Inmediato al crear cuenta customer',
			'file'    => 'bsc-welcome-email.php',
		),
		array(
			'slug'    => 'password-reset',
			'label'   => 'Recuperar contrasena',
			'trigger' => 'Inmediato al solicitar reset',
			'file'    => 'bsc-password-reset-email.php',
		),
		array(
			'slug'    => 'birthday',
			'label'   => 'Cumpleanos',
			'trigger' => 'Diario, una vez por ano',
			'file'    => 'bsc-birthday-email.php',
		),
		array(
			'slug'    => 'order-confirmed',
			'label'   => 'Compra confirmada',
			'trigger' => 'Transaccional por estado processing',
			'file'    => 'bsc-order-confirmed.php',
		),
		array(
			'slug'    => 'order-preparing',
			'label'   => 'Pedido en preparacion',
			'trigger' => 'Transaccional por estado preparing',
			'file'    => 'bsc-order-preparing.php',
		),
		array(
			'slug'    => 'order-shipped',
			'label'   => 'Envio',
			'trigger' => 'Transaccional al guardar guia / shipped',
			'file'    => 'bsc-order-shipped.php',
		),
		array(
			'slug'    => 'order-delivered',
			'label'   => 'Pedido entregado',
			'trigger' => 'Transaccional por estado completed legitimo',
			'file'    => 'bsc-order-delivered.php',
		),
		array(
			'slug'    => 'order-cancelled',
			'label'   => 'Pedido cancelado',
			'trigger' => 'Transaccional por estado cancelled',
			'file'    => 'bsc-order-cancelled.php',
		),
		array(
			'slug'    => 'followup-inactive',
			'label'   => 'Hace mucho no compras',
			'trigger' => 'Cron diario segun ultima compra',
			'file'    => 'bsc-followup-inactive.php',
		),
		array(
			'slug'    => 'followup-repurchase',
			'label'   => 'Se te acabo el producto',
			'trigger' => 'Cron diario segun timeout por producto',
			'file'    => 'bsc-followup-repurchase.php',
		),
		array(
			'slug'    => 'abandoned-cart',
			'label'   => 'Carrito abandonado',
			'trigger' => 'Cron horario segun ultima actividad de checkout',
			'file'    => 'bsc-abandoned-cart.php',
		),
	);
}
