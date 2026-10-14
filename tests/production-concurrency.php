<?php
/** MySQL concurrency fixture, orchestrated by tools/test-production-concurrency.js. */
if ( PHP_SAPI !== 'cli' || getenv( 'BSC_LOCAL_INTEGRATION' ) !== '1' ) { exit( 1 ); }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== 'bsc.local' ) { exit( 1 ); }
add_filter( 'pre_wp_mail', '__return_true', PHP_INT_MAX );
add_filter( 'pre_http_request', static function () { return new WP_Error( 'test_network_blocked' ); }, PHP_INT_MAX );
$mode = $argv[1] ?? '';
$state = json_decode( getenv( 'BSC_TEST_STATE' ) ?: '{}', true );
if ( 'setup' === $mode ) {
	$p = new WC_Product_Simple(); $p->set_name( 'Disposable concurrency fixture' ); $p->set_status( 'private' ); $p->set_regular_price( 10000 ); $p->save();
	$errors = array();
	$matrix = bsc_sanitize_product_variant_matrix( array(
		array( 'color_name' => 'Rojo', 'regular_price' => '10000', 'stock_bodega' => 2, 'stock_tienda' => 0, 'enabled' => 1 ),
		array( 'color_name' => 'Azul', 'regular_price' => '10000', 'stock_bodega' => 2, 'stock_tienda' => 0, 'enabled' => 1 ),
	), $errors );
	BSC_Stock::save_variant_matrix( $p->get_id(), $matrix );
	$uid = wp_insert_user( array( 'user_login' => 'bsc-race-' . wp_generate_password( 10, false ), 'user_pass' => wp_generate_password( 30 ), 'user_email' => 'race-' . time() . '@example.invalid', 'role' => 'customer' ) );
	$o = wc_create_order( array( 'customer_id' => $uid ) ); $o->set_total( 10000 ); $o->save();
	echo wp_json_encode( array( 'product' => $p->get_id(), 'user' => $uid, 'order' => $o->get_id(), 'keys' => array_column( $matrix, 'key' ), 'revision' => BSC_Stock::revision( $p->get_id() ) ) ) . PHP_EOL;
} elseif ( 'worker' === $mode ) {
	$which = (int) ( $argv[2] ?? 0 );
	// Preload identical stale matrices before all workers are released together.
	BSC_Stock::get_stock( $state['product'] ); bsc_bp_get_balance( $state['user'] );
	while ( microtime( true ) < (float) $state['start'] ) { usleep( 10000 ); }
	$r = BSC_Stock::allocate_variant_and_deduct( $state['product'], $state['keys'][ $which % 2 ], 1, 'Concurrent integration' );
	$points = bsc_bp_add_ledger_entry( $state['user'], 10, 'order_complete', $state['order'] );
	echo wp_json_encode( array( 'stock' => ! is_wp_error( $r ), 'points' => $points['ok'] ) ) . PHP_EOL;
} elseif ( 'verify' === $mode ) {
	global $wpdb;
	$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . bsc_bp_table() . ' WHERE order_id = %d', $state['order'] ) );
	$stale = BSC_Stock::save_variant_matrix( $state['product'], bsc_get_product_saved_variant_matrix( $state['product'], true ), $state['revision'] );
	$result = array( 'remaining_stock' => BSC_Stock::get_total_stock( $state['product'] ), 'balance' => bsc_bp_get_balance( $state['user'] ), 'ledger_rows' => $count, 'stale_admin_rejected' => is_wp_error( $stale ) );
	echo wp_json_encode( $result ) . PHP_EOL;
} elseif ( 'cleanup' === $mode ) {
	global $wpdb;
	wp_delete_post( $state['product'], true );
	$o = wc_get_order( $state['order'] ); if ( $o ) { $o->delete( true ); }
	$wpdb->delete( bsc_bp_table(), array( 'user_id' => $state['user'] ) );
	require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $state['user'] );
}
