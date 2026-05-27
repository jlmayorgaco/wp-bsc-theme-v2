<?php
/**
 * Frontend performance cleanup hooks.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_generator' );

add_filter( 'woocommerce_should_load_block_assets', 'bsc_should_load_woocommerce_block_assets', 20 );

/**
 * Avoid WooCommerce block assets on catalog/product pages handled by custom templates.
 *
 * @param bool $should_load Whether WooCommerce wants to load block assets.
 */
function bsc_should_load_woocommerce_block_assets( bool $should_load ): bool {
	if ( is_admin() ) {
		return $should_load;
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return $should_load;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return $should_load;
	}

	return false;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-blocks-style' );
		wp_dequeue_style( 'wc-blocks-vendors-style' );

		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
			wp_dequeue_script( 'bsc-filter-slider-script' );
		}

		if ( ! ( function_exists( 'is_cart' ) && is_cart() ) && ! ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
			$block_asset_handles = array(
				'wc-blocks',
				'wc-blocks-registry',
				'wc-blocks-middleware',
				'wc-blocks-data-store',
				'wc-blocks-checkout',
				'wc-cart-checkout-base',
				'wc-cart-checkout-vendors',
				'wc-blocks-components',
			);

			foreach ( $block_asset_handles as $handle ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
	},
	100
);
