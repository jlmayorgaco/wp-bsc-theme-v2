<?php
defined( 'ABSPATH' ) || exit;

function bsc_recommendation_product_is_eligible( ?WC_Product $product, array $exclude_ids = array() ): bool {
	if (!$product instanceof WC_Product) {
		return false;
	}

	$product_id = $product->get_id();

	if (in_array( $product_id, $exclude_ids, true )) {
		return false;
	}

	$is_publicly_listable = function_exists( 'bsc_product_is_publicly_listable' )
		? bsc_product_is_publicly_listable( $product )
		: $product->get_status() === 'publish';

	return $is_publicly_listable
		&& $product->get_sku() !== ''
		&& $product->is_purchasable()
		&& $product->is_in_stock();
}

function bsc_recommendation_collect_skus( array $product_ids, int $limit, array $exclude_ids = array(), array $existing_skus = array() ): array {
	$skus = $existing_skus;

	foreach (array_unique( array_map( 'absint', $product_ids ) ) as $product_id) {
		if (count( $skus ) >= $limit) {
			break;
		}

		$product = wc_get_product( $product_id );

		if (!bsc_recommendation_product_is_eligible( $product, $exclude_ids )) {
			continue;
		}

		$sku = $product->get_sku();

		if ($sku && !in_array( $sku, $skus, true )) {
			$skus[] = $sku;
		}
	}

	return $skus;
}

function bsc_recommendation_get_category_slugs( array $product_ids ): array {
	$slugs = array();

	foreach ($product_ids as $product_id) {
		$terms = wp_get_post_terms( (int) $product_id, 'product_cat', array( 'fields' => 'slugs' ) );

		if (!is_wp_error( $terms ) && !empty( $terms )) {
			$slugs = array_merge( $slugs, $terms );
		}
	}

	return array_values( array_unique( $slugs ) );
}

function bsc_recommendation_query_ids( array $args ): array {
	$defaults = array(
		'limit'        => 12,
		'status'       => 'publish',
		'stock_status' => 'instock',
		'return'       => 'ids',
		'orderby'      => 'date',
		'order'        => 'DESC',
	);

	return array_map( 'absint', wc_get_products( array_merge( $defaults, $args ) ) );
}

function bsc_recommendation_fill_from_latest( array $skus, int $limit, array $exclude_ids ): array {
	if (count( $skus ) >= $limit) {
		return array_slice( $skus, 0, $limit );
	}

	$fallback_ids = bsc_recommendation_query_ids(
		array(
			'limit'   => max( $limit * 3, 12 ),
			'exclude' => $exclude_ids,
		)
	);

	return array_slice(
		bsc_recommendation_collect_skus( $fallback_ids, $limit, $exclude_ids, $skus ),
		0,
		$limit
	);
}

function get_related_product_skus( int $product_id, int $limit = 8 ): array {
	$exclude_ids = array( $product_id );
	$skus        = array();

	$related_ids = wc_get_related_products( $product_id, $limit * 2, $exclude_ids );
	$skus        = bsc_recommendation_collect_skus( $related_ids, $limit, $exclude_ids, $skus );

	if (count( $skus ) < $limit) {
		$category_slugs = bsc_recommendation_get_category_slugs( array( $product_id ) );

		if (!empty( $category_slugs )) {
			$category_ids = bsc_recommendation_query_ids(
				array(
					'limit'    => max( $limit * 3, 12 ),
					'exclude'  => array_merge( $exclude_ids, $related_ids ),
					'category' => $category_slugs,
				)
			);

			$skus = bsc_recommendation_collect_skus( $category_ids, $limit, $exclude_ids, $skus );
		}
	}

	return bsc_recommendation_fill_from_latest( $skus, $limit, $exclude_ids );
}

function get_cart_recommendation_skus( int $limit = 8 ): array {
	$cart = WC()->cart;

	if (!$cart || $cart->is_empty()) {
		return bsc_recommendation_fill_from_latest( array(), $limit, array() );
	}

	$cart_product_ids = array_values(
		array_unique(
			array_map(
				static fn( $item ): int => isset( $item['product_id'] ) ? (int) $item['product_id'] : 0,
				$cart->get_cart()
			)
		)
	);
	$cart_product_ids = array_filter( $cart_product_ids );
	$skus             = array();

	foreach ($cart_product_ids as $product_id) {
		$related_ids = wc_get_related_products( $product_id, $limit * 2, $cart_product_ids );
		$skus        = bsc_recommendation_collect_skus( $related_ids, $limit, $cart_product_ids, $skus );

		if (count( $skus ) >= $limit) {
			return array_slice( $skus, 0, $limit );
		}
	}

	$category_slugs = bsc_recommendation_get_category_slugs( $cart_product_ids );

	if (!empty( $category_slugs )) {
		$category_ids = bsc_recommendation_query_ids(
			array(
				'limit'    => max( $limit * 3, 12 ),
				'exclude'  => $cart_product_ids,
				'category' => $category_slugs,
			)
		);

		$skus = bsc_recommendation_collect_skus( $category_ids, $limit, $cart_product_ids, $skus );
	}

	return bsc_recommendation_fill_from_latest( $skus, $limit, $cart_product_ids );
}
