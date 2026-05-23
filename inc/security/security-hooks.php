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

	if ( defined( 'BSC_TRUST_PROXY_HEADERS' ) && BSC_TRUST_PROXY_HEADERS ) {
		$proxy_headers = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
		];

		foreach ( $proxy_headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			$candidate = trim( explode( ',', $value )[0] );

			if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				$ip = $candidate;
				break;
			}
		}
	}

	return $ip !== '' ? $ip : 'unknown';
}

function bsc_rate_limit_key( string $scope ): string {
	$user_id = get_current_user_id();
	$actor   = $user_id > 0 ? 'u' . $user_id : 'ip' . bsc_get_request_ip();

	return 'bsc_rl_' . sanitize_key( $scope ) . '_' . md5( $actor );
}

function bsc_rate_limit_passed( string $scope, int $limit, int $window_seconds ): bool {
	$key  = bsc_rate_limit_key( $scope );
	$hits = (int) get_transient( $key );

	if ( $hits >= $limit ) {
		do_action( 'bsc_rate_limit_blocked', sanitize_key( $scope ), bsc_get_request_ip(), get_current_user_id() );
		return false;
	}

	set_transient( $key, $hits + 1, $window_seconds );
	return true;
}

function bsc_rate_limit( string $scope, int $limit, int $window_seconds ): void {
	if ( bsc_rate_limit_passed( $scope, $limit, $window_seconds ) ) {
		return;
	}

	wp_send_json_error(
		[
			'message'     => __( 'Demasiadas solicitudes. Intenta de nuevo en un momento.', 'bsc-2-0' ),
			'status'      => 'rate_limited',
			'retry_after' => $window_seconds,
		],
		429
	);
}

add_action( 'bsc_rate_limit_blocked', 'bsc_record_rate_limit_blocked', 10, 3 );

function bsc_record_rate_limit_blocked( string $scope, string $ip, int $user_id ): void {
	$rows = get_option( 'bsc_rate_limit_blocked_log', [] );

	if ( ! is_array( $rows ) ) {
		$rows = [];
	}

	$rows[] = [
		'blocked_at' => current_time( 'mysql' ),
		'scope'      => sanitize_key( $scope ),
		'ip'         => sanitize_text_field( $ip ),
		'user_id'    => max( 0, $user_id ),
	];

	update_option( 'bsc_rate_limit_blocked_log', array_slice( $rows, -100 ), false );
}

function bsc_get_recent_rate_limit_blocks( int $limit = 20 ): array {
	$rows = get_option( 'bsc_rate_limit_blocked_log', [] );

	if ( ! is_array( $rows ) ) {
		return [];
	}

	return array_reverse( array_slice( $rows, -1 * max( 1, $limit ) ) );
}

function bsc_count_rate_limit_blocks_since( int $seconds ): int {
	$rows = get_option( 'bsc_rate_limit_blocked_log', [] );

	if ( ! is_array( $rows ) ) {
		return 0;
	}

	$threshold = current_time( 'timestamp' ) - max( 1, $seconds );
	$count     = 0;

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$timestamp = strtotime( (string) ( $row['blocked_at'] ?? '' ) );

		if ( false !== $timestamp && $timestamp >= $threshold ) {
			$count++;
		}
	}

	return $count;
}
