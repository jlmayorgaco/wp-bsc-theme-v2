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

/**
 * Public site origin used for links and images in transactional emails.
 *
 * Email recipients cannot access a local WordPress domain, so local URLs are
 * rewritten to the production site before rendering.
 *
 * @return string
 */
function bsc_get_email_public_base_url(): string {
	$fallback   = 'https://bubblesskincare.com';
	$configured = defined( 'BSC_EMAIL_PUBLIC_BASE_URL' ) ? (string) BSC_EMAIL_PUBLIC_BASE_URL : $fallback;
	$configured = (string) apply_filters( 'bsc_email_public_base_url', $configured );
	$configured = untrailingslashit( esc_url_raw( trim( $configured ) ) );
	$scheme     = strtolower( (string) wp_parse_url( $configured, PHP_URL_SCHEME ) );
	$host       = (string) wp_parse_url( $configured, PHP_URL_HOST );

	if ( '' === $host || ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return $fallback;
	}

	return $configured;
}

/**
 * Replace a local site origin with the public production origin.
 *
 * External URLs are intentionally left untouched.
 *
 * @param string $url URL to inspect.
 * @return string
 */
function bsc_email_publicize_url( string $url ): string {
	$url = trim( $url );
	if ( '' === $url ) {
		return '';
	}

	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( '' === $host ) {
		return $url;
	}

	$local_hosts = array( 'bsc.local', 'localhost', '127.0.0.1', '::1' ); // bsc-email-local-host-rewrite.
	if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
		$local_hosts[] = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$local_hosts[] = strtolower( (string) wp_parse_url( site_url( '/' ), PHP_URL_HOST ) );
	}
	$local_hosts = array_values( array_unique( array_filter( $local_hosts ) ) );

	if ( ! in_array( $host, $local_hosts, true ) ) {
		return $url;
	}

	$rewritten = preg_replace(
		'~^(?:https?:)?//' . preg_quote( $host, '~' ) . '(?::\d+)?~i',
		bsc_get_email_public_base_url(),
		$url,
		1
	);

	return is_string( $rewritten ) && '' !== $rewritten ? $rewritten : $url;
}

/**
 * Backwards-compatible image-specific wrapper.
 *
 * @param string $url Image URL to inspect.
 * @return string
 */
function bsc_email_publicize_image_url( string $url ): string {
	return bsc_email_publicize_url( $url );
}

/**
 * Return the official Instagram URL used in every BSC email.
 *
 * @return string
 */
function bsc_get_email_instagram_url(): string {
	return 'https://www.instagram.com/bubbles.skincare?igsi=em1zNmw0Z2pjMDlu';
}

/**
 * Return the official TikTok URL used in every BSC email.
 *
 * @return string
 */
function bsc_get_email_tiktok_url(): string {
	return 'https://www.tiktok.com/@bubblesskincare?_r=1&_t=ZS-99BnmyXTB7C';
}

function bsc_get_smtp_string_setting( string $option_name, string $constant_name = '', string $default = '' ): string {
	$value = get_option( $option_name, null );

	if ( is_string( $value ) && $value !== '' ) {
		return sanitize_text_field( $value );
	}

	if ( is_numeric( $value ) ) {
		return sanitize_text_field( (string) $value );
	}

	if ( $constant_name !== '' && defined( $constant_name ) ) {
		return sanitize_text_field( (string) constant( $constant_name ) );
	}

	return $default;
}

function bsc_get_smtp_password(): string {
	$password = (string) get_option( 'bsc_smtp_password', '' );

	if ( $password !== '' ) {
		return $password;
	}

	if ( defined( 'BSC_SMTP_PASSWORD' ) ) {
		return (string) BSC_SMTP_PASSWORD;
	}

	return '';
}

function bsc_smtp_password_is_configured(): bool {
	return bsc_get_smtp_password() !== '';
}

function bsc_get_smtp_provider(): string {
	$provider = sanitize_key( bsc_get_smtp_string_setting( 'bsc_smtp_provider', 'BSC_SMTP_PROVIDER', 'custom' ) );

	return in_array( $provider, array( 'custom', 'zeptomail' ), true ) ? $provider : 'custom';
}

