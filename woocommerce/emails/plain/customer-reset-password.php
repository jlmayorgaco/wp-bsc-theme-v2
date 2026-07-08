<?php
/**
 * Plain-text BSC override for the WooCommerce customer reset password email.
 *
 * @package BSC2
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bsc_email_name_from_user' ) ) {
	require_once get_template_directory() . '/emails/bsc-email-design-system.php';
}

$bsc_user_id    = isset( $user_id ) ? absint( $user_id ) : 0;
$bsc_user_login = isset( $user_login ) ? (string) $user_login : '';
$bsc_reset_key  = isset( $reset_key ) ? (string) $reset_key : '';
$bsc_user       = $bsc_user_id > 0 ? get_user_by( 'id', $bsc_user_id ) : null;

if ( ! ( $bsc_user instanceof WP_User ) && '' !== $bsc_user_login ) {
	$bsc_user = get_user_by( 'login', $bsc_user_login );
}

if ( $bsc_user instanceof WP_User ) {
	$bsc_user_id    = (int) $bsc_user->ID;
	$bsc_user_login = (string) $bsc_user->user_login;
}

$bsc_reset_url = '';
if ( '' !== $bsc_reset_key && '' !== $bsc_user_login && function_exists( 'wc_get_endpoint_url' ) && function_exists( 'wc_get_page_permalink' ) ) {
	$bsc_reset_url = add_query_arg(
		array(
			'key'   => $bsc_reset_key,
			'id'    => $bsc_user_id,
			'login' => rawurlencode( $bsc_user_login ),
		),
		wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) )
	);
}

if ( '' === $bsc_reset_url ) {
	$bsc_reset_url = wp_lostpassword_url();
}

$bsc_display_name = bsc_email_name_from_user( $bsc_user instanceof WP_User ? $bsc_user : null, 'Bubble Lover' );
$bsc_password     = wp_specialchars_decode( 'contrase&ntilde;a', ENT_QUOTES );

printf(
	"Hola %s,\n\nRecibimos una solicitud para restablecer la %s de tu cuenta BSC.\n\nCambia tu %s aqui:\n%s\n\nSi no solicitaste este cambio, ignora este correo.\n",
	esc_html( sanitize_text_field( $bsc_display_name ) ),
	esc_html( $bsc_password ),
	esc_html( $bsc_password ),
	esc_url_raw( $bsc_reset_url )
);
