<?php
/** Real local WooCommerce checks; disposable users/orders, with all email intercepted. */
if ( 'cli' !== PHP_SAPI || '1' !== getenv( 'BSC_LOCAL_INTEGRATION' ) ) { exit( 1 ); }
require_once dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'bsc.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) || ! preg_match( '/^(?:localhost|127\.0\.0\.1)(?::\d+)?$/D', DB_HOST ) ) { exit( 1 ); }

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController;

$verification_mail = array();
add_filter( 'pre_wp_mail', static function ( $pre, $args ) { $GLOBALS['verification_mail'][] = $args; return true; }, PHP_INT_MAX, 2 );
add_filter( 'action_scheduler_allow_async_request_runner', '__return_false', PHP_INT_MAX );
$orders = array();
$users  = array();
$email  = 'qa-guest-' . strtolower( wp_generate_password( 12, false ) ) . '@example.invalid';

function guest_check( bool $ok, string $label ): void {
	if ( ! $ok ) { throw new RuntimeException( $label ); }
	echo 'PASS ' . $label . PHP_EOL;
}
function guest_order( string $email, string $status = 'processing', bool $legacy = false ): WC_Order {
	$order = wc_create_order();
	$GLOBALS['orders'][] = $order->get_id();
	$order->set_billing_email( $email );
	$order->set_payment_method( 'cod' );
	$order->set_total( 100000 );
	if ( 'processing' === $status ) { $order->set_date_paid( time() ); }
	$order->set_status( $status );
	$order->save();
	if ( $legacy ) {
		$order->delete_meta_data( '_bsc_bp_guest_points' );
		$order->save_meta_data();
	}
	return wc_get_order( $order->get_id() );
}

