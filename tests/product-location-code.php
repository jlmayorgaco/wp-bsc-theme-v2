<?php
/**
 * Focused regression test for product location code validation.
 *
 * Run with: php tests/product-location-code.php
 */

define( 'ABSPATH', __DIR__ );

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

require_once dirname( __DIR__ ) . '/inc/product/product-location-code.php';

function bsc_location_code_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

bsc_location_code_assert_same(
	'COD-M4-E1',
	bsc_normalize_product_location_code( ' cod-m4-e1 ' ),
	'Location codes must be trimmed and normalized to uppercase.'
);
bsc_location_code_assert_same( true, bsc_is_valid_product_location_code( 'COD-M4-E1' ), 'The documented format must be valid.' );
bsc_location_code_assert_same( true, bsc_is_valid_product_location_code( 'COD-M24-E12' ), 'Multi-digit furniture and space numbers must be valid.' );
bsc_location_code_assert_same( true, bsc_is_valid_product_location_code( '' ), 'The location code must remain optional for existing products.' );
bsc_location_code_assert_same( true, bsc_is_valid_product_location_code( 'COD-M0-E0' ), 'Furniture and space identifiers may contain any numeric value.' );
bsc_location_code_assert_same( false, bsc_is_valid_product_location_code( 'COD-MA-E1' ), 'Furniture identifiers must be numeric.' );
bsc_location_code_assert_same( false, bsc_is_valid_product_location_code( 'M4-E1' ), 'The COD prefix is required.' );

fwrite( STDOUT, "Product location code test passed.\n" );
