<?php
/**
 * Account setup email. Preserve WooCommerce's single-use password URL.
 *
 * @package WooCommerce\Templates\Emails
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;
do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p>Hola <?php echo esc_html( $user_display_name ); ?>,</p>
<p>Tu cuenta en Bubble Skin Care está creada. Podrás consultar tus pedidos y tus Bubble Points desde tu perfil.</p>
<?php if ( $password_generated && $set_password_url ) : ?>
	<p>Establece tu contraseña con el siguiente enlace:</p>
	<p><a href="<?php echo esc_url( $set_password_url ); ?>">Establecer mi contraseña</a></p>
	<p>Al usarlo confirmarás tu correo. Las compras anteriores hechas como invitado con este mismo correo y sus puntos correspondientes se asociarán a tu perfil.</p>
<?php endif; ?>
<p><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Ir a mi cuenta</a></p>
<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email );