function bsc_get_smtp_settings(): array {
	$provider = bsc_get_smtp_provider();
	$host     = bsc_get_smtp_string_setting( 'bsc_smtp_host', 'BSC_SMTP_HOST', '' );
	$username = bsc_get_smtp_string_setting( 'bsc_smtp_username', 'BSC_SMTP_USERNAME', '' );
	$password = bsc_get_smtp_password();
	$secure   = strtolower( bsc_get_smtp_string_setting( 'bsc_smtp_secure', 'BSC_SMTP_SECURE', '' ) );

	if ( 'zeptomail' === $provider ) {
		$host     = false !== strpos( strtolower( $host ), 'zeptomail' ) ? $host : 'smtp.zeptomail.com';
		$username = false === strpos( $username, '@' ) && $username !== '' ? $username : 'emailapikey';
		$secure   = $secure !== '' && 'ssl' !== $secure ? $secure : 'tls';
	}

	$host_lc  = strtolower( $host );

	if ( '' === $secure ) {
		$secure = false !== strpos( $host_lc, 'zeptomail' ) ? 'tls' : 'ssl';
	}

	if ( ! in_array( $secure, array( 'ssl', 'tls', '' ), true ) ) {
		$secure = 'ssl';
	}

	$port = absint( bsc_get_smtp_string_setting( 'bsc_smtp_port', 'BSC_SMTP_PORT', '' ) );
	if ( 'zeptomail' === $provider && ( $port <= 0 || 465 === $port ) ) {
		$port = 587;
	} elseif ( $port <= 0 ) {
		$port = 'zeptomail' === $provider ? 587 : ( $secure === 'tls' ? 587 : 465 );
	}

	$auth_raw = get_option( 'bsc_smtp_auth', '__missing__' );
	if ( '__missing__' === $auth_raw ) {
		$auth = defined( 'BSC_SMTP_AUTH' ) ? (bool) BSC_SMTP_AUTH : true;
	} else {
		$auth = (int) $auth_raw === 1;
	}

	$enabled_raw = get_option( 'bsc_smtp_enabled', '__missing__' );
	if ( '__missing__' === $enabled_raw ) {
		$enabled = $host !== '' && $username !== '' && $password !== '';
	} else {
		$enabled = (int) $enabled_raw === 1;
	}

	return array(
		'provider' => $provider,
		'enabled'  => $enabled,
		'host'     => $host,
		'port'     => $port,
		'secure'   => $secure,
		'auth'     => $auth,
		'username' => $username,
		'password' => $password,
	);
}

add_filter(
	'wp_mail_from',
	static function ( string $from ): string {
		$configured = bsc_get_email_from_address();
		return is_email( $configured ) ? $configured : $from;
	}
);

add_filter(
	'wp_mail_from_name',
	static function ( string $name ): string {
		$configured = bsc_get_email_from_name();
		return $configured !== '' ? $configured : $name;
	}
);

add_action(
	'phpmailer_init',
	static function ( $phpmailer ): void {
		$settings = bsc_get_smtp_settings();

		if ( ! $settings['enabled'] || $settings['host'] === '' ) {
			return;
		}

		if ( $settings['auth'] && ( $settings['username'] === '' || $settings['password'] === '' ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $settings['host'];
		$phpmailer->SMTPAuth   = (bool) $settings['auth'];
		$phpmailer->Port       = (int) $settings['port'];
		$phpmailer->SMTPSecure = $settings['secure'];
		$phpmailer->Username   = $settings['username'];
		$phpmailer->Password   = $settings['password'];
		$phpmailer->CharSet    = 'UTF-8';
		$phpmailer->Encoding   = 'base64';

		$from_email = bsc_get_email_from_address();
		if ( is_email( $from_email ) ) {
			$phpmailer->From     = $from_email;
			$phpmailer->FromName = bsc_get_email_from_name();
			$phpmailer->Sender   = $from_email;
		}
	},
	15
);

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
			'slug'    => 'password-changed',
			'label'   => 'Contrasena actualizada',
			'trigger' => 'Inmediato al cambiar contrasena desde Mis datos',
			'file'    => 'bsc-password-changed-email.php',
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
