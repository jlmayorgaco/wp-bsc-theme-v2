<?php
/**
 * Small compatibility helpers exposed to legacy theme files.
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bsc_growth_search_suggestions' ) ) {
	function bsc_growth_search_suggestions( string $query, array $product_ids = array() ): array {
		return BSC_Growth_Search_Service::get_suggestions( $query, $product_ids );
	}
}

if ( ! function_exists( 'bsc_growth_bundle_repository' ) ) {
	function bsc_growth_bundle_repository(): BSC_Growth_Bundle_Repository {
		return new BSC_Growth_Bundle_Repository();
	}
}
