<?php
/**
 * Focused regression test for responsive product-card images.
 *
 * Run with: php tests/product-card-responsive-image.php
 */

define( 'ABSPATH', __DIR__ );

class WC_Product {
	public function get_id() {
		return 42;
	}

	public function get_price_html() {
		return '$10';
	}

	public function get_price() {
		return '10';
	}

	public function get_regular_price() {
		return '10';
	}

	public function get_sale_price() {
		return '';
	}

	public function get_stock_status() {
		return 'instock';
	}

	public function get_image_id() {
		return 99;
	}

	public function get_sku() {
		return 'TEST-42';
	}

	public function get_average_rating() {
		return '5';
	}

	public function get_type() {
		return 'simple';
	}

	public function is_purchasable() {
		return true;
	}

	public function is_in_stock() {
		return true;
	}
}

class WC_Product_Variable extends WC_Product {}
class WP_Term {}

$GLOBALS['bsc_test_card_image_calls'] = array();

function get_the_title( $product_id ) {
	return 'Responsive cleanser';
}

function wc_get_price_to_display( $product, $args = array() ) {
	return isset( $args['price'] ) ? (float) $args['price'] : 10.0;
}

function wc_price( $price ) {
	return '$' . (string) $price;
}

function wp_get_attachment_image_src( $attachment_id, $size ) {
	$GLOBALS['bsc_test_card_image_calls'][] = array(
		'method' => 'src',
		'size'   => $size,
	);

	return array( 'https://example.test/product-400x400.webp', 400, 400, true );
}

function wp_get_attachment_image( $attachment_id, $size, $icon, $attrs ) {
	$GLOBALS['bsc_test_card_image_calls'][] = array(
		'method' => 'html',
		'size'   => $size,
		'attrs'  => $attrs,
	);

	return '<img class="card__image" src="https://example.test/product-400x400.webp" sizes="' . $attrs['sizes'] . '">';
}

function get_stylesheet_directory_uri() {
	return 'https://example.test/theme';
}

function esc_url( $value ) {
	return (string) $value;
}

function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function get_permalink( $product_id ) {
	return 'https://example.test/product/' . (int) $product_id;
}

function is_wp_error( $value ) {
	return false;
}

require_once dirname( __DIR__ ) . '/components/products/card.php';

function bsc_card_image_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$card = new BSC_Products_Card();
$card->setProduct( new WC_Product(), array() );
$card->setImagePriority( true );

ob_start();
$card->render_images();
$html = ob_get_clean();

$src_call  = $GLOBALS['bsc_test_card_image_calls'][0] ?? array();
$html_call = $GLOBALS['bsc_test_card_image_calls'][1] ?? array();

bsc_card_image_assert_same( 'bsc-card', $src_call['size'] ?? null, 'The card data source must use the 400px bsc-card image.' );
bsc_card_image_assert_same( 'bsc-card', $html_call['size'] ?? null, 'The rendered card must use the 400px bsc-card image.' );
bsc_card_image_assert_same(
	'(max-width: 767px) calc((100vw - 64px) / 2), 200px',
	$html_call['attrs']['sizes'] ?? null,
	'The sizes attribute must describe the real two-column mobile card slot.'
);
bsc_card_image_assert_same( 'eager', $html_call['attrs']['loading'] ?? null, 'The priority card must load eagerly.' );
bsc_card_image_assert_same( 'high', $html_call['attrs']['fetchpriority'] ?? null, 'The priority card must retain high fetch priority.' );
bsc_card_image_assert_same( 'sync', $html_call['attrs']['decoding'] ?? null, 'The priority card must retain synchronous decoding.' );
bsc_card_image_assert_same(
	true,
	strpos( $html, 'product-400x400.webp' ) !== false,
	'The rendered markup must keep the 400px fallback URL.'
);

fwrite( STDOUT, "Product card responsive image test passed.\n" );
