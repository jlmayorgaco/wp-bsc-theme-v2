<?php
/**
 * Optional checkout accounts with an emailed password setup link.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

// The custom checkout supports both guest purchases and explicit account opt-in.
add_filter( 'woocommerce_checkout_registration_enabled', '__return_true' );
add_filter( 'woocommerce_checkout_registration_required', '__return_false' );

/**
 * Generate login credentials internally for checkout; customers choose their
 * password using the emailed, single-use link instead of entering it at checkout.
 *
 * @param mixed $pre_option Existing option override.
 * @return mixed
 */
function bsc_checkout_generate_credentials( $pre_option ) {
	return ( is_checkout() || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) ) ? 'yes' : $pre_option;
}
add_filter( 'pre_option_woocommerce_registration_generate_username', 'bsc_checkout_generate_credentials' );
add_filter( 'pre_option_woocommerce_registration_generate_password', 'bsc_checkout_generate_credentials' );

/**
 * Persist the checkout origin before user_register and email hooks run.
 *
 * @param array $data New WooCommerce customer data.
 * @return array
 */
function bsc_checkout_mark_new_account( array $data ): array {
	if ( is_checkout() || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) ) {
		$data['meta_input']['_bsc_checkout_account'] = 'yes';
	}
	return $data;
}
add_filter( 'woocommerce_new_customer_data', 'bsc_checkout_mark_new_account' );

/**
 * The opted-in account must receive its password link, including deferred mail.
 *
 * @param bool    $enabled Existing email setting.
 * @param WP_User $user New account.
 * @return bool
 */
function bsc_checkout_enable_account_email( $enabled, $user ): bool {
	return (bool) $enabled || ( $user instanceof WP_User && 'yes' === get_user_meta( $user->ID, '_bsc_checkout_account', true ) );
}
add_filter( 'woocommerce_email_enabled_customer_new_account', 'bsc_checkout_enable_account_email', 20, 2 );

/**
 * Link to the actual custom login page when the checkout email already exists.
 *
 * @param string $message WooCommerce error.
 * @return string
 */
function bsc_checkout_existing_account_message( string $message ): string {
	if ( ! is_checkout() && ! ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) ) {
		return $message;
	}
	return sprintf(
		'Ya existe una cuenta con este correo. <a href="%s">Inicia sesión</a> o desmarca la opción de crear cuenta para comprar como invitado.',
		esc_url( home_url( '/login/' ) )
	);
}
add_filter( 'woocommerce_registration_error_email_exists', 'bsc_checkout_existing_account_message' );

/**
 * Use Spanish for the transactional account setup email.
 *
 * @return string
 */
function bsc_checkout_account_email_title(): string {
	return 'Tu cuenta en Bubble Skin Care está lista';
}
add_filter( 'woocommerce_email_subject_customer_new_account', 'bsc_checkout_account_email_title' );
add_filter( 'woocommerce_email_heading_customer_new_account', 'bsc_checkout_account_email_title' );
