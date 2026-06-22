<?php
/**
 * Bootstrapper for the BSC Growth module.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Plugin {
	public const VERSION = '1.0.0';

	public static function register_hooks(): void {
		BSC_Growth_Bundles::register_hooks();
		BSC_Growth_Skin_Quiz_Store::register_hooks();
		BSC_Growth_Skin_Quiz::register_hooks();
		BSC_Growth_Skin_Quiz_Admin::register_hooks();
		BSC_Growth_CRM::register_hooks();
		BSC_Growth_Conversion::register_hooks();
		BSC_Growth_Repurchase::register_hooks();
	}

	public static function path( string $relative = '' ): string {
		return trailingslashit( dirname( __DIR__ ) ) . ltrim( $relative, '/\\' );
	}

	public static function url( string $relative = '' ): string {
		return trailingslashit( get_template_directory_uri() . '/plugins/bsc-growth' ) . ltrim( $relative, '/\\' );
	}

	public static function asset_version( string $relative ): string {
		$path = self::path( $relative );

		return file_exists( $path ) ? (string) filemtime( $path ) : self::VERSION;
	}

	public static function admin_css_version(): string {
		$path = get_template_directory() . '/admin/bsc-growth.css';

		return file_exists( $path ) ? (string) filemtime( $path ) : self::VERSION;
	}

	public static function cart() {
		return is_callable( 'WC' ) ? call_user_func( 'WC' )->cart : null;
	}

	public static function cart_url(): string {
		return self::checkout_url();
	}

	public static function checkout_url(): string {
		return is_callable( 'wc_get_checkout_url' ) ? (string) call_user_func( 'wc_get_checkout_url' ) : home_url( '/checkout/' );
	}

	public static function shop_url(): string {
		return is_callable( 'wc_get_page_permalink' ) ? (string) call_user_func( 'wc_get_page_permalink', 'shop' ) : home_url( '/shop/' );
	}

	public static function placeholder_image(): string {
		return is_callable( 'wc_placeholder_img_src' ) ? (string) call_user_func( 'wc_placeholder_img_src', 'woocommerce_thumbnail' ) : '';
	}

	public static function price_html( float $amount ): string {
		return is_callable( 'wc_price' ) ? (string) call_user_func( 'wc_price', $amount ) : number_format_i18n( $amount, 0 );
	}

	public static function orders( array $args ): array {
		$orders = is_callable( 'wc_get_orders' ) ? call_user_func( 'wc_get_orders', $args ) : array();

		return is_array( $orders ) ? $orders : array();
	}

	public static function products( array $args ): array {
		$products = is_callable( 'wc_get_products' ) ? call_user_func( 'wc_get_products', $args ) : array();

		return is_array( $products ) ? $products : array();
	}

	public static function order_statuses(): array {
		$statuses = is_callable( 'wc_get_order_statuses' ) ? call_user_func( 'wc_get_order_statuses' ) : array();

		return is_array( $statuses ) ? $statuses : array();
	}
}
