<?php
/**
 * Isolated regression: unpaid orders, admin filters, customer display and Wompi.
 * Run: php -n tests/order-payment-status.php
 * No WordPress bootstrap, database, cron, gateway requests or real email.
 */
if ( PHP_SAPI !== 'cli' ) {
	exit;
}
define( 'ABSPATH', __DIR__ );

function esc_html( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ): string { return esc_html( $value ); }
function selected( $value, $option, $echo = true ): string { return $value === $option ? ' selected="selected"' : ''; }
function get_template_directory(): string { return dirname( __DIR__ ); }
function add_action( ...$args ): void {}
function add_filter( ...$args ): void {}
function is_admin(): bool { return false; }
function absint( $value ): int { return abs( (int) $value ); }
function get_query_var( $key ): int { return 69087; }
function wp_unslash( $value ) { return $value; }
function sanitize_text_field( $value ): string { return trim( (string) $value ); }
function sanitize_email( $value ): string { return (string) $value; }
function get_post_meta( ...$args ): string { return ''; }
function wc_get_order( $id ) { return $GLOBALS['test_order']; }
function wp_mail( ...$args ): bool { throw new RuntimeException( 'This test must never send email.' ); }

class WP_List_Table {
	public function __construct( ...$args ) {}
}

class WC_Order {
	public int $completed = 0;
	public int $saved = 0;
	public int $recipient_reads = 0;
	public string $transaction_id = '';
	public array $notes = array();
	public function __construct( public string $status = 'pending', public bool $archived = false ) {}
	public function get_id(): int { return 69087; }
	public function get_status(): string { return $this->status; }
	public function get_meta( $key, $single = true ) { return '_bsc_archived_at' === $key && $this->archived ? '2026-09-03' : ''; }
	public function get_total(): string { return '1000.00'; }
	public function get_currency(): string { return 'COP'; }
	public function get_payment_method(): string { return 'wompi'; }
	public function get_order_key(): string { return 'test_order_key'; }
	public function get_transaction_id(): string { return $this->transaction_id; }
	public function set_transaction_id( $id ): void { $this->transaction_id = $id; }
	public function has_status( $statuses ): bool { return in_array( $this->status, (array) $statuses, true ); }
	public function is_paid(): bool { return $this->has_status( array( 'processing', 'completed' ) ); }
	public function get_billing_email(): string { ++$this->recipient_reads; return ''; }
	public function get_billing_first_name(): string { return ''; }
	public function get_billing_phone(): string { return ''; }
	public function save(): void { ++$this->saved; }
	public function payment_complete( $id ): void { ++$this->completed; $this->status = 'processing'; }
	public function add_order_note( $note ): void { $this->notes[] = $note; }
}

class Wompi_Portal_Pagos_API {
	public static ?object $transaction = null;
	public static int $requests = 0;
	public static function instance(): self { return new self(); }
	public function request( ...$args ): object {
		++self::$requests;
		return (object) array( 'data' => self::$transaction );
	}
}

require_once get_template_directory() . '/inc/order-status.php';
require_once get_template_directory() . '/components/orders/order-progress-bar.php';
require_once get_template_directory() . '/admin/bsc-orders-page.php';
require_once get_template_directory() . '/inc/payments/wompi-compat.php';
require_once get_template_directory() . '/emails/bsc-emails.php';

if ( in_array( '--widget-script', $argv, true ) ) {
	echo bsc_get_wompi_widget_inline_script();
	exit;
}

$checks = 0;
function check( bool $passed, string $message ): void {
	++$GLOBALS['checks'];
	if ( ! $passed ) {
		throw new RuntimeException( $message );
	}
}

$table = new BSC_Admin_Orders_Table();
$tabs = bsc_orders_status_tabs();
$preview = array();
foreach ( array( 'pending' => 'Pendiente de pago', 'on-hold' => 'Pago por confirmar' ) as $status => $label ) {
	$order = new WC_Order( $status );
	$column = $table->column_status( $order );
	check( str_contains( $column, 'bsc-order-badge--' . $status . '">' . $label ), "$status badge must identify unpaid orders." );
	check( str_contains( $column, 'value="wc-' . $status . '" selected="selected"' ), "$status must not select Recibido." );
	check( bsc_simplified_order_status_label( $order ) === $label, 'CSV and admin label must agree.' );
	check( ! in_array( $status, $tabs['wc-processing']['statuses'], true ), 'Unpaid orders must not inflate Recibidos.' );
	check( $tabs[ 'wc-' . $status ]['statuses'] === array( $status ), 'Unpaid filter must query its actual WooCommerce status.' );
	$bar = new BSC_Order_Progress_Bar( bsc_map_order_to_bar( $order ) );
	ob_start();
	$bar->render();
	$progress = ob_get_clean();
	check( str_contains( $progress, $label ) && ! str_contains( $progress, 'Recibido' ), 'Customer progress must not imply payment.' );
	$copy = bsc_get_order_confirmation_copy( $status, '69087' );
	check( ! str_contains( $copy['title'], 'Gracias' ), 'Unpaid thank-you page must not confirm a purchase.' );
	$preview[ $status ] = compact( 'column', 'progress', 'copy' );
}

