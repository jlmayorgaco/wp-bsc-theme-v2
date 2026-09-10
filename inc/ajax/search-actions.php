<?php
defined( 'ABSPATH' ) || exit;

/**
 * BSC-038: Custom Product Search — optimized with transient cache.
 * Searches by: title/description, SKU, brand (product category taxonomy).
 * Results cached 15 min per unique query; client cache handled in search.js.
 */

add_action( 'wp_ajax_bsc_search_products', 'bsc_search_products' );
add_action( 'wp_ajax_nopriv_bsc_search_products', 'bsc_search_products' );
add_action( 'save_post_product', 'bsc_bump_search_cache_version' );
add_action( 'edited_product_cat', 'bsc_bump_search_cache_version' );
add_action( 'created_product_cat', 'bsc_bump_search_cache_version' );
add_action( 'delete_product_cat', 'bsc_bump_search_cache_version' );
add_action( 'added_post_meta', 'bsc_maybe_bump_search_cache_version_for_product_meta', 10, 4 );
add_action( 'updated_post_meta', 'bsc_maybe_bump_search_cache_version_for_product_meta', 10, 4 );
add_action( 'deleted_post_meta', 'bsc_maybe_bump_search_cache_version_for_product_meta', 10, 4 );

function bsc_search_product_has_available_stock( WC_Product $product ): bool {
	$product_id = function_exists( 'bsc_dual_stock_product_id' )
		? bsc_dual_stock_product_id( $product )
		: (int) $product->get_id();

	if ( class_exists( 'BSC_Stock' ) && $product_id > 0 && BSC_Stock::has_dual_stock( $product_id ) ) {
		return BSC_Stock::get_total_stock( $product_id ) > 0;
	}

	$stock_quantity = $product->get_stock_quantity();
	if ( null !== $stock_quantity ) {
		return (int) $stock_quantity > 0;
	}

	return $product->is_in_stock();
}

function bsc_search_product_is_eligible( $product ): bool {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	$is_publicly_listable = function_exists( 'bsc_product_is_publicly_listable' )
		? bsc_product_is_publicly_listable( $product, 'search' )
		: 'publish' === $product->get_status();

	return $is_publicly_listable
		&& $product->is_purchasable()
		&& bsc_search_product_has_available_stock( $product );
}

function bsc_search_filter_eligible_product_ids( array $product_ids, int $limit = 8, int $offset = 0 ): array {
	$product_ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
	if ( empty( $product_ids ) ) {
		return array();
	}

	update_meta_cache( 'post', $product_ids );

	$eligible_ids = array();
	foreach ( $product_ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( bsc_search_product_is_eligible( $product ) ) {
			$eligible_ids[] = $product_id;
		}
	}

	if ( $limit <= 0 ) {
		return array_slice( $eligible_ids, max( 0, $offset ) );
	}

	return array_slice( $eligible_ids, max( 0, $offset ), $limit );
}

function bsc_search_is_excluded_category_term( WP_Term $term ): bool {
	$excluded_slugs = array( 'uncategorized', 'sin-categorizar' );
	$excluded_names = array( 'uncategorized', 'sin categorizar', 'sin categoria' );

	return in_array( strtolower( $term->slug ), $excluded_slugs, true )
		|| in_array( strtolower( $term->name ), $excluded_names, true );
}

function bsc_search_get_excluded_category_term_ids(): array {
	$excluded_terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'slug'       => array( 'uncategorized', 'sin-categorizar' ),
			'fields'     => 'ids',
			'hide_empty' => false,
		)
	);

	if ( empty( $excluded_terms ) || is_wp_error( $excluded_terms ) ) {
		return array();
	}

	return array_values( array_map( 'absint', $excluded_terms ) );
}

function bsc_search_filter_suggestions( array $suggestions ): array {
	return array_values(
		array_filter(
			$suggestions,
			static function ( $suggestion ): bool {
				if ( ! is_array( $suggestion ) ) {
					return false;
				}

				$type = (string) ( $suggestion['type'] ?? '' );
				if ( ! in_array( $type, array( 'brand', 'category' ), true ) ) {
					return true;
				}

				$label = sanitize_key( remove_accents( strtolower( wp_strip_all_tags( (string) ( $suggestion['label'] ?? '' ) ) ) ) );
				$url   = sanitize_title( wp_parse_url( (string) ( $suggestion['url'] ?? '' ), PHP_URL_PATH ) ?: '' );

				return ! in_array( $label, array( 'uncategorized', 'sin-categorizar' ), true )
					&& ! str_contains( $url, 'uncategorized' )
					&& ! str_contains( $url, 'sin-categorizar' );
			}
		)
	);
}

