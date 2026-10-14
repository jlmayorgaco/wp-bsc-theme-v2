<?php
/** Local, destructive fixture tests. Never run against a live store. */
if ( 'cli' !== PHP_SAPI || '1' !== getenv( 'BSC_LOCAL_INTEGRATION' ) ) {
	exit( 'Set BSC_LOCAL_INTEGRATION=1 in a local WordPress installation.' );
}
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'bsc.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	exit( 'Local site required.' );
}
add_filter( 'pre_wp_mail', '__return_true', PHP_INT_MAX );
add_filter( 'pre_http_request', static function () { return new WP_Error( 'test_network_blocked' ); }, PHP_INT_MAX );
// Disposable orders are removed below; render their blocked emails before cleanup.
add_filter( 'woocommerce_defer_transactional_emails', '__return_false', PHP_INT_MAX );
$failures = array();
$checks = 0;
function check_it( $condition, string $label ): void {
	global $checks, $failures;
	++$checks;
	echo ( $condition ? 'PASS ' : 'FAIL ' ) . $label . PHP_EOL;
	if ( ! $condition ) { $failures[] = $label; }
}
function stock_now( int $id ): int {
	wp_cache_delete( $id, 'post_meta' );
	return BSC_Stock::get_total_stock( $id );
}
$created = array();
$coupons_created = array();
$product = new WC_Product_Simple();
$product->set_name( 'BSC integration disposable fixture' );
$product->set_status( 'private' );
$product->set_regular_price( 10000 );
$product->save();
$pid = $product->get_id();
update_post_meta( $pid, '_stock_bodega', 3 );
update_post_meta( $pid, '_stock_tienda', 2 );
BSC_Stock::sync_stock_status( $pid );
$uid = wp_insert_user( array( 'user_login' => 'bsc-int-' . wp_generate_password( 10, false ), 'user_pass' => wp_generate_password( 30 ), 'user_email' => 'integration-' . time() . '@example.invalid', 'role' => 'customer' ) );
try {
	check_it( ! is_wp_error( $uid ), 'fixture customer created' );
	$order = wc_create_order( array( 'customer_id' => $uid ) );
	$created[] = $order->get_id();
	$order->set_payment_method( 'wompi' );
	$order->set_currency( 'COP' );
	$item_id = $order->add_product( wc_get_product( $pid ), 4 );
	$order->calculate_totals();
	$order->save();
	$id = $order->get_id();
	check_it( true === BSC_Order_Stock::allocate( $id, false ), 'reserve four units before payment' );
	check_it( 1 === stock_now( $pid ), 'reservation deducted exactly four' );
	BSC_Order_Stock::allocate( $id, false );
	check_it( 1 === stock_now( $pid ), 'duplicate reservation is idempotent' );
	BSC_Order_Stock::expire( $id );
	check_it( 1 === stock_now( $pid ), 'early expiry does not release active reservation' );
	$tx = (object) array( 'id' => 'qa-' . $id, 'reference' => (string) $id, 'amount_in_cents' => (int) round( (float) $order->get_total() * 100 ), 'currency' => 'COP', 'status' => 'APPROVED', 'customer_email' => 'payment@example.invalid' );
	check_it( true === bsc_wompi_reconcile_transaction( $tx ), 'verified Wompi approval completes payment' );
	$paid = wc_get_order( $id );
	check_it( (bool) $paid->get_date_paid(), 'payment date persists through HPOS' );
	check_it( 'qa-' . $id === $paid->get_transaction_id(), 'HPOS transaction id persists' );
	check_it( 1 === stock_now( $pid ), 'payment does not deduct reservation twice' );
	$balance = bsc_bp_get_balance( $uid );
	check_it( $balance > 0, 'payment awards points' );
	bsc_wompi_reconcile_transaction( $tx );
	bsc_bp_award_order_points( $id );
	check_it( $balance === bsc_bp_get_balance( $uid ), 'duplicate payment does not award points twice' );
	$paid->update_status( 'shipped' );
	$tx->status = 'DECLINED';
	bsc_wompi_reconcile_transaction( $tx );
	check_it( 'shipped' === wc_get_order( $id )->get_status(), 'late decline cannot regress shipped order' );
	$bad = clone $tx;
	$bad->amount_in_cents++;
	check_it( is_wp_error( bsc_wompi_reconcile_transaction( $bad ) ), 'wrong amount rejected' );
	$bad = clone $tx;
	$bad->currency = 'USD';
	check_it( is_wp_error( bsc_wompi_reconcile_transaction( $bad ) ), 'wrong currency rejected' );
	$args = array( 'order_id' => $id, 'amount' => 10000, 'reason' => 'Integration partial refund', 'refund_payment' => false, 'restock_items' => true, 'line_items' => array( $item_id => array( 'qty' => 1, 'refund_total' => 10000, 'refund_tax' => array() ) ) );
	$refund = wc_create_refund( $args );
	check_it( ! is_wp_error( $refund ), 'partial refund created' );
	check_it( 2 === stock_now( $pid ), 'partial refund restores exactly one unit' );
	BSC_Order_Stock::refund( $refund->get_id(), $args );
	check_it( 2 === stock_now( $pid ), 'repeated refund callback is idempotent' );
	$expected = (int) floor( $balance * ( (float) $paid->get_total() - 10000 ) / (float) $paid->get_total() );
	check_it( $expected === bsc_bp_get_balance( $uid ), 'partial refund reverses proportional points' );
	bsc_bp_reconcile_order_points( $id );
	check_it( $expected === bsc_bp_get_balance( $uid ), 'points reconciliation is idempotent' );
	$second = wc_create_order();
	$created[] = $second->get_id();
	$second->add_product( wc_get_product( $pid ), 2 );
	$second->calculate_totals();
	$second->save();
	BSC_Order_Stock::allocate( $second->get_id(), false );
	check_it( 0 === stock_now( $pid ), 'last units reserved' );
	$second->update_status( 'cancelled' );
	check_it( 2 === stock_now( $pid ), 'cancelled unpaid order restores reservation' );
	$second->update_status( 'pending' );
	BSC_Order_Stock::allocate( $second->get_id(), false );
	$second->update_status( 'cancelled' );
	check_it( 2 === stock_now( $pid ), 'same-second reservation cycle restores once per cycle' );
	$third = wc_create_order();
	$created[] = $third->get_id();
	$third->add_product( wc_get_product( $pid ), 5 );
	$third->calculate_totals();
	$third->save();
	check_it( is_wp_error( BSC_Order_Stock::allocate( $third->get_id(), false ) ), 'insufficient stock fails checkout reservation' );
	check_it( 2 === stock_now( $pid ), 'failed allocation does not alter stock' );
	BSC_Order_Stock::paid( $third->get_id() );
	$review = wc_get_order( $third->get_id() );
	check_it( 'on-hold' === $review->get_status() && $review->get_meta( '_bsc_stock_review_required' ), 'late paid order without stock is held for review' );
	check_it( ! bsc_wompi_valid_event( (object) array(), '', 'test' ), 'missing Wompi event secret fails closed' );
	$http = bsc_wompi_secure_http( array( 'sslverify' => false, 'redirection' => 5 ), 'https://sandbox.wompi.co/v1/transactions/test' );
	check_it( true === $http['sslverify'] && 0 === $http['redirection'], 'Wompi TLS verification forced' );
	check_it( -100 === has_action( 'woocommerce_api_wc_wompi', 'bsc_wompi_secure_webhook' ), 'security handler precedes vendor webhook' );
	$late = wc_create_order(); $created[] = $late->get_id();
	$late->set_payment_method( 'wompi' ); $late->set_currency( 'COP' );
	$late->add_product( wc_get_product( $pid ), 5 ); $late->calculate_totals(); $late->save();
	$late_tx = (object) array( 'id' => 'late-' . $late->get_id(), 'reference' => (string) $late->get_id(), 'amount_in_cents' => (int) round( (float) $late->get_total() * 100 ), 'currency' => 'COP', 'status' => 'APPROVED' );
	bsc_wompi_reconcile_transaction( $late_tx );
	$late = wc_get_order( $late->get_id() );
	check_it( $late->get_date_paid() && 'on-hold' === $late->get_status() && $late->get_meta( '_bsc_stock_review_required' ), 'actual late payment preserves review hold and payment date' );
	$before = stock_now( $pid );
	$rollback = BSC_Stock::with_product_lock( $pid, static function () use ( $pid ) {
		BSC_Stock::adjust_strict( $pid, 'bodega', 10, 'Rollback integration test' );
		return new WP_Error( 'simulated_failure' );
	} );
	check_it( is_wp_error( $rollback ) && stock_now( $pid ) === $before, 'transaction rolls back inventory after a downstream failure' );
	$secret = 'integration-only-event-secret';
	$event = (object) array( 'event' => 'transaction.updated', 'environment' => 'test', 'timestamp' => time(), 'data' => (object) array( 'transaction' => $tx ), 'signature' => (object) array( 'properties' => array( 'transaction.id', 'transaction.status', 'transaction.amount_in_cents' ) ) );
	$event->signature->checksum = hash( 'sha256', $tx->id . $tx->status . $tx->amount_in_cents . $event->timestamp . $secret );
	check_it( bsc_wompi_valid_event( $event, $secret, 'test' ), 'documented Wompi property signature accepted' );
	$event->data->transaction->amount_in_cents++;
	check_it( ! bsc_wompi_valid_event( $event, $secret, 'test' ), 'tampered Wompi amount rejected' );
	wp_set_current_user( $uid );
	$run = BSC_Growth_Skin_Quiz_Store::create_run( array( 'skin_type' => 'dry', 'needs' => array() ), array(), array(), 'normal' );
	$raw_run = BSC_Growth_Skin_Quiz_Store::get_run( $run );
	check_it( $run > 0 && $raw_run, 'private routine stored' );
	global $wpdb;
	$wpdb->update( BSC_Growth_Skin_Quiz_Store::runs_table(), array( 'expires_at' => '2000-01-01 00:00:00' ), array( 'id' => $run ) );
	check_it( null === BSC_Growth_Skin_Quiz_Store::get_run_by_token( $raw_run['share_token'] ), 'expired shared routine denied before cron cleanup' );
	BSC_Growth_Skin_Quiz_Store::delete_run( $run );
	check_it( null === BSC_Growth_Skin_Quiz_Store::get_run( $run ), 'expired routine erased successfully' );
	$private_method = new ReflectionMethod( BSC_Growth_Skin_Quiz_Store::class, 'private_storage_dir' );
	$path = $private_method->invoke( null );
	check_it( $path !== '' && ! str_starts_with( wp_normalize_path( $path ), wp_normalize_path( ABSPATH ) ), 'private directory resolves outside public root' );
	BSC_Stock::adjust_strict( $pid, 'tienda', 3, 'Showroom fixture' );
	$before = stock_now( $pid );
	$customer = array( 'name' => 'Integration Test', 'email' => '', 'phone' => '' );
	$request_id = wp_generate_uuid4();
	$sale = BSC_Order_Stock::showroom( array( array( 'id' => $pid, 'qty' => 1 ) ), 'cash', 'Efectivo', $customer, $request_id );
	check_it( $sale instanceof WC_Order && $sale->has_status( 'completed' ), 'showroom sale completes' );
	if ( $sale instanceof WC_Order ) {
		$created[] = $sale->get_id();
		check_it( stock_now( $pid ) === $before - 1, 'showroom deducts once' );
		$repeat = BSC_Order_Stock::showroom( array( array( 'id' => $pid, 'qty' => 1 ) ), 'cash', 'Efectivo', $customer, $request_id );
		check_it( $repeat instanceof WC_Order && $repeat->get_id() === $sale->get_id() && stock_now( $pid ) === $before - 1, 'showroom retry returns same HPOS order without deduction' );
		$sale->update_status( 'pending' );
		$repeat = BSC_Order_Stock::showroom( array( array( 'id' => $pid, 'qty' => 1 ) ), 'cash', 'Efectivo', $customer, $request_id );
		check_it( $repeat instanceof WC_Order && $repeat->has_status( 'completed' ) && stock_now( $pid ) === $before - 1, 'showroom recovers committed sale after interrupted completion' );
		$line_id = array_key_first( $sale->get_items() );
		$refund = wc_create_refund( array( 'order_id' => $sale->get_id(), 'amount' => $sale->get_total(), 'refund_payment' => false, 'restock_items' => true, 'line_items' => array( $line_id => array( 'qty' => 1, 'refund_total' => $sale->get_total(), 'refund_tax' => array() ) ) ) );
		check_it( ! is_wp_error( $refund ) && stock_now( $pid ) === $before, 'showroom refund restores the store location' );
	}
	$before = stock_now( $pid );
	$failed_id = wp_generate_uuid4();
	$failed = BSC_Order_Stock::showroom( array( array( 'id' => $pid, 'qty' => 1 ), array( 'id' => $pid, 'qty' => 999 ) ), 'cash', 'Efectivo', $customer, $failed_id );
	check_it( is_wp_error( $failed ) && stock_now( $pid ) === $before, 'showroom multi-line failure rolls back earlier deductions' );
	check_it( ! wc_get_orders( array( 'limit' => 1, 'meta_key' => '_bsc_showroom_request_id', 'meta_value' => $uid . ':' . $failed_id ) ), 'failed showroom transaction leaves no order' );
	$run = BSC_Growth_Skin_Quiz_Store::create_run( array( 'skin_type' => 'dry' ), array(), array(), 'normal' );
	update_user_meta( $uid, 'bsc_skin_type', 'dry' );
	$email = get_userdata( $uid )->user_email;
	$export = bsc_skin_quiz_privacy_export( $email );
	check_it( count( $export['data'] ) === 1 && ! str_contains( wp_json_encode( $export ), 'share_token' ), 'privacy export includes answers without access tokens' );
	$erased = bsc_skin_quiz_privacy_erase( $email );
	check_it( $erased['done'] && ! $erased['items_retained'] && ! get_user_meta( $uid, 'bsc_skin_type', true ) && ! BSC_Growth_Skin_Quiz_Store::get_run( $run ), 'privacy eraser removes routine and profile' );
	if ( ! defined( 'DOING_AJAX' ) ) { define( 'DOING_AJAX', true ); }
	require_once dirname( __DIR__ ) . '/plugins/bubble-points/ajax/redeem.php';
	add_filter( 'wp_die_ajax_handler', static function () { return static function () { throw new RuntimeException( 'test_ajax_response' ); }; } );
	$redeem = static function ( $points, $request_id = null ) use ( $uid ) {
		$_REQUEST['nonce'] = wp_create_nonce( 'bsc_redeem_points' ); $_POST['points'] = $points;
		$_POST['request_id'] = $request_id ?? wp_generate_uuid4();
		ob_start();
		try { bsc_bp_ajax_redeem_points(); } catch ( RuntimeException $e ) { if ( 'test_ajax_response' !== $e->getMessage() ) { throw $e; } }
		$json = json_decode( ob_get_clean(), true );
		BSC_DB_Lock::release( 'points-user:' . $uid ); wp_cache_delete( $uid, 'user_meta' );
		return $json;
	};
	bsc_bp_add_ledger_entry( $uid, 2000 - bsc_bp_get_balance( $uid ), 'integration_fixture' );
	$redeem_id = wp_generate_uuid4();
	$redemption = $redeem( 1470, $redeem_id );
	check_it( ! empty( $redemption['success'] ) && 110000 === $redemption['data']['coupon_value'] && 530 === bsc_bp_get_balance( $uid ), 'redemption uses configured tier value and atomically debits points' );
	if ( ! empty( $redemption['data']['coupon_code'] ) ) {
		$coupon = new WC_Coupon( $redemption['data']['coupon_code'] ); $coupons_created[] = $coupon->get_id();
		check_it( 1 === $coupon->get_usage_limit() && in_array( $email, $coupon->get_email_restrictions(), true ), 'redeemed coupon is single-use and customer restricted' );
		$repeat = $redeem( 1470, $redeem_id );
		check_it( ! empty( $repeat['success'] ) && strtoupper( $repeat['data']['coupon_code'] ) === strtoupper( $redemption['data']['coupon_code'] ) && 530 === bsc_bp_get_balance( $uid ), 'retry of same redemption returns same coupon without second debit' );
	}
	$insufficient = $redeem( 1470 );
	check_it( empty( $insufficient['success'] ) && 530 === bsc_bp_get_balance( $uid ), 'insufficient redemption leaves balance unchanged' );
	bsc_bp_add_ledger_entry( $uid, 1470, 'integration_fixture' );
	$fault = static function ( $check, $object_id, $key ) { return 'usage_limit' === $key && 'shop_coupon' === get_post_type( $object_id ) ? false : $check; };
	add_filter( 'add_post_metadata', $fault, 10, 3 );
	$failure = $redeem( 1470 ); remove_filter( 'add_post_metadata', $fault, 10 );
	check_it( empty( $failure['success'] ) && 2000 === bsc_bp_get_balance( $uid ), 'coupon configuration failure rolls back and preserves points' );
} finally {
	foreach ( $coupons_created as $coupon_id ) { wp_delete_post( $coupon_id, true ); }
	foreach ( $created as $order_id ) {
		wp_clear_scheduled_hook( 'bsc_expire_stock_reservation', array( $order_id ) );
		$o = wc_get_order( $order_id );
		if ( $o ) { foreach ( $o->get_refunds() as $r ) { $r->delete( true ); } $o->delete( true ); }
	}
	global $wpdb;
	if ( ! is_wp_error( $uid ) ) {
		$wpdb->delete( bsc_bp_table(), array( 'user_id' => $uid ) );
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $uid );
	}
	wp_delete_post( $pid, true );
}
echo wp_json_encode( array( 'checks' => $checks, 'failures' => $failures ) ) . PHP_EOL;
exit( $failures ? 1 : 0 );
