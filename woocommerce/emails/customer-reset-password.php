<?php
/**
 * BSC override for the WooCommerce customer reset password email.
 *
 * @package BSC2
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bsc_render_email_template' ) ) {
	require_once get_template_directory() . '/emails/bsc-email-helpers.php';
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

$bsc_body = bsc_render_email_template(
	'bsc-password-reset-email.php',
	array(
		'user'      => $bsc_user instanceof WP_User ? $bsc_user : null,
		'reset_url' => $bsc_reset_url,
	)
);

if ( '' !== $bsc_body ) {
	echo $bsc_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template renderer escapes dynamic values.
	return;
}

?>
<p><?php echo esc_html( wp_specialchars_decode( 'Recibimos una solicitud para restablecer la contrase&ntilde;a de tu cuenta BSC.', ENT_QUOTES ) ); ?></p>
<p><a href="<?php echo esc_url( $bsc_reset_url ); ?>"><?php echo esc_html( wp_specialchars_decode( 'Cambiar mi contrase&ntilde;a', ENT_QUOTES ) ); ?></a></p>
