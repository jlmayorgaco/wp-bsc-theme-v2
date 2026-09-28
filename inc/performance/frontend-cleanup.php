<?php
/**
 * Frontend performance cleanup hooks.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return true for responses that contain private/session state.
 */
function bsc_should_bypass_page_cache(): bool {
	if ( ( function_exists( 'is_admin' ) && is_admin() ) || ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) || ( function_exists( 'is_preview' ) && is_preview() ) || ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) ) {
		return true;
	}

	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized before comparison.
	if ( ! in_array( $request_method, array( 'GET', 'HEAD' ), true ) ) {
		return true;
	}

	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return true;
	}

	foreach ( array( 'wc-ajax', 'add-to-cart', 'order-pay', 'key', 'routine_run' ) as $query_arg ) {
		if ( isset( $_GET[ $query_arg ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Cache routing only; no state is changed.
			return true;
		}
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return true;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return true;
	}
	// Custom authentication pages carry nonces and reset/registration state.
	if ( function_exists( 'is_page' ) && is_page( array( 'login', 'register', 'registro-familia-bubbles', 'forgot-password', 'reset-password' ) ) ) {
		return true;
	}
	if ( function_exists( 'is_page_template' ) && is_page_template( array( 'page-login.php', 'page-register.php' ) ) ) {
		return true;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
		return true;
	}

	foreach ( array_keys( wp_unslash( $_COOKIE ) ) as $cookie_name ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Cookie names only determine cache variation.
		if ( preg_match( '/^(?:wordpress_logged_in_|wordpress_sec_|wp-settings-|woocommerce_|wp_woocommerce_session_|comment_author_)/', (string) $cookie_name ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Keep private/session responses out of page caches while allowing anonymous
 * home, catalog and product pages to be cached by a reverse proxy.
 *
 * @param array<string, string> $headers Response headers prepared by WordPress.
 * @return array<string, string>
 */
function bsc_frontend_page_cache_headers( array $headers ): array {
	if ( bsc_should_bypass_page_cache() ) {
		$headers['Cache-Control']   = 'private, no-store, no-cache, must-revalidate, max-age=0';
		$headers['Pragma']          = 'no-cache';
		$headers['Expires']         = 'Wed, 11 Jan 1984 05:00:00 GMT';
		$headers['X-Accel-Expires'] = '0';
		return $headers;
	}

	$headers['Cache-Control']   = 'public, max-age=0, s-maxage=300, stale-while-revalidate=30';
	$headers['X-Accel-Expires'] = '300';
	unset( $headers['Pragma'], $headers['Expires'] );

	return $headers;
}
add_filter( 'wp_headers', 'bsc_frontend_page_cache_headers', PHP_INT_MAX );

/**
 * Keep WordPress cache plugins and reverse proxies from storing private pages.
 */
function bsc_disable_frontend_page_cache(): void {
	if ( ! bsc_should_bypass_page_cache() ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();
	header( 'X-Accel-Expires: 0' );
}
add_action( 'template_redirect', 'bsc_disable_frontend_page_cache', -100 );

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

/**
 * Avoid shipping the password-strength dictionary where no password can be entered.
 * WooCommerce enqueues it on classic cart pages and on empty checkout responses,
 * despite those views not rendering an account/password form.
 */
function bsc_dequeue_password_meter_without_password_form(): void {
	if ( is_admin() ) {
		return;
	}

	$should_dequeue = function_exists( 'is_cart' ) && is_cart();

	if ( ! $should_dequeue && function_exists( 'is_checkout' ) && is_checkout() && function_exists( 'WC' ) ) {
		$woocommerce = WC();
		$should_dequeue = $woocommerce && $woocommerce->cart instanceof WC_Cart && $woocommerce->cart->is_empty();
	}

	if ( ! $should_dequeue ) {
		return;
	}

	foreach ( array( 'wc-password-strength-meter', 'password-strength-meter', 'zxcvbn-async', 'zxcvbn' ) as $handle ) {
		wp_dequeue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'bsc_dequeue_password_meter_without_password_form', PHP_INT_MAX );
