<?php
/**
 * Frontend routing guards.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

function bsc_redirect_my_account_guests() {
	if ( is_account_page() && ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/login/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'bsc_redirect_my_account_guests' );

add_action(
	'template_redirect',
	function () {
		if ( is_admin() || is_user_logged_in() ) {
			return;
		}

		if ( function_exists( 'wc_admin_get_feature_config' ) ) {
			$visibility = get_option( 'woocommerce_coming_soon_visibility', 'coming-soon' );

			if ( $visibility === 'coming-soon' ) {
				include get_stylesheet_directory() . '/woocommerce/coming-soon.php';
				exit;
			}
		}
	}
);
