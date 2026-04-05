<?php
/**
 * BSC2 functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package BSC2
 */

if ( ! defined( '_S_VERSION' ) ) {
	define( '_S_VERSION', (string) filemtime( get_template_directory() . '/style.css' ) );
}

// BSC-008: contact form destination — override in wp-config.php if needed
if ( ! defined( 'BSC_CONTACT_EMAIL' ) ) {
	define( 'BSC_CONTACT_EMAIL', 'contacto@bubbleskincare.co' );
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
require get_template_directory() . '/inc/functions_bsc.php';
require_once get_template_directory() . '/inc/bsc-contact-options.php';

// Load Jetpack compatibility file.
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

// WooCommerce
if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce.php';
}

// Admin: product custom fields
if ( is_admin() ) {
	require_once get_template_directory() . '/inc/admin/product-covers.php';
}

// BSC-029: custom roles (always loaded — roles must exist for frontend checks too)
require_once get_template_directory() . '/includes/class-bsc-roles.php';

// BSC-029: admin access restrictions + BSC-030: custom admin menu (admin only)
if ( is_admin() ) {
	require_once get_template_directory() . '/includes/class-bsc-permissions.php';
	require_once get_template_directory() . '/admin/bsc-admin-menu.php';
}

// Scripts 
require_once get_template_directory() . '/scripts/script_init.php';
require_once get_template_directory() . '/scripts/script_custom_types.php';

// AJAX Actions
require_once get_template_directory() . '/inc/ajax/cart-actions.php';
require_once get_template_directory() . '/inc/ajax/checkout-actions.php';
require_once get_template_directory() . '/inc/ajax/coupons-actions.php';
require_once get_template_directory() . '/inc/ajax/filters-actions.php';
require_once get_template_directory() . '/inc/ajax/review-summary-actions.php';
require_once get_template_directory() . '/inc/ajax/newsletter-actions.php';
require_once get_template_directory() . '/inc/ajax/contact-actions.php';
require_once get_template_directory() . '/inc/ajax/creator-actions.php';
require_once get_template_directory() . '/inc/ajax/search-actions.php';



// ── Performance: disable WordPress emoji scripts/styles ───────────────────
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

// ── Performance: remove oEmbed / REST API exposure from <head> ─────────────
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_generator');

// ── Performance: disable Gutenberg block editor CSS on frontend ────────────
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-blocks-style');
}, 100);

// ── Security: remove WordPress version from all outputs ───────────────────
add_filter('the_generator', '__return_empty_string');

// ── Performance: add preconnect for Google Fonts CDN (used in style.css) ──
add_action('wp_head', function () {
    echo '<link rel="preconnect" href="https://fonts.cdnfonts.com" crossorigin>' . "\n";
    echo '<meta name="robots" content="max-image-preview:large">' . "\n";
}, 1);

// BSC-015: render extra images block below product summary
add_action('woocommerce_after_single_product_summary', 'bsc_render_extra_images', 25);
function bsc_render_extra_images(): void {
    $post_id = get_the_ID();
    $images  = [];
    for ($i = 1; $i <= 3; $i++) {
        $attachment_id = (int) get_post_meta($post_id, "_bsc_extra_image_{$i}", true);
        if ($attachment_id > 0) {
            $url = wp_get_attachment_image_url($attachment_id, 'large');
            if ($url) $images[] = ['url' => $url, 'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true)];
        }
    }
    if (empty($images)) return;

    echo '<div class="bsc-product-extra-images">';
    foreach ($images as $img) {
        echo '<img src="' . esc_url($img['url']) . '" alt="' . esc_attr($img['alt']) . '" loading="lazy">';
    }
    echo '</div>';
}

function bsc_redirect_my_account_guests() {
  if (is_account_page() && !is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
  }
}
add_action('template_redirect', 'bsc_redirect_my_account_guests');



add_action('template_redirect', function () {
    // Only act on the frontend
    if (is_admin() || is_user_logged_in()) {
        return;
    }

    // Check if WooCommerce Coming Soon mode is active
    if (function_exists('wc_admin_get_feature_config')) {
        $visibility = get_option('woocommerce_coming_soon_visibility', 'coming-soon');

        if ($visibility === 'coming-soon') {
            include get_stylesheet_directory() . '/woocommerce/coming-soon.php';
            exit;
        }
    }
});
