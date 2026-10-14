<?php
/** Regression checks for the WordPress 6.8 REST batch compatibility guard. */

define( 'ABSPATH', __DIR__ );

$registered_filters = array();

function add_filter( $hook, $callback, $priority, $accepted_args ) {
	global $registered_filters;
	$registered_filters[] = compact( 'hook', 'callback', 'priority', 'accepted_args' );
}

function wp_parse_url( $url ) {
	return parse_url( $url );
}

function __( $message, $domain = '' ) {
	return $message;
}

class WP_REST_Server {}

class WP_REST_Request {
	private $route;
	private $requests;

	public function __construct( $route, $requests ) {
		$this->route    = $route;
		$this->requests = $requests;
	}

	public function get_route() {
		return $this->route;
	}

	public function get_param( $name ) {
		return 'requests' === $name ? $this->requests : null;
	}
}

class WP_Error {
	public $code;
	public $data;

	public function __construct( $code, $message, $data ) {
		$this->code = $code;
		$this->data = $data;
	}
}

require_once dirname( __DIR__ ) . '/inc/security/rest-batch-compat.php';

function bsc_assert_batch( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$server = new WP_REST_Server();
bsc_assert_batch( 1 === count( $registered_filters ), 'Batch guard must register one filter.' );
bsc_assert_batch( 'rest_pre_dispatch' === $registered_filters[0]['hook'], 'Batch guard must run before core dispatch.' );
bsc_assert_batch( 9 === $registered_filters[0]['priority'] && 3 === $registered_filters[0]['accepted_args'], 'Batch guard filter signature changed.' );

$bad = new WP_REST_Request( '/batch/v1', array( array( 'path' => 'http://user@:80' ) ) );
$result = bsc_reject_invalid_rest_batch_paths( null, $server, $bad );
bsc_assert_batch( $result instanceof WP_Error, 'Unparseable batch path must be rejected before WordPress core.' );
bsc_assert_batch( 'bsc_rest_batch_invalid_path' === $result->code && 400 === $result->data['status'], 'Invalid batch path must return HTTP 400.' );

$mixed = new WP_REST_Request( '/batch/v1', array( array( 'path' => '/wp/v2/posts' ), array( 'path' => 'http://user@:80' ) ) );
bsc_assert_batch( bsc_reject_invalid_rest_batch_paths( null, $server, $mixed ) instanceof WP_Error, 'A malformed later subrequest must reject the whole batch.' );

$good = new WP_REST_Request( '/batch/v1', array( array( 'path' => '/wp/v2/posts?per_page=1' ) ) );
bsc_assert_batch( null === bsc_reject_invalid_rest_batch_paths( null, $server, $good ), 'Valid batch paths must reach core.' );

$other_route = new WP_REST_Request( '/wp/v2/posts', array( array( 'path' => 'http://user@:80' ) ) );
bsc_assert_batch( null === bsc_reject_invalid_rest_batch_paths( null, $server, $other_route ), 'Other REST routes must not be changed.' );

$already_handled = new WP_Error( 'previous_filter', 'Handled.', array( 'status' => 403 ) );
bsc_assert_batch( $already_handled === bsc_reject_invalid_rest_batch_paths( $already_handled, $server, $bad ), 'Earlier filter results must be preserved.' );

$invalid_shape = new WP_REST_Request( '/batch/v1', 'not-an-array' );
bsc_assert_batch( null === bsc_reject_invalid_rest_batch_paths( null, $server, $invalid_shape ), 'Core must validate non-array request shapes.' );

fwrite( STDOUT, "REST batch compatibility checks passed.\n" );
