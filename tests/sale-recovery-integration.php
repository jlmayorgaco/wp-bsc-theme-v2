<?php
/** Disposable integration checks against Local only. No real mail or gateway calls. */
if ( 'cli' !== PHP_SAPI || '1' !== getenv( 'BSC_LOCAL_INTEGRATION' ) ) { exit( 'Local test opt-in required.' ); }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'bsc.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { exit( 'Only bsc.local is allowed.' ); }
add_filter( 'pre_http_request', static function () { return new WP_Error( 'test_network_disabled' ); }, PHP_INT_MAX );
$mail_result = true;
$attachment = '';
add_filter( 'pre_wp_mail', static function ( $result, $args ) use ( &$mail_result, &$attachment ) {
	foreach ( (array) $args['attachments'] as $file ) { $attachment = file_get_contents( $file ); }
	return $mail_result;
}, PHP_INT_MAX, 2 );
add_filter( 'woocommerce_defer_transactional_emails', '__return_false', PHP_INT_MAX );
$saved = get_option( BSC_Sale_Backup::OPTION, null );
$settings = array( 'enabled' => true, 'recipient' => 'sale-recovery@example.invalid', 'key' => base64_encode( random_bytes( 32 ) ), 'site_id' => wp_generate_uuid4() );
BSC_Sale_Backup::install();
update_option( BSC_Sale_Backup::OPTION, $settings, false );
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( $admins[0] );
$checks = 0;
$failures = array();
$orders = array();
$jobs = array();
$users = array();
$product = new WC_Product_Simple();
$product->set_name( 'BSC recovery disposable product' );
$product->set_slug( 'bsc-recovery-' . wp_generate_uuid4() );
$product->set_regular_price( 10000 );
$product->set_sku( 'BSC-RECOVERY-' . wp_generate_uuid4() );
$product->set_status( 'private' );
$pid = $product->save();
update_post_meta( $pid, '_stock_bodega', 20 );
update_post_meta( $pid, '_stock_tienda', 5 );
$email = 'recovery-' . wp_generate_uuid4() . '@example.invalid';
$uid = wp_insert_user( array( 'user_login' => 'recovery-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password( 40 ), 'user_email' => $email, 'role' => 'customer' ) );
$users[] = $uid;
function recovery_check( $condition, string $message ): void {
	global $checks, $failures;
	++$checks;
	echo ( $condition ? 'PASS ' : 'FAIL ' ) . $message . PHP_EOL;
	if ( ! $condition ) { $failures[] = $message; }
}
function recovery_unique( array $data ): array {
	$data['order_uuid'] = wp_generate_uuid4();
	$data['order']['order_key'] = wc_generate_order_key();
	$data['order']['transaction_id'] = 'test-' . wp_generate_uuid4();
	return $data;
}
try {
	$order = new WC_Order();
	$order->set_customer_id( $uid );
	$order->set_billing_email( $email );
	$order->set_billing_first_name( 'Prueba' );
	$order->set_shipping_address_1( 'Calle de prueba 123' );
	$order->set_shipping_city( 'Bogotá' );
	$order->set_currency( 'COP' );
	$order->set_payment_method( 'wompi' );
	$order->set_customer_note( 'Conservar esta nota' );
	$order->save();
	$source_id = $order->get_id();
	$orders[] = $source_id;
	$order->add_product( $product, 2 );
	$shipping = new WC_Order_Item_Shipping();
	$shipping->set_method_title( 'Envío de prueba' ); $shipping->set_method_id( 'flat_rate' ); $shipping->set_total( 5000 ); $order->add_item( $shipping );
	$order->calculate_totals(); $order->save();
	recovery_check( 0 === BSC_Sale_Backup::capture( $source_id ), 'unpaid order is not backed up' );
	BSC_Order_Stock::allocate( $source_id, false );
	$order = wc_get_order( $source_id );
	$order->payment_complete( 'test-paid-' . wp_generate_uuid4() );
	$order = wc_get_order( $source_id );
	$order->update_meta_data( 'payment_token', 'must-never-export' ); $order->save_meta_data();
	BSC_Sale_Backup::flush();
	$job = BSC_Sale_Backup::capture( $source_id );
	$jobs[] = $job;
	recovery_check( is_int( $job ) && $job > 0, 'payment hook creates a durable snapshot' );
	recovery_check( $job === BSC_Sale_Backup::capture( $source_id ), 'unchanged snapshot is deduplicated' );
	$table = BSC_Sale_Backup::table();
	$encrypted = $wpdb->get_var( $wpdb->prepare( "SELECT payload FROM $table WHERE id = %d", $job ) );
	recovery_check( ! str_contains( $encrypted, $email ) && ! str_contains( $encrypted, 'Calle' ), 'outbox contains encrypted customer data' );
	$data = BSC_Sale_Backup::decrypt( $encrypted, $settings['key'] );
	if ( is_wp_error( $data ) ) { throw new RuntimeException( $data->get_error_message() ); }
	recovery_check( ! str_contains( wp_json_encode( $data ), 'must-never-export' ), 'payment tokens are excluded' );
	recovery_check( $data['points'] && 5000.0 === (float) $data['order']['shipping_total'], 'snapshot contains points and shipping' );
	recovery_check( is_wp_error( BSC_Sale_Backup::decrypt( $encrypted, base64_encode( random_bytes( 32 ) ) ) ), 'wrong recovery key rejected' );
	$corrupt = json_decode( $encrypted, true ); $corrupt['tag'] = base64_encode( random_bytes( 16 ) );
	recovery_check( is_wp_error( BSC_Sale_Backup::decrypt( wp_json_encode( $corrupt ), $settings['key'] ) ), 'tampered attachment rejected' );
	$mail_result = false;
	BSC_Sale_Backup::send( $job );
	$state = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $job ) );
	recovery_check( 'pending' === $state->state && 1 === (int) $state->attempts, 'SMTP failure remains retryable' );
	$mail_result = true;
	BSC_Sale_Backup::send( $job );
	$state = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $job ) );
	recovery_check( 'sent' === $state->state && $attachment === $encrypted, 'SMTP receives the encrypted attachment' );
	$exists = BSC_Sale_Recovery::restore( $data );
	recovery_check( ! is_wp_error( $exists ) && $exists['already_exists'] && $source_id === $exists['id'], 'live original sale cannot be duplicated' );
	wp_set_current_user( $uid );
	recovery_check( is_wp_error( BSC_Sale_Recovery::restore( $data ) ), 'customer cannot import a sale' );
	wp_set_current_user( $admins[0] );
	$before = bsc_bp_get_balance( $uid );
	$order->delete( true );
	$result = BSC_Sale_Recovery::restore( $data );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	$orders[] = $result['id'];
	$restored = wc_get_order( $result['id'] );
	recovery_check( $restored->get_customer_id() === $uid, 'recovered sale is linked to the account by email' );
	recovery_check( $restored->get_transaction_id() === $data['order']['transaction_id'] && $restored->get_total() === $data['order']['total'], 'original payment reference and total preserved' );
	recovery_check( $restored->get_date_paid()->getTimestamp() === $data['order']['date_paid'] && $restored->get_shipping_address_1() === 'Calle de prueba 123', 'payment date and shipping address preserved' );
	recovery_check( $before === bsc_bp_get_balance( $uid ), 'surviving points are linked without awarding again' );
	recovery_check( 23 === BSC_Stock::get_total_stock( $pid ), 'history-only recovery does not deduct inventory' );
	$again = BSC_Sale_Recovery::restore( $data, true );
	recovery_check( ! is_wp_error( $again ) && $again['already_exists'] && $again['id'] === $result['id'] && $before === bsc_bp_get_balance( $uid ), 'repeated import is idempotent for order, points and stock' );
	$restored->delete( true );
	$wpdb->delete( bsc_bp_table(), array( 'user_id' => $uid ) ); bsc_bp_set_balance( $uid, 0 );
	update_post_meta( $pid, '_stock_bodega', 20 ); update_post_meta( $pid, '_stock_tienda', 5 );
	$result = BSC_Sale_Recovery::restore( $data, true );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	$orders[] = $result['id'];
	recovery_check( $before === bsc_bp_get_balance( $uid ) && 23 === BSC_Stock::get_total_stock( $pid ), 'lost points and exact location stock recover once after rollback' );
	$refundable = wc_get_order( $result['id'] );
	$original_item = current( $refundable->get_items() );
	$refund = wc_create_refund( array( 'order_id' => $refundable->get_id(), 'amount' => 10000, 'reason' => 'Prueba de recuperación parcial', 'refund_payment' => false, 'restock_items' => true, 'line_items' => array( $original_item->get_id() => array( 'qty' => 1, 'refund_total' => 10000, 'refund_tax' => array() ) ) ) );
	if ( is_wp_error( $refund ) ) { throw new RuntimeException( $refund->get_error_message() ); }
	$refund_job = BSC_Sale_Backup::capture( $refundable->get_id() ); $jobs[] = $refund_job;
	$refunded_data = BSC_Sale_Backup::decrypt( $wpdb->get_var( $wpdb->prepare( "SELECT payload FROM $table WHERE id = %d", $refund_job ) ), $settings['key'] );
	if ( is_wp_error( $refunded_data ) ) { throw new RuntimeException( $refunded_data->get_error_message() ); }
	recovery_check( 1 === count( $refunded_data['refunds'] ) && count( $refunded_data['points'] ) > 1, 'refund snapshot includes refund lines and reversed points' );
	$refund_balance = bsc_bp_get_balance( $uid );
	$refundable->delete( true );
	$result = BSC_Sale_Recovery::restore( $refunded_data );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	$orders[] = $result['id']; $refundable = wc_get_order( $result['id'] );
	$refund_item = current( $refundable->get_items() );
	recovery_check( 10000.0 === (float) $refundable->get_total_refunded() && -1 === (int) $refundable->get_qty_refunded_for_item( $refund_item->get_id() ), 'restored refund uses new line IDs and original amount' );
	recovery_check( $refund_balance === bsc_bp_get_balance( $uid ), 'refund recovery does not reverse points twice' );
	$malformed = $data; unset( $malformed['points'] );
	recovery_check( is_wp_error( BSC_Sale_Backup::decrypt( BSC_Sale_Backup::encrypt( $malformed, $settings['key'] ), $settings['key'] ) ), 'authenticated but incomplete payload is rejected before import' );
	$bad = recovery_unique( $data );
	$bad['customer']['email'] = 'rollback-' . wp_generate_uuid4() . '@example.invalid';
	update_post_meta( $pid, '_stock_bodega', 0 ); update_post_meta( $pid, '_stock_tienda', 0 );
	$failed = BSC_Sale_Recovery::restore( $bad, true );
	recovery_check( is_wp_error( $failed ) && ! BSC_Sale_Recovery::existing( $bad ) && ! get_user_by( 'email', $bad['customer']['email'] ), 'stock failure rolls back both order and new customer' );
	$missing = recovery_unique( $data );
	$missing['points'] = array();
	$missing['meta'] = array();
	$missing['customer']['email'] = 'new-' . wp_generate_uuid4() . '@example.invalid';
	$missing['items'][0]['product']['sku'] = 'missing'; $missing['items'][0]['product']['slug'] = 'missing';
	$result = BSC_Sale_Recovery::restore( $missing );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	$orders[] = $result['id']; $new_order = wc_get_order( $result['id'] ); $users[] = $new_order->get_customer_id();
	$first = current( $new_order->get_items() );
	recovery_check( 0 === $first->get_product_id() && 'BSC recovery disposable product' === $first->get_name(), 'missing catalog product is preserved as historical line' );
	recovery_check( in_array( 'customer', get_userdata( $new_order->get_customer_id() )->roles, true ), 'missing account is created with customer permissions only' );
	$fixture_dir = getenv( 'BSC_RECOVERY_FIXTURE_DIR' );
	if ( $fixture_dir && is_dir( $fixture_dir ) ) {
		$ui = recovery_unique( $missing );
		$ui['customer']['email'] = 'ui-' . wp_generate_uuid4() . '@example.invalid';
		file_put_contents( $fixture_dir . '/sale.bsc-sale', BSC_Sale_Backup::encrypt( $ui, $settings['key'] ) );
		file_put_contents( $fixture_dir . '/key.bsc-key', wp_json_encode( array( 'format' => 'bsc-recovery-key', 'version' => 1, 'site_id' => $settings['site_id'], 'key' => $settings['key'] ) ) );
		file_put_contents( $fixture_dir . '/fixture.json', wp_json_encode( array( 'email' => $ui['customer']['email'], 'order_uuid' => $ui['order_uuid'] ) ) );
	}
} catch ( Throwable $error ) {
	recovery_check( false, $error->getMessage() );
} finally {
	BSC_Sale_Backup::$restoring = true;
	foreach ( array_unique( $orders ) as $id ) {
		$order = wc_get_order( $id ); if ( $order ) { $order->delete( true ); }
		foreach ( as_get_scheduled_actions( array( 'hook' => 'bsc_order_email_job', 'status' => 'pending', 'per_page' => 1000 ) ) as $action ) {
			$args = $action->get_args();
			if ( (int) ( $args[0] ?? 0 ) === $id ) { as_unschedule_all_actions( 'bsc_order_email_job', $args, 'bsc-email' ); }
		}
	}
	foreach ( $jobs as $id ) { if ( is_int( $id ) ) { as_unschedule_all_actions( 'bsc_sale_backup_send', array( $id ), 'bsc-sale-backup' ); } }
	foreach ( $orders as $id ) { $wpdb->delete( BSC_Sale_Backup::table(), array( 'order_id' => $id ) ); }
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $users as $id ) { $wpdb->delete( bsc_bp_table(), array( 'user_id' => $id ) ); wp_delete_user( $id ); }
	$product->delete( true );
	if ( null === $saved ) { delete_option( BSC_Sale_Backup::OPTION ); } else { update_option( BSC_Sale_Backup::OPTION, $saved, false ); }
}
echo count( $failures ) . ' failures / ' . $checks . ' checks' . PHP_EOL;
exit( $failures ? 1 : 0 );
