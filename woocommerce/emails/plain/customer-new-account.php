<?php
/**
 * Plain text account setup email.
 *
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;
echo esc_html( $email_heading ) . "\n\n";
echo 'Hola ' . esc_html( $user_display_name ) . ",\n\n";
echo "Tu cuenta en Bubble Skin Care está creada. Podrás consultar tus pedidos y tus Bubble Points desde tu perfil.\n\n";
if ( $password_generated && $set_password_url ) {
	echo "Establece tu contraseña con este enlace:\n" . esc_url_raw( $set_password_url ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text email URL.
	echo "Al usarlo confirmarás tu correo. Las compras anteriores hechas como invitado con este mismo correo y sus puntos correspondientes se asociarán a tu perfil.\n\n";
}
echo 'Mi cuenta: ' . esc_url_raw( wc_get_page_permalink( 'myaccount' ) ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text email URL.
if ( $additional_content ) {
	echo "\n" . esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n";
}
