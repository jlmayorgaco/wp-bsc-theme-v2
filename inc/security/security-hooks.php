<?php
/**
 * Security hardening and request throttling helpers.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'the_generator', '__return_empty_string' );
add_filter( 'xmlrpc_enabled', '__return_false' );

add_action( 'send_headers', 'bsc_send_security_headers' );
add_filter( 'rest_pre_serve_request', 'bsc_send_rest_security_headers' );

/**
 * Send baseline security headers shared by frontend and REST responses.
 */
function bsc_send_base_security_headers(): void {
	if ( headers_sent() ) {
		return;
	}

	header_remove( 'X-Powered-By' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: accelerometer=(), autoplay=(), camera=(self), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(self), usb=(), browsing-topics=()' );

	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
	}
}

/**
 * Send browser security headers.
 */
function bsc_send_security_headers(): void {
	bsc_send_base_security_headers();

	if ( headers_sent() ) {
		return;
	}

	header( 'X-Frame-Options: SAMEORIGIN' );

	if ( ! is_admin() ) {
		$upgrade_insecure_requests = ( 'local' === wp_get_environment_type() && ! is_ssl() ) ? '' : '; upgrade-insecure-requests';

		header( "Content-Security-Policy: default-src 'self' https: data: blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: blob:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data: blob:; font-src 'self' https: data:; connect-src 'self' https: wss:; frame-src 'self' https:; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self' https:" . $upgrade_insecure_requests );
	}
}

/**
 * Send security headers on REST responses too.
 *
 * @param bool $served Whether the REST response has already been served.
 */
function bsc_send_rest_security_headers( bool $served ): bool {
	bsc_send_base_security_headers();

	return $served;
}

add_filter( 'rest_endpoints', 'bsc_restrict_rest_user_endpoints' );

/**
 * Hide public REST user endpoints from non-admin actors.
 *
 * @param array $endpoints Registered REST endpoints.
 */
function bsc_restrict_rest_user_endpoints( array $endpoints ): array {
	if ( current_user_can( 'list_users' ) ) {
		return $endpoints;
	}

	unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );

	return $endpoints;
}

add_filter( 'rest_endpoints', 'bsc_hide_public_blog_rest_endpoints' );

/**
 * Keep the unused blog API routes unavailable to visitors.
 *
 * @param array $endpoints Registered REST endpoints.
 */
function bsc_hide_public_blog_rest_endpoints( array $endpoints ): array {
	if ( current_user_can( 'edit_posts' ) ) {
		return $endpoints;
	}

	foreach ( array_keys( $endpoints ) as $route ) {
		if ( preg_match( '#^/wp/v2/(?:posts|categories|tags)(?:/|$)#', $route ) ) {
			unset( $endpoints[ $route ] );
		}
	}

	return $endpoints;
}

add_filter( 'rest_post_search_query', 'bsc_exclude_blog_posts_from_rest_search' );

/**
 * Keep product and page REST searches available without listing blog posts.
 *
 * @param array $args REST search query args.
 */
function bsc_exclude_blog_posts_from_rest_search( array $args ): array {
	if ( current_user_can( 'edit_posts' ) ) {
		return $args;
	}

	$args['post_type'] = array_values( array_diff( (array) ( $args['post_type'] ?? array() ), array( 'post' ) ) );
	if ( empty( $args['post_type'] ) ) {
		$args['post_type'] = array( 'page' );
		$args['post__in']  = array( 0 );
	}

	return $args;
}

add_filter( 'rest_term_search_query', 'bsc_exclude_blog_terms_from_rest_search' );

/**
 * Exclude blog terms from public REST search results.
 *
 * @param array $args REST term search query args.
 */
function bsc_exclude_blog_terms_from_rest_search( array $args ): array {
	if ( current_user_can( 'edit_posts' ) ) {
		return $args;
	}

	$args['taxonomy'] = array_values( array_diff( (array) ( $args['taxonomy'] ?? array() ), array( 'category', 'post_tag' ) ) );
	if ( empty( $args['taxonomy'] ) ) {
		$args['taxonomy'] = array( 'product_cat' );
		$args['include']  = array( 0 );
	}

	return $args;
}

add_action( 'template_redirect', 'bsc_disable_public_blog_routes', 0 );

/**
 * Return a real 404 for unused author, blog and WordPress feed routes.
 */
function bsc_disable_public_blog_routes(): void {
	$feed = (string) get_query_var( 'feed' );
	if ( 'bsc-google-merchant' === $feed ) {
		return;
	}

	if (
		! is_author()
		&& ! ( is_home() && ! is_front_page() )
		&& ! is_singular( 'post' )
		&& ! is_category()
		&& ! is_tag()
		&& ! is_date()
		&& ! is_feed()
		&& 'users' !== get_query_var( 'sitemap' )
	) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	remove_action( 'template_redirect', 'redirect_canonical' );
}

add_action(
	'wp_login_failed',
	function ( string $username ): void {
		if ( 'local' === wp_get_environment_type() ) {
			return;
		}

		$key   = 'bsc_lf_' . md5( bsc_get_request_ip() );
		$fails = (int) get_transient( $key );
		set_transient( $key, $fails + 1, 15 * MINUTE_IN_SECONDS );
	}
);

add_filter(
	'authenticate',
	function ( $user, string $username, string $password ) {
		if ( 'local' === wp_get_environment_type() ) {
			return $user;
		}

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

	if ( defined( 'BSC_TRUST_PROXY_HEADERS' ) && BSC_TRUST_PROXY_HEADERS && bsc_is_trusted_proxy( $ip ) ) {
		$proxy_headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
		);

		foreach ( $proxy_headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$value     = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			$candidate = trim( explode( ',', $value )[0] );

			if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				$ip = $candidate;
				break;
			}
		}
	}

	return $ip !== '' ? $ip : 'unknown';
}

