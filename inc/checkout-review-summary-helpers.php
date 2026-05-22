<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bsc_recalculate_checkout_totals' ) ) {
	function bsc_recalculate_checkout_totals( bool $clear_shipping_cache = false ): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		if ( $clear_shipping_cache && function_exists( 'bsc_clear_cached_shipping_packages' ) ) {
			bsc_clear_cached_shipping_packages();
		}

		if ( WC()->cart->needs_shipping() ) {
			WC()->cart->calculate_shipping();
		}

		WC()->cart->calculate_totals();
	}
}

if ( ! function_exists( 'bsc_get_checkout_shipping_method_label' ) ) {
	function bsc_get_checkout_shipping_method_label(): string {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return '';
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		if ( empty( $chosen_methods ) || ! is_array( $chosen_methods ) ) {
			return '';
		}

		foreach ( WC()->shipping()->get_packages() as $index => $package ) {
			if ( empty( $package['rates'] ) || ! isset( $chosen_methods[ $index ] ) ) {
				continue;
			}

			foreach ( $package['rates'] as $rate_id => $rate ) {
				if ( $rate_id === $chosen_methods[ $index ] ) {
					return (string) $rate->get_label();
				}
			}
		}

		return '';
	}
}

if ( ! function_exists( 'bsc_get_checkout_summary_payload' ) ) {
	function bsc_get_checkout_summary_payload(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return [
				'cart_count'                => 0,
				'subtotal_html'            => '',
				'subtotal_discounted_html' => '',
				'shipping_total_html'      => '',
				'cart_total_html'          => '',
			];
		}

		$cart                 = WC()->cart;
		$shipping_total       = (float) $cart->get_shipping_total();
		$shipping_display     = $shipping_total <= 0 ? 'Gratis' : wc_price( $shipping_total );
		$shipping_method_label = bsc_get_checkout_shipping_method_label();
		$shipping_separator   = html_entity_decode( '&ndash;', ENT_QUOTES, 'UTF-8' );
		$subtotal_discounted  = max( 0, (float) $cart->get_subtotal() - (float) $cart->get_discount_total() );
		$shipping_text        = $shipping_method_label !== ''
			? sprintf( '%s %s %s', $shipping_method_label, $shipping_separator, $shipping_display )
			: $shipping_display;

		return [
			'cart_count'                => (int) $cart->get_cart_contents_count(),
			'subtotal_html'            => wc_price( $cart->get_subtotal() ),
			'subtotal_discounted_html' => wc_price( $subtotal_discounted ),
			'shipping_total_html'      => $shipping_text,
			'cart_total_html'          => $cart->get_cart_total(),
		];
	}
}
