<?php
/**
 * Reject malformed REST batch paths before affected WordPress core releases
 * try to dispatch a WP_Error as a WP_REST_Request.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'rest_pre_dispatch', 'bsc_reject_invalid_rest_batch_paths', 9, 3 );

/**
 * Reject a batch whose subrequest path cannot be parsed.
 *
 * WordPress 6.8 can put a WP_Error in its batch request list when wp_parse_url()
 * returns false, then call WP_REST_Request methods on that error. This filter
 * runs on the outer request, before the core batch callback.
 *
 * @param mixed           $result  Result from an earlier pre-dispatch filter.
 * @param WP_REST_Server  $server  REST server (unused).
 * @param WP_REST_Request $request Current REST request.
 * @return mixed Original result or a 400 error for an unparseable batch path.
 */
function bsc_reject_invalid_rest_batch_paths( $result, WP_REST_Server $server, WP_REST_Request $request ) {
	if ( null !== $result || '/batch/v1' !== $request->get_route() ) {
		return $result;
	}

	$requests = $request->get_param( 'requests' );
	if ( ! is_array( $requests ) ) {
		return $result; // Let the core schema report invalid request shapes.
	}

	foreach ( $requests as $subrequest ) {
		if ( ! is_array( $subrequest ) || ! isset( $subrequest['path'] ) || ! is_string( $subrequest['path'] ) ) {
			continue; // The core schema validates required fields and types.
		}

		if ( false === wp_parse_url( $subrequest['path'] ) ) {
			return new WP_Error(
				'bsc_rest_batch_invalid_path',
				__( 'La ruta de una solicitud por lotes no es válida.', 'bsc-2-0' ),
				array( 'status' => 400 )
			);
		}
	}

	return $result;
}