/**
 * Validate the immediate proxy before trusting forwarding headers.
 * Configure BSC_TRUSTED_PROXY_CIDRS as a comma-separated list of proxy IPs/CIDRs.
 */
function bsc_is_trusted_proxy( string $ip ): bool {
	$configured = defined( 'BSC_TRUSTED_PROXY_CIDRS' ) ? BSC_TRUSTED_PROXY_CIDRS : array();
	if ( ! is_array( $configured ) ) {
		$configured = preg_split( '/[\s,]+/', (string) $configured, -1, PREG_SPLIT_NO_EMPTY );
	}

	foreach ( (array) $configured as $network ) {
		$network = trim( (string) $network );
		if ( '' !== $network && bsc_ip_in_cidr( $ip, $network ) ) {
			return true;
		}
	}

	return false;
}

function bsc_ip_in_cidr( string $ip, string $cidr ): bool {
	$parts  = explode( '/', trim( $cidr ), 2 );
	$network = trim( (string) ( $parts[0] ?? '' ) );
	$ip_bin = filter_var( $ip, FILTER_VALIDATE_IP ) ? inet_pton( $ip ) : false;
	$net_bin = filter_var( $network, FILTER_VALIDATE_IP ) ? inet_pton( $network ) : false;

	if ( false === $ip_bin || false === $net_bin || strlen( $ip_bin ) !== strlen( $net_bin ) ) {
		return false;
	}

	$max_bits = strlen( $net_bin ) * 8;
	$bits     = isset( $parts[1] ) && is_numeric( $parts[1] ) ? (int) $parts[1] : $max_bits;
	if ( $bits < 0 || $bits > $max_bits ) {
		return false;
	}

	$full_bytes = intdiv( $bits, 8 );
	if ( $full_bytes > 0 && substr( $ip_bin, 0, $full_bytes ) !== substr( $net_bin, 0, $full_bytes ) ) {
		return false;
	}

	$remaining_bits = $bits % 8;
	if ( 0 === $remaining_bits ) {
		return true;
	}

	$mask = ( 0xFF << ( 8 - $remaining_bits ) ) & 0xFF;
	return ( ord( $ip_bin[ $full_bytes ] ) & $mask ) === ( ord( $net_bin[ $full_bytes ] ) & $mask );
}

function bsc_rate_limit_key( string $scope ): string {
	$user_id = get_current_user_id();
	$actor   = $user_id > 0 ? 'u' . $user_id : 'ip' . bsc_get_request_ip();

	return 'bsc_rl_' . sanitize_key( $scope ) . '_' . md5( $actor );
}

function bsc_rate_limit_passed( string $scope, int $limit, int $window_seconds ): bool {
	$key  = bsc_rate_limit_key( $scope );
	if ( function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache() ) {
		$group = 'bsc-rate-limit';
		if ( false === wp_cache_get( $key, $group ) ) {
			wp_cache_add( $key, 0, $group, $window_seconds );
		}

		$hits = wp_cache_incr( $key, 1, $group );
		if ( false !== $hits ) {
			if ( $hits > $limit ) {
				do_action( 'bsc_rate_limit_blocked', sanitize_key( $scope ), bsc_get_request_ip(), get_current_user_id() );
				return false;
			}

			return true;
		}
	}

	$result = BSC_DB_Lock::run( 'rate:' . $key, static function () use ( $key, $scope, $limit, $window_seconds ) {
		wp_cache_delete( '_transient_' . $key, 'options' );
		wp_cache_delete( '_transient_timeout_' . $key, 'options' );
		$hits = (int) get_transient( $key );
		if ( $hits >= $limit ) {
			do_action( 'bsc_rate_limit_blocked', sanitize_key( $scope ), bsc_get_request_ip(), get_current_user_id() );
			return false;
		}
		return set_transient( $key, $hits + 1, $window_seconds );
	}, 0 );
	return true === $result;
}

function bsc_rate_limit( string $scope, int $limit, int $window_seconds ): void {
	if ( bsc_rate_limit_passed( $scope, $limit, $window_seconds ) ) {
		return;
	}

	wp_send_json_error(
		array(
			'message'     => __( 'Demasiadas solicitudes. Intenta de nuevo en un momento.', 'bsc-2-0' ),
			'status'      => 'rate_limited',
			'retry_after' => $window_seconds,
		),
		429
	);
}

add_action( 'bsc_rate_limit_blocked', 'bsc_record_rate_limit_blocked', 10, 3 );

function bsc_record_rate_limit_blocked( string $scope, string $ip, int $user_id ): void {
	$rows = get_option( 'bsc_rate_limit_blocked_log', array() );

	if ( ! is_array( $rows ) ) {
		$rows = array();
	}

	$rows[] = array(
		'blocked_at' => current_time( 'mysql' ),
		'scope'      => sanitize_key( $scope ),
		'ip'         => substr( wp_hash( $ip ), 0, 16 ),
		'user_id'    => max( 0, $user_id ),
	);

	update_option( 'bsc_rate_limit_blocked_log', array_slice( $rows, -100 ), false );
}

function bsc_get_recent_rate_limit_blocks( int $limit = 20 ): array {
	$rows = get_option( 'bsc_rate_limit_blocked_log', array() );

	if ( ! is_array( $rows ) ) {
		return array();
	}

	return array_reverse( array_slice( $rows, -1 * max( 1, $limit ) ) );
}

function bsc_count_rate_limit_blocks_since( int $seconds ): int {
	$rows = get_option( 'bsc_rate_limit_blocked_log', array() );

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
			++$count;
		}
	}

	return $count;
}