try {
	$paid = guest_order( $email );
	$old = guest_order( $email, 'processing', true );
	$partial = guest_order( $email );
	$refund = wc_create_refund( array( 'order_id' => $partial->get_id(), 'amount' => 25000 ) );
	if ( is_wp_error( $refund ) ) { throw new RuntimeException( $refund->get_error_message() ); }
	$orders[] = $refund->get_id();
	$unpaid = guest_order( $email, 'pending' );
	$cancelled = guest_order( $email, 'cancelled' );
	$other = guest_order( 'other-' . $email );
	$full = guest_order( $email );
	$refund = wc_create_refund( array( 'order_id' => $full->get_id(), 'amount' => 100000 ) );
	if ( is_wp_error( $refund ) ) { throw new RuntimeException( $refund->get_error_message() ); }
	$orders[] = $refund->get_id();
	guest_check( 100 === (int) $paid->get_meta( '_bsc_bp_guest_points' ), 'Paid guest order persists pending points' );
	guest_check( '' === $unpaid->get_meta( '_bsc_bp_guest_points' ), 'Unpaid purchase has no pending reward' );
	$user_id = wp_insert_user( array( 'user_login' => $email, 'user_email' => $email, 'user_pass' => wp_generate_password( 32 ), 'role' => 'customer' ) );
	if ( is_wp_error( $user_id ) ) { throw new RuntimeException( $user_id->get_error_message() ); }
	$users[] = $user_id;
	$other_user_id = wp_insert_user( array( 'user_login' => 'other-' . $email, 'user_email' => 'other-' . $email, 'user_pass' => wp_generate_password( 32 ), 'role' => 'customer' ) );
	if ( is_wp_error( $other_user_id ) ) { throw new RuntimeException( $other_user_id->get_error_message() ); }
	$users[] = $other_user_id;
	$assigned = guest_order( $email );
	$assigned->set_customer_id( $other_user_id );
	$assigned->save();
	guest_check( 0 === (int) wc_get_order( $paid->get_id() )->get_customer_id(), 'Registration alone does not expose guest orders' );
	guest_check( 0 === bsc_bp_get_balance( $user_id ), 'Unverified email cannot spend guest points' );
	$service = wc_get_container()->get( EmailVerificationService::class );
	$controller = wc_get_container()->get( VerificationController::class );
	wp_set_current_user( $user_id );
	$disable_guest_option = static fn() => 'no';
	add_filter( 'option_woocommerce_enable_guest_checkout', $disable_guest_option );
	guest_check( $controller->should_show_prompt(), 'Unverified profile can recover history even if the old guest-checkout option is disabled' );
	ob_start();
	bsc_bp_guest_recovery_prompt();
	$prompt = ob_get_clean();
	guest_check( str_contains( $prompt, 'Confirmar mi correo' ) && ! str_contains( $prompt, 'Confirm email address' ), 'Profile confirmation action is in Spanish' );
	guest_check( str_contains( $prompt, 'bsc__email-confirmation' ) && str_contains( $prompt, '_wpnonce=' ), 'Styled confirmation preserves the native protected action' );
	remove_filter( 'option_woocommerce_enable_guest_checkout', $disable_guest_option );
	wp_set_current_user( 0 );
	bsc_bp_send_guest_recovery_email( $user_id );
	$key = '';
	foreach ( $verification_mail as $sent ) {
		$body = html_entity_decode( $sent['message'], ENT_QUOTES, 'UTF-8' );
		if ( preg_match( '~https?://[^\s"<>]+wc_verify_email_key=[^\s"<>]+~', $body, $match ) ) {
			parse_str( (string) wp_parse_url( $match[0], PHP_URL_QUERY ), $query );
			if ( $user_id === (int) ( $query['wc_verify_email_user'] ?? 0 ) ) {
				$key = $query['wc_verify_email_key'];
			}
		}
	}
	guest_check( '' !== $key && $service->check_verification_key( $user_id, $key ), 'Standalone registration sends a valid email confirmation link for history recovery' );
	guest_check( ! $controller->process_verification( $user_id, 'wrong-token' ), 'Invalid confirmation token cannot migrate purchases' );
	guest_check( $controller->process_verification( $user_id, $key ), 'Valid email confirmation migrates matching purchases' );
	foreach ( array( $paid, $old, $partial, $unpaid, $cancelled, $full ) as $order ) {
		guest_check( $user_id === (int) wc_get_order( $order->get_id() )->get_customer_id(), 'Matching purchase linked: ' . $order->get_status() );
	}
	guest_check( 0 === (int) wc_get_order( $other->get_id() )->get_customer_id(), 'Different email purchase remains private' );
	guest_check( $other_user_id === (int) wc_get_order( $assigned->get_id() )->get_customer_id(), 'Purchase assigned to another account is never transferred' );
	guest_check( 275 === bsc_bp_get_balance( $user_id ), 'Recovers current and legacy points, subtracts refunds, excludes unpaid/cancelled orders' );
	guest_check( '' === wc_get_order( $full->get_id() )->get_meta( '_bsc_bp_points_awarded' ), 'Fully refunded order earns no points' );
	bsc_bp_recover_linked_order_points( $paid->get_id(), $user_id );
	bsc_bp_recover_linked_order_points( $partial->get_id(), $user_id );
	wc_update_new_customer_past_orders( $user_id );
	guest_check( 275 === bsc_bp_get_balance( $user_id ), 'Repeated migration and retries do not duplicate points' );
	guest_check( ! $controller->process_verification( $user_id, $key ), 'Consumed email confirmation token cannot be reused' );

	// A new guest purchase paid after linking still uses the normal award hooks.
	$unpaid = wc_get_order( $unpaid->get_id() );
	$unpaid->set_date_paid( time() );
	$unpaid->set_status( 'processing' );
	$unpaid->save();
	guest_check( 375 === bsc_bp_get_balance( $user_id ), 'Previously unpaid linked order earns points only after payment' );
	$refund = wc_create_refund( array( 'order_id' => $paid->get_id(), 'amount' => 50000 ) );
	if ( is_wp_error( $refund ) ) { throw new RuntimeException( $refund->get_error_message() ); }
	$orders[] = $refund->get_id();
	guest_check( 325 === bsc_bp_get_balance( $user_id ), 'Refund after migration adjusts recovered balance' );

	// Fail the refund adjustment: the initial award must roll back too.
	$retry = guest_order( $email );
	$refund = wc_create_refund( array( 'order_id' => $retry->get_id(), 'amount' => 25000 ) );
	if ( is_wp_error( $refund ) ) { throw new RuntimeException( $refund->get_error_message() ); }
	$orders[] = $refund->get_id();
	$fail_adjustment = static function ( $sql ) {
		return str_starts_with( $sql, 'INSERT INTO' ) && str_contains( $sql, 'bsc_points_ledger' ) && str_contains( $sql, "'order_reconcile'" ) ? 'INSERT INTO bsc_deliberate_missing_qa_table VALUES (1)' : $sql;
	};
	global $wpdb;
	$suppressed = $wpdb->suppress_errors( true );
	add_filter( 'query', $fail_adjustment );
	try { wc_update_new_customer_past_orders( $user_id ); }
	finally { remove_filter( 'query', $fail_adjustment ); $wpdb->suppress_errors( $suppressed ); }
	guest_check( 325 === bsc_bp_get_balance( $user_id ), 'Refund adjustment failure rolls back the entire recovered reward' );
	guest_check( (bool) wp_next_scheduled( 'bsc_bp_recover_linked_order_points', array( $retry->get_id(), $user_id ) ), 'Failed recovery schedules a retry' );
	bsc_bp_recover_linked_order_points( $retry->get_id(), $user_id );
	guest_check( 400 === bsc_bp_get_balance( $user_id ), 'Recovery retry awards only the net points once (balance: ' . bsc_bp_get_balance( $user_id ) . ')' );

	// Losing/changing the verified email must block recovery even for linked orders.
	$service->clear_verification( $user_id );
	bsc_bp_recover_linked_order_points( $cancelled->get_id(), $user_id );
	guest_check( 400 === bsc_bp_get_balance( $user_id ), 'Unverified recovery callback cannot alter the balance' );
} finally {
	global $wpdb;
	foreach ( $orders as $order_id ) {
		wp_clear_scheduled_hook( 'bsc_bp_reconcile_order_points', array( $order_id ) );
		foreach ( $users as $user_id ) { wp_clear_scheduled_hook( 'bsc_bp_recover_linked_order_points', array( $order_id, $user_id ) ); }
		$wpdb->delete( bsc_bp_table(), array( 'order_id' => $order_id ) );
		$order = wc_get_order( $order_id );
		if ( $order ) { $order->delete( true ); }
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $users as $user_id ) { wp_delete_user( $user_id ); }
}
