<?php
/** Copy to mu-plugins only for local integration tests; remove immediately after QA. */
defined( 'ABSPATH' ) || exit;
if ( 'bsc.local' !== wp_parse_url( (string) get_option( 'home' ), PHP_URL_HOST ) ) { return; }
add_filter( 'pre_wp_mail', static function () { return true; }, PHP_INT_MAX );
// Keep disposable checkout emails out of Action Scheduler after their orders are deleted.
add_filter( 'woocommerce_defer_transactional_emails', '__return_false', PHP_INT_MAX );
add_filter( 'pre_http_request', static function ( $pre, $args, $url ) {
	if ( false !== $pre ) { return $pre; }
	if ( in_array( wp_parse_url( $url, PHP_URL_HOST ), array( 'bsc.local', 'localhost', '127.0.0.1' ), true ) ) { return $pre; }
	return new WP_Error( 'bsc_local_test_network', 'External HTTP disabled during local tests.' );
}, PHP_INT_MAX, 3 );
add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	class BSC_Local_Audit_Gateway extends WC_Payment_Gateway {
		public function __construct() { $this->id = 'bsc_local_audit'; $this->title = 'Prueba local sin cobro'; $this->enabled = 'yes'; $this->has_fields = false; }
		public function is_available() {
			$login = wp_get_current_user()->user_login;
			return 'qa.visual' === $login || str_starts_with( $login, 'qa-checkout-' );
		}
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			$order->update_meta_data( '_bsc_local_audit_checkout', '1' ); $order->save();
			$order->payment_complete( 'local-test-' . $order_id ); WC()->cart->empty_cart();
			return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
		}
	}
	add_filter( 'woocommerce_payment_gateways', static function ( $gateways ) { $gateways[] = BSC_Local_Audit_Gateway::class; return $gateways; } );
}, 30 );
