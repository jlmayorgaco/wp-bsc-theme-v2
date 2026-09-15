<?php
/**
 * Shared storefront pricing helpers.
 *
 * Product cards, product pages and search results must all use the same
 * lowest purchasable option price.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Determine whether a native WooCommerce variation has available stock.
 *
 * @param WC_Product $variation Native product variation.
 * @return bool
 */
function bsc_product_price_variation_is_available( WC_Product $variation ): bool {
	if ( ! $variation->is_purchasable() || ! $variation->is_in_stock() ) {
		return false;
	}

	$stock_quantity = $variation->get_stock_quantity();
	return null === $stock_quantity || (int) $stock_quantity > 0;
}

/**
 * Return the lowest available option price for a product.
 *
 * @param WC_Product $product Product whose available options will be inspected.
 * @return array{price: float, regular_price: float, sale_price: float}|null
 */
function bsc_get_product_lowest_available_price( WC_Product $product ): ?array {
	$option_prices = array();

	if ( $product instanceof WC_Product_Variable ) {
		$variation_prices = $product->get_variation_prices( true );
		$visible_prices   = $variation_prices['price'] ?? array();

		foreach ( $visible_prices as $variation_id => $variation_price ) {
			if ( '' === (string) $variation_price || ! is_numeric( $variation_price ) ) {
				continue;
			}

			if ( function_exists( 'wc_get_product' ) ) {
				$variation = wc_get_product( $variation_id );
				if ( ! $variation instanceof WC_Product || ! bsc_product_price_variation_is_available( $variation ) ) {
					continue;
				}
			}

			$regular_price   = $variation_prices['regular_price'][ $variation_id ] ?? $variation_price;
			$sale_price      = $variation_prices['sale_price'][ $variation_id ] ?? '';
			$option_prices[] = array(
				'price'         => (float) $variation_price,
				'regular_price' => is_numeric( $regular_price ) ? (float) $regular_price : (float) $variation_price,
				'sale_price'    => is_numeric( $sale_price ) ? (float) $sale_price : 0.0,
			);
		}
	}

	if ( function_exists( 'bsc_get_product_variant_matrix_public_data' ) ) {
		$variants = bsc_get_product_variant_matrix_public_data( $product->get_id() );

		foreach ( $variants as $variant ) {
			if ( isset( $variant['stock_total'] ) && 0 >= (int) $variant['stock_total'] ) {
				continue;
			}

			$variant_price = (string) ( $variant['price'] ?? '' );
			if ( '' === $variant_price ) {
				$variant_price = (string) $product->get_price();
			}

			if ( '' === $variant_price || ! is_numeric( $variant_price ) ) {
				continue;
			}

			$regular_price   = (string) ( $variant['regular_price'] ?? $variant_price );
			$sale_price      = (string) ( $variant['sale_price'] ?? '' );
			$display_price   = (float) wc_get_price_to_display(
				$product,
				array( 'price' => (float) $variant_price )
			);
			$option_prices[] = array(
				'price'         => $display_price,
				'regular_price' => is_numeric( $regular_price )
					? (float) wc_get_price_to_display( $product, array( 'price' => (float) $regular_price ) )
					: $display_price,
				'sale_price'    => is_numeric( $sale_price )
					? (float) wc_get_price_to_display( $product, array( 'price' => (float) $sale_price ) )
					: 0.0,
			);
		}
	}

	if ( empty( $option_prices ) ) {
		return null;
	}

	usort(
		$option_prices,
		static function ( array $left, array $right ): int {
			return $left['price'] <=> $right['price'];
		}
	);

	return $option_prices[0];
}

/**
 * Format a price returned by bsc_get_product_lowest_available_price().
 *
 * @param array{price: float, regular_price: float, sale_price: float} $price_data Price values.
 * @return string
 */
function bsc_format_product_available_price_html( array $price_data ): string {
	$price         = (float) ( $price_data['price'] ?? 0 );
	$regular_price = (float) ( $price_data['regular_price'] ?? $price );
	$sale_price    = (float) ( $price_data['sale_price'] ?? 0 );

	if ( $regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price ) {
		if ( function_exists( 'wc_format_sale_price' ) ) {
			return wc_format_sale_price( $regular_price, $sale_price );
		}

		return '<del aria-hidden="true">' . wc_price( $regular_price ) . '</del> <ins>' . wc_price( $sale_price ) . '</ins>';
	}

	return wc_price( $price );
}

/**
 * Return the storefront price HTML shared by cards, product pages and search.
 *
 * @param WC_Product $product Product whose price will be formatted.
 * @return string
 */
function bsc_get_product_available_price_html( WC_Product $product ): string {
	$lowest_price = bsc_get_product_lowest_available_price( $product );

	return null === $lowest_price
		? $product->get_price_html()
		: bsc_format_product_available_price_html( $lowest_price );
}
