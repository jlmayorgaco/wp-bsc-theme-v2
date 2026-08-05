<?php
/**
 * Product location code helpers.
 */
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BSC_PRODUCT_LOCATION_CODE_META_KEY' ) ) {
	define( 'BSC_PRODUCT_LOCATION_CODE_META_KEY', '_bsc_location_code' );
}

/**
 * Normalize a product location code before validation or storage.
 */
function bsc_normalize_product_location_code( $raw_value ): string {
	return strtoupper( trim( (string) sanitize_text_field( $raw_value ) ) );
}

/**
 * Determine whether a location code uses COD-M4-E1 format.
 *
 * An empty value is allowed so existing products can be updated gradually.
 */
function bsc_is_valid_product_location_code( string $location_code ): bool {
	return '' === $location_code
		|| 1 === preg_match( '/\ACOD-M[0-9]+-E[0-9]+\z/D', $location_code );
}

function bsc_get_product_location_code( int $product_id ): string {
	return bsc_normalize_product_location_code(
		get_post_meta( $product_id, BSC_PRODUCT_LOCATION_CODE_META_KEY, true )
	);
}

function bsc_update_product_location_code( int $product_id, string $location_code ): void {
	if ( '' === $location_code ) {
		delete_post_meta( $product_id, BSC_PRODUCT_LOCATION_CODE_META_KEY );
		return;
	}

	update_post_meta( $product_id, BSC_PRODUCT_LOCATION_CODE_META_KEY, $location_code );
}
