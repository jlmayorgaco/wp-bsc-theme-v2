<?php
/**
 * WooCommerce Compatibility File
 *
 * @link https://woocommerce.com/
 *
 * @package BSC2
 */

/**
 * WooCommerce setup function.
 *
 * @link https://docs.woocommerce.com/document/third-party-custom-theme-compatibility/
 * @link https://github.com/woocommerce/woocommerce/wiki/Enabling-product-gallery-features-(zoom,-swipe,-lightbox)
 * @link https://github.com/woocommerce/woocommerce/wiki/Declaring-WooCommerce-support-in-themes
 *
 * @return void
 */
function bsc_2_0_woocommerce_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 150,
			'single_image_width'    => 300,
			'product_grid'          => array(
				'default_rows'    => 3,
				'min_rows'        => 1,
				'default_columns' => 4,
				'min_columns'     => 1,
				'max_columns'     => 6,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'bsc_2_0_woocommerce_setup' );

/**
 * WooCommerce specific scripts & stylesheets.
 *
 * @return void
 */
function bsc_2_0_woocommerce_scripts() {
	wp_enqueue_style( 'bsc-2-0-woocommerce-style', get_template_directory_uri() . '/woocommerce.css', array(), _S_VERSION );

	$font_path   = WC()->plugin_url() . '/assets/fonts/';
	$inline_font = '@font-face {
			font-family: "star";
			src: url("' . $font_path . 'star.eot");
			src: url("' . $font_path . 'star.eot?#iefix") format("embedded-opentype"),
				url("' . $font_path . 'star.woff") format("woff"),
				url("' . $font_path . 'star.ttf") format("truetype"),
				url("' . $font_path . 'star.svg#star") format("svg");
			font-weight: normal;
			font-style: normal;
		}';

	wp_add_inline_style( 'bsc-2-0-woocommerce-style', $inline_font );
}
add_action( 'wp_enqueue_scripts', 'bsc_2_0_woocommerce_scripts' );

/**
 * Disable the default WooCommerce stylesheet.
 *
 * Removing the default WooCommerce stylesheet and enqueing your own will
 * protect you during WooCommerce core updates.
 *
 * @link https://docs.woocommerce.com/document/disable-the-default-stylesheet/
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Add 'woocommerce-active' class to the body tag.
 *
 * @param  array $classes CSS classes applied to the body tag.
 * @return array $classes modified to include 'woocommerce-active' class.
 */
function bsc_2_0_woocommerce_active_body_class( $classes ) {
	$classes[] = 'woocommerce-active';

	return $classes;
}
add_filter( 'body_class', 'bsc_2_0_woocommerce_active_body_class' );

/**
 * Keep checkout as the single cart destination.
 *
 * BSC has its own empty-checkout view, so WooCommerce should not send users
 * from an empty checkout to the cart page.
 */
add_filter( 'woocommerce_checkout_redirect_empty_cart', '__return_false' );

function bsc_2_0_get_checkout_url(): string {
	return function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
}

function bsc_product_variants_normalize_hex( $raw_color ): string {
	$raw_color = trim( sanitize_text_field( (string) $raw_color ) );
	if ( $raw_color === '' ) {
		return '';
	}

	if ( ! str_starts_with( $raw_color, '#' ) ) {
		$raw_color = '#' . $raw_color;
	}

	$hex = sanitize_hex_color( $raw_color );
	if ( ! $hex ) {
		return '';
	}

	if ( strlen( $hex ) === 4 ) {
		$hex = sprintf( '#%1$s%1$s%2$s%2$s%3$s%3$s', $hex[1], $hex[2], $hex[3] );
	}

	return strtoupper( $hex );
}

function bsc_product_variants_sanitize_price_value( $raw_price ): ?string {
	$raw_price = trim( (string) sanitize_text_field( $raw_price ) );

	if ( $raw_price === '' ) {
		return '';
	}

	$normalized = str_replace( ',', '.', $raw_price );
	if ( ! is_numeric( $normalized ) || (float) $normalized < 0 ) {
		return null;
	}

	return wc_format_decimal( $normalized, wc_get_price_decimals() );
}

function bsc_product_variants_normalize_price( $raw_price ): string {
	$price = bsc_product_variants_sanitize_price_value( $raw_price );

	return $price === null ? '' : $price;
}

function bsc_product_variant_matrix_key( string $color_name, string $size_name ): string {
	$color_key = sanitize_title( remove_accents( $color_name ) );
	$size_key  = sanitize_title( remove_accents( $size_name ) );
	$source    = strtolower( $color_key . '|' . $size_key );

	if ( $source === '|' ) {
		return '';
	}

	return substr( md5( $source ), 0, 16 );
}

function bsc_product_variant_bool( $raw_value, bool $default = true ): bool {
	if ( $raw_value === null || $raw_value === '' ) {
		return $default;
	}

	if ( is_bool( $raw_value ) ) {
		return $raw_value;
	}

	return in_array( strtolower( (string) $raw_value ), array( '1', 'true', 'yes', 'on', 'enabled' ), true );
}

function bsc_product_variant_effective_price( array $variant ): string {
	$sale_price    = (string) ( $variant['sale_price'] ?? '' );
	$regular_price = (string) ( $variant['regular_price'] ?? '' );

	return $sale_price !== '' ? $sale_price : $regular_price;
}

/**
 * Return the combined warehouse and store stock for a custom variant.
 *
 * @param array $variant Custom product variant data.
 * @return int
 */
function bsc_product_variant_stock_total( array $variant ): int {
	$stock_bodega = max( 0, (int) ( $variant['stock_bodega'] ?? 0 ) );
	$stock_tienda = max( 0, (int) ( $variant['stock_tienda'] ?? 0 ) );

	return $stock_bodega + $stock_tienda;
}

/**
 * Determine whether a custom variant can be shown on the storefront.
 *
 * @param array $variant Custom product variant data.
 * @return bool
 */
function bsc_product_variant_is_publicly_available( array $variant ): bool {
	return ! empty( $variant['enabled'] ) && bsc_product_variant_stock_total( $variant ) > 0;
}

function bsc_get_legacy_product_color_variants( int $product_id ): array {
	if ( $product_id <= 0 || get_post_meta( $product_id, '_bsc_color_variants_enabled', true ) !== '1' ) {
		return array();
	}

	$raw_variants = get_post_meta( $product_id, '_bsc_color_variants', true );
	if ( ! is_array( $raw_variants ) ) {
		return array();
	}

	$variants = array();
	foreach ( $raw_variants as $raw_variant ) {
		if ( ! is_array( $raw_variant ) ) {
			continue;
		}

		$name = sanitize_text_field( (string) ( $raw_variant['name'] ?? '' ) );
		$hex  = bsc_product_variants_normalize_hex( $raw_variant['hex'] ?? '' );

		if ( $name === '' || $hex === '' ) {
			continue;
		}

		$variants[] = array(
			'name'  => $name,
			'hex'   => $hex,
			'price' => bsc_product_variants_normalize_price( $raw_variant['price'] ?? '' ),
		);
	}

	return $variants;
}

function bsc_get_legacy_product_size_variants( int $product_id ): array {
	if ( $product_id <= 0 || get_post_meta( $product_id, '_bsc_size_variants_enabled', true ) !== '1' ) {
		return array();
	}

	$raw_variants = get_post_meta( $product_id, '_bsc_size_variants', true );
	if ( ! is_array( $raw_variants ) ) {
		return array();
	}

	$variants = array();
	foreach ( $raw_variants as $raw_variant ) {
		if ( ! is_array( $raw_variant ) ) {
			continue;
		}

		$name = sanitize_text_field( (string) ( $raw_variant['name'] ?? '' ) );
		if ( $name === '' ) {
			continue;
		}

		$variants[] = array(
			'name'  => $name,
			'price' => bsc_product_variants_normalize_price( $raw_variant['price'] ?? '' ),
		);
	}

	return $variants;
}