// Paid, cancelled and archived orders must retain their existing grouping and selection.
foreach ( array( 'processing', 'preparing', 'shipped', 'completed', 'cancelled', 'failed', 'refunded' ) as $status ) {
	$order = new WC_Order( $status );
	$display = bsc_get_order_status_display( $status );
	check( isset( BSC_Admin_Orders_Table::STATUS_OPTIONS[ $display['key'] ] ), "$status must have a selectable current value." );
	check( in_array( $status, $tabs[ $display['key'] ]['statuses'], true ), "$status must remain in its matching filter." );
	check( str_contains( $table->column_status( $order ), 'value="' . $display['key'] . '" selected="selected"' ), "$status selection must survive reloads." );
}
$archived = new WC_Order( 'pending', true );
check( bsc_simplified_order_status_label( $archived ) === 'Archivado', 'Archive flag must remain authoritative.' );
check( bsc_map_order_to_bar( $archived ) === BSC_Order_Progress_Bar::ARCHIVED, 'Archived orders keep their customer label.' );
check( str_contains( $table->column_status( new WC_Order( 'custom-status' ) ), 'selected disabled>Estado desconocido' ), 'Unknown statuses must not silently select a paid status.' );
check( str_contains( bsc_get_order_confirmation_copy( 'processing', '69089' )['title'], 'Gracias por tu compra' ), 'Paid purchases keep their success heading.' );
foreach ( array( 'failed', 'cancelled', 'refunded', 'custom-status' ) as $status ) {
	check( ! str_contains( bsc_get_order_confirmation_copy( $status, '69087' )['title'], 'Gracias' ), "$status must not show purchase success." );
}

// Even an accidental payment-complete hook cannot send confirmation while unpaid.
foreach ( array( 'pending', 'on-hold', 'failed', 'cancelled', 'refunded' ) as $status ) {
	$GLOBALS['test_order'] = new WC_Order( $status );
	bsc_handle_payment_complete_order_email( 69087 );
	check( 0 === $GLOBALS['test_order']->recipient_reads, "$status must stop before the email dispatcher." );
}
foreach ( array( 'pending', 'on-hold' ) as $status ) {
	$GLOBALS['test_order'] = new WC_Order( $status );
	bsc_handle_order_status_email( 69087, 'pending', $status );
	check( 0 === $GLOBALS['test_order']->recipient_reads, 'Unpaid status transitions must not send purchase emails.' );
}
$GLOBALS['test_order'] = new WC_Order( 'processing' );
bsc_handle_payment_complete_order_email( 69087 );
check( 1 === $GLOBALS['test_order']->recipient_reads, 'Paid orders still reach the dispatcher (empty test recipient).' );

$approved = (object) array( 'id' => 'test-transaction', 'status' => 'APPROVED', 'reference' => '69087', 'amount_in_cents' => 100000, 'currency' => 'COP' );
$query = array( 'bsc_wompi_return' => '1', 'bsc_wompi_transaction_id' => 'test-transaction', 'key' => 'test_order_key', 'bsc_wompi_status' => 'APPROVED' );

// API status is authoritative, even if the browser URL claims APPROVED.
foreach ( array( null, 'PENDING', 'DECLINED', 'VOIDED', 'ERROR' ) as $status ) {
	$GLOBALS['test_order'] = new WC_Order();
	$_GET = $query;
	Wompi_Portal_Pagos_API::$transaction = null === $status ? null : (object) array_merge( (array) $approved, array( 'status' => $status ) );
	bsc_maybe_complete_wompi_return();
	check( 'pending' === $GLOBALS['test_order']->status && 0 === $GLOBALS['test_order']->saved && 0 === $GLOBALS['test_order']->completed, 'Unapproved or missing transactions must leave the order unpaid.' );
}
foreach ( array( array( 'reference' => '99999' ), array( 'amount_in_cents' => 1 ), array( 'currency' => 'USD' ) ) as $mismatch ) {
	$GLOBALS['test_order'] = new WC_Order();
	$_GET = $query;
	Wompi_Portal_Pagos_API::$transaction = (object) array_merge( (array) $approved, $mismatch );
	bsc_maybe_complete_wompi_return();
	check( 0 === $GLOBALS['test_order']->completed, 'Another order, amount or currency must be rejected.' );
}
$GLOBALS['test_order'] = new WC_Order();
$_GET = $query;
$_GET['key'] = 'wrong-key';
Wompi_Portal_Pagos_API::$requests = 0;
bsc_maybe_complete_wompi_return();
check( 0 === Wompi_Portal_Pagos_API::$requests && 0 === $GLOBALS['test_order']->completed, 'Invalid order key must stop before querying the gateway.' );

$GLOBALS['test_order'] = new WC_Order();
$_GET = $query;
unset( $_GET['bsc_wompi_transaction_id'] );
bsc_maybe_complete_wompi_return();
check( 0 === $GLOBALS['test_order']->completed, 'Closing without a transaction cannot complete payment.' );

$GLOBALS['test_order'] = new WC_Order();
$_GET = $query;
Wompi_Portal_Pagos_API::$transaction = $approved;
bsc_maybe_complete_wompi_return();
check( 1 === $GLOBALS['test_order']->completed && 'processing' === $GLOBALS['test_order']->status, 'A matching approved transaction must still complete payment.' );
check( 'test-transaction' === $GLOBALS['test_order']->transaction_id && 1 === count( $GLOBALS['test_order']->notes ), 'Verified payment retains its transaction and note.' );

if ( in_array( '--preview', $argv, true ) ) {
	echo json_encode( $preview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
} else {
	fwrite( STDOUT, "Order payment regression: $checks checks passed.\n" );
}
