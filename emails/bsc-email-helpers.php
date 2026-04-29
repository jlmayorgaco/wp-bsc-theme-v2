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

function bsc_get_email_headers( array $extra_headers = [] ): array {
	$headers = [
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: %s <%s>', bsc_get_email_from_name(), bsc_get_email_from_address() ),
	];

	return array_merge( $headers, $extra_headers );
}

function bsc_get_email_whatsapp_url(): string {
	if ( function_exists( 'bsc_get_whatsapp_url' ) ) {
		return (string) bsc_get_whatsapp_url( 'general' );
	}

	$number = preg_replace( '/[^0-9]/', '', (string) get_option( 'bsc_whatsapp_number', '573156922859' ) );

	return 'https://wa.me/' . ( $number !== '' ? $number : '573156922859' );
}

function bsc_render_email_template( string $template, array $vars = [] ): string {
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

function bsc_send_email_from_template( string $to, string $subject, string $template, array $vars = [], array $headers = [] ): bool {
	$to = sanitize_email( $to );
	if ( $to === '' ) {
		return false;
	}

	$body = bsc_render_email_template( $template, $vars );
	if ( $body === '' ) {
		return false;
	}

	$sent = wp_mail( $to, $subject, $body, $headers ?: bsc_get_email_headers() );

	if ( ! $sent ) {
		error_log( sprintf( 'BSC email failed for %s using template %s', $to, $template ) );
	}

	return $sent;
}

function bsc_get_email_template_manifest(): array {
	return [
		[
			'label'   => 'Usuario nuevo',
			'trigger' => 'Inmediato al crear cuenta customer',
			'file'    => 'bsc-welcome-email.php',
		],
		[
			'label'   => 'Recuperar contraseña',
			'trigger' => 'Inmediato al solicitar reset',
			'file'    => 'bsc-password-reset-email.php',
		],
		[
			'label'   => 'Cumpleaños',
			'trigger' => 'Diario, una vez por año',
			'file'    => 'bsc-birthday-email.php',
		],
		[
			'label'   => 'Compra',
			'trigger' => 'Transaccional por estado processing',
			'file'    => 'bsc-order-confirmed.php',
		],
		[
			'label'   => 'Envío',
			'trigger' => 'Transaccional al guardar guía / shipped',
			'file'    => 'bsc-order-shipped.php',
		],
		[
			'label'   => 'Hace mucho no compras',
			'trigger' => 'Cron diario según última compra',
			'file'    => 'bsc-followup-inactive.php',
		],
		[
			'label'   => 'Se te acabó el producto',
			'trigger' => 'Cron diario según timeout por producto',
			'file'    => 'bsc-followup-repurchase.php',
		],
	];
}
