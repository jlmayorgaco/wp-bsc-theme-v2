<?php
/**
 * Delay Meta Purchase events for Wompi until the verified payment is saved.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

function bsc_meta_guard_wompi_purchases(): void {
	$purchase_hooks = array( 'woocommerce_new_order', 'woocommerce_process_shop_order_meta', 'woocommerce_checkout_update_order_meta', 'woocommerce_thankyou' );

	foreach ( $purchase_hooks as $hook ) {
		foreach ( $GLOBALS['wp_filter'][ $hook ]->callbacks ?? array() as $priority => $callbacks ) {
			foreach ( $callbacks as $entry ) {
				$callback = $entry['function'];
				if ( ! is_array( $callback ) || ! $callback[0] instanceof WC_Facebookcommerce_EventsTracker || 'inject_purchase_event' !== $callback[1] ) {
					continue;
				}
				remove_action( $hook, $callback, $priority );
				add_action( $hook, static function ( $order_id, $order = null ) use ( $callback, $hook ): void {
					bsc_meta_maybe_track_wompi_purchase( $callback, $hook, $order_id, $order );
				}, $priority, $entry['accepted_args'] );
			}
		}
	}
}
add_action( 'init', 'bsc_meta_guard_wompi_purchases', 100 );

function bsc_meta_maybe_track_wompi_purchase( callable $track_purchase, string $hook, $order_id, $order ): void {
	$purchase_order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
	if ( ! $purchase_order instanceof WC_Order || ! bsc_is_wompi_gateway_id( $purchase_order->get_payment_method() ) ) {
		$track_purchase( $order_id, $order );
		return;
	}

	// The checkout can create a pending order before the shopper reaches Wompi.
	if ( 'woocommerce_thankyou' !== $hook || ! bsc_meta_tracking_allowed() ) {
		return;
	}

	BSC_DB_Lock::run( 'payment-order:' . $order_id, static function () use ( $track_purchase, $order_id ): void {
		$confirmed_order = wc_get_order( $order_id );
		if ( ! $confirmed_order instanceof WC_Order ) {
			return;
		}
		$confirmed_order->read_meta_data( true );
		$approved = (string) $confirmed_order->get_meta( '_bsc_wompi_approved_id', true );
		if ( '' !== $approved && $approved === $confirmed_order->get_transaction_id() && $confirmed_order->get_date_paid() ) {
			// Meta uses the same saved event ID for its browser and API sends.
			$track_purchase( $order_id, $confirmed_order );
		}
	} );
}
