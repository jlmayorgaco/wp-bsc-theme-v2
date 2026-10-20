<?php
/**
 * Wompi regression using real local WooCommerce/HPOS, inventory, points and queues.
 * The authoritative API is intercepted; no transaction is created at Wompi.
 * Run only through a local bootstrap with BSC_LOCAL_INTEGRATION=1.
 */
if ( 'cli' !== PHP_SAPI || '1' !== getenv( 'BSC_LOCAL_INTEGRATION' ) ) { exit( 1 ); }
require_once dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'bsc.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) || ! preg_match( '/^(?:localhost|127\.0\.0\.1)(?::\d+)?$/D', DB_HOST ) ) { exit( 1 ); }

add_filter( 'pre_wp_mail', '__return_true', PHP_INT_MAX );
add_filter( 'action_scheduler_allow_async_request_runner', '__return_false', PHP_INT_MAX );
add_filter( 'woocommerce_defer_transactional_emails', '__return_false', PHP_INT_MAX );
$qa_orders = array();
$qa_transactions = array();
$qa_api_calls = 0;
$qa_api_fail = false;
$qa_checks = 0;
$qa_settings = Wompi_Portal_Pagos_Main::$settings;
$qa_original_query = $GLOBALS['wp_query'] ?? null;
$qa_original_get = $_GET;
$qa_secret = 'local-only-integration-' . wp_generate_password( 20, false );
Wompi_Portal_Pagos_Main::$settings['testmode'] = 'yes';
Wompi_Portal_Pagos_Main::$settings['test_event_secret_key'] = $qa_secret;

