<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_bsc_creator_apply', 'bsc_creator_apply' );
add_action( 'wp_ajax_nopriv_bsc_creator_apply', 'bsc_creator_apply' );

function bsc_creator_is_platform_url( string $url, string $expected_host ): bool {
	if ($url === '' || !wp_http_validate_url( $url )) {
		return false;
	}

	$host = wp_parse_url( $url, PHP_URL_HOST );
	if (!$host) {
		return false;
	}

	$host          = strtolower( $host );
	$expected_host = strtolower( $expected_host );
	$suffix        = '.' . $expected_host;

	return $host === $expected_host || substr( $host, -strlen( $suffix ) ) === $suffix;
}

function bsc_get_creator_email_recipient(): string {
	$option_email = sanitize_email( (string) get_option( 'bsc_creator_email', '' ) );
	if ($option_email && is_email( $option_email )) {
		return $option_email;
	}

	if (defined( 'BSC_CREATOR_EMAIL' ) && is_email( BSC_CREATOR_EMAIL )) {
		return BSC_CREATOR_EMAIL;
	}

	return (string) get_option( 'admin_email' );
}

function bsc_creator_apply() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );

	$nombre    = isset( $_POST['nombre'] ) ? sanitize_text_field( wp_unslash( $_POST['nombre'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$instagram = isset( $_POST['instagram'] ) ? esc_url_raw( wp_unslash( $_POST['instagram'] ) ) : '';
	$tiktok    = isset( $_POST['tiktok'] ) ? esc_url_raw( wp_unslash( $_POST['tiktok'] ) ) : '';
	$mensaje   = isset( $_POST['mensaje'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mensaje'] ) ) : '';

	if (empty( $nombre ) || empty( $email ) || empty( $instagram ) || empty( $tiktok ) || empty( $mensaje )) {
		wp_send_json_error( array( 'message' => 'Por favor completa todos los campos requeridos.' ) );
	}

	if (!is_email( $email )) {
		wp_send_json_error( array( 'message' => 'Por favor ingresa un correo electronico valido.' ) );
	}

	if (!bsc_creator_is_platform_url( $instagram, 'instagram.com' )) {
		wp_send_json_error( array( 'message' => 'Por favor ingresa un link valido de Instagram.' ) );
	}

	if (!bsc_creator_is_platform_url( $tiktok, 'tiktok.com' )) {
		wp_send_json_error( array( 'message' => 'Por favor ingresa un link valido de TikTok.' ) );
	}

	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'creator', 1, 5 * MINUTE_IN_SECONDS );
	}

	$applications   = get_option( 'bsc_creator_applications', array() );
	$applications   = is_array( $applications ) ? $applications : array();
	$applications[] = array_merge(
		array(
			'nombre'    => $nombre,
			'email'     => $email,
			'instagram' => $instagram,
			'tiktok'    => $tiktok,
			'mensaje'   => $mensaje,
			'status'    => 'new',
			'source'    => 'bubble-creators-form',
			'date'      => current_time( 'mysql' ),
		),
		function_exists( 'bsc_privacy_form_metadata' ) ? bsc_privacy_form_metadata() : array()
	);
	$applications   = function_exists( 'bsc_privacy_prune_rows' )
		? bsc_privacy_prune_rows( $applications, 1000 )
		: array_slice( array_values( $applications ), -1000 );

	update_option( 'bsc_creator_applications', $applications, false );

	$creator_email = bsc_get_creator_email_recipient();
	$body          = "Nueva solicitud Bubble Creator\n\n";
	$body         .= "Nombre: {$nombre}\n";
	$body         .= "Email: {$email}\n";
	$body         .= "Instagram: {$instagram}\n";
	$body         .= "TikTok: {$tiktok}\n";
	$body         .= "Mensaje:\n{$mensaje}\n\n";
	$body         .= 'Fecha: ' . current_time( 'mysql' );

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $nombre . ' <' . $email . '>',
	);

	wp_mail( $creator_email, '[Bubble Creators] Nueva solicitud - ' . $nombre, $body, $headers );

	wp_send_json_success( array( 'message' => 'Gracias por tu solicitud. El equipo de BSC revisara tu perfil y se pondra en contacto contigo pronto.' ) );
}
