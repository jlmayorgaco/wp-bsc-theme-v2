<?php
/** Real local checkout account/email/password setup and guest history integration. */
if ( 'cli' !== PHP_SAPI || '1' !== getenv( 'BSC_LOCAL_INTEGRATION' ) ) { exit( 1 ); }
ob_start();
require_once dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'bsc.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) || ! preg_match( '/^(?:localhost|127\.0\.0\.1)(?::\d+)?$/D', DB_HOST ) ) { exit( 1 ); }
define( 'WOOCOMMERCE_CHECKOUT', true );
add_filter( 'action_scheduler_allow_async_request_runner', '__return_false', PHP_INT_MAX );
add_filter( 'woocommerce_defer_transactional_emails', '__return_false', PHP_INT_MAX );
$mail = array();
add_filter( 'pre_wp_mail', static function ( $pre, $args ) { $GLOBALS['mail'][] = $args; return true; }, PHP_INT_MAX, 2 );
// Ensure explicit checkout opt-in works even when old admin settings disabled it.
add_filter( 'option_woocommerce_enable_signup_and_login_from_checkout', '__return_false' );
add_filter( 'option_woocommerce_enable_guest_checkout', '__return_false' );
add_filter( 'option_woocommerce_registration_generate_username', static fn() => 'no' );
add_filter( 'option_woocommerce_registration_generate_password', static fn() => 'no' );
add_filter( 'option_woocommerce_customer_new_account_settings', static fn() => array( 'enabled' => 'no' ) );
WC()->mailer();
// Core registers deferred mail hooks before this CLI test can add its filters.
remove_action( 'woocommerce_created_customer', array( 'WC_Emails', 'queue_transactional_email' ), 10 );
add_action( 'woocommerce_created_customer', array( 'WC_Emails', 'send_transactional_email' ), 10, 10 );
wc_load_cart();
$orders = array();
$users = array();
$email = 'qa-account-' . strtolower( wp_generate_password( 12, false ) ) . '@example.invalid';

function account_check( bool $ok, string $label ): void {
	if ( ! $ok ) { throw new RuntimeException( $label ); }
	echo 'PASS ' . $label . PHP_EOL;
}

try {
	$past = wc_create_order();
	$orders[] = $past->get_id();
	$past->set_billing_email( $email );
	$past->set_payment_method( 'cod' );
	$past->set_total( 100000 );
	$past->set_date_paid( time() );
	$past->set_status( 'processing' );
	$past->save();
	$mail = array();
	$checkout = WC()->checkout();
	account_check( $checkout->is_registration_enabled() && ! $checkout->is_registration_required(), 'Checkout offers optional account creation regardless of old settings' );
	$fields = $checkout->get_checkout_fields( 'account' );
	account_check( ! isset( $fields['account_username'], $fields['account_password'] ), 'Checkout does not ask for a username or password' );
	$process = new ReflectionMethod( WC_Checkout::class, 'process_customer' );
	$data = array( 'billing_email' => $email, 'billing_first_name' => 'Prueba', 'billing_last_name' => 'Checkout', 'createaccount' => 0, 'payment_method' => 'cod' );
	$process->invoke( $checkout, $data );
	account_check( ! email_exists( $email ) && ! is_user_logged_in(), 'Unchecked box leaves the purchase as guest and creates no account' );
	account_check( 0 === count( $mail ), 'Unchecked box sends no account email' );
	$data['createaccount'] = 1;
	$process->invoke( $checkout, $data );
	$user_id = get_current_user_id();
	if ( $user_id ) { $users[] = $user_id; }
	account_check( $user_id > 0 && (int) email_exists( $email ) === $user_id, 'Checked box creates the customer with the billing email' );
	account_check( 'yes' === get_user_meta( $user_id, '_bsc_checkout_account', true ), 'Checkout account origin is persisted' );
	account_check( 1 === count( $mail ), 'Sends one account setup email, with no duplicate welcome (count: ' . count( $mail ) . '; subjects: ' . implode( ', ', array_column( $mail, 'subject' ) ) . ')' );
	$body = html_entity_decode( $mail[0]['message'], ENT_QUOTES, 'UTF-8' );
	account_check( str_contains( $body, 'Establecer mi contraseña' ) && str_contains( $body, 'compras anteriores' ), 'Account email explains password setup and guest history recovery in Spanish' );
	preg_match( '~https?://[^\s"<>]+[?&]key=[^\s"<>]+~', $body, $match );
	$url = $match[0] ?? '';
	parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
	$reset_user = check_password_reset_key( $query['key'] ?? '', $query['login'] ?? '' );
	account_check( $reset_user instanceof WP_User && $reset_user->ID === $user_id, 'Email contains a valid single-use password setup link for this account' );
	account_check( 0 === (int) wc_get_order( $past->get_id() )->get_customer_id(), 'Past guest purchases stay private until email ownership is proven' );
	account_check( 0 === bsc_bp_get_balance( $user_id ), 'Past guest points wait for email confirmation' );
	$current = $checkout->create_order( $data );
	if ( is_wp_error( $current ) ) { throw new RuntimeException( $current->get_error_message() ); }
	$orders[] = $current;
	$current = wc_get_order( $current );
	account_check( $user_id === (int) $current->get_customer_id(), 'Current checkout purchase belongs to the new account' );
	$new_password = wp_generate_password( 32 );
	reset_password( $reset_user, $new_password );
	account_check( wp_check_password( $new_password, get_userdata( $user_id )->user_pass, $user_id ), 'Customer can set their chosen password' );
	account_check( $user_id === (int) wc_get_order( $past->get_id() )->get_customer_id(), 'Setting the password verifies email and imports past guest purchases' );
	account_check( 100 === bsc_bp_get_balance( $user_id ), 'Past guest points become visible in the account balance' );
	account_check( 100 === BSC_Bubble_Points::get( $user_id ), 'Profile uses the recovered points balance' );
	$history = wc_get_orders( array( 'customer_id' => $user_id, 'return' => 'ids', 'limit' => -1 ) );
	account_check( in_array( $past->get_id(), $history, true ) && in_array( $current->get_id(), $history, true ), 'Account history contains both the old guest purchase and current checkout purchase' );
	account_check( is_wp_error( check_password_reset_key( $query['key'], $query['login'] ) ), 'Password setup link cannot be reused' );
	wp_set_current_user( 0 );
	try {
		$process->invoke( $checkout, $data );
		throw new RuntimeException( 'Existing email must not create a second customer.' );
	} catch ( Exception $error ) {
		account_check( str_contains( $error->getMessage(), '/login/' ), 'Existing email directs to the actual login without duplicating accounts' );
	}
} finally {
	wp_set_current_user( 0 );
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
	ob_end_flush();
}
