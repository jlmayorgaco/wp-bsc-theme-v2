<?php
/**
 * BSC2 functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BSC_THEME_VERSION' ) ) {
	define( 'BSC_THEME_VERSION', '2.1.3' );
}

require_once get_template_directory() . '/inc/asset-version.php';

if ( ! defined( '_S_VERSION' ) ) {
	define( '_S_VERSION', bsc_get_asset_version( 'style.css' ) );
}

if ( ! defined( 'BSC_CONTACT_EMAIL' ) ) {
	define( 'BSC_CONTACT_EMAIL', 'contacto@bubbleskincare.co' );
}

// Setup.
require_once get_template_directory() . '/inc/setup/theme-setup.php';
require_once get_template_directory() . '/inc/setup/widgets-setup.php';

// Assets.
require_once get_template_directory() . '/inc/scripts/enqueue-scripts.php';

// Core theme helpers.
require get_template_directory() . '/inc/custom-header.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/functions_bsc.php';
require_once get_template_directory() . '/inc/bsc-contact-options.php';
require_once get_template_directory() . '/inc/privacy-data.php';
require_once get_template_directory() . '/inc/contact-store.php';
require_once get_template_directory() . '/inc/newsletter-store.php';
require_once get_template_directory() . '/inc/bsc-static-pages.php';
require_once get_template_directory() . '/inc/bsc-url-helpers.php';
require_once get_template_directory() . '/inc/local-url-scheme.php';
require_once get_template_directory() . '/inc/header-menu-covers.php';
require_once get_template_directory() . '/inc/home-brands.php';
require_once get_template_directory() . '/inc/responsive-images.php';
require_once get_template_directory() . '/inc/product/product-location-code.php';
require_once get_template_directory() . '/inc/product-category-labels.php';
require_once get_template_directory() . '/inc/seo/meta.php';
require_once get_template_directory() . '/inc/seo/structured-data.php';
require_once get_template_directory() . '/inc/analytics/ga4.php';
require_once get_template_directory() . '/inc/analytics/metrics.php';
require_once get_template_directory() . '/inc/merchant-center-feed.php';

if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce.php';
	require_once get_template_directory() . '/inc/payments/wompi-compat.php';
}

if ( is_admin() ) {
	require_once get_template_directory() . '/inc/admin/product-covers.php';
}

require_once get_template_directory() . '/includes/class-bsc-roles.php';
require_once get_template_directory() . '/includes/class-bsc-stock.php';

foreach ( glob( get_template_directory() . '/plugins/*/index.php' ) as $bsc_module_bootstrap ) {
	require_once $bsc_module_bootstrap;
}

require_once get_template_directory() . '/emails/bsc-email-helpers.php';
require_once get_template_directory() . '/emails/bsc-email-previews.php';
require_once get_template_directory() . '/emails/bsc-followup-emails.php';
require_once get_template_directory() . '/inc/abandoned-cart.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once get_template_directory() . '/emails/bsc-emails.php';
}

if ( is_admin() ) {
	require_once get_template_directory() . '/includes/class-bsc-permissions.php';
	require_once get_template_directory() . '/admin/bsc-admin-menu.php';
}

require_once get_template_directory() . '/scripts/script_init.php';
require_once get_template_directory() . '/scripts/script_custom_types.php';

// Runtime behavior modules.
require_once get_template_directory() . '/inc/performance/frontend-cleanup.php';
require_once get_template_directory() . '/inc/security/security-hooks.php';
require_once get_template_directory() . '/inc/product/product-extra-images.php';
require_once get_template_directory() . '/inc/routing/frontend-routing.php';

// AJAX actions.
require_once get_template_directory() . '/inc/ajax/cart-actions.php';
require_once get_template_directory() . '/inc/ajax/checkout-actions.php';
require_once get_template_directory() . '/inc/ajax/coupons-actions.php';
require_once get_template_directory() . '/inc/ajax/review-summary-actions.php';
require_once get_template_directory() . '/inc/ajax/newsletter-actions.php';
require_once get_template_directory() . '/inc/ajax/contact-actions.php';
require_once get_template_directory() . '/inc/ajax/creator-actions.php';
require_once get_template_directory() . '/inc/ajax/search-actions.php';
