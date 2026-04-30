<?php
/**
 * Shared URL helpers for storefront and account routes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bsc_get_static_page_url' ) ) {
	function bsc_get_static_page_url( string $slug ): string {
		$page = get_page_by_path( trim( $slug, '/' ) );

		if ( $page instanceof WP_Post ) {
			$permalink = get_permalink( $page );

			if ( is_string( $permalink ) && $permalink !== '' ) {
				return $permalink;
			}
		}

		return home_url( '/' . trim( $slug, '/' ) . '/' );
	}
}

if ( ! function_exists( 'bsc_get_shop_url' ) ) {
	function bsc_get_shop_url(): string {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop_url = wc_get_page_permalink( 'shop' );

			if ( is_string( $shop_url ) && $shop_url !== '' ) {
				return $shop_url;
			}
		}

		return home_url( '/shop/' );
	}
}

if ( ! function_exists( 'bsc_get_shipping_returns_url' ) ) {
	function bsc_get_shipping_returns_url(): string {
		return bsc_get_static_page_url( 'shipping-returns' );
	}
}

if ( ! function_exists( 'bsc_get_account_orders_url' ) ) {
	function bsc_get_account_orders_url(): string {
		if ( function_exists( 'wc_get_page_permalink' ) && function_exists( 'wc_get_endpoint_url' ) ) {
			$my_account_url = wc_get_page_permalink( 'myaccount' );

			if ( is_string( $my_account_url ) && $my_account_url !== '' ) {
				return wc_get_endpoint_url( 'orders', '', $my_account_url );
			}
		}

		return home_url( '/mi-cuenta/orders/' );
	}
}
