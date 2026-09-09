<?php
/**
 * Focused regression test for the bsc.local parking bypass.
 *
 * Run with: php tests/frontend-routing-local-host.php
 */

define( 'ABSPATH', __DIR__ );

$bsc_test_options = array(
	'bsc_parking_page_enabled'             => 1,
	'woocommerce_coming_soon'              => 'yes',
	'woocommerce_coming_soon_visibility'   => 'coming-soon',
);
$bsc_test_environment_type = 'local';
$bsc_test_cache_flushed    = false;
$bsc_test_actions          = array();
$bsc_test_nocache_headers  = false;

function add_action() {}
function add_filter() {}
function remove_action() {}
function remove_filter() {}
function do_action( $hook ) {
	global $bsc_test_actions;
	$bsc_test_actions[] = $hook;
}
function wp_cache_flush() {
	global $bsc_test_cache_flushed;
	$bsc_test_cache_flushed = true;
	return true;
}
function nocache_headers() {
	global $bsc_test_nocache_headers;
	$bsc_test_nocache_headers = true;
}
function wp_unslash( $value ) {
	return $value;
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function wp_get_environment_type() {
	global $bsc_test_environment_type;
	return $bsc_test_environment_type;
}
function get_option( $name, $default = false ) {
	global $bsc_test_options;
	return array_key_exists( $name, $bsc_test_options ) ? $bsc_test_options[ $name ] : $default;
}
function home_url() {
	return 'https://bsc.local/';
}

require_once dirname( __DIR__ ) . '/inc/performance/frontend-cleanup.php';
require_once dirname( __DIR__ ) . '/inc/routing/frontend-routing.php';

function bsc_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$_SERVER['REQUEST_URI'] = '/';

$no_cache_headers = bsc_disable_frontend_page_cache_headers( array( 'Content-Type' => 'text/html' ) );
bsc_assert_same( 'no-store, no-cache, must-revalidate, max-age=0', $no_cache_headers['Cache-Control'], 'Public HTML must disable browser and proxy page caches.' );
bsc_assert_same( '0', $no_cache_headers['X-Accel-Expires'], 'Public HTML must disable the WordOps FastCGI cache.' );

bsc_disable_frontend_page_cache();
bsc_assert_same( true, defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE, 'Public HTML must disable WordPress page-cache plugins.' );
bsc_assert_same( true, $bsc_test_nocache_headers, 'Public HTML must send WordPress no-cache headers.' );
$bsc_test_nocache_headers = false;

$_SERVER['HTTP_HOST'] = 'bsc.local';
bsc_assert_same( true, bsc_is_local_storefront_request(), 'bsc.local must bypass parking.' );
bsc_assert_same( true, bsc_is_parking_page_enabled(), 'The saved parking setting must remain enabled.' );
bsc_assert_same( 'no', bsc_allow_auth_routes_through_woocommerce_coming_soon( 'yes' ), 'WooCommerce coming soon must be disabled locally.' );
bsc_assert_same( 'live', bsc_allow_auth_routes_through_woocommerce_coming_soon_visibility( 'coming-soon' ), 'WooCommerce visibility must be live locally.' );

$_SERVER['HTTP_HOST'] = 'bsc.local:8443';
bsc_assert_same( true, bsc_is_local_storefront_request(), 'bsc.local with a port must bypass parking.' );

$bsc_test_environment_type = 'production';
bsc_assert_same( false, bsc_is_local_storefront_request(), 'Production must not bypass parking even with the bsc.local Host header.' );
$bsc_test_environment_type = 'local';

$_SERVER['HTTP_HOST'] = 'www.bsc.local';
bsc_assert_same( false, bsc_is_local_storefront_request(), 'Only the exact bsc.local host may bypass parking.' );

$_SERVER['HTTP_HOST'] = 'bubblesskincare.com';
bsc_assert_same( false, bsc_is_local_storefront_request(), 'Production hosts must not bypass parking.' );
bsc_assert_same( 'yes', bsc_allow_auth_routes_through_woocommerce_coming_soon( 'yes' ), 'Production coming soon must remain enabled.' );
bsc_assert_same( 'coming-soon', bsc_allow_auth_routes_through_woocommerce_coming_soon_visibility( 'coming-soon' ), 'Production visibility must remain coming soon.' );

bsc_purge_parking_page_cache();
bsc_assert_same( true, $bsc_test_cache_flushed, 'Changing parking mode must flush the WordPress cache.' );
bsc_assert_same( true, in_array( 'rt_nginx_helper_purge_all', $bsc_test_actions, true ), 'Changing parking mode must purge the WordOps Nginx cache.' );

bsc_send_parking_page_nocache_headers();
bsc_assert_same( true, $bsc_test_nocache_headers, 'The parking response must send no-cache headers.' );

fwrite( STDOUT, "Local parking routing test passed.\n" );