function bsc_qa_check( bool $passed, string $message ): void {
	++$GLOBALS['qa_checks'];
	if ( ! $passed ) { throw new RuntimeException( $message ); }
	echo 'PASS ' . $message . PHP_EOL;
}
function bsc_qa_stock(): int {
	wp_cache_delete( $GLOBALS['qa_product_id'], 'post_meta' );
	return BSC_Stock::get_total_stock( $GLOBALS['qa_product_id'] );
}
function bsc_qa_order(): WC_Order {
	$order = wc_create_order( array( 'customer_id' => $GLOBALS['qa_user_id'] ) );
	if ( is_wp_error( $order ) ) { throw new RuntimeException( 'Could not create disposable order.' ); }
	$GLOBALS['qa_orders'][] = $order->get_id();
	$order->set_payment_method( 'wompi' );
	$order->set_currency( 'COP' );
	$order->set_billing_email( 'local-qa@example.invalid' );
	$order->update_meta_data( '_bsc_local_payment_qa', '1' );
	$order->add_product( wc_get_product( $GLOBALS['qa_product_id'] ), 1 );
	$order->calculate_totals();
	$order->save();
	$reserved = BSC_Order_Stock::allocate( $order->get_id(), false );
	if ( is_wp_error( $reserved ) ) { throw new RuntimeException( 'Disposable stock reservation failed.' ); }
	return wc_get_order( $order->get_id() );
}
function bsc_qa_transaction( WC_Order $order, string $status = 'APPROVED', string $suffix = '' ): object {
	$id = 'local-qa-' . $order->get_id() . $suffix;
	$tx = (object) array( 'id' => $id, 'reference' => (string) $order->get_id(), 'status' => $status, 'amount_in_cents' => (int) round( (float) $order->get_total() * 100 ), 'currency' => 'COP', 'payment_method_type' => 'NEQUI' );
	$GLOBALS['qa_transactions'][ $id ] = $tx;
	return $tx;
}
function bsc_qa_event( object $tx ): string {
	$event = (object) array( 'event' => 'transaction.updated', 'environment' => 'test', 'timestamp' => time(), 'data' => (object) array( 'transaction' => $tx ), 'signature' => (object) array( 'properties' => array( 'transaction.id', 'transaction.status', 'transaction.amount_in_cents' ) ) );
	$event->signature->checksum = hash( 'sha256', $tx->id . $tx->status . $tx->amount_in_cents . $event->timestamp . $GLOBALS['qa_secret'] );
	return wp_json_encode( $event );
}
function bsc_qa_verify_note_count( int $id ): int {
	$notes = wc_get_order_notes( array( 'order_id' => $id, 'limit' => 100 ) );
	return count( array_filter( $notes, static function ( $note ) { return str_contains( $note->content, 'Pago Wompi verificado.' ); } ) );
}
function bsc_qa_fake_api( $pre, $args, $url ) {
	if ( 'sandbox.wompi.co' !== wp_parse_url( $url, PHP_URL_HOST ) || ! preg_match( '{^/v1/transactions/(local-qa-[a-zA-Z0-9_-]+)$}D', (string) wp_parse_url( $url, PHP_URL_PATH ), $match ) ) { return $pre; }
	++$GLOBALS['qa_api_calls'];
	if ( 'GET' !== $args['method'] || empty( $args['sslverify'] ) || ! str_starts_with( $args['headers']['Authorization'] ?? '', 'Bearer prv_test_' ) ) { throw new RuntimeException( 'Expected a verified read-only Sandbox request.' ); }
	if ( $GLOBALS['qa_api_fail'] ) { return new WP_Error( 'qa_api_unavailable' ); }
	return array( 'headers' => array(), 'body' => wp_json_encode( array( 'data' => $GLOBALS['qa_transactions'][ $match[1] ] ?? null ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
}
add_filter( 'pre_http_request', 'bsc_qa_fake_api', PHP_INT_MAX, 3 );

$product = new WC_Product_Simple();
$product->set_name( 'Disposable Wompi integration QA' );
$product->set_status( 'private' );
$product->set_regular_price( 10000 );
$product->update_meta_data( '_bsc_local_payment_qa', '1' );
$product->save();
$qa_product_id = $product->get_id();
update_post_meta( $qa_product_id, '_stock_bodega', 40 );
update_post_meta( $qa_product_id, '_stock_tienda', 0 );
BSC_Stock::sync_stock_status( $qa_product_id );
$qa_user_id = wp_insert_user( array( 'user_login' => 'payment-qa-' . wp_generate_password( 10, false ), 'user_pass' => wp_generate_password( 32 ), 'user_email' => 'payment-qa-' . time() . '@example.invalid', 'role' => 'customer' ) );
if ( is_wp_error( $qa_user_id ) ) { wp_delete_post( $qa_product_id, true ); throw new RuntimeException( 'Could not create disposable customer.' ); }

try {
	$_GET = array(); // No browser return participates in webhook tests.
	$order = bsc_qa_order();
	$tx = bsc_qa_transaction( $order );
	$stock = bsc_qa_stock();
	bsc_qa_check( 200 === bsc_wompi_process_webhook( 'POST', bsc_qa_event( $tx ) ), 'Backend event completes a real HPOS order without browser return' );
	$paid = wc_get_order( $order->get_id() );
	bsc_qa_check( $paid->is_paid() && $paid->get_date_paid() && $paid->get_transaction_id() === $tx->id && $paid->get_meta( '_bsc_wompi_approved_id' ) === $tx->id, 'Payment state, date, ID and verification marker persist in MySQL' );
	$points = bsc_bp_get_balance( $qa_user_id );
	bsc_qa_check( $points > 0 && bsc_qa_stock() === $stock, 'Payment awards points and keeps the existing stock deduction' );
	bsc_qa_check( 200 === bsc_wompi_process_webhook( 'POST', bsc_qa_event( $tx ) ) && bsc_qa_stock() === $stock && bsc_bp_get_balance( $qa_user_id ) === $points && 1 === bsc_qa_verify_note_count( $order->get_id() ), 'Duplicate event repeats neither stock, points nor verification notes' );
	$mail_count = 0;
	$mail_counter = static function ( $pre ) use ( &$mail_count ) { ++$mail_count; return true; };
	add_filter( 'pre_wp_mail', $mail_counter, PHP_INT_MAX );
	bsc_order_email_job( $order->get_id(), 'processing' );
	$first_mail_count = $mail_count;
	bsc_order_email_job( $order->get_id(), 'processing' );
	remove_filter( 'pre_wp_mail', $mail_counter, PHP_INT_MAX );
	bsc_qa_check( 1 === $first_mail_count && $mail_count === $first_mail_count, 'Real email dispatcher sends once; transport is intercepted' );

	$conflict = bsc_qa_transaction( $paid, 'APPROVED', '-second' );
	bsc_qa_check( 200 === bsc_wompi_process_webhook( 'POST', bsc_qa_event( $conflict ) ) && wc_get_order( $paid->get_id() )->get_transaction_id() === $tx->id && 'multiple_approved_transactions' === wc_get_order( $paid->get_id() )->get_meta( '_bsc_payment_review_required' ), 'Conflicting approval preserves the accepted transaction for review' );

	$order = bsc_qa_order(); $tx = bsc_qa_transaction( $order );
	$bad = json_decode( bsc_qa_event( $tx ) ); $bad->signature->checksum = str_repeat( '0', 64 );
	$calls = $qa_api_calls;
	bsc_qa_check( 400 === bsc_wompi_process_webhook( 'POST', wp_json_encode( $bad ) ) && $calls === $qa_api_calls && ! wc_get_order( $order->get_id() )->get_date_paid(), 'Invalid signature never reaches the API or marks the order paid' );
	$qa_api_fail = true;
	bsc_qa_check( 503 === bsc_wompi_process_webhook( 'POST', bsc_qa_event( $tx ) ) && ! wc_get_order( $order->get_id() )->get_meta( '_bsc_wompi_approved_id' ), 'A real WordPress HTTP failure returns 503 without recording approval' );
	$retry_args = array( $tx->id, 0, 0, 'test' );
	bsc_qa_check( as_has_scheduled_action( 'bsc_wompi_retry_transaction', $retry_args, 'bsc-wompi' ), 'Retry is durable in the real Action Scheduler database' );
	$qa_api_fail = false;
	bsc_wompi_retry_transaction( ...$retry_args );
	bsc_qa_check( wc_get_order( $order->get_id() )->is_paid(), 'Background worker recovers payment without a browser' );

	$order = bsc_qa_order(); $tx = bsc_qa_transaction( $order );
	$failing_order_id = $order->get_id();
	$fault = static function ( $id ) use ( $failing_order_id ) { if ( $id === $failing_order_id ) { throw new RuntimeException( 'Synthetic local persistence failure' ); } };
	add_action( 'woocommerce_pre_payment_complete', $fault, PHP_INT_MAX );
	try { $result = bsc_wompi_process_webhook( 'POST', bsc_qa_event( $tx ) ); } finally { remove_action( 'woocommerce_pre_payment_complete', $fault, PHP_INT_MAX ); }
	bsc_qa_check( 503 === $result && ! wc_get_order( $order->get_id() )->get_meta( '_bsc_wompi_approved_id' ), 'Actual WooCommerce payment_complete exception is not acknowledged as success' );
	bsc_qa_check( 200 === bsc_wompi_process_webhook( 'POST', bsc_qa_event( $tx ) ) && wc_get_order( $order->get_id() )->is_paid(), 'Retry completes payment after a WooCommerce exception' );

	$order = bsc_qa_order(); $tx = bsc_qa_transaction( $order );
	$order->update_status( 'processing' ); $order = wc_get_order( $order->get_id() );
	$order->update_status( 'shipped' );
	$stock = bsc_qa_stock();
	bsc_qa_check( 200 === bsc_wompi_process_webhook( 'POST', bsc_qa_event( $tx ) ), 'Late approval can attach to a manually fulfilled order' );
	$late = wc_get_order( $order->get_id() );
	bsc_qa_check( 'shipped' === $late->get_status() && $tx->id === $late->get_transaction_id() && $stock === bsc_qa_stock(), 'Late verification preserves shipment and does not deduct stock again' );

	$order = bsc_qa_order(); $tx = bsc_qa_transaction( $order );
	$order->update_status( 'cancelled' );
	$stock = bsc_qa_stock();
	bsc_qa_check( 200 === bsc_wompi_process_webhook( 'POST', bsc_qa_event( $tx ) ) && wc_get_order( $order->get_id() )->is_paid() && bsc_qa_stock() === $stock - 1, 'A late valid payment recovers a cancelled order and allocates stock once' );

	$order = bsc_qa_order(); $tx = bsc_qa_transaction( $order, 'PENDING' );
	bsc_qa_check( 200 === bsc_wompi_process_webhook( 'POST', bsc_qa_event( $tx ) ) && ! wc_get_order( $order->get_id() )->get_date_paid(), 'Pending API state does not imply payment' );
	$tx->status = 'APPROVED';
	bsc_wompi_retry_transaction( $tx->id, $order->get_id(), 0, 'test' );
	bsc_qa_check( wc_get_order( $order->get_id() )->is_paid(), 'Pending-to-approved transition is recovered by the worker' );

	$order = bsc_qa_order(); $tx = bsc_qa_transaction( $order );
	$GLOBALS['wp_query'] = new WP_Query();
	$GLOBALS['wp_query']->set( 'order-received', $order->get_id() );
	$_GET = array( 'id' => $tx->id, 'key' => $order->get_order_key() );
	bsc_maybe_complete_wompi_return();
	bsc_qa_check( wc_get_order( $order->get_id() )->is_paid(), 'Native return with id/key works without the custom marker in real WordPress' );
} finally {
	remove_filter( 'pre_http_request', 'bsc_qa_fake_api', PHP_INT_MAX );
	Wompi_Portal_Pagos_Main::$settings = $qa_settings;
	$GLOBALS['wp_query'] = $qa_original_query;
	$_GET = $qa_original_get;
	// Cancel only actions created for disposable fixture orders/transactions.
	foreach ( array( 'bsc_order_email_job', 'bsc_wompi_retry_transaction' ) as $hook ) {
		$actions = as_get_scheduled_actions( array( 'hook' => $hook, 'status' => ActionScheduler_Store::STATUS_PENDING, 'per_page' => -1 ), 'OBJECT' );
		foreach ( $actions as $action ) {
			$args = array_values( $action->get_args() );
			$belongs = 'bsc_order_email_job' === $hook ? in_array( (int) ( $args[0] ?? 0 ), $qa_orders, true ) : isset( $qa_transactions[ $args[0] ?? '' ] );
			if ( $belongs ) { as_unschedule_all_actions( $hook, $action->get_args(), $action->get_group() ); }
		}
	}
	foreach ( $qa_orders as $id ) {
		wp_clear_scheduled_hook( 'bsc_expire_stock_reservation', array( $id ) );
		$order = wc_get_order( $id );
		if ( $order && '1' === $order->get_meta( '_bsc_local_payment_qa' ) ) { $order->delete( true ); }
	}
	global $wpdb;
	$wpdb->delete( bsc_bp_table(), array( 'user_id' => $qa_user_id ) );
	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $qa_user_id );
	wp_delete_post( $qa_product_id, true );
}
echo wp_json_encode( array( 'checks' => $qa_checks, 'failures' => array(), 'woocommerce' => WC_VERSION, 'api' => 'intercepted Sandbox GETs', 'storage' => 'real local MySQL/HPOS', 'fixture_cleanup' => 'complete' ) ) . PHP_EOL;