function bsc_search_apply_public_query_constraints( array $args ): array {
	if ( function_exists( 'bsc_apply_public_product_query_constraints' ) ) {
		return bsc_apply_public_product_query_constraints( $args, 'search' );
	}

	return $args;
}

function bsc_search_products() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );

	if ( ! bsc_search_rate_limit_passed() ) {
		wp_send_json_error( array( 'message' => 'Demasiadas busquedas. Intenta de nuevo en un momento.' ), 429 );
	}

	$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

	if ( strlen( $query ) < 2 ) {
		wp_send_json_success( array( 'products' => array() ) );
	}

	// ── Transient cache (15 min per unique search term) ────────────────────
	$cache_key = 'bsc_search_schema3_v' . bsc_get_search_cache_version() . '_' . md5( $query );
	$cached    = get_transient( $cache_key );
	if ( $cached !== false ) {
		$cached_products    = isset( $cached['products'] ) && is_array( $cached['products'] ) ? $cached['products'] : (array) $cached;
		$cached_product_ids = wp_list_pluck( $cached_products, 'id' );
		$cached_suggestions = isset( $cached['suggestions'] ) && is_array( $cached['suggestions'] )
			? $cached['suggestions']
			: ( function_exists( 'bsc_growth_search_suggestions' ) ? bsc_growth_search_suggestions( $query, $cached_product_ids ) : array() );
		$cached_suggestions = bsc_search_filter_suggestions( $cached_suggestions );

		$cached_products = array_values(
			array_filter(
				$cached_products,
				static function ( $cached_product ): bool {
					if ( ! is_array( $cached_product ) ) {
						return false;
					}

					return bsc_search_product_is_eligible( wc_get_product( absint( $cached_product['id'] ?? 0 ) ) );
				}
			)
		);

		if ( function_exists( 'bsc_metrics_record_search' ) ) {
			bsc_metrics_record_search( $query, count( $cached_products ) );
		}
		wp_send_json_success(
			array(
				'products'    => $cached_products,
				'suggestions' => $cached_suggestions,
			)
		);
	}

	$collected_ids = array();

	// ── 1. Title + description ─────────────────────────────────────────────
	$q1_args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		's'                      => $query,
		'fields'                 => 'ids',
		'posts_per_page'         => 24,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);
	$q1_args = bsc_search_apply_public_query_constraints( $q1_args );

	$q1 = new WP_Query( $q1_args );
	if ( ! empty( $q1->posts ) ) {
		$collected_ids = array_merge( $collected_ids, $q1->posts );
	}

	// ── 2. SKU (product meta _sku) ─────────────────────────────────────────
	if ( count( bsc_search_filter_eligible_product_ids( $collected_ids, 8 ) ) < 8 ) {
		$q2_args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'posts_per_page'         => 24,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => '_sku',
					'value'   => $query,
					'compare' => 'LIKE',
				),
			),
		);
		$q2_args = bsc_search_apply_public_query_constraints( $q2_args );

		$q2 = new WP_Query( $q2_args );
		if ( ! empty( $q2->posts ) ) {
			$collected_ids = array_merge( $collected_ids, $q2->posts );
		}
	}

	// ── 3. Brand / category name ───────────────────────────────────────────
	if ( count( bsc_search_filter_eligible_product_ids( $collected_ids, 8 ) ) < 8 ) {
		$excluded_category_term_ids = bsc_search_get_excluded_category_term_ids();
		$matching_terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'name__like' => $query,
				'fields'     => 'ids',
				'hide_empty' => true,
				'exclude'    => $excluded_category_term_ids,
			)
		);

		if ( ! empty( $matching_terms ) && ! is_wp_error( $matching_terms ) ) {
			$q3_args = array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'posts_per_page'         => 24,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $matching_terms,
					),
				),
			);
			$q3_args = bsc_search_apply_public_query_constraints( $q3_args );

			$q3 = new WP_Query( $q3_args );
			if ( ! empty( $q3->posts ) ) {
				$collected_ids = array_merge( $collected_ids, $q3->posts );
			}
		}
	}

	// ── Deduplicate and limit to 8 ─────────────────────────────────────────
	$product_ids = bsc_search_filter_eligible_product_ids( $collected_ids, 8 );

	if ( empty( $product_ids ) ) {
		$suggestions = function_exists( 'bsc_growth_search_suggestions' ) ? bsc_growth_search_suggestions( $query, array() ) : array();
		$suggestions = bsc_search_filter_suggestions( $suggestions );
		set_transient(
			$cache_key,
			array(
				'products'    => array(),
				'suggestions' => $suggestions,
			),
			15 * MINUTE_IN_SECONDS
		);
		if ( function_exists( 'bsc_metrics_record_search' ) ) {
			bsc_metrics_record_search( $query, 0 );
		}
		wp_send_json_success(
			array(
				'products'    => array(),
				'suggestions' => $suggestions,
			)
		);
	}

	// ── Build response ─────────────────────────────────────────────────────
	$results = array();

	foreach ( $product_ids as $pid ) {
		$product = wc_get_product( $pid );
		if ( ! bsc_search_product_is_eligible( $product ) ) {
			continue;
		}

		// Brand: first category with -marca in slug
		$brand = '';
		$terms = get_the_terms( $pid, 'product_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( bsc_search_is_excluded_category_term( $term ) ) {
					continue;
				}

				if ( strpos( $term->slug, '-marca' ) !== false ) {
					$brand = $term->name;
					break;
				}
			}
		}

		// Image
		$img_url = '';
		$img_id  = $product->get_image_id();
		if ( $img_id ) {
			$img_src = wp_get_attachment_image_src( $img_id, 'woocommerce_thumbnail' );
			$img_url = $img_src ? $img_src[0] : '';
		}

		$price_html = $product->get_price_html();

		$results[] = array(
			'id'           => $pid,
			'name'         => wp_strip_all_tags( $product->get_name() ),
			'permalink'    => esc_url_raw( get_permalink( $pid ) ),
			'image'        => esc_url_raw( $img_url ),
			'brand'        => wp_strip_all_tags( $brand ),
			'price'        => wp_strip_all_tags( $price_html ),
			'price_html'   => wp_kses_post( $price_html ),
			'sku'          => sanitize_text_field( $product->get_sku() ),
			'availability' => 'instock',
		);
	}

	// ── Cache and return ───────────────────────────────────────────────────
	$suggestions = function_exists( 'bsc_growth_search_suggestions' ) ? bsc_growth_search_suggestions( $query, $product_ids ) : array();
	$suggestions = bsc_search_filter_suggestions( $suggestions );
	set_transient(
		$cache_key,
		array(
			'products'    => $results,
			'suggestions' => $suggestions,
		),
		15 * MINUTE_IN_SECONDS
	);
	if ( function_exists( 'bsc_metrics_record_search' ) ) {
		bsc_metrics_record_search( $query, count( $results ) );
	}
	wp_send_json_success(
		array(
			'products'    => $results,
			'suggestions' => $suggestions,
		)
	);
}

function bsc_search_rate_limit_passed(): bool {
	if ( function_exists( 'bsc_rate_limit_passed' ) ) {
		return bsc_rate_limit_passed( 'search', 60, MINUTE_IN_SECONDS );
	}

	return true;
}

function bsc_get_search_cache_version(): int {
	return max( 1, (int) get_option( 'bsc_search_cache_version', 1 ) );
}

function bsc_bump_search_cache_version(): void {
	update_option( 'bsc_search_cache_version', bsc_get_search_cache_version() + 1, false );
}

function bsc_maybe_bump_search_cache_version_for_product_meta( $meta_id, $object_id, $meta_key, $_meta_value ): void {
	unset( $meta_id, $_meta_value );

	if ( 'product' !== get_post_type( (int) $object_id ) ) {
		return;
	}

	$cache_sensitive_meta_keys = array(
		'_stock',
		'_stock_bodega',
		'_stock_tienda',
		'_stock_status',
		'_bsc_product_archived',
	);

	if ( in_array( (string) $meta_key, $cache_sensitive_meta_keys, true ) ) {
		bsc_bump_search_cache_version();
	}
}
