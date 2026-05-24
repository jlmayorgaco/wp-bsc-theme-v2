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
	$cache_key = 'bsc_search_v' . bsc_get_search_cache_version() . '_' . md5( $query );
	$cached    = get_transient( $cache_key );
	if ( $cached !== false ) {
		if ( function_exists( 'bsc_metrics_record_search' ) ) {
			bsc_metrics_record_search( $query, count( $cached ) );
		}
		wp_send_json_success( array( 'products' => $cached ) );
	}

	$collected_ids = array();

	// ── 1. Title + description ─────────────────────────────────────────────
	$q1 = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			's'                      => $query,
			'fields'                 => 'ids',
			'posts_per_page'         => 8,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	if ( ! empty( $q1->posts ) ) {
		$collected_ids = array_merge( $collected_ids, $q1->posts );
	}

	// ── 2. SKU (product meta _sku) ─────────────────────────────────────────
	if ( count( $collected_ids ) < 8 ) {
		$q2 = new WP_Query(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'posts_per_page'         => 8,
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
			)
		);
		if ( ! empty( $q2->posts ) ) {
			$collected_ids = array_merge( $collected_ids, $q2->posts );
		}
	}

	// ── 3. Brand / category name ───────────────────────────────────────────
	if ( count( $collected_ids ) < 8 ) {
		$matching_terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'name__like' => $query,
				'fields'     => 'ids',
				'hide_empty' => true,
			)
		);

		if ( ! empty( $matching_terms ) && ! is_wp_error( $matching_terms ) ) {
			$q3 = new WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'fields'                 => 'ids',
					'posts_per_page'         => 8,
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
				)
			);
			if ( ! empty( $q3->posts ) ) {
				$collected_ids = array_merge( $collected_ids, $q3->posts );
			}
		}
	}

	// ── Deduplicate and limit to 8 ─────────────────────────────────────────
	$product_ids = array_slice( array_unique( $collected_ids ), 0, 8 );

	if ( empty( $product_ids ) ) {
		set_transient( $cache_key, array(), 15 * MINUTE_IN_SECONDS );
		if ( function_exists( 'bsc_metrics_record_search' ) ) {
			bsc_metrics_record_search( $query, 0 );
		}
		wp_send_json_success( array( 'products' => array() ) );
	}

	// ── Build response ─────────────────────────────────────────────────────
	$results = array();

	foreach ( $product_ids as $pid ) {
		$product = wc_get_product( $pid );
		if ( ! $product ) {
			continue;
		}
		if ( $product->get_status() !== 'publish' || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			continue;
		}

		// Brand: first category with -marca in slug
		$brand = '';
		$terms = get_the_terms( $pid, 'product_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
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

		$results[] = array(
			'id'           => $pid,
			'name'         => wp_strip_all_tags( $product->get_name() ),
			'permalink'    => esc_url_raw( get_permalink( $pid ) ),
			'image'        => esc_url_raw( $img_url ),
			'brand'        => wp_strip_all_tags( $brand ),
			'price'        => strip_tags( $product->get_price_html() ),
			'sku'          => sanitize_text_field( $product->get_sku() ),
			'availability' => $product->is_in_stock() ? 'instock' : 'outofstock',
		);
	}

	// ── Cache and return ───────────────────────────────────────────────────
	set_transient( $cache_key, $results, 15 * MINUTE_IN_SECONDS );
	if ( function_exists( 'bsc_metrics_record_search' ) ) {
		bsc_metrics_record_search( $query, count( $results ) );
	}
	wp_send_json_success( array( 'products' => $results ) );
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
