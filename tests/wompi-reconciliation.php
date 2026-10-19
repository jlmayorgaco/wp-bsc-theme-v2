<?php
/**
 * Payment regression with fresh reads from memory-only storage; no WordPress, DB, HTTP or mail.
 * Run: php -n tests/wompi-reconciliation.php
 */
if ( PHP_SAPI !== 'cli' ) { exit; }
define( 'ABSPATH', __DIR__ );

function add_action( $hook, $callback, $priority = 10, $argc = 1 ): void { $GLOBALS['hooks'][ $hook ] = array( $callback, $priority, $argc ); }
function add_filter( ...$args ): void {}
function sanitize_key( $value ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function sanitize_email( $value ): string { return (string) $value; }
function wp_unslash( $value ) { return $value; }
function is_admin(): bool { return false; }
function absint( $value ): int { return abs( (int) $value ); }
function get_query_var( $key ): int { return 69087; }
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function wp_mail( ...$args ): void { throw new RuntimeException( 'Real mail is forbidden.' ); }
class WP_Error {
	public function __construct( private string $code, public string $message = '' ) {}
	public function get_error_code(): string { return $this->code; }
}
class BSC_DB_Lock {
	public static function run( $key, $callback ) {
		return $GLOBALS['lock_fails'] ? new WP_Error( 'bsc_lock_timeout' ) : $callback();
	}
}
function wc_get_logger() {
	return new class {
		public function log( $level, $message, $context ): void { $GLOBALS['logs'][] = compact( 'level', 'message', 'context' ); }
	};
}
function as_has_scheduled_action( $hook, $args, $group ): bool { return isset( $GLOBALS['jobs'][ serialize( $args ) ] ); }
function as_schedule_single_action( $at, $hook, $args, $group, $unique ): int {
	if ( $GLOBALS['as_throws'] ) { throw new RuntimeException('Scheduler temporarily unavailable.'); }
	if ( $GLOBALS['scheduler_fails'] || $GLOBALS['as_fails'] ) { return 0; }
	$GLOBALS['jobs'][ serialize( $args ) ] = compact( 'at', 'hook', 'args', 'group' );
	return count( $GLOBALS['jobs'] );
}
function wp_next_scheduled( $hook, $args ) { return $GLOBALS['cron'][ serialize( $args ) ] ?? false; }
function wp_schedule_single_event( $at, $hook, $args ): bool {
	if ( $GLOBALS['scheduler_fails'] ) { return false; }
	$GLOBALS['cron'][ serialize( $args ) ] = $at;
	return true;
}
class Wompi_Portal_Pagos_Main {
	public static function get_setting( $key, $default = '' ) {
		return array( 'testmode' => 'no', 'event_secret_key' => $GLOBALS['event_secret'] )[ $key ] ?? $default;
	}
}
class Wompi_Portal_Pagos_API {
	public static function instance(): self { return new self(); }
	public function request( $method, $path, $body, $authenticated ): object {
		++$GLOBALS['api_calls'];
		if ( 'GET' !== $method || ! $authenticated || null !== $body ) { throw new RuntimeException( 'Read-only private API required.' ); }
		if ( $GLOBALS['api_throws'] ) { throw new RuntimeException( 'Sensitive upstream body must never be logged.' ); }
		return (object) array( 'data' => $GLOBALS['api_transaction'] );
	}
}
class WC_Order {
	public string $transaction_id = '';
	public array $meta = array();
	public bool $date_paid = false;
	public string $gateway = 'wompi';
	public function __construct( public int $id = 69087, public string $status = 'pending' ) {}
	public function get_id(): int { return $this->id; }
	public function get_status(): string { return $this->status; }
	public function get_payment_method(): string { return $this->gateway; }
	public function get_order_key(): string { return 'test_order_key'; }
	public function get_total(): string { return '1000.00'; }
	public function get_currency(): string { return 'COP'; }
	public function get_transaction_id(): string { return $this->transaction_id; }
	public function set_transaction_id( $id ): void { $this->transaction_id = $id; }
	public function get_meta( $key, $single = true ) { return $this->meta[ $key ] ?? ''; }
	public function update_meta_data( $key, $value ): void { $this->meta[ $key ] = $value; }
	public function read_meta_data( $force = false ): void { $this->meta = $GLOBALS['db'][ $this->id ]->meta; }
	public function is_paid(): bool { return in_array( $this->status, array( 'processing', 'completed' ), true ); }
	public function get_date_paid() { return $this->date_paid; }
	public function get_billing_email(): string { return ''; }
	public function get_billing_first_name(): string { return ''; }
	public function get_billing_phone(): string { return ''; }
	public function save(): int {
		++$GLOBALS['save_calls'];
		if ( $GLOBALS['save_calls'] === $GLOBALS['fail_save_at'] ) { return 0; }
		$GLOBALS['db'][ $this->id ] = clone $this;
		return $this->id;
	}
	public function payment_complete( $id ): bool {
		++$GLOBALS['payment_calls'];
		if ( 'false' === $GLOBALS['payment_mode'] ) { return false; }
		$this->status = 'date_only' === $GLOBALS['payment_mode'] ? 'pending' : ( 'stock_review' === $GLOBALS['payment_mode'] ? 'on-hold' : 'processing' );
		$this->date_paid = true;
		if ( 'unsaved' !== $GLOBALS['payment_mode'] ) { $this->save(); }
		return 'partial_failure' !== $GLOBALS['payment_mode'];
	}
	public function update_status( $status, $note ): bool {
		if ( $GLOBALS['status_fails'] ) { return false; }
		$this->status = $status;
		return (bool) $this->save();
	}
	public function add_order_note( $note ): int { $GLOBALS['notes'][] = $note; return count( $GLOBALS['notes'] ); }
}
function wc_get_order( $id ) { return isset( $GLOBALS['db'][ $id ] ) ? clone $GLOBALS['db'][ $id ] : false; }

require dirname( __DIR__ ) . '/inc/payments/wompi-compat.php';
require dirname( __DIR__ ) . '/inc/payments/wompi-security.php';

function reset_payment( string $status = 'pending' ): void {
	$GLOBALS['db'] = array( 69087 => new WC_Order( 69087, $status ) );
	$GLOBALS['api_transaction'] = (object) array( 'id' => 'test-transaction', 'reference' => '69087', 'status' => 'APPROVED', 'amount_in_cents' => 100000, 'currency' => 'COP' );
	foreach ( array( 'api_calls', 'save_calls', 'payment_calls', 'fail_save_at' ) as $key ) { $GLOBALS[ $key ] = 0; }
	foreach ( array( 'api_throws', 'lock_fails', 'scheduler_fails', 'as_fails', 'as_throws', 'status_fails' ) as $key ) { $GLOBALS[ $key ] = false; }
	foreach ( array( 'logs', 'notes', 'jobs', 'cron' ) as $key ) { $GLOBALS[ $key ] = array(); }
	$GLOBALS['payment_mode'] = 'normal';
	$GLOBALS['event_secret'] = 'local-event-secret';
	$_GET = array();
}
function event_body( array $changes = array() ): string {
	$event = array_replace_recursive( array(
		'event' => 'transaction.updated', 'environment' => 'prod', 'timestamp' => 1530291411,
		'data' => array( 'transaction' => array( 'id' => 'test-transaction', 'status' => 'APPROVED', 'amount_in_cents' => 100000 ) ),
		'signature' => array( 'properties' => array( 'transaction.id', 'transaction.status', 'transaction.amount_in_cents' ) ),
	), $changes );
	$t = $event['data']['transaction'];
	$event['signature']['checksum'] = hash( 'sha256', $t['id'] . $t['status'] . $t['amount_in_cents'] . $event['timestamp'] . 'local-event-secret' );
	return json_encode( $event );
}
$checks = 0;
function expect_payment( bool $condition, string $label ): void {
	++$GLOBALS['checks'];
	if ( ! $condition ) { throw new RuntimeException( $label ); }
}
function approval_seen(): bool {
	return in_array( hash( 'sha256', 'test-transaction:APPROVED' ), (array) wc_get_order( 69087 )->get_meta( '_bsc_wompi_events' ), true );
}
function process_event(): int { return bsc_wompi_process_webhook( 'POST', event_body() ); }

expect_payment( array( 'bsc_wompi_secure_webhook', -100, 1 ) === $GLOBALS['hooks']['woocommerce_api_wc_wompi'], 'The secure callback owns the vendor hook.' );
foreach ( array( 'pending', 'cancelled', 'failed', 'on-hold' ) as $initial ) {
	reset_payment( $initial );
	expect_payment( 200 === process_event(), 'A signed backend event succeeds without a browser return: ' . $initial );
	$paid = wc_get_order( 69087 );
	expect_payment( $paid->is_paid() && $paid->date_paid && 'test-transaction' === $paid->transaction_id && approval_seen(), 'Payment and ID persist: ' . $initial );
	expect_payment( 200 === process_event() && 1 === $GLOBALS['payment_calls'] && 1 === count( $GLOBALS['notes'] ), 'Duplicate events never repeat payment effects: ' . $initial );
}
foreach ( array( 'processing', 'preparing', 'shipped', 'completed', 'refunded' ) as $initial ) {
	reset_payment( $initial );
	$GLOBALS['db'][69087]->date_paid = true;
	expect_payment( 200 === process_event(), 'Late approval is reconciled: ' . $initial );
	$paid = wc_get_order( 69087 );
	expect_payment( $initial === $paid->status && 'test-transaction' === $paid->transaction_id && 0 === $GLOBALS['payment_calls'], 'Late approval preserves fulfillment without replaying hooks: ' . $initial );
	expect_payment( 200 === process_event() && 1 === count( $GLOBALS['notes'] ), 'Late duplicate keeps one verification note: ' . $initial );
}
reset_payment();
$GLOBALS['payment_mode'] = 'stock_review';
expect_payment( 200 === process_event() && 'on-hold' === wc_get_order(69087)->status && wc_get_order(69087)->date_paid, 'Approved payment may legitimately remain held for stock review.' );
expect_payment( 200 === process_event() && 1 === $GLOBALS['payment_calls'], 'A stock review hold must not replay payment completion.' );

foreach ( array( 'false', 'partial_failure', 'unsaved', 'date_only' ) as $failure ) {
	reset_payment();
	$GLOBALS['payment_mode'] = $failure;
	expect_payment( 503 === process_event(), 'Failed/incomplete persistence must request a retry: ' . $failure );
	expect_payment( ! approval_seen() && '' === wc_get_order(69087)->get_meta('_bsc_wompi_approved_id') && ! $GLOBALS['notes'] && 1 === count($GLOBALS['jobs']), 'Failed completion is never marked approved/handled: ' . $failure );
	$GLOBALS['payment_mode'] = 'normal';
	expect_payment( 200 === process_event() && approval_seen() && wc_get_order(69087)->date_paid, 'The same event recovers after transient failure: ' . $failure );
}
foreach ( array( 1, 3 ) as $save ) {
	reset_payment();
	$GLOBALS['fail_save_at'] = $save;
	expect_payment( 503 === process_event() && ! approval_seen(), 'A failed transaction/approval save must not acknowledge success.' );
	$GLOBALS['fail_save_at'] = 0;
	expect_payment( 200 === process_event() && approval_seen() && 1 === count($GLOBALS['notes']), 'Interrupted persistence recovers once.' );
}
reset_payment();
$GLOBALS['db'][69087]->meta['_bsc_wompi_events'] = array( hash('sha256','test-transaction:APPROVED') );
$GLOBALS['db'][69087]->meta['_bsc_wompi_approved_id'] = 'test-transaction';
expect_payment( 200 === process_event() && wc_get_order(69087)->date_paid && 1 === $GLOBALS['payment_calls'], 'Repair an old false-success marker instead of trusting it.' );

reset_payment('shipped');
$GLOBALS['db'][69087]->date_paid = true;
$GLOBALS['db'][69087]->transaction_id = 'original-approved';
$GLOBALS['db'][69087]->meta['_bsc_wompi_approved_id'] = 'original-approved';
expect_payment( 200 === process_event() && 'original-approved' === wc_get_order(69087)->transaction_id && 'multiple_approved_transactions' === wc_get_order(69087)->get_meta('_bsc_payment_review_required'), 'A second approved transaction is flagged without replacing the settled payment.' );
expect_payment( 200 === process_event() && 1 === count($GLOBALS['notes']), 'Duplicate conflicting approvals must not duplicate review notes.' );

foreach ( array( 'GET', 'bad_body', 'bad_signature', 'wrong_environment', 'wrong_type', 'missing_secret', 'oversized' ) as $failure ) {
	reset_payment();
	$body = event_body();
	$method = 'POST';
	if ( 'GET' === $failure ) { $method = 'GET'; }
	if ( 'bad_body' === $failure ) { $body = '{'; }
	if ( 'bad_signature' === $failure ) { $body = str_replace('APPROVED','DECLINED',$body); }
	if ( 'wrong_environment' === $failure ) { $body = event_body(array('environment'=>'test')); }
	if ( 'wrong_type' === $failure ) { $body = event_body(array('event'=>'unexpected.event')); }
	if ( 'missing_secret' === $failure ) { $GLOBALS['event_secret'] = ''; }
	if ( 'oversized' === $failure ) { $body = str_repeat('x',262145); }
	expect_payment( ('missing_secret' === $failure ? 503 : 400) === bsc_wompi_process_webhook($method,$body), 'Reject invalid input: ' . $failure );
	expect_payment( 0 === $GLOBALS['api_calls'] && 0 === $GLOBALS['save_calls'] && ! $GLOBALS['jobs'], 'Unverified events cannot query, mutate or queue payments: ' . $failure );
	expect_payment( count($GLOBALS['logs']) > 0, 'Every rejection has a technical reason: ' . $failure );
}
foreach ( array( array('reference'=>'99999'), array('amount_in_cents'=>100000.5), array('currency'=>'USD'), array('status'=>'UNKNOWN'), array('reference'=>array()) ) as $mismatch ) {
	reset_payment();
	$GLOBALS['api_transaction'] = (object) array_merge((array)$GLOBALS['api_transaction'],$mismatch);
	expect_payment( 400 === process_event() && 0 === $GLOBALS['payment_calls'] && ! approval_seen(), 'Reject an authoritative transaction that cannot be applied to this order.' );
}
reset_payment();
$GLOBALS['db'][69087]->gateway = 'bacs';
expect_payment( 400 === process_event() && 0 === $GLOBALS['save_calls'], 'Never apply Wompi to another gateway.' );
reset_payment();
$GLOBALS['lock_fails'] = true;
expect_payment( 503 === process_event() && 1 === count($GLOBALS['jobs']), 'Lock timeouts are retryable.' );
foreach ( array( 'missing', 'wrong_id', 'exception' ) as $failure ) {
	reset_payment();
	if ('missing' === $failure) { $GLOBALS['api_transaction'] = null; }
	if ('wrong_id' === $failure) { $GLOBALS['api_transaction']->id = 'unexpected-id'; }
	if ('exception' === $failure) { $GLOBALS['api_throws'] = true; }
	expect_payment( 503 === process_event() && ! approval_seen() && 1 === count($GLOBALS['jobs']), 'Recover an unavailable or invalid API response: ' . $failure );
	expect_payment( ! str_contains(json_encode($GLOBALS['logs']),'Sensitive upstream'), 'Exception bodies stay out of logs.' );
}
reset_payment();
$GLOBALS['api_transaction']->status = 'DECLINED';
$GLOBALS['status_fails'] = true;
expect_payment( 503 === process_event(), 'Failure to persist a decline is retryable too.' );
reset_payment();
process_event();
$GLOBALS['api_transaction']->status = 'DECLINED';
expect_payment( 200 === process_event() && 'processing' === wc_get_order(69087)->status, 'Late declines cannot undo a verified payment.' );
$GLOBALS['api_transaction']->status = 'VOIDED';
expect_payment( 200 === process_event() && 'voided' === wc_get_order(69087)->get_meta('_bsc_payment_review_required') && 'processing' === wc_get_order(69087)->status, 'A settled void requires review, not stock reversal.' );

foreach ( array( 'native', 'custom' ) as $return ) {
	reset_payment();
	$_GET = array('id'=>'test-transaction','key'=>'test_order_key','bsc_wompi_status'=>'DECLINED');
	if ('custom' === $return) { $_GET['bsc_wompi_return']='1'; }
	bsc_maybe_complete_wompi_return();
	expect_payment( wc_get_order(69087)->date_paid && 1 === $GLOBALS['api_calls'], 'A valid return uses API truth, with or without the custom marker.' );
	bsc_maybe_complete_wompi_return();
	expect_payment( 1 === $GLOBALS['payment_calls'] && 1 === count($GLOBALS['notes']), 'Reloading the return never repeats payment effects.' );
}
reset_payment();
$_GET = array('id'=>'test-transaction','key'=>'wrong-key');
bsc_maybe_complete_wompi_return();
expect_payment( 0 === $GLOBALS['api_calls'] && ! $GLOBALS['jobs'], 'Invalid order keys cannot query or queue a return.' );
reset_payment();
$_GET = array('id'=>'test-transaction','key'=>'test_order_key');
$GLOBALS['api_transaction']->reference = '99999';
bsc_maybe_complete_wompi_return();
expect_payment( 0 === $GLOBALS['save_calls'] && ! $GLOBALS['jobs'], 'Return cannot apply or queue another order transaction.' );
reset_payment();
$_GET = array('id'=>'test-transaction','key'=>'test_order_key');
$GLOBALS['api_transaction']->status = 'PENDING';
bsc_maybe_complete_wompi_return();
bsc_maybe_complete_wompi_return();
expect_payment( ! wc_get_order(69087)->date_paid && 1 === count($GLOBALS['jobs']), 'Pending browser returns queue one durable recovery.' );
$GLOBALS['api_transaction']->status = 'APPROVED';
$job = array_values($GLOBALS['jobs'])[0];
$GLOBALS['jobs'] = array();
call_user_func_array('bsc_wompi_retry_transaction',$job['args']);
expect_payment( wc_get_order(69087)->date_paid && ! $GLOBALS['jobs'], 'The background job confirms approval after the browser is gone.' );
reset_payment();
$GLOBALS['api_transaction'] = null;
expect_payment( 503 === process_event(), 'API outage starts durable recovery from the signed ID.' );
$job = array_values($GLOBALS['jobs'])[0];
expect_payment( 0 === $job['args'][1], 'Unsigned event references are not trusted for job ownership.' );
$GLOBALS['jobs'] = array();
bsc_wompi_retry_transaction(...$job['args']);
$next = array_values($GLOBALS['jobs'])[0];
expect_payment( 1 === $next['args'][2] && $next['at'] >= time()+299, 'Recovery uses increasing bounded delays.' );
$GLOBALS['jobs'] = array();
bsc_wompi_retry_transaction('test-transaction',0,7);
expect_payment( ! $GLOBALS['jobs'] && str_contains(json_encode($GLOBALS['logs']),'retry_exhausted'), 'Recovery stops and logs exhaustion.' );
reset_payment();
$GLOBALS['as_fails'] = true;
expect_payment( bsc_wompi_schedule_reconciliation('test-transaction',69087) && 1 === count($GLOBALS['cron']), 'WP-Cron fallback persists when Action Scheduler fails.' );
reset_payment();
$GLOBALS['as_throws'] = true;
expect_payment( bsc_wompi_schedule_reconciliation('test-transaction',69087) && 1 === count($GLOBALS['cron']), 'A scheduler exception also falls back to WP-Cron.' );
reset_payment();
$GLOBALS['scheduler_fails'] = true;
$GLOBALS['api_transaction']->status='PENDING';
expect_payment( 503 === process_event(), 'An unscheduled pending event must not be silently acknowledged.' );
reset_payment();
$GLOBALS['api_transaction']->reference='99999';
bsc_wompi_retry_transaction('test-transaction',69087,0);
expect_payment( 0 === $GLOBALS['save_calls'] && ! $GLOBALS['jobs'], 'A return recovery job stays bound to its verified order key.' );
reset_payment();
bsc_wompi_retry_transaction('test-transaction',69087,0,'test');
expect_payment( 0 === $GLOBALS['api_calls'] && ! $GLOBALS['jobs'], 'A queued sandbox payment cannot be queried or applied after a switch to production.' );
reset_payment();
$body = json_decode(event_body());
$body->data->transaction->customer_email='private@example.invalid';
$body->data->transaction->reference='99999';
expect_payment( 200 === bsc_wompi_process_webhook('POST',json_encode($body)) && wc_get_order(69087)->date_paid, 'Only API reference and signature-covered fields are authoritative.' );
$logs=json_encode($GLOBALS['logs']);
expect_payment( ! str_contains($logs,'private@example.invalid') && ! str_contains($logs,'local-event-secret') && ! str_contains($logs,'test_order_key'), 'Logs exclude customer data, secrets and order keys.' );
fwrite(STDOUT,"Wompi reconciliation regression: $checks checks passed.\n");
