<?php
/** Isolated checks for the duplicate-page and unpaid-order regressions. */
if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ );

function add_action( ...$args ): void {}
function add_filter( ...$args ): void {}
function apply_filters( $name, $value ) { return $value; }
function home_url( $path = '' ): string { return 'https://example.test' . $path; }
function esc_url( $url ): string { return $url; }
function sanitize_key( $value ): string { return strtolower( (string) $value ); }
function get_option( $name ) { return true; }
function get_transient( $name ) { return true; }
function get_page_template_slug( $id ): string { return ''; }
function update_post_meta( ...$args ): void {}

class WC_Order {
	public function __construct( private string $status, private bool $paid, private string $method = 'cash', private string $transaction = '' ) {}
	public function get_status(): string { return $this->status; }
	public function get_date_paid( $context = 'view' ) { return $this->paid ? new stdClass() : null; }
	public function get_payment_method(): string { return $this->method; }
	public function get_transaction_id(): string { return $this->transaction; }
}

$pages = array();
$insertions = array();
$lookups = array();
foreach ( array( 'login', 'register', 'mi-cuenta', 'mi-cuenta/bubble-points', 'product-category', 'bubble-creators', 'shipping-returns', 'faq', 'contact-us', 'registro-familia-bubbles' ) as $index => $path ) {
	$pages[ $path ] = (object) array( 'ID' => $index + 1 );
}
function get_page_by_path( $path ) {
	$GLOBALS['lookups'][] = $path;
	return $GLOBALS['pages'][ $path ] ?? null;
}
function wp_insert_post( $data ) {
	$GLOBALS['insertions'][] = $data;
	return 100;
}

require dirname( __DIR__ ) . '/inc/setup/theme-setup.php';
require dirname( __DIR__ ) . '/plugins/bubble-points/includes/redeem.php';

function check( bool $ok, string $label ): void {
	if ( ! $ok ) {
		throw new RuntimeException( $label );
	}
	echo "PASS {$label}\n";
}

bsc_create_default_pages();
check( in_array( 'mi-cuenta/bubble-points', $lookups, true ), 'Nested Bubble Points page is looked up by full path' );
check( ! $insertions, 'Existing nested page is not created again' );

foreach ( array( 'processing', 'preparing', 'shipped', 'completed' ) as $status ) {
	check( ! bsc_bp_order_is_awardable( new WC_Order( $status, false ) ), "Unpaid {$status} order cannot earn points" );
	check( bsc_bp_order_is_awardable( new WC_Order( $status, true ) ), "Paid {$status} order can earn points" );
}
foreach ( array( 'pending', 'on-hold', 'failed', 'cancelled', 'refunded' ) as $status ) {
	check( ! bsc_bp_order_is_awardable( new WC_Order( $status, true ) ), "{$status} order cannot retain an awardable state" );
}
check( ! bsc_bp_order_is_awardable( new WC_Order( 'processing', true, 'wompi' ) ), 'Wompi order without a verified transaction cannot earn points' );
check( bsc_bp_order_is_awardable( new WC_Order( 'processing', true, 'wompi', 'verified-tx' ) ), 'Paid Wompi order with transaction can earn points' );
