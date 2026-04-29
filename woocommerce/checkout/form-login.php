<?php
/**
 * Checkout login form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-login.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
	return;
}
?>
<div class="woocommerce-form-login-toggle">
	<?php wc_print_notice( apply_filters( 'woocommerce_checkout_login_message', '¿Ya tienes una cuenta?' ) . ' <a href="#" class="showlogin">Haz clic aquí para iniciar sesión</a>', 'notice' ); ?>
</div>
<?php
woocommerce_login_form(
	array(
		'message'  => 'Si ya has comprado con nosotros, ingresa tus datos a continuación. Si eres cliente nuevo, continúa con la sección de facturación.',
		'redirect' => wc_get_checkout_url(),
		'hidden'   => true,
	)
);