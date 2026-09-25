<?php
/**
 * Store shipping prices shared by checkout, settings and informational pages.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the Bogotá and Cundinamarca shipping price in COP.
 *
 * @return int
 */
function bsc_get_bogota_shipping_price(): int {
	return max( 0, (int) get_option( 'bsc_bogota_shipping_price', 10000 ) );
}

/**
 * Return the national shipping price in COP.
 *
 * @return int
 */
function bsc_get_other_shipping_price(): int {
	return max( 0, (int) get_option( 'bsc_other_shipping_price', 18000 ) );
}

/**
 * Format a shipping amount for informational pages.
 *
 * @param int $price Amount in COP.
 * @return string
 */
function bsc_format_cop_shipping_price( int $price ): string {
	return '$' . number_format( $price, 0, ',', '.' ) . ' COP';
}

/** Apply the requested prices once; later changes in the settings panel persist. */
function bsc_upgrade_shipping_prices_2026_09(): void {
	if ( get_option( 'bsc_shipping_prices_2026_09', false ) ) {
		return;
	}

	update_option( 'bsc_bogota_shipping_price', 10000 );
	update_option( 'bsc_other_shipping_price', 18000 );
	update_option( 'bsc_shipping_prices_2026_09', '1', false );

	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
}
add_action( 'init', 'bsc_upgrade_shipping_prices_2026_09' );
