<?php
/**
 * Frontend routing guards.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirect anonymous account-page visitors to the custom login page.
 */
function bsc_redirect_my_account_guests() {
	if ( is_account_page() && ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/login/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'bsc_redirect_my_account_guests' );

/**
 * Check whether the BSC parking page should be shown.
 */
function bsc_is_parking_page_enabled(): bool {
	$saved = get_option( 'bsc_parking_page_enabled', null );

	if ( null !== $saved ) {
		return 1 === (int) $saved;
	}

	if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'wc_admin_get_feature_config' ) ) {
		return false;
	}

	if ( 'yes' === get_option( 'woocommerce_coming_soon', 'no' ) ) {
		return true;
	}

	return 'coming-soon' === get_option( 'woocommerce_coming_soon_visibility', 'coming-soon' );
}

/**
 * Check whether the current user can bypass the parking page.
 */
function bsc_current_user_can_bypass_parking_page(): bool {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers manage_woocommerce.
	return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
}

/**
 * Render the BSC parking page before public template routing.
 */
function bsc_maybe_render_parking_page(): void {
	if ( is_admin() || bsc_current_user_can_bypass_parking_page() || ! bsc_is_parking_page_enabled() ) {
		return;
	}

	$template = get_stylesheet_directory() . '/woocommerce/coming-soon.php';

	if ( file_exists( $template ) ) {
		include $template;
		exit;
	}
}
add_action( 'template_redirect', 'bsc_maybe_render_parking_page', 0 );
