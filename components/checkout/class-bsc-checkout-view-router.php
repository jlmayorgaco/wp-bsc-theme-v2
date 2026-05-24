<?php

defined( 'ABSPATH' ) || exit;

class BSC_Checkout_View_Router {

	public function get_state(): string {
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			return 'order_received';
		}

		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			return 'order_pay';
		}

		if ( is_wc_endpoint_url( 'cancelled' ) ) {
			return 'order_cancelled';
		}

		if ( WC()->cart && WC()->cart->is_empty() ) {
			return 'empty_cart';
		}

		return 'checkout';
	}

	public function render(): void {
		require $this->get_template_path( $this->get_state() );
	}

	protected function get_template_path( string $state ): string {
		$base = get_template_directory() . '/components/checkout/views/';

		switch ( $state ) {
			case 'order_received':
				return $base . 'checkout-view-thankyou.php';
			case 'order_pay':
				return $base . 'checkout-view-pay.php';
			case 'order_cancelled':
				return $base . 'checkout-view-cancelled.php';
			case 'empty_cart':
				return $base . 'checkout-view-empty.php';
			case 'checkout':
			default:
				return $base . 'checkout-view-main.php';
		}
	}
}
