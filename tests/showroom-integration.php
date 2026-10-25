<?php
/** Real local Showroom coupon, account, points and inventory integration. */
if ( 'cli' !== PHP_SAPI || '1' !== getenv( 'BSC_LOCAL_INTEGRATION' ) ) { exit( 1 ); }
ob_start();
require_once dirname( __DIR__, 4 ) . '/wp-load.php';
require_once dirname( __DIR__ ) . '/admin/bsc-showroom-page.php';
if ( 'bsc.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) || ! preg_match( '/^(?:localhost|127\.0\.0\.1)(?::\d+)?$/D', DB_HOST ) ) { exit( 1 ); }
add_filter( 'action_scheduler_allow_async_request_runner', '__return_false', PHP_INT_MAX );
add_filter( 'woocommerce_defer_transactional_emails', '__return_false', PHP_INT_MAX );
add_filter( 'option_woocommerce_customer_new_account_settings', static fn() => array( 'enabled' => 'no' ) );
$mail = array();
add_filter( 'pre_wp_mail', static function ( $pre, $args ) { $GLOBALS['mail'][] = $args; return true; }, PHP_INT_MAX, 2 );
WC()->mailer();
remove_action( 'woocommerce_created_customer', array( 'WC_Emails', 'queue_transactional_email' ), 10 );
add_action( 'woocommerce_created_customer', array( 'WC_Emails', 'send_transactional_email' ), 10, 10 );
$orders = array();
$users = array();
$coupons = array();
$products = array();
$email = 'qa-showroom-' . strtolower( wp_generate_password( 12, false ) ) . '@example.invalid';
function showroom_check( bool $ok, string $label ): void {
	if ( ! $ok ) { throw new RuntimeException( $label ); }
	echo 'PASS ' . $label . PHP_EOL;
}
function showroom_order( $order ): WC_Order {
	if ( is_wp_error( $order ) ) { throw new RuntimeException( $order->get_error_message() ); }
	$GLOBALS['orders'][] = $order->get_id();
	return $order;
}
try {
	$product = new WC_Product_Simple();
	$product->set_name( 'QA Showroom integration' );
	$product->set_sku( 'qa-showroom-sku-' . strtolower( wp_generate_password( 12, false ) ) );
	$product->set_regular_price( 100000 );
	$product->set_tax_status( 'none' );
	$product->save();
	$products[] = $product->get_id();
	update_post_meta( $product->get_id(), '_stock_tienda', 3 );
	update_post_meta( $product->get_id(), '_stock_bodega', 0 );
	$coupon = new WC_Coupon();
	$coupon->set_code( 'qa-showroom-' . strtolower( wp_generate_password( 12, false ) ) );
	$coupon->set_discount_type( 'percent' );
	$coupon->set_amount( 10 );
	$coupon->set_email_restrictions( array( $email ) );
	$coupon->set_usage_limit_per_user( 1 );
	$coupon->save();
	$coupons[] = $coupon->get_id();
	$items = array( array( 'id' => $product->get_id(), 'qty' => 2 ) );
	$customer = array( 'name' => 'Prueba Showroom', 'email' => $email, 'phone' => '3001234567' );
	$quote = BSC_Order_Stock::showroom_quote( $items, $email, $coupon->get_code() );
	showroom_check( ! is_wp_error( $quote ) && 180000.0 === $quote['total'] && 20000.0 === $quote['discount'], 'Apply previews the real 10% discount: ' . wp_json_encode( $quote ) );
	$multiple = BSC_Order_Stock::showroom_quote( array_merge( $items, array( array( 'id' => $product->get_id(), 'qty' => 1 ) ) ), $email, $coupon->get_code() );
	showroom_check( ! is_wp_error( $multiple ) && 270000.0 === $multiple['total'], 'Coupon preview discounts every line in a multi-item order' );
	showroom_check( 0 === ( new WC_Coupon( $coupon->get_id() ) )->get_usage_count() && 3 === (int) get_post_meta( $product->get_id(), '_stock_tienda', true ) && ! email_exists( $email ), 'Preview consumes no coupon, stock or account' );
	showroom_check( is_wp_error( BSC_Order_Stock::showroom_quote( $items, 'wrong@example.invalid', $coupon->get_code() ) ), 'Rejects coupon for another email' );
	showroom_check( is_wp_error( BSC_Order_Stock::showroom_quote( $items, $email, 'missing-showroom-coupon' ) ), 'Rejects nonexistent coupon' );
	$request_id = wp_generate_uuid4();
	$order = showroom_order( BSC_Order_Stock::showroom( $items, 'efectivo', 'Efectivo', $customer, $request_id, $coupon->get_code() ) );
	$user_id = (int) email_exists( $email );
	if ( $user_id ) { $users[] = $user_id; }
	showroom_check( $user_id > 0 && $user_id === $order->get_customer_id(), 'Sale creates and links the customer account' );
	showroom_check( 180000.0 === (float) $order->get_total() && 20000.0 === (float) $order->get_discount_total(), 'Saved order matches the discounted preview' );
	showroom_check( 1 === (int) get_post_meta( $product->get_id(), '_stock_tienda', true ), 'Sale deducts store stock once' );
	showroom_check( bsc_bp_calculate_order_points( $order ) === bsc_bp_get_balance( $user_id ), 'Points are awarded on the discounted purchase' );
	$account_mail = array_values( array_filter( $mail, static fn( $message ) => str_contains( $message['subject'], 'Tu cuenta en Bubble Skin Care' ) ) );
	showroom_check( 1 === count( $account_mail ) && str_contains( $account_mail[0]['message'], 'Establecer mi contraseña' ), 'Sends one password setup email even with account emails disabled' );
	$balance = bsc_bp_get_balance( $user_id );
	$retry = showroom_order( BSC_Order_Stock::showroom( $items, 'efectivo', 'Efectivo', $customer, $request_id, $coupon->get_code() ) );
	showroom_check( $retry->get_id() === $order->get_id() && $balance === bsc_bp_get_balance( $user_id ) && 1 === (int) get_post_meta( $product->get_id(), '_stock_tienda', true ), 'Retry duplicates no order, inventory or points' );
	showroom_check( is_wp_error( BSC_Order_Stock::showroom_quote( $items, $email, $coupon->get_code() ) ), 'Enforces coupon usage per customer' );
	$failed_request = wp_generate_uuid4();
	$failed = BSC_Order_Stock::showroom( $items, 'efectivo', 'Efectivo', $customer, $failed_request, $coupon->get_code() );
	showroom_check( is_wp_error( $failed ) && 1 === (int) get_post_meta( $product->get_id(), '_stock_tienda', true ), 'Invalid coupon rolls back the stock deduction' );
	showroom_check( ! wc_get_orders( array( 'meta_key' => '_bsc_showroom_request_id', 'meta_value' => get_current_user_id() . ':' . $failed_request, 'limit' => 1 ) ), 'Invalid coupon leaves no saved sale' );
	$second = showroom_order( BSC_Order_Stock::showroom( $items, 'tarjeta', 'Tarjeta', array( 'name' => '', 'email' => $email, 'phone' => '' ), wp_generate_uuid4() ) );
	showroom_check( $user_id === $second->get_customer_id(), 'Email alone attaches another sale to the existing account' );
	showroom_check( $second->has_status( 'processing' ) && 0 === (int) get_post_meta( $product->get_id(), '_stock_tienda', true ), 'Shortage becomes an order in processing without negative stock' );
	$item = current( $second->get_items() );
	showroom_check( 1 === (int) $item->get_meta( 'Encargo' ) && 1 === (int) $item->get_meta( '_bsc_stock_tienda_qty' ), 'Tracks the ordered and allocated quantities separately' );
	$third = showroom_order( BSC_Order_Stock::showroom( array( array( 'id' => $product->get_id(), 'qty' => 1 ) ), 'efectivo', 'Efectivo', $customer, wp_generate_uuid4() ) );
	showroom_check( $third->has_status( 'processing' ) && 0 === (int) get_post_meta( $product->get_id(), '_stock_tienda', true ), 'Fully out-of-stock product can be bought as Encargo' );
	$third->update_status( 'cancelled' );
	showroom_check( 0 === (int) get_post_meta( $product->get_id(), '_stock_tienda', true ), 'Canceling Encargo does not invent stock' );
	$product = wc_get_product( $product->get_id() );
	$product->set_stock_status( 'outofstock' );
	$product->save();
	$search_ids = array_map( static fn( $match ) => $match->get_id(), bsc_showroom_find_products( $product->get_sku() ) );
	showroom_check( in_array( $product->get_id(), $search_ids, true ), 'Showroom SKU search finds a product with zero stock in both locations' );
	$coupon->set_usage_limit_per_user( 0 );
	$coupon->save();
	update_post_meta( $product->get_id(), '_bsc_variant_matrix', array( array( 'size_name' => '50 ml', 'regular_price' => '50000', 'sale_price' => '40000', 'stock_tienda' => 1, 'stock_bodega' => 0, 'enabled' => true ) ) );
	$variant = bsc_get_product_saved_variant_matrix( $product->get_id(), false )[0];
	$variant_items = array( array( 'id' => $product->get_id(), 'variant_key' => $variant['key'], 'qty' => 2 ) );
	$variant_quote = BSC_Order_Stock::showroom_quote( $variant_items, $email, $coupon->get_code() );
	showroom_check( ! is_wp_error( $variant_quote ) && 72000.0 === $variant_quote['total'], 'Variant quote uses its sale price and coupon' );
	$variant_order = showroom_order( BSC_Order_Stock::showroom( $variant_items, 'efectivo', 'Efectivo', $customer, wp_generate_uuid4(), $coupon->get_code() ) );
	showroom_check( 72000.0 === (float) $variant_order->get_total() && 0 === bsc_get_product_saved_variant_matrix( $product->get_id(), false )[0]['stock_tienda'], 'Variant sale matches preview and deducts only available variant stock' );
	showroom_check( is_wp_error( BSC_Order_Stock::showroom_quote( $items, $email, $coupon->get_code() ) ), 'Variant products require a valid variant in coupon previews' );

	// Reproduce sales saved by older code, before customer linking was enabled.
	remove_action( 'woocommerce_after_order_object_save', array( BSC_Order_Stock::class, 'sync_showroom_customer' ), 40 );
	$legacy = wc_create_order();
	$orders[] = $legacy->get_id();
	$legacy->update_meta_data( '_bsc_is_showroom_sale', '1' );
	$legacy->set_billing_email( $email );
	$legacy->set_total( 100000 );
	$legacy->set_date_paid( time() );
	$legacy->set_status( 'shipped' );
	$legacy->save();
	showroom_check( 0 === (int) wc_get_order( $legacy->get_id() )->get_customer_id(), 'Reproduces a paid legacy Showroom sale missing from the customer profile' );
	$refund = wc_create_refund( array( 'order_id' => $legacy->get_id(), 'amount' => 25000, 'refund_payment' => false, 'restock_items' => false ) );
	if ( is_wp_error( $refund ) ) { throw new RuntimeException( $refund->get_error_message() ); }
	$orders[] = $refund->get_id();
	$web_guest = wc_create_order();
	$orders[] = $web_guest->get_id();
	$web_guest->set_billing_email( $email );
	$web_guest->set_total( 100000 );
	$web_guest->set_status( 'completed' );
	$web_guest->save();
	$unpaid = wc_create_order();
	$orders[] = $unpaid->get_id();
	$unpaid->update_meta_data( '_bsc_is_showroom_sale', '1' );
	$unpaid->set_billing_email( $email );
	$unpaid->set_total( 100000 );
	$unpaid->save();
	add_action( 'woocommerce_after_order_object_save', array( BSC_Order_Stock::class, 'sync_showroom_customer' ), 40 );
	$balance = bsc_bp_get_balance( $user_id );
	$expected = (int) floor( bsc_bp_calculate_order_points( $legacy ) * 0.75 );
	BSC_Order_Stock::sync_showroom_customer_orders( $user_id );
	$repaired = wc_get_order( $legacy->get_id() );
	showroom_check( $user_id === $repaired->get_customer_id(), 'Profile repair links the older Showroom sale by its billing email' );
	showroom_check( in_array( $legacy->get_id(), wc_get_orders( array( 'customer_id' => $user_id, 'return' => 'ids', 'limit' => -1 ) ), true ), 'Repaired sale appears in the native account order history' );
	showroom_check( $balance + $expected === bsc_bp_get_balance( $user_id ), 'Older Showroom purchase credits the same points rule with refunds deducted' );
	BSC_Order_Stock::sync_showroom_customer_orders( $user_id );
	BSC_Order_Stock::sync_showroom_customer( $legacy->get_id() );
	showroom_check( $balance + $expected === bsc_bp_get_balance( $user_id ), 'Refreshing the profile and retrying cannot duplicate recovered points' );
	showroom_check( 0 === (int) wc_get_order( $web_guest->get_id() )->get_customer_id(), 'Web guest purchases still require email confirmation' );
	showroom_check( 0 === (int) wc_get_order( $unpaid->get_id() )->get_customer_id(), 'An unpaid Showroom draft is not completed by profile repair' );

	$balance = bsc_bp_get_balance( $user_id );
	$unpaid->set_status( 'completed' );
	$unpaid->save();
	showroom_check( $user_id === (int) wc_get_order( $unpaid->get_id() )->get_customer_id(), 'A later paid-status save links the customer before awarding points' );
	showroom_check( $balance + bsc_bp_calculate_order_points( $unpaid ) === bsc_bp_get_balance( $user_id ), 'Paid-status save credits points to that customer exactly once' );

	remove_action( 'woocommerce_after_order_object_save', array( BSC_Order_Stock::class, 'sync_showroom_customer' ), 40 );
	remove_action( 'woocommerce_order_status_changed', 'bsc_bp_award_order_points_on_status_change', 20 );
	$missing_points = wc_create_order( array( 'customer_id' => $user_id ) );
	$orders[] = $missing_points->get_id();
	$missing_points->update_meta_data( '_bsc_is_showroom_sale', '1' );
	$missing_points->set_billing_email( $email );
	$missing_points->set_total( 120000 );
	$missing_points->set_status( 'completed' );
	$missing_points->save();
	$refund = wc_create_refund( array( 'order_id' => $missing_points->get_id(), 'amount' => 30000, 'refund_payment' => false, 'restock_items' => false ) );
	if ( is_wp_error( $refund ) ) { throw new RuntimeException( $refund->get_error_message() ); }
	$orders[] = $refund->get_id();
	add_action( 'woocommerce_after_order_object_save', array( BSC_Order_Stock::class, 'sync_showroom_customer' ), 40 );
	add_action( 'woocommerce_order_status_changed', 'bsc_bp_award_order_points_on_status_change', 20, 4 );
	$balance = bsc_bp_get_balance( $user_id );
	global $wpdb;
	$fail_adjustment = static function ( $sql ) {
		return str_starts_with( $sql, 'INSERT INTO' ) && str_contains( $sql, 'bsc_points_ledger' ) && str_contains( $sql, "'order_reconcile'" ) ? 'INSERT INTO bsc_deliberate_missing_qa_table VALUES (1)' : $sql;
	};
	$suppressed = $wpdb->suppress_errors( true );
	add_filter( 'query', $fail_adjustment );
	try { $failure = BSC_Order_Stock::sync_showroom_customer( $missing_points->get_id() ); }
	finally { remove_filter( 'query', $fail_adjustment ); $wpdb->suppress_errors( $suppressed ); }
	showroom_check( is_wp_error( $failure ) && $balance === bsc_bp_get_balance( $user_id ), 'A points reconciliation failure rolls back the entire credit' );
	showroom_check( (bool) wp_next_scheduled( 'bsc_sync_showroom_customer', array( $missing_points->get_id() ) ), 'A failed Showroom credit is automatically scheduled for retry' );
	BSC_Order_Stock::sync_showroom_customer_orders( $user_id );
	$expected = (int) floor( bsc_bp_calculate_order_points( $missing_points ) * 0.75 );
	showroom_check( $balance + $expected === bsc_bp_get_balance( $user_id ), 'An already linked paid order recovers its missing net points on retry' );
	BSC_Order_Stock::sync_showroom_customer( $missing_points->get_id() );
	showroom_check( $balance + $expected === bsc_bp_get_balance( $user_id ), 'Retry after successful repair does not award the same points twice' );
} finally {
	global $wpdb;
	foreach ( array_unique( $orders ) as $order_id ) {
		wp_clear_scheduled_hook( 'bsc_bp_reconcile_order_points', array( $order_id ) );
		wp_clear_scheduled_hook( 'bsc_sync_showroom_customer', array( $order_id ) );
		$wpdb->delete( bsc_bp_table(), array( 'order_id' => $order_id ) );
		$order = wc_get_order( $order_id );
		if ( $order ) { $order->delete( true ); }
	}
	foreach ( $products as $id ) { wc_get_product( $id )->delete( true ); }
	foreach ( $coupons as $id ) { ( new WC_Coupon( $id ) )->delete( true ); }
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $users as $id ) { wp_delete_user( $id ); }
	ob_end_flush();
}
