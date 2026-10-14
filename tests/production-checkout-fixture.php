<?php
/** Disposable product for the explicitly enabled local checkout test. */
if ( 'cli' !== PHP_SAPI || '1' !== getenv( 'BSC_LOCAL_INTEGRATION' ) ) { exit( 1 ); }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'bsc.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { exit( 1 ); }
$mode = $argv[1] ?? '';
if ( in_array( $mode, array( 'points-prepare', 'points-restore' ), true ) ) {
	$qa = get_user_by( 'login', 'qa.visual' ); if ( ! $qa ) { exit( 3 ); }
	$original = bsc_bp_with_user_lock( $qa->ID, static function () use ( $qa, $mode, $argv ) {
		$old = bsc_bp_get_balance( $qa->ID ); bsc_bp_set_balance( $qa->ID, 'points-prepare' === $mode ? 5000 : (int) ( $argv[2] ?? 0 ) ); return $old;
	} );
	echo wp_json_encode( array( 'balance' => $original ) ); exit;
}
if ( 'create' === $mode ) {
	$p = new WC_Product_Simple(); $p->set_name( 'BSC prueba local de checkout' ); $p->set_status( 'publish' ); $p->set_regular_price( 10000 );
	$p->update_meta_data( '_bsc_local_checkout_fixture', '1' ); $p->save();
	update_post_meta( $p->get_id(), '_stock_bodega', 2 ); update_post_meta( $p->get_id(), '_stock_tienda', 0 ); BSC_Stock::sync_stock_status( $p->get_id() );
	$login    = 'qa-checkout-' . strtolower( wp_generate_password( 12, false, false ) );
	$password = wp_generate_password( 32, true, true );
	$user_id  = wp_insert_user(
		array(
			'user_login'   => $login,
			'user_pass'    => $password,
			'user_email'   => $login . '@example.invalid',
			'role'         => 'customer',
			'display_name' => 'BSC QA checkout',
		)
	);
	if ( is_wp_error( $user_id ) ) {
		wp_delete_post( $p->get_id(), true );
		exit( 3 );
	}
	update_post_meta( $p->get_id(), '_bsc_local_checkout_user_id', $user_id );
	echo wp_json_encode( array( 'id' => $p->get_id(), 'login' => $login, 'password' => $password ) );
} else {
	$id = absint( $argv[2] ?? 0 );
	if ( ! get_post_meta( $id, '_bsc_local_checkout_fixture', true ) ) { exit( 2 ); }
	$user_id = absint( get_post_meta( $id, '_bsc_local_checkout_user_id', true ) );
	$orders = wc_get_orders( array( 'limit' => 20, 'meta_key' => '_bsc_local_audit_checkout', 'meta_value' => '1' ) );
	foreach ( $orders as $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( (int) $item->get_product_id() !== $id ) { continue; }
			if ( 'verify' === $mode ) {
				echo wp_json_encode( array( 'paid' => (bool) $order->get_date_paid(), 'transaction' => str_starts_with( $order->get_transaction_id(), 'local-test-' ), 'stock' => BSC_Stock::get_total_stock( $id ), 'quantity' => $item->get_quantity() ) );
				break 2;
			}
			$order->update_status( 'cancelled' );
			global $wpdb;
			$wpdb->delete( bsc_bp_table(), array( 'order_id' => $order->get_id() ) );
			wp_clear_scheduled_hook( 'bsc_expire_stock_reservation', array( $order->get_id() ) );
			$order->delete( true ); break;
		}
	}
	if ( 'cleanup' === $mode ) {
		wp_delete_post( $id, true );
		if ( $user_id ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );
		}
	}
}
