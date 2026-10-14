<?php
/** Verify the vendor's disabled TLS verification is overridden for Wompi. */

define( 'ABSPATH', __DIR__ );

function add_filter() {}
function add_action() {}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

require_once dirname( __DIR__ ) . '/inc/payments/wompi-security.php';

function bsc_assert_wompi_http( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$vendor_args = array( 'sslverify' => false, 'redirection' => 5, 'timeout' => 30 );

foreach ( array( 'production.wompi.co', 'sandbox.wompi.co', 'api.wompi.co' ) as $host ) {
	$secured = bsc_wompi_secure_http( $vendor_args, 'https://' . $host . '/v1/transactions' );
	bsc_assert_wompi_http( true === $secured['sslverify'], $host . ': TLS certificate verification must be enabled.' );
	bsc_assert_wompi_http( 0 === $secured['redirection'], $host . ': redirects must be disabled.' );
	bsc_assert_wompi_http( 30 === $secured['timeout'], $host . ': unrelated request settings must survive.' );
}

$unrelated = bsc_wompi_secure_http( $vendor_args, 'https://example.org/api' );
bsc_assert_wompi_http( $vendor_args === $unrelated, 'Other HTTP clients must not be changed.' );

fwrite( STDOUT, "Wompi HTTP security checks passed.\n" );
