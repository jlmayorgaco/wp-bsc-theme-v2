<?php
/**
 * Focused regression test for BSC cart stock aggregation.
 *
 * Run with: php tests/cart-stock-limit.php
 */

define( 'ABSPATH', __DIR__ );

function add_action() {}

function get_template_directory() {
	return dirname( __DIR__ );
}

function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) );
}

class WC_Product {
	private $id;

	public function __construct( int $id ) {
		$this->id = $id;
	}

	public function managing_stock() {
		return false;
	}

	public function get_stock_quantity() {
		return null;
	}

	public function get_stock_managed_by_id() {
		return $this->id;
	}
}

class BSC_Stock {
	public static $stock = array();

	public static function has_dual_stock( int $product_id ): bool {
		return array_key_exists( $product_id, self::$stock );
	}

	public static function get_total_stock( int $product_id ): int {
		return (int) ( self::$stock[ $product_id ] ?? 0 );
	}
}

class BSC_Test_Cart {
	public $items = array();

	public function get_cart(): array {
		return $this->items;
	}
}

class BSC_Test_WooCommerce {
	public $cart;

	public function __construct() {
		$this->cart = new BSC_Test_Cart();
	}
}

$GLOBALS['bsc_test_woocommerce'] = new BSC_Test_WooCommerce();

function WC() {
	return $GLOBALS['bsc_test_woocommerce'];
}

require_once dirname( __DIR__ ) . '/inc/ajax/cart-actions.php';

function bsc_cart_stock_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$product = new WC_Product( 501 );
$item    = array(
	'product_id'  => 501,
	'variation_id' => 0,
	'data'        => $product,
);

BSC_Stock::$stock[501] = 1;

bsc_cart_stock_assert_same(
	1,
	bsc_cart_item_stock_total( $item ),
	'The cart item must use the BSC warehouse plus store stock total.'
);
bsc_cart_stock_assert_same(
	2,
	bsc_cart_item_requested_stock_quantity( $item, 2 ),
	'An initial request for two units must remain two so the endpoint can reject it against stock one.'
);
bsc_cart_stock_assert_same(
	false,
	bsc_cart_item_has_enough_stock( $item, 2 ),
	'An initial add above the BSC stock limit must be rejected.'
);

WC()->cart->items = array(
	'existing-line' => $item + array( 'quantity' => 1 ),
);

bsc_cart_stock_assert_same(
	2,
	bsc_cart_item_requested_stock_quantity( $item, 1 ),
	'A repeated add must include the quantity already consuming the same stock pool.'
);
bsc_cart_stock_assert_same(
	false,
	bsc_cart_item_has_enough_stock( $item, 1 ),
	'A repeated add must be rejected when the existing line already consumes all stock.'
);
bsc_cart_stock_assert_same(
	2,
	bsc_cart_item_requested_stock_quantity( $item, 2, 'existing-line' ),
	'Updating an existing line must replace its old quantity instead of counting it twice.'
);

fwrite( STDOUT, "Cart stock limit test passed.\n" );