function bsc_sanitize_product_variant_matrix( $raw_matrix, ?array &$errors = null ): array {
	if ( ! is_array( $raw_matrix ) ) {
		return array();
	}

	$matrix         = array();
	$seen           = array();
	$collect_errors = is_array( $errors );

	foreach ( $raw_matrix as $raw_variant ) {
		if ( ! is_array( $raw_variant ) ) {
			continue;
		}

		$color_name = sanitize_text_field( (string) ( $raw_variant['color_name'] ?? '' ) );
		$color_hex  = bsc_product_variants_normalize_hex( $raw_variant['color_hex'] ?? '' );
		$size_name  = sanitize_text_field( (string) ( $raw_variant['size_name'] ?? '' ) );

		if ( $color_name === '' && $size_name === '' ) {
			continue;
		}

		if ( $color_name !== '' && $color_hex === '' ) {
			$color_hex = '#F7C0CD';
		}

		$regular_price = bsc_product_variants_sanitize_price_value(
			$raw_variant['regular_price'] ?? ( $raw_variant['price'] ?? '' )
		);
		$sale_price    = bsc_product_variants_sanitize_price_value( $raw_variant['sale_price'] ?? '' );

		if ( $regular_price === null ) {
			if ( $collect_errors ) {
				$errors[] = sprintf( 'Precio regular invalido para la variante "%s".', trim( $color_name . ' ' . $size_name ) );
			}
			continue;
		}

		if ( $sale_price === null ) {
			if ( $collect_errors ) {
				$errors[] = sprintf( 'Precio de oferta invalido para la variante "%s".', trim( $color_name . ' ' . $size_name ) );
			}
			continue;
		}

		if ( $sale_price !== '' && $regular_price === '' ) {
			if ( $collect_errors ) {
				$errors[] = sprintf( 'La variante "%s" necesita precio regular para usar oferta.', trim( $color_name . ' ' . $size_name ) );
			}
			continue;
		}

		if ( $sale_price !== '' && $regular_price !== '' && (float) $sale_price > (float) $regular_price ) {
			if ( $collect_errors ) {
				$errors[] = sprintf( 'La oferta supera el precio regular en la variante "%s".', trim( $color_name . ' ' . $size_name ) );
			}
			continue;
		}

		$key = bsc_product_variant_matrix_key( $color_name, $size_name );
		if ( $key === '' || isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$row          = array(
			'key'           => $key,
			'color_name'    => $color_name,
			'color_hex'     => $color_name !== '' ? $color_hex : '',
			'size_name'     => $size_name,
			'regular_price' => $regular_price,
			'sale_price'    => $sale_price,
			'stock_bodega'  => max( 0, intval( $raw_variant['stock_bodega'] ?? 0 ) ),
			'stock_tienda'  => max( 0, intval( $raw_variant['stock_tienda'] ?? 0 ) ),
			'enabled'       => bsc_product_variant_bool( $raw_variant['enabled'] ?? true, true ),
		);
		$row['price'] = bsc_product_variant_effective_price( $row );

		$matrix[] = $row;

		if ( count( $matrix ) >= 500 ) {
			break;
		}
	}

	return $matrix;
}

function bsc_get_product_saved_variant_matrix( int $product_id, bool $include_disabled = false ): array {
	if ( $product_id <= 0 ) {
		return array();
	}

	$raw_matrix = get_post_meta( $product_id, '_bsc_variant_matrix', true );
	if ( is_string( $raw_matrix ) && $raw_matrix !== '' ) {
		$decoded = json_decode( $raw_matrix, true );
		if ( is_array( $decoded ) ) {
			$raw_matrix = $decoded;
		}
	}

	$matrix = bsc_sanitize_product_variant_matrix( is_array( $raw_matrix ) ? $raw_matrix : array() );
	if ( $include_disabled ) {
		return $matrix;
	}

	return array_values(
		array_filter(
			$matrix,
			static function ( array $variant ): bool {
				return ! empty( $variant['enabled'] );
			}
		)
	);
}

function bsc_product_has_saved_variant_matrix( int $product_id ): bool {
	return ! empty( bsc_get_product_saved_variant_matrix( $product_id, true ) );
}

function bsc_build_legacy_product_variant_matrix( int $product_id ): array {
	$colors = bsc_get_legacy_product_color_variants( $product_id );
	$sizes  = bsc_get_legacy_product_size_variants( $product_id );

	if ( empty( $colors ) && empty( $sizes ) ) {
		return array();
	}

	$product       = wc_get_product( $product_id );
	$base_regular  = $product instanceof WC_Product ? (string) $product->get_regular_price() : '';
	$base_sale     = $product instanceof WC_Product ? (string) $product->get_sale_price() : '';
	$stock         = class_exists( 'BSC_Stock' )
		? BSC_Stock::get_stock( $product_id )
		: array(
			'bodega' => (int) get_post_meta( $product_id, '_stock_bodega', true ),
			'tienda' => (int) get_post_meta( $product_id, '_stock_tienda', true ),
		);
	$color_options = ! empty( $colors ) ? $colors : array( null );
	$size_options  = ! empty( $sizes ) ? $sizes : array( null );
	$total_rows    = max( 1, count( $color_options ) * count( $size_options ) );
	$bodega_total  = max( 0, (int) ( $stock['bodega'] ?? 0 ) );
	$tienda_total  = max( 0, (int) ( $stock['tienda'] ?? 0 ) );
	$matrix        = array();
	$row_index     = 0;

	foreach ( $color_options as $color ) {
		foreach ( $size_options as $size ) {
			$variant_price = '';
			if ( is_array( $color ) && (string) ( $color['price'] ?? '' ) !== '' ) {
				$variant_price = (string) $color['price'];
			}
			if ( is_array( $size ) && (string) ( $size['price'] ?? '' ) !== '' ) {
				$variant_price = (string) $size['price'];
			}

			$stock_bodega = intdiv( $bodega_total, $total_rows ) + ( $row_index < ( $bodega_total % $total_rows ) ? 1 : 0 );
			$stock_tienda = intdiv( $tienda_total, $total_rows ) + ( $row_index < ( $tienda_total % $total_rows ) ? 1 : 0 );
			$row = array(
				'color_name'    => is_array( $color ) ? (string) $color['name'] : '',
				'color_hex'     => is_array( $color ) ? (string) $color['hex'] : '',
				'size_name'     => is_array( $size ) ? (string) $size['name'] : '',
				'regular_price' => $variant_price !== '' ? $variant_price : $base_regular,
				'sale_price'    => $variant_price !== '' ? '' : $base_sale,
				'stock_bodega'  => $stock_bodega,
				'stock_tienda'  => $stock_tienda,
				'enabled'       => true,
			);
			$row['key']   = bsc_product_variant_matrix_key( $row['color_name'], $row['size_name'] );
			$row['price'] = bsc_product_variant_effective_price( $row );

			if ( $row['key'] !== '' ) {
				$matrix[] = $row;
			}
			$row_index++;
		}
	}

	return $matrix;
}

function bsc_get_product_variant_matrix( int $product_id, bool $include_disabled = false ): array {
	$saved_matrix = bsc_get_product_saved_variant_matrix( $product_id, $include_disabled );
	if ( ! empty( $saved_matrix ) ) {
		return $saved_matrix;
	}

	$legacy_matrix = bsc_build_legacy_product_variant_matrix( $product_id );
	if ( $include_disabled ) {
		return $legacy_matrix;
	}

	return array_values(
		array_filter(
			$legacy_matrix,
			static function ( array $variant ): bool {
				return ! empty( $variant['enabled'] );
			}
		)
	);
}

function bsc_get_product_variant_matrix_public_data( int $product_id ): array {
	$matrix = array_filter(
		bsc_get_product_variant_matrix( $product_id, false ),
		'bsc_product_variant_is_publicly_available'
	);

	return array_values(
		array_map(
			static function ( array $variant ): array {
				$stock_bodega = max( 0, (int) ( $variant['stock_bodega'] ?? 0 ) );
				$stock_tienda = max( 0, (int) ( $variant['stock_tienda'] ?? 0 ) );

				return array(
					'key'           => (string) ( $variant['key'] ?? '' ),
					'color_name'    => (string) ( $variant['color_name'] ?? '' ),
					'color_hex'     => (string) ( $variant['color_hex'] ?? '' ),
					'size_name'     => (string) ( $variant['size_name'] ?? '' ),
					'regular_price' => (string) ( $variant['regular_price'] ?? '' ),
					'sale_price'    => (string) ( $variant['sale_price'] ?? '' ),
					'price'         => bsc_product_variant_effective_price( $variant ),
					'stock_bodega'  => $stock_bodega,
					'stock_tienda'  => $stock_tienda,
					'stock_total'   => bsc_product_variant_stock_total( $variant ),
					'enabled'       => ! empty( $variant['enabled'] ),
				);
			},
			$matrix
		)
	);
}

function bsc_sync_product_variant_parent_stock( int $product_id, ?array $matrix = null ): void {
	if ( $product_id <= 0 ) {
		return;
	}

	$matrix = $matrix ?? bsc_get_product_saved_variant_matrix( $product_id, false );
	if ( empty( $matrix ) ) {
		return;
	}

	$bodega = 0;
	$tienda = 0;
	foreach ( $matrix as $variant ) {
		if ( empty( $variant['enabled'] ) ) {
			continue;
		}
		$bodega += max( 0, (int) ( $variant['stock_bodega'] ?? 0 ) );
		$tienda += max( 0, (int) ( $variant['stock_tienda'] ?? 0 ) );
	}

	update_post_meta( $product_id, '_stock_bodega', $bodega );
	update_post_meta( $product_id, '_stock_tienda', $tienda );
	update_post_meta( $product_id, '_stock_status', ( $bodega + $tienda ) > 0 ? 'instock' : 'outofstock' );
	wc_delete_product_transients( $product_id );
}

function bsc_get_product_color_variants( int $product_id ): array {
	$matrix = array_filter(
		bsc_get_product_variant_matrix( $product_id, false ),
		'bsc_product_variant_is_publicly_available'
	);
	if ( empty( $matrix ) ) {
		return array();
	}

	$has_sizes = false;
	foreach ( $matrix as $variant ) {
		if ( (string) ( $variant['size_name'] ?? '' ) !== '' ) {
			$has_sizes = true;
			break;
		}
	}

	$variants = array();
	$seen     = array();
	foreach ( $matrix as $variant ) {
		$name = (string) ( $variant['color_name'] ?? '' );
		$hex  = (string) ( $variant['color_hex'] ?? '' );
		if ( $name === '' || $hex === '' ) {
			continue;
		}

		$key = strtolower( $name . '|' . $hex );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$variants[]   = array(
			'name'  => $name,
			'hex'   => $hex,
			'price' => $has_sizes ? '' : bsc_product_variant_effective_price( $variant ),
		);
	}

	return $variants;
}

function bsc_get_product_size_variants( int $product_id ): array {
	$matrix = array_filter(
		bsc_get_product_variant_matrix( $product_id, false ),
		'bsc_product_variant_is_publicly_available'
	);
	if ( empty( $matrix ) ) {
		return array();
	}

	$has_colors = false;
	foreach ( $matrix as $variant ) {
		if ( (string) ( $variant['color_name'] ?? '' ) !== '' ) {
			$has_colors = true;
			break;
		}
	}

	$variants = array();
	$seen     = array();
	foreach ( $matrix as $variant ) {
		$name = (string) ( $variant['size_name'] ?? '' );
		if ( $name === '' || isset( $seen[ strtolower( $name ) ] ) ) {
			continue;
		}

		$seen[ strtolower( $name ) ] = true;
		$variants[]                  = array(
			'name'  => $name,
			'price' => $has_colors ? '' : bsc_product_variant_effective_price( $variant ),
		);
	}

	return $variants;
}

function bsc_product_has_public_variant_options( int $product_id ): bool {
	return ! empty( bsc_get_product_variant_matrix_public_data( $product_id ) );
}

function bsc_find_product_variant_matrix_row(
	int $product_id,
	string $variant_key = '',
	string $color_name = '',
	string $color_hex = '',
	string $size_name = '',
	bool $include_disabled = false
): ?array {
	$matrix     = bsc_get_product_variant_matrix( $product_id, true );
	$color_name = sanitize_text_field( $color_name );
	$color_hex  = bsc_product_variants_normalize_hex( $color_hex );
	$size_name  = sanitize_text_field( $size_name );

	foreach ( $matrix as $variant ) {
		if ( ! $include_disabled && empty( $variant['enabled'] ) ) {
			continue;
		}

		if ( $variant_key !== '' && hash_equals( (string) ( $variant['key'] ?? '' ), $variant_key ) ) {
			return $variant;
		}
	}

	foreach ( $matrix as $variant ) {
		if ( ! $include_disabled && empty( $variant['enabled'] ) ) {
			continue;
		}

		$row_color_name = (string) ( $variant['color_name'] ?? '' );
		$row_color_hex  = (string) ( $variant['color_hex'] ?? '' );
		$row_size_name  = (string) ( $variant['size_name'] ?? '' );
		$color_matches  = $color_name === ''
			? $row_color_name === ''
			: strcasecmp( $row_color_name, $color_name ) === 0 && ( $color_hex === '' || strcasecmp( $row_color_hex, $color_hex ) === 0 );
		$size_matches   = $size_name === ''
			? $row_size_name === ''
			: strcasecmp( $row_size_name, $size_name ) === 0;

		if ( $color_matches && $size_matches ) {
			return $variant;
		}
	}

	return null;
}

function bsc_get_selected_product_variant_options( int $product_id, array $data ): array {
	$variant_key = isset( $data['bsc_product_variant_key'] ) ? sanitize_key( wp_unslash( $data['bsc_product_variant_key'] ) ) : '';
	$color_name  = isset( $data['bsc_color_variant_name'] ) ? sanitize_text_field( wp_unslash( $data['bsc_color_variant_name'] ) ) : '';
	$color_hex   = isset( $data['bsc_color_variant_hex'] ) ? bsc_product_variants_normalize_hex( wp_unslash( $data['bsc_color_variant_hex'] ) ) : '';
	$size_name   = isset( $data['bsc_size_variant_name'] ) ? sanitize_text_field( wp_unslash( $data['bsc_size_variant_name'] ) ) : '';
	$matrix      = bsc_get_product_variant_matrix( $product_id, true );

	$selection = array(
		'color'       => null,
		'size'        => null,
		'price'       => '',
		'variant'     => null,
		'variant_key' => '',
		'stock_total' => 0,
		'invalid'     => false,
	);

	if ( empty( $matrix ) ) {
		return $selection;
	}

	$variant = bsc_find_product_variant_matrix_row( $product_id, $variant_key, $color_name, $color_hex, $size_name, true );
	if ( ! is_array( $variant ) || empty( $variant['enabled'] ) ) {
		$selection['invalid'] = true;
		return $selection;
	}

	if ( (string) ( $variant['color_name'] ?? '' ) !== '' ) {
		$selection['color'] = array(
			'name' => (string) $variant['color_name'],
			'hex'  => (string) ( $variant['color_hex'] ?? '' ),
		);
	}

	if ( (string) ( $variant['size_name'] ?? '' ) !== '' ) {
		$selection['size'] = array(
			'name' => (string) $variant['size_name'],
		);
	}

	$selection['price']       = bsc_product_variant_effective_price( $variant );
	$selection['variant']     = $variant;
	$selection['variant_key'] = (string) ( $variant['key'] ?? '' );
	$selection['stock_total'] = max( 0, (int) ( $variant['stock_bodega'] ?? 0 ) ) + max( 0, (int) ( $variant['stock_tienda'] ?? 0 ) );

	return $selection;
}

function bsc_build_product_variant_cart_item_data( int $product_id, array $data ) {
	$selection = bsc_get_selected_product_variant_options( $product_id, $data );

	if ( ! empty( $selection['invalid'] ) ) {
		return new WP_Error( 'bsc_invalid_product_variant', 'Selecciona una variante disponible.' );
	}

	if ( ! is_array( $selection['color'] ) && ! is_array( $selection['size'] ) && empty( $selection['variant_key'] ) ) {
		return array();
	}

	$quantity  = empty( $data['quantity'] ) ? 1 : max( 1, (int) wc_stock_amount( sanitize_text_field( wp_unslash( $data['quantity'] ) ) ) );
	$requested = $quantity;
	if ( $selection['variant_key'] !== '' && function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$cart_product_id = (int) ( $cart_item['product_id'] ?? 0 );
			$cart_variant_key = sanitize_key( (string) ( $cart_item['bsc_product_variant_key'] ?? '' ) );
			if ( $cart_product_id === $product_id && hash_equals( $selection['variant_key'], $cart_variant_key ) ) {
				$requested += (int) ( $cart_item['quantity'] ?? 0 );
			}
		}
	}

	if ( $selection['stock_total'] < $requested ) {
		return new WP_Error( 'bsc_product_variant_out_of_stock', 'No hay stock suficiente para esa variante.' );
	}

	$cart_item_data = array(
		'bsc_product_options'     => $selection,
		'bsc_product_options_key' => md5(
			implode(
				'|',
				array(
					$selection['variant_key'],
					is_array( $selection['color'] ) ? (string) ( $selection['color']['name'] ?? '' ) : '',
					is_array( $selection['size'] ) ? (string) ( $selection['size']['name'] ?? '' ) : '',
				)
			)
		),
	);

	if ( $selection['variant_key'] !== '' ) {
		$cart_item_data['bsc_product_variant_key'] = $selection['variant_key'];
	}

	if ( $selection['price'] !== '' ) {
		$cart_item_data['bsc_product_variant_price'] = $selection['price'];
	}

	return $cart_item_data;
}

