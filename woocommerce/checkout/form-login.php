<?php
/**
 * Checkout login form.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-login.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.0.0
 */

defined( 'ABSPATH' ) || exit;

$registration_at_checkout   = WC_Checkout::instance()->is_registration_enabled();
$login_reminder_at_checkout = 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' );

if ( is_user_logged_in() ) {
	return;
}

if ( $login_reminder_at_checkout ) : ?>
	<div class="woocommerce-form-login-toggle">
		<?php
		wc_print_notice(
			apply_filters( 'woocommerce_checkout_login_message', '¿Ya tienes una cuenta?' ) . // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			' <a href="#" class="showlogin">Haz clic aquí para iniciar sesión</a>',
			'notice'
		);
		?>
	</div>
	<?php
endif;

if ( $registration_at_checkout || $login_reminder_at_checkout ) :
	$show_form = isset( $_POST['login'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	woocommerce_login_form(
		array(
			'message'  => 'Si ya has comprado con nosotros, ingresa tus datos a continuación. Si eres cliente nuevo, continúa con la sección de facturación.',
			'redirect' => wc_get_checkout_url(),
			'hidden'   => ! $show_form,
		)
	);
endif;
