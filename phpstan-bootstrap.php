<?php
/**
 * PHPStan bootstrap for theme analysis.
 *
 * @package BSC2
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' );
}

if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
	define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'mu-plugins' );
}

if ( ! defined( 'BSC_THEME_VERSION' ) ) {
	define( 'BSC_THEME_VERSION', '2.1.0' );
}

if ( ! class_exists( 'WC_Product' ) ) {
	class WC_Product {
		public function __construct( $product = 0 ) {}
		public function set_name( $name ): void {}
		public function set_regular_price( $price ): void {}
		public function set_short_description( $description ): void {}
		public function set_sku( $sku ): void {}
		public function set_category_ids( array $category_ids ): void {}
		public function set_image_id( $image_id ): void {}
		public function set_gallery_image_ids( array $image_ids ): void {}
		public function update_meta_data( $key, $value ): void {}
		public function save(): void {}
	}
}

if ( ! class_exists( 'WC_Product_Simple' ) ) {
	class WC_Product_Simple extends WC_Product {}
}

if ( ! function_exists( 'wc_get_product' ) ) {
	function wc_get_product( $product = 0 ) {
		return null;
	}
}

if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
	function wc_get_product_id_by_sku( $sku ): int {
		return 0;
	}
}
