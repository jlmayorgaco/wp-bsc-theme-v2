<?php
/**
 * Regression coverage for the checkout grand total using WooCommerce's cart API.
 * Run with the local WordPress PHP configuration: php tests/checkout-summary-total.php
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

require_once dirname( __DIR__, 4 ) . '/wp-load.php';
require_once dirname( __DIR__ ) . '/inc/checkout-review-summary-helpers.php';

$original_cart = WC()->cart;
WC()->cart    = new WC_Cart();

// These are already-calculated WooCommerce totals, not a second price calculator.
$cases = array(
	'national shipping' => array( 2000, 0, 17000, 0, 0, 19000 ),
	'local shipping'    => array( 2000, 0, 10000, 0, 0, 12000 ),
	'coupon discount'   => array( 2000, 500, 17000, 0, 0, 18500 ),
	'free shipping'     => array( 2000, 0, 0, 0, 0, 2000 ),
	'fees and taxes'    => array( 2000, 500, 17000, 300, 200, 19000 ),
);

try {
	foreach ( $cases as $label => $values ) {
		list( $subtotal, $discount, $shipping, $fees, $tax, $total ) = $values;
		WC()->cart->set_totals(
			array(
				'subtotal'            => $subtotal,
				'cart_contents_total' => $subtotal - $discount,
				'discount_total'      => $discount,
				'shipping_total'      => $shipping,
				'fee_total'           => $fees,
				'total_tax'           => $tax,
				'total'               => $total,
			)
		);

		$summary = bsc_get_checkout_summary_payload();
		if ( wc_price( $total ) !== $summary['cart_total_html'] ) {
			throw new RuntimeException( $label . ': checkout must display the grand total, including shipping, fees and taxes.' );
		}
		if ( wc_price( $subtotal - $discount ) !== $summary['subtotal_discounted_html'] ) {
			throw new RuntimeException( $label . ': discounted subtotal must remain separate from the grand total.' );
		}

		$coupons_payload = bsc_get_coupon_totals_payload();
		if ( wc_price( $total ) !== $coupons_payload['cart_total'] ) {
			throw new RuntimeException( $label . ': coupon AJAX responses must include the same grand total.' );
		}
		fwrite( STDOUT, $label . ': passed' . PHP_EOL );
	}
} finally {
	WC()->cart = $original_cart;
}
