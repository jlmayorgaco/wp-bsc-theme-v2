<?php
/**
 * Security hardening and request throttling helpers.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'the_generator', '__return_empty_string' );
add_filter( 'xmlrpc_enabled', '__return_false' );

add_action(
	'wp_login_failed',
	function ( string $username ): void {
		$key   = 'bsc_lf_' . md5( bsc_get_request_ip() );
		$fails = (int) get_transient( $key );
		set_transient( $key, $fails + 1, 15 * MINUTE_IN_SECONDS );
	}
);

add_filter(
	'authenticate',
	function ( $user, string $username, string $password ) {
		if ( empty( $username ) && empty( $password ) ) {
			return $user;
		}

		$key   = 'bsc_lf_' . md5( bsc_get_request_ip() );
		$fails = (int) get_transient( $key );

		if ( $fails >= 5 ) {
			return new WP_Error(
				'bsc_too_many_retries',
				__( 'Demasiados intentos fallidos. Por favor espera 15 minutos antes de intentar de nuevo.', 'bsc-2-0' )
			);
		}

		return $user;
	},
	30,
	3
);

function bsc_get_request_ip(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: 'unknown';

	return $ip !== '' ? $ip : 'unknown';
}

function bsc_rate_limit_passed( string $scope, int $limit, int $window_seconds ): bool {
	$key  = 'bsc_rl_' . sanitize_key( $scope ) . '_' . md5( bsc_get_request_ip() );
	$hits = (int) get_transient( $key );

	if ( $hits >= $limit ) {
		return false;
	}

	set_transient( $key, $hits + 1, $window_seconds );
	return true;
}