function bsc_apply_product_variant_price_to_cart( $cart ): void {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	if ( ! $cart instanceof WC_Cart ) {
		return;
	}

	foreach ( $cart->get_cart() as $cart_item ) {
		$price = $cart_item['bsc_product_variant_price'] ?? ( $cart_item['bsc_product_options']['price'] ?? '' );
		if ( $price === '' || ! isset( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) {
			continue;
		}

		$cart_item['data']->set_price( (float) $price );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'bsc_apply_product_variant_price_to_cart', 20 );

function bsc_add_product_variant_item_data( array $item_data, array $cart_item ): array {
	$options = $cart_item['bsc_product_options'] ?? array();

	if ( ! empty( $options['color']['name'] ) ) {
		$item_data[] = array(
			'key'   => 'Color',
			'value' => wc_clean( $options['color']['name'] ),
		);
	}

	if ( ! empty( $options['size']['name'] ) ) {
		$item_data[] = array(
			'key'   => 'Tamaño',
			'value' => wc_clean( $options['size']['name'] ),
		);
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'bsc_add_product_variant_item_data', 20, 2 );

/**
 * Get BSC variant metadata persisted on an order line item.
 *
 * @param WC_Order_Item_Product $item Order line item.
 * @return array{color_name:string,size_name:string,variant_key:string,parts:array<int,string>}
 */
function bsc_get_order_item_product_variant_data( WC_Order_Item_Product $item ): array {
	$color_name  = trim( (string) $item->get_meta( 'Color', true ) );
	$size_name   = trim( (string) $item->get_meta( 'Tamaño', true ) );
	$variant_key = sanitize_key( (string) $item->get_meta( '_bsc_product_variant_key', true ) );

	if ( '' === $size_name ) {
		$size_name = trim( (string) $item->get_meta( 'Tamaño', true ) );
	}

	$parts = array_values(
		array_filter(
			array(
				$color_name,
				$size_name,
			),
			static fn( $value ): bool => '' !== $value
		)
	);

	return array(
		'color_name'  => $color_name,
		'size_name'   => $size_name,
		'variant_key' => $variant_key,
		'parts'       => $parts,
	);
}

/**
 * Format the selected BSC variant as a short label.
 *
 * @param WC_Order_Item_Product $item Order line item.
 * @return string
 */
function bsc_format_order_item_variant_label( WC_Order_Item_Product $item ): string {
	$variant_data = bsc_get_order_item_product_variant_data( $item );

	return implode( ' / ', $variant_data['parts'] );
}

/**
 * Format an order item product name with its selected BSC variant.
 *
 * @param WC_Order_Item_Product $item Order line item.
 * @return string
 */
function bsc_format_order_item_name_with_variant( WC_Order_Item_Product $item ): string {
	$name          = $item->get_name();
	$variant_label = bsc_format_order_item_variant_label( $item );

	return '' !== $variant_label ? $name . ' - ' . $variant_label : $name;
}

function bsc_add_product_variant_meta_to_order_item( $item, $cart_item_key, $values, $order ): void {
	unset( $cart_item_key, $order );

	if ( ! $item instanceof WC_Order_Item_Product ) {
		return;
	}

	$options = $values['bsc_product_options'] ?? array();

	if ( ! empty( $options['color']['name'] ) ) {
		$item->add_meta_data( 'Color', wc_clean( $options['color']['name'] ), true );
	}

	if ( ! empty( $options['size']['name'] ) ) {
		$item->add_meta_data( 'Tamaño', wc_clean( $options['size']['name'] ), true );
	}

	if ( ! empty( $values['bsc_product_variant_key'] ) ) {
		$item->add_meta_data( '_bsc_product_variant_key', sanitize_key( $values['bsc_product_variant_key'] ), true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'bsc_add_product_variant_meta_to_order_item', 20, 4 );

/**
 * Prevent front-end cart links generated by WooCommerce from pointing to /cart/.
 *
 * @param string $url Original WooCommerce cart URL.
 * @return string Checkout URL.
 */
function bsc_2_0_force_cart_url_to_checkout( $url ): string {
	unset( $url );

	return bsc_2_0_get_checkout_url();
}
add_filter( 'woocommerce_get_cart_url', 'bsc_2_0_force_cart_url_to_checkout', 20 );

function bsc_2_0_redirect_cart_page_to_checkout(): void {
	if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}

	wp_safe_redirect( bsc_2_0_get_checkout_url(), 302 );
	exit;
}
add_action( 'template_redirect', 'bsc_2_0_redirect_cart_page_to_checkout', 1 );

function bsc_get_public_product_visibility_tax_query( string $context = 'catalog' ): array {
	if ( ! function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		return array();
	}

	$excluded_visibility_keys = 'search' === $context
		? array( 'exclude-from-search' )
		: array( 'exclude-from-catalog' );
	$visibility_terms = wc_get_product_visibility_term_ids();
	$excluded_terms   = array();

	foreach ( $excluded_visibility_keys as $visibility_key ) {
		$excluded_terms[] = absint( $visibility_terms[ $visibility_key ] ?? 0 );
	}

	$excluded_terms = array_filter( $excluded_terms );

	if ( empty( $excluded_terms ) ) {
		return array();
	}

	return array(
		'taxonomy' => 'product_visibility',
		'field'    => 'term_taxonomy_id',
		'terms'    => array_values( array_unique( $excluded_terms ) ),
		'operator' => 'NOT IN',
	);
}

function bsc_get_public_product_archive_meta_query(): array {
	return array(
		'relation' => 'OR',
		array(
			'key'     => '_bsc_product_archived',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_bsc_product_archived',
			'value'   => '1',
			'compare' => '!=',
		),
	);
}

function bsc_apply_public_product_query_constraints( array $args, string $context = 'catalog' ): array {
	$meta_query   = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
	$meta_query[] = bsc_get_public_product_archive_meta_query();
	$args['meta_query'] = $meta_query;

	$visibility_tax_query = bsc_get_public_product_visibility_tax_query( $context );
	if ( ! empty( $visibility_tax_query ) ) {
		$tax_query   = isset( $args['tax_query'] ) && is_array( $args['tax_query'] ) ? $args['tax_query'] : array();
		$tax_query[] = $visibility_tax_query;
		$args['tax_query'] = $tax_query;
	}

	return $args;
}

function bsc_product_is_publicly_listable( ?WC_Product $product, string $context = 'catalog' ): bool {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	$allowed_visibility = 'search' === $context
		? array( 'visible', 'search' )
		: array( 'visible', 'catalog' );

	return 'publish' === $product->get_status()
		&& in_array( $product->get_catalog_visibility(), $allowed_visibility, true )
		&& '1' !== (string) get_post_meta( $product->get_id(), '_bsc_product_archived', true );
}

function bsc_get_product_brand_category_term( int $product_id ): ?WP_Term {
	$terms = get_the_terms( $product_id, 'product_cat' );

	if ( ! is_array( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term && false !== strpos( $term->slug, '-marca' ) ) {
			return $term;
		}
	}

	return null;
}

function bsc_sync_product_brand_attribute_from_category( int $product_id ): void {
	static $syncing = array();

	if ( $product_id <= 0 || isset( $syncing[ $product_id ] ) || 'product' !== get_post_type( $product_id ) ) {
		return;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$syncing[ $product_id ] = true;

	try {
		$brand_term = bsc_get_product_brand_category_term( $product_id );
		$brand_name = $brand_term instanceof WP_Term ? trim( wp_strip_all_tags( $brand_term->name ) ) : '';
		$attributes = $product->get_attributes();

		if ( '' === $brand_name ) {
			if ( isset( $attributes['brand'] ) ) {
				unset( $attributes['brand'] );
				$product->set_attributes( $attributes );
				$product->save();
			}

			return;
		}

		$current_brand = trim( wp_strip_all_tags( (string) $product->get_attribute( 'brand' ) ) );
		if ( isset( $attributes['brand'] ) && $current_brand === $brand_name ) {
			return;
		}

		$attribute = $attributes['brand'] ?? new WC_Product_Attribute();
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			$attribute = new WC_Product_Attribute();
		}

		$attribute->set_id( 0 );
		$attribute->set_name( 'brand' );
		$attribute->set_options( array( $brand_name ) );
		$attribute->set_visible( $attribute->get_visible() );
		$attribute->set_variation( false );

		$attributes['brand'] = $attribute;
		$product->set_attributes( $attributes );
		$product->save();
	} finally {
		unset( $syncing[ $product_id ] );
	}
}

function bsc_sync_product_brand_attribute_after_category_terms_set( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ): void {
	unset( $terms, $tt_ids, $append, $old_tt_ids );

	if ( 'product_cat' !== $taxonomy ) {
		return;
	}

	bsc_sync_product_brand_attribute_from_category( (int) $object_id );
}
add_action( 'set_object_terms', 'bsc_sync_product_brand_attribute_after_category_terms_set', 20, 6 );

/**
 * Related Products Args.
 *
 * @param array $args related products args.
 * @return array $args related products args.
 */
function bsc_2_0_woocommerce_related_products_args( $args ) {
	$defaults = array(
		'posts_per_page' => 3,
		'columns'        => 3,
	);

	$args = wp_parse_args( $defaults, $args );

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'bsc_2_0_woocommerce_related_products_args' );

/**
 * Remove default WooCommerce wrapper.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

if ( ! function_exists( 'bsc_2_0_woocommerce_wrapper_before' ) ) {
	/**
	 * Before Content.
	 *
	 * Wraps all WooCommerce content in wrappers which match the theme markup.
	 *
	 * @return void
	 */
	function bsc_2_0_woocommerce_wrapper_before() {
		?>
			<main id="primary" class="site-main">
		<?php
	}
}
add_action( 'woocommerce_before_main_content', 'bsc_2_0_woocommerce_wrapper_before' );

if ( ! function_exists( 'bsc_2_0_woocommerce_wrapper_after' ) ) {
	/**
	 * After Content.
	 *
	 * Closes the wrapping divs.
	 *
	 * @return void
	 */
	function bsc_2_0_woocommerce_wrapper_after() {
		?>
			</main><!-- #main -->
		<?php
	}
}
add_action( 'woocommerce_after_main_content', 'bsc_2_0_woocommerce_wrapper_after' );

/**
 * Sample implementation of the WooCommerce Mini Cart.
 *
 * You can add the WooCommerce Mini Cart to header.php like so ...
 *
	<?php
		if ( function_exists( 'bsc_2_0_woocommerce_header_cart' ) ) {
			bsc_2_0_woocommerce_header_cart();
		}
	?>
 */

if ( ! function_exists( 'bsc_2_0_woocommerce_cart_link_fragment' ) ) {
	/**
	 * Cart Fragments.
	 *
	 * Ensure cart contents update when products are added to the cart via AJAX.
	 *
	 * @param array $fragments Fragments to refresh via AJAX.
	 * @return array Fragments to refresh via AJAX.
	 */
	function bsc_2_0_woocommerce_cart_link_fragment( $fragments ) {
		ob_start();
		bsc_2_0_woocommerce_cart_link();
		$fragments['a.cart-contents'] = ob_get_clean();

		return $fragments;
	}
}
add_filter( 'woocommerce_add_to_cart_fragments', 'bsc_2_0_woocommerce_cart_link_fragment' );

if ( ! function_exists( 'bsc_2_0_woocommerce_cart_link' ) ) {
	/**
	 * Cart Link.
	 *
	 * Displayed a link to the cart including the number of items present and the cart total.
	 *
	 * @return void
	 */
	function bsc_2_0_woocommerce_cart_link() {
		?>
		<a class="cart-contents" href="<?php echo esc_url( wc_get_checkout_url() ); ?>" title="<?php esc_attr_e( 'Ir al checkout', 'bsc-2-0' ); ?>">
			<?php
			$item_count_text = sprintf(
				/* translators: number of items in the mini cart. */
				_n( '%d producto', '%d productos', WC()->cart->get_cart_contents_count(), 'bsc-2-0' ),
				WC()->cart->get_cart_contents_count()
			);
			?>
			<span class="amount"><?php echo wp_kses_data( WC()->cart->get_cart_subtotal() ); ?></span> <span class="count"><?php echo esc_html( $item_count_text ); ?></span>
		</a>
		<?php
	}
}

if ( ! function_exists( 'bsc_2_0_woocommerce_header_cart' ) ) {
	/**
	 * Display Header Cart.
	 *
	 * @return void
	 */
	function bsc_2_0_woocommerce_header_cart() {
		if ( is_cart() ) {
			$class = 'current-menu-item';
		} else {
			$class = '';
		}
		?>
		<ul id="site-header-cart" class="site-header-cart">
			<li class="<?php echo esc_attr( $class ); ?>">
				<?php bsc_2_0_woocommerce_cart_link(); ?>
			</li>
			<li>
				<?php
				$instance = array(
					'title' => '',
				);

				the_widget( 'WC_Widget_Cart', $instance );
				?>
			</li>
		</ul>
		<?php
	}
}



	add_action(
		'after_setup_theme',
		function () {
			add_theme_support( 'wc-product-gallery-lightbox' );
			add_theme_support( 'wc-product-gallery-slider' );
		}
	);

	/**
	 * BSC-089: Product gallery image sizes tuned for PDP quality/performance balance.
	 *
	 * @param array $size Image size config.
	 * @return array
	 */
	function bsc_woocommerce_single_image_size( $size ) {
		return array(
			'width'  => 900,
			'height' => 900,
			'crop'   => 0,
		);
	}
	add_filter( 'woocommerce_get_image_size_single', 'bsc_woocommerce_single_image_size' );

	/**
	 * BSC-089: Keep only the real PDP gallery main image eager for faster LCP.
	 *
	 * @param array        $attr          Image attributes.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size          Requested image size.
	 * @param bool         $main_image    Whether this is the main gallery image.
	 * @return array
	 */
	function bsc_product_gallery_image_loading_attrs( $attr, $attachment_id, $size, $main_image ) {
		if ( ! is_product() ) {
			return $attr;
		}

		if ( $main_image ) {
			$attr['loading']       = 'eager';
			$attr['fetchpriority'] = 'high';
			$attr['decoding']      = 'sync';
		} else {
			$attr['loading']  = 'lazy';
			$attr['decoding'] = 'async';
			unset( $attr['fetchpriority'] );
		}

		return $attr;
	}
	add_filter( 'woocommerce_gallery_image_html_attachment_image_params', 'bsc_product_gallery_image_loading_attrs', 10, 4 );


	add_filter( 'woocommerce_checkout_fields', 'bsc_add_billing_cedula_field' );
	function bsc_add_billing_cedula_field( $fields ) {
		$fields['billing']['billing_cedula'] = array(
			'type'        => 'text',
			'label'       => 'Cédula',
			'required'    => true,
			'class'       => array( 'form-row-wide' ),
			'priority'    => 21,
			'placeholder' => 'Cédula',
		);

		return $fields;
	}


	add_action(
		'after_setup_theme',
		function () {
			load_textdomain( 'woocommerce', WP_LANG_DIR . '/woocommerce/woocommerce-es_ES.mo' );
		}
	);

	if ( ! function_exists( 'bsc_translate_password_reset_woocommerce_text' ) ) {
		function bsc_translate_password_reset_woocommerce_text( string $translation, string $text, string $domain ): string {
			if ( 'woocommerce' !== $domain ) {
				return $translation;
			}

			$password_reset_text = array(
				'Enter a username or email address.' => 'Ingresa tu usuario o correo electr&oacute;nico.',
				'Invalid username or email.' => 'No encontramos una cuenta con ese usuario o correo electr&oacute;nico.',
				'Password reset is not allowed for this user' => 'No es posible restablecer la contrase&ntilde;a de este usuario.',
				'Please enter your password.' => 'Ingresa tu nueva contrase&ntilde;a.',
				'Passwords do not match.' => 'Las contrase&ntilde;as no coinciden.',
				'Your password has been reset successfully.' => 'Tu contrase&ntilde;a se actualiz&oacute; correctamente.',
				'This key is invalid or has already been used. Please reset your password again if needed.' => 'Este enlace no es v&aacute;lido o ya fue usado. Solicita uno nuevo si necesitas restablecer tu contrase&ntilde;a.',
				'This password reset key is for a different user account. Please log out and try again.' => 'Este enlace pertenece a otra cuenta. Cierra sesi&oacute;n e intenta de nuevo.',
			);

			return $password_reset_text[ $text ] ?? $translation;
		}
	}
	add_filter( 'gettext', 'bsc_translate_password_reset_woocommerce_text', 20, 3 );

	if ( ! function_exists( 'bsc_translate_address_woocommerce_text' ) ) {
		function bsc_translate_address_woocommerce_text( string $translation, string $text, string $domain ): string {
			if ( 'woocommerce' === $domain && 'Address changed successfully.' === $text ) {
				return '¡Dirección actualizada correctamente!';
			}

			return $translation;
		}
	}
	add_filter( 'gettext', 'bsc_translate_address_woocommerce_text', 20, 3 );





	add_filter( 'woocommerce_checkout_fields', 'bsc_translate_placeholders' );
	function bsc_translate_placeholders( $fields ) {
		$fields['billing']['billing_state']['placeholder']   = 'Selecciona un departamento…';
		$fields['billing']['billing_city']['placeholder']    = 'Selecciona una ciudad…';
		$fields['billing']['billing_country']['placeholder'] = 'Selecciona un país…';
		return $fields;
	}

	add_filter( 'woocommerce_billing_fields', 'bsc_billing_field_placeholders' );
	function bsc_billing_field_placeholders( $fields ) {
		$map = array(
			'billing_first_name' => 'Tu nombre',
			'billing_last_name'  => 'Tu apellido',
			'billing_company'    => 'Empresa (opcional)',
			'billing_address_1'  => 'Dirección (calle, barrio, número…)',
			'billing_address_2'  => 'Complemento (apto, piso, torre…)',
			'billing_postcode'   => 'Código postal',
			'billing_phone'      => 'Teléfono de contacto',
			'billing_email'      => 'Correo electrónico',
			'billing_state'      => 'Selecciona un departamento…',
			'billing_city'       => 'Selecciona una ciudad…',
			'billing_country'    => 'Selecciona un país…',
		);
		foreach ( $map as $key => $placeholder ) {
			if ( isset( $fields[ $key ] ) ) {
				$fields[ $key ]['placeholder'] = $placeholder;
			}
		}
		return $fields;
	}

	add_filter( 'woocommerce_shipping_fields', 'bsc_shipping_field_placeholders' );
	function bsc_shipping_field_placeholders( $fields ) {
		$map = array(
			'shipping_first_name' => 'Tu nombre',
			'shipping_last_name'  => 'Tu apellido',
			'shipping_company'    => 'Empresa (opcional)',
			'shipping_address_1'  => 'Dirección (calle, barrio, número…)',
			'shipping_address_2'  => 'Complemento (apto, piso, torre…)',
			'shipping_postcode'   => 'Código postal',
			'shipping_state'      => 'Selecciona un departamento…',
			'shipping_city'       => 'Selecciona una ciudad…',
			'shipping_country'    => 'Selecciona un país…',
		);
		foreach ( $map as $key => $placeholder ) {
			if ( isset( $fields[ $key ] ) ) {
				$fields[ $key ]['placeholder'] = $placeholder;
			}
		}
		return $fields;
	}

	add_filter( 'woocommerce_order_button_text', 'bsc_custom_order_button_text' );
	function bsc_custom_order_button_text( $button_text ) {
		return '¡ Hacer Compra !';
	}


	function bsc_calculate_order_bubble_points( WC_Order $order ): int {
		return max( 0, (int) floor( (float) $order->get_total() / 1000 ) );
	}

	function bsc_get_order_bubble_points_earned( WC_Order $order ): int {
		return bsc_calculate_order_bubble_points( $order );
	}

	function bsc_get_order_bubble_points_balance( WC_Order $order ): int {
		return bsc_calculate_order_bubble_points( $order );
	}

	function bsc_cart_has_free_shipping_coupon(): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_coupons() as $coupon ) {
			if ( is_object( $coupon ) && method_exists( $coupon, 'get_free_shipping' ) && $coupon->get_free_shipping() ) {
				return true;
			}
		}

		return false;
	}

	function bsc_cart_qualifies_for_free_shipping(): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		$subtotal                = (float) WC()->cart->get_subtotal();
		$discount                = (float) WC()->cart->get_discount_total();
		$subtotal_after_discount = max( 0, $subtotal - $discount );
		$min_amount              = (float) get_option( 'bsc_free_shipping_threshold', 300000 ); // BSC-064: configurable

		return $subtotal_after_discount >= $min_amount || bsc_cart_has_free_shipping_coupon();
	}

	add_filter( 'woocommerce_package_rates', 'bsc_force_hide_free_shipping_if_under_discount_threshold', 10, 2 );

	function bsc_force_hide_free_shipping_if_under_discount_threshold( $rates, $package ) {
		if ( bsc_cart_has_free_shipping_coupon() ) {
			return $rates;
		}

		$subtotal                = WC()->cart->get_subtotal();
		$discount                = WC()->cart->get_discount_total();
		$subtotal_after_discount = $subtotal - $discount;
		$min_amount              = (int) get_option( 'bsc_free_shipping_threshold', 300000 ); // BSC-064: configurable
		// Si no alcanza el mínimo, eliminamos el envío gratuito
		foreach ($rates as $rate_id => $rate) {
			if ($rate->method_id === 'free_shipping' && $subtotal_after_discount < $min_amount) {
				unset( $rates[ $rate_id ] );
			}
		}

		return $rates;
	}

	// BSC-058: removed woocommerce_before_calculate_totals/calculate_shipping() — caused infinite loops

	// BSC-058: force correct flat rate based on billing state + city
	add_filter( 'woocommerce_cart_shipping_packages', 'bsc_force_checkout_shipping_package_destination', 20 );
	add_filter( 'woocommerce_package_rates', 'bsc_force_shipping_by_location', 20, 2 );
	function bsc_force_shipping_by_location( array $rates, array $package ): array {
		$destination = bsc_get_checkout_shipping_destination( $package );
		$state       = $destination['state'];
		$city        = $destination['city'];

		return bsc_apply_location_shipping_rates( $rates, $state, $city );
	}

	add_action( 'woocommerce_checkout_update_order_review', 'bsc_update_customer_destination_from_checkout_post', 5 );
	function bsc_update_customer_destination_from_checkout_post( string $post_data = '' ): void {
		$posted = array();
		if ( $post_data !== '' ) {
			parse_str( $post_data, $posted );
		}

		bsc_sync_customer_shipping_destination( $posted ?: null );
	}

	function bsc_force_checkout_shipping_package_destination( array $packages ): array {
		$destination = bsc_get_checkout_shipping_destination();
		if ( ! bsc_checkout_destination_has_rate_context( $destination ) ) {
			return $packages;
		}

		foreach ( $packages as $package_index => $package ) {
			$package_destination = is_array( $package['destination'] ?? null )
			? $package['destination']
			: array();

			$packages[ $package_index ]['destination'] = array_merge(
				$package_destination,
				array(
					'country'  => $destination['country'] ?: 'CO',
					'state'    => $destination['state'],
					'city'     => $destination['city'],
					'postcode' => $destination['postcode'],
				)
			);
		}

		return $packages;
	}

	function bsc_get_checkout_shipping_destination( array $package = array() ): array {
		$posted_destination = bsc_normalize_checkout_destination( bsc_get_posted_checkout_destination() );
		if ( bsc_checkout_destination_has_rate_context( $posted_destination ) ) {
			return $posted_destination;
		}

		$package_destination = $package['destination'] ?? array();
		$package_destination = bsc_normalize_checkout_destination(
			array(
				'country'  => $package_destination['country'] ?? 'CO',
				'state'    => $package_destination['state'] ?? '',
				'city'     => $package_destination['city'] ?? '',
				'postcode' => $package_destination['postcode'] ?? '',
			)
		);

		if ( bsc_checkout_destination_has_rate_context( $package_destination ) ) {
			return $package_destination;
		}

		$customer_destination = bsc_get_customer_checkout_destination();
		if ( bsc_checkout_destination_has_rate_context( $customer_destination ) ) {
			return $customer_destination;
		}

		return array(
			'country'  => 'CO',
			'state'    => '',
			'city'     => '',
			'postcode' => '',
		);
	}

	function bsc_get_posted_checkout_destination( ?array $posted = null ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout/AJAX callers validate their own WooCommerce nonce before this helper reads posted data.
		$posted = $posted ?? wp_unslash( $_POST );

		if ( isset( $posted['post_data'] ) && is_string( $posted['post_data'] ) ) {
			$checkout_post_data = array();
			parse_str( wp_unslash( $posted['post_data'] ), $checkout_post_data );
			$posted = array_merge( $checkout_post_data, $posted );
		}

		$normalized_destination = bsc_normalize_checkout_destination(
			array(
				'country'  => $posted['s_country'] ?? $posted['country'] ?? 'CO',
				'state'    => $posted['s_state'] ?? $posted['state'] ?? '',
				'city'     => $posted['s_city'] ?? $posted['city'] ?? '',
				'postcode' => $posted['s_postcode'] ?? $posted['postcode'] ?? '',
			)
		);

		if ( bsc_checkout_destination_is_complete( $normalized_destination ) ) {
			return $normalized_destination;
		}

		$ship_to_different = ! empty( $posted['ship_to_different_address'] );
		$prefix            = $ship_to_different && ! empty( $posted['shipping_state'] )
		? 'shipping'
		: 'billing';

		return bsc_normalize_checkout_destination(
			array(
				'country'  => sanitize_text_field( (string) ( $posted[ "{$prefix}_country" ] ?? 'CO' ) ),
				'state'    => sanitize_text_field( (string) ( $posted[ "{$prefix}_state" ] ?? '' ) ),
				'city'     => sanitize_text_field( (string) ( $posted[ "{$prefix}_city" ] ?? '' ) ),
				'postcode' => sanitize_text_field( (string) ( $posted[ "{$prefix}_postcode" ] ?? '' ) ),
			)
		);
	}

	function bsc_get_customer_checkout_destination(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			return array(
				'country'  => 'CO',
				'state'    => '',
				'city'     => '',
				'postcode' => '',
			);
		}

		$customer_state = WC()->customer->get_shipping_state() ?: WC()->customer->get_billing_state();
		$customer_city  = WC()->customer->get_shipping_city() ?: WC()->customer->get_billing_city();

		return bsc_normalize_checkout_destination(
			array(
				'country'  => WC()->customer->get_shipping_country() ?: WC()->customer->get_billing_country() ?: 'CO',
				'state'    => $customer_state,
				'city'     => $customer_city,
				'postcode' => WC()->customer->get_shipping_postcode() ?: WC()->customer->get_billing_postcode(),
			)
		);
	}

	function bsc_checkout_destination_is_complete( array $destination ): bool {
		return trim( (string) ( $destination['state'] ?? '' ) ) !== ''
		&& trim( (string) ( $destination['city'] ?? '' ) ) !== '';
	}

	function bsc_checkout_destination_has_rate_context( array $destination ): bool {
		return trim( (string) ( $destination['state'] ?? '' ) ) !== '';
	}

	function bsc_normalize_checkout_destination( array $destination ): array {
		$destination = array(
			'country'  => 'CO',
			'state'    => sanitize_text_field( (string) ( $destination['state'] ?? '' ) ),
			'city'     => sanitize_text_field( (string) ( $destination['city'] ?? '' ) ),
			'postcode' => sanitize_text_field( (string) ( $destination['postcode'] ?? '' ) ),
		);

		$city_location = bsc_lookup_colombia_city_location( $destination['city'] );
		if ( ! empty( $city_location['state'] ) && ( $destination['state'] === '' || ! empty( $city_location['matched_by_code'] ) ) ) {
			$destination['state'] = $city_location['state'];
		}

		return $destination;
	}

	function bsc_sync_customer_shipping_destination( ?array $posted = null ): void {
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			return;
		}

		$destination = bsc_get_posted_checkout_destination( $posted );
		if ( ! bsc_checkout_destination_has_rate_context( $destination ) ) {
			return;
		}

		WC()->customer->set_billing_country( $destination['country'] ?: 'CO' );
		WC()->customer->set_billing_state( $destination['state'] );
		WC()->customer->set_billing_postcode( $destination['postcode'] );
		WC()->customer->set_shipping_country( $destination['country'] ?: 'CO' );
		WC()->customer->set_shipping_state( $destination['state'] );
		WC()->customer->set_shipping_postcode( $destination['postcode'] );

		if ( $destination['city'] !== '' ) {
			WC()->customer->set_billing_city( $destination['city'] );
			WC()->customer->set_shipping_city( $destination['city'] );
		}

		WC()->customer->save();

		bsc_clear_cached_shipping_packages();
	}

	function bsc_clear_cached_shipping_packages(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->session || ! WC()->cart ) {
			return;
		}

		foreach ( WC()->cart->get_shipping_packages() as $package_index => $package ) {
			WC()->session->__unset( 'shipping_for_package_' . $package_index );
		}
	}

	function bsc_apply_location_shipping_rates( array $rates, string $state, string $city ): array {
		if ( trim( $state ) === '' ) {
			return $rates;
		}

		$has_free_shipping = false;
		foreach ( $rates as $rate ) {
			if ( $rate->method_id === 'free_shipping' ) {
				$has_free_shipping = true;
				break;
			}
		}

		if ( $has_free_shipping ) {
			foreach ( $rates as $rate_id => $rate ) {
				if ( $rate->method_id === 'flat_rate' ) {
					unset( $rates[ $rate_id ] );
				}
			}

			return $rates;
		}

		$is_local                    = bsc_is_bogota_or_cundinamarca_destination( $state, $city );
		$qualifies_for_free_shipping = bsc_cart_qualifies_for_free_shipping();
		$bogota_cost                 = (float) bsc_get_bogota_shipping_price();
		$other_cost                  = (float) bsc_get_other_shipping_price();
		$target_cost                 = $qualifies_for_free_shipping ? 0 : ( $is_local ? $bogota_cost : $other_cost );
		$target_label                = $qualifies_for_free_shipping
		? 'Envio gratis'
		: ( $is_local ? 'Envio Bogota/Cundinamarca' : 'Envio nacional' );
		$first_flat_rate_id          = null;
		$preferred_flat_rate_id      = null;

		foreach ( $rates as $rate_id => $rate ) {
			if ( $rate->method_id !== 'flat_rate' ) {
				continue;
			}

			if ( null === $first_flat_rate_id ) {
				$first_flat_rate_id = $rate_id;
			}

			if ( null === $preferred_flat_rate_id && bsc_flat_rate_matches_destination( $rate, $is_local ) ) {
				$preferred_flat_rate_id = $rate_id;
			}

			bsc_set_shipping_rate_cost( $rate, $target_cost );

			if ( method_exists( $rate, 'set_label' ) ) {
				$rate->set_label( $target_label );
			}
		}

		$flat_rate_to_keep = $preferred_flat_rate_id ?: $first_flat_rate_id;
		if ( null === $flat_rate_to_keep ) {
			return $rates;
		}

		foreach ( $rates as $rate_id => $rate ) {
			if ( $rate->method_id === 'flat_rate' && $rate_id !== $flat_rate_to_keep ) {
				unset( $rates[ $rate_id ] );
			}
		}

		return $rates;
	}

	function bsc_get_bogota_shipping_price(): int {
		return max( 0, (int) get_option( 'bsc_bogota_shipping_price', 10000 ) );
	}

	function bsc_get_other_shipping_price(): int {
		return max( 0, (int) get_option( 'bsc_other_shipping_price', 17000 ) );
	}

	function bsc_normalize_shipping_text( string $value ): string {
		$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

		if ( function_exists( 'remove_accents' ) ) {
			$value = remove_accents( $value );
		}

		$value = strtolower( trim( $value ) );
		$value = preg_replace( '/[^a-z0-9]+/', ' ', $value ) ?: '';
		$value = preg_replace( '/\s+/', ' ', $value ) ?: '';

		return trim( $value );
	}

	function bsc_get_colombia_shipping_places(): array {
		static $colombia_places = null;

		if ( null !== $colombia_places ) {
			return $colombia_places;
		}

		$colombia_places = array();
		$places_file     = WP_PLUGIN_DIR . '/wc-departamentos-y-ciudades-colombia/assets/places/CO-cities.php';

		if ( file_exists( $places_file ) ) {
			global $places;

			if ( ! is_array( $places ?? null ) ) {
				$places = array();
			}

			include $places_file;

			if ( isset( $places['CO'] ) && is_array( $places['CO'] ) ) {
				$colombia_places = $places['CO'];
			}
		}

		return $colombia_places;
	}

	function bsc_get_colombia_city_code_candidates( string $city ): array {
		if ( ! preg_match_all( '/\d{5,8}/', $city, $matches ) ) {
			return array();
		}

		$candidates = array();

		foreach ( $matches[0] as $match ) {
			$code         = (string) $match;
			$candidates[] = $code;

			if ( strlen( $code ) < 8 ) {
				$candidates[] = str_pad( $code, 8, '0' );
			}
		}

		return array_values( array_unique( $candidates ) );
	}

	function bsc_lookup_colombia_city_location( string $city ): array {
		$city = trim( $city );
		if ( $city === '' ) {
			return array();
		}

		$city_code_candidates = bsc_get_colombia_city_code_candidates( $city );
		$normalized_city      = bsc_normalize_shipping_text( $city );

		foreach ( bsc_get_colombia_shipping_places() as $state => $cities ) {
			if ( ! is_array( $cities ) ) {
				continue;
			}

			foreach ( $cities as $code => $label ) {
				$code  = (string) $code;
				$label = (string) $label;

				if ( ! empty( $city_code_candidates ) && in_array( $code, $city_code_candidates, true ) ) {
					return array(
						'state'           => (string) $state,
						'city'            => $label,
						'code'            => $code,
						'matched_by_code' => true,
					);
				}

				if ( $normalized_city !== '' && $normalized_city === bsc_normalize_shipping_text( $label ) ) {
					return array(
						'state'           => (string) $state,
						'city'            => $label,
						'code'            => $code,
						'matched_by_code' => false,
					);
				}
			}
		}

		return array();
	}

	function bsc_resolve_colombia_city_label( string $city ): string {
		$city = trim( $city );
		if ( '' === $city ) {
			return '';
		}

		$city_location = bsc_lookup_colombia_city_location( $city );
		if ( ! empty( $city_location['city'] ) ) {
			return (string) $city_location['city'];
		}

		return $city;
	}

	add_action( 'woocommerce_checkout_update_order_meta', 'bsc_normalize_colombia_order_city_labels', 20 );
	function bsc_normalize_colombia_order_city_labels( $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$billing_city  = bsc_resolve_colombia_city_label( (string) $order->get_billing_city() );
		$shipping_city = bsc_resolve_colombia_city_label( (string) $order->get_shipping_city() );
		$changed       = false;

		if ( '' !== $billing_city && $billing_city !== $order->get_billing_city() ) {
			$order->set_billing_city( $billing_city );
			$changed = true;
		}

		if ( '' !== $shipping_city && $shipping_city !== $order->get_shipping_city() ) {
			$order->set_shipping_city( $shipping_city );
			$changed = true;
		}

		if ( $changed ) {
			$order->save();
		}
	}

	function bsc_shipping_text_contains_any( string $haystack, array $needles ): bool {
		foreach ( $needles as $needle ) {
			if ( $needle !== '' && strpos( $haystack, $needle ) !== false ) {
				return true;
			}
		}

		return false;
	}

	function bsc_is_bogota_or_cundinamarca_destination( string $state, string $city ): bool {
		$city_location = bsc_lookup_colombia_city_location( $city );
		if ( ! empty( $city_location['state'] ) && ( trim( $state ) === '' || ! empty( $city_location['matched_by_code'] ) ) ) {
			$state = $city_location['state'];
		}

		$state = bsc_normalize_shipping_text( $state );
		$city  = bsc_normalize_shipping_text( $city );

		$local_states = array(
			'bog',
			'bogota',
			'bogota d c',
			'bogota dc',
			'capital district',
			'cun',
			'co cun',
			'cundinamarca',
			'd c',
			'dc',
			'distrito capital',
		);

		$bogota_cities = array(
			'bog',
			'bogota',
			'bogota d c',
			'bogota dc',
			'santa fe de bogota',
		);

		return in_array( $state, $local_states, true )
		|| bsc_shipping_text_contains_any( $state, array( 'bogota', 'cundinamarca', 'distrito capital' ) )
		|| in_array( $city, $bogota_cities, true )
		|| bsc_shipping_text_contains_any( $city, array( 'bogota' ) );
	}

	function bsc_flat_rate_matches_destination( $rate, bool $is_local ): bool {
		$label = method_exists( $rate, 'get_label' )
		? bsc_normalize_shipping_text( (string) $rate->get_label() )
		: bsc_normalize_shipping_text( (string) ( $rate->label ?? '' ) );

		$is_local_label = bsc_shipping_text_contains_any( $label, array( 'bogot', 'cundinamarca', 'local' ) );

		if ( $is_local ) {
			return $is_local_label;
		}

		return bsc_shipping_text_contains_any( $label, array( 'nacional', 'colombia', 'general' ) ) || ! $is_local_label;
	}

	function bsc_set_shipping_rate_cost( $rate, float $target_cost ): void {
		if ( ! method_exists( $rate, 'set_cost' ) ) {
			return;
		}

		$current_cost = method_exists( $rate, 'get_cost' ) ? (float) $rate->get_cost() : 0;
		$rate->set_cost( $target_cost );

		if ( ! method_exists( $rate, 'get_taxes' ) || ! method_exists( $rate, 'set_taxes' ) ) {
			return;
		}

		$taxes = $rate->get_taxes();
		if ( empty( $taxes ) || ! is_array( $taxes ) ) {
			return;
		}

		foreach ( $taxes as $tax_id => $tax ) {
			$taxes[ $tax_id ] = $current_cost > 0
			? wc_format_decimal( (float) $tax * ( $target_cost / $current_cost ) )
			: 0;
		}

		$rate->set_taxes( $taxes );
	}

	add_filter(
		'default_checkout_billing_country',
		function () {
			return 'CO';
		}
	);
	add_filter(
		'default_checkout_shipping_country',
		function () {
			return 'CO';
		}
	);

	function bsc_limit_wc_countries_to_colombia( array $countries ): array {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $countries;
		}

		return array(
			'CO' => $countries['CO'] ?? 'Colombia',
		);
	}
	add_filter( 'woocommerce_countries_allowed_countries', 'bsc_limit_wc_countries_to_colombia', 20 );
	add_filter( 'woocommerce_countries_shipping_countries', 'bsc_limit_wc_countries_to_colombia', 20 );

	function bsc_force_checkout_posted_countries_to_colombia( array $data ): array {
		$data['billing_country']  = 'CO';
		$data['shipping_country'] = 'CO';

		return $data;
	}
	add_filter( 'woocommerce_checkout_posted_data', 'bsc_force_checkout_posted_countries_to_colombia', 20 );

	// ── BSC-032: Custom order statuses ────────────────────────────────────
	add_action( 'init', 'bsc_register_order_statuses' );
	function bsc_register_order_statuses(): void {
		register_post_status(
			'wc-preparing',
			array(
				'label'                     => _x( 'En preparación', 'Order status', 'bsc-2-0' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'En preparación <span class="count">(%s)</span>',
					'En preparación <span class="count">(%s)</span>'
				),
			)
		);
		register_post_status(
			'wc-shipped',
			array(
				'label'                     => _x( 'Enviado', 'Order status', 'bsc-2-0' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Enviado <span class="count">(%s)</span>',
					'Enviados <span class="count">(%s)</span>'
				),
			)
		);
	}

	add_filter( 'wc_order_statuses', 'bsc_add_order_statuses_to_woo' );
	function bsc_add_order_statuses_to_woo( array $statuses ): array {
		$new = array();
		foreach ($statuses as $key => $label) {
			$new[ $key ] = $label;
			if ($key === 'wc-processing') {
				$new['wc-preparing'] = _x( 'En preparación', 'Order status', 'bsc-2-0' );
			}
		}
		$new['wc-shipped'] = _x( 'Enviado', 'Order status', 'bsc-2-0' );
		return $new;
	}

	// ── BSC-036: Deduct bodega stock when web order moves to processing ───
	function bsc_dual_stock_product_id( $product ): int {
		if ( ! $product instanceof WC_Product ) {
			return 0;
		}

		return (int) ( $product->get_parent_id() ?: $product->get_id() );
	}

	function bsc_dual_stock_quantity_filter( $quantity, $product ) {
		if ( ! class_exists( 'BSC_Stock' ) ) {
			return $quantity;
		}

		$product_id = bsc_dual_stock_product_id( $product );
		if ( $product_id <= 0 || ! BSC_Stock::has_dual_stock( $product_id ) ) {
			return $quantity;
		}

		return BSC_Stock::get_total_stock( $product_id );
	}
	add_filter( 'woocommerce_product_get_stock_quantity', 'bsc_dual_stock_quantity_filter', 20, 2 );
	add_filter( 'woocommerce_product_variation_get_stock_quantity', 'bsc_dual_stock_quantity_filter', 20, 2 );

	function bsc_dual_stock_status_filter( $status, $product ) {
		if ( ! class_exists( 'BSC_Stock' ) ) {
			return $status;
		}

		$product_id = bsc_dual_stock_product_id( $product );
		if ( $product_id <= 0 || ! BSC_Stock::has_dual_stock( $product_id ) ) {
			return $status;
		}

		return BSC_Stock::get_total_stock( $product_id ) > 0 ? 'instock' : 'outofstock';
	}
	add_filter( 'woocommerce_product_get_stock_status', 'bsc_dual_stock_status_filter', 20, 2 );
	add_filter( 'woocommerce_product_variation_get_stock_status', 'bsc_dual_stock_status_filter', 20, 2 );

	function bsc_dual_stock_is_in_stock_filter( $is_in_stock, $product ) {
		if ( ! class_exists( 'BSC_Stock' ) ) {
			return $is_in_stock;
		}

		$product_id = bsc_dual_stock_product_id( $product );
		if ( $product_id <= 0 || ! BSC_Stock::has_dual_stock( $product_id ) ) {
			return $is_in_stock;
		}

		return BSC_Stock::get_total_stock( $product_id ) > 0;
	}
	add_filter( 'woocommerce_product_is_in_stock', 'bsc_dual_stock_is_in_stock_filter', 20, 2 );

	function bsc_dual_stock_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = 0 ): bool {
		if ( ! $passed || ! class_exists( 'BSC_Stock' ) ) {
			return (bool) $passed;
		}

		$stock_product_id = $variation_id ? (int) $variation_id : (int) $product_id;
		if ( ! BSC_Stock::has_dual_stock( $stock_product_id ) && $variation_id ) {
			$stock_product_id = (int) $product_id;
		}

		if ( $stock_product_id <= 0 || ! BSC_Stock::has_dual_stock( $stock_product_id ) ) {
			return (bool) $passed;
		}

		$requested = max( 1, (int) $quantity );
		if ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$cart_product_id = (int) ( $cart_item['variation_id'] ?: $cart_item['product_id'] );
				if ( $cart_product_id === $stock_product_id ) {
					$requested += (int) $cart_item['quantity'];
				}
			}
		}

		if ( $requested > BSC_Stock::get_total_stock( $stock_product_id ) ) {
			wc_add_notice( __( 'No hay stock suficiente para agregar esa cantidad.', 'bsc-2-0' ), 'error' );
			return false;
		}

		return true;
	}
	add_filter( 'woocommerce_add_to_cart_validation', 'bsc_dual_stock_add_to_cart_validation', 20, 4 );

	function bsc_deduct_dual_stock_for_paid_order( int $order_id ): void {
		if (class_exists( 'BSC_Stock' )) {
			BSC_Stock::deduct_web_order( $order_id );
		}
	}
	add_action( 'woocommerce_order_status_processing', 'bsc_deduct_dual_stock_for_paid_order' );
	add_action( 'woocommerce_order_status_completed', 'bsc_deduct_dual_stock_for_paid_order' );

	// Allow email triggers for custom statuses
	add_filter(
		'woocommerce_valid_order_statuses_for_payment_complete',
		function ( array $statuses ): array {
			$statuses[] = 'preparing';
			$statuses[] = 'shipped';
			return $statuses;
		}
	);

	// ── BSC: Unified WC status → BSC progress bar state map ──────────────
	function bsc_map_order_status_to_bar( string $wc_status ): string {
		require_once get_template_directory() . '/components/orders/order-progress-bar.php';
		$map = array(
			'processing' => BSC_Order_Progress_Bar::RECEIVED,
			'on-hold'    => BSC_Order_Progress_Bar::RECEIVED,
			'pending'    => BSC_Order_Progress_Bar::RECEIVED,
			'preparing'  => BSC_Order_Progress_Bar::RECEIVED,
			'shipped'    => BSC_Order_Progress_Bar::SHIPPED,
			'completed'  => BSC_Order_Progress_Bar::SHIPPED,
			'refunded'   => BSC_Order_Progress_Bar::CANCELLED,
			'cancelled'  => BSC_Order_Progress_Bar::CANCELLED,
			'failed'     => BSC_Order_Progress_Bar::CANCELLED,
			'bsc-archived' => BSC_Order_Progress_Bar::ARCHIVED,
		);
		return $map[ $wc_status ] ?? BSC_Order_Progress_Bar::CANCELLED;
	}

	function bsc_map_order_to_bar( WC_Order $order ): string {
		require_once get_template_directory() . '/components/orders/order-progress-bar.php';

		if ( $order->get_meta( '_bsc_archived_at', true ) ) {
			return BSC_Order_Progress_Bar::ARCHIVED;
		}

		return bsc_map_order_status_to_bar( $order->get_status() );
	}

	// ── BSC: Auto-archive orders after N days (daily WP cron) ────────────
	add_action( 'init', 'bsc_schedule_order_archiver' );
	function bsc_schedule_order_archiver(): void {
		if (!wp_next_scheduled( 'bsc_auto_archive_orders' )) {
			wp_schedule_event( time(), 'daily', 'bsc_auto_archive_orders' );
		}
	}

	add_action( 'bsc_auto_archive_orders', 'bsc_run_order_archiver' );
	function bsc_mark_order_archived( WC_Order $order, string $bucket, int $days ): void {
		if ($order->get_meta( '_bsc_archived_at', true )) {
			return;
		}

		$order->update_meta_data( '_bsc_archived_at', gmdate( 'Y-m-d H:i:s' ) );
		$order->update_meta_data( '_bsc_archive_bucket', $bucket );
		$order->save_meta_data();
		$order->add_order_note( sprintf( 'Auto-archivado tras %d dias en estado %s.', $days, $bucket ) );
	}

	function bsc_run_order_archiver(): void {
		$default_days   = max( 1, (int) apply_filters( 'bsc_auto_archive_days', 30 ) );
		$days_shipped   = max( 1, (int) apply_filters( 'bsc_auto_archive_days_shipped', $default_days ) );
		$days_cancelled = max( 1, (int) apply_filters( 'bsc_auto_archive_days_cancelled', $default_days ) );

		$cutoff_shipped   = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_shipped} days" ) );
		$cutoff_cancelled = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_cancelled} days" ) );

		bsc_archive_orders_batch( array( 'shipped', 'completed' ), $cutoff_shipped, 'shipped', $days_shipped );
		bsc_archive_orders_batch( array( 'cancelled', 'failed', 'refunded' ), $cutoff_cancelled, 'cancelled', $days_cancelled );
	}

	function bsc_archive_orders_batch( array $statuses, string $date_before, string $bucket, int $days ): void {
		$page  = 1;
		$limit = 100;

		do {
			$ids = wc_get_orders(
				array(
					'status'      => $statuses,
					'date_before' => $date_before,
					'limit'       => $limit,
					'paged'       => $page,
					'return'      => 'ids',
				)
			);

			foreach ($ids as $id) {
				$order = wc_get_order( $id );
				if ($order instanceof WC_Order) {
					bsc_mark_order_archived( $order, $bucket, $days );
				}
			}

			++$page;
		} while ( count( $ids ) === $limit );
	}
