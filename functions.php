<?php
/**
 * BSC2 functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package BSC2
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

// Setup
require_once get_template_directory() . '/inc/setup/theme-setup.php';
require_once get_template_directory() . '/inc/setup/widgets-setup.php';

// Assets Scripts
require_once get_template_directory() . '/inc/scripts/enqueue-scripts.php';

// Former Inc
require get_template_directory() . '/inc/custom-header.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/customizer.php';

// Load Jetpack compatibility file.
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

// WooCommerce
if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce.php';
}

// Scripts 
require_once get_template_directory() . '/scripts/script_init.php';
require_once get_template_directory() . '/scripts/script_custom_types.php';

// Shortcodes
require_once get_template_directory() . '/shortcodes/bsc_simple_carousel.php';

// AJAX Actions
require_once get_template_directory() . '/inc/ajax/cart-actions.php';
require_once get_template_directory() . '/inc/ajax/checkout-actions.php';
require_once get_template_directory() . '/inc/ajax/coupons-actions.php';
require_once get_template_directory() . '/inc/ajax/filters-actions.php';
require_once get_template_directory() . '/inc/ajax/review-summary-actions.php';






function bsc_redirect_my_account_guests() {
  if (is_account_page() && !is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
  }
}
add_action('template_redirect', 'bsc_redirect_my_account_guests');


