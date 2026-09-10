<?php
/**
 * Frontend routing guards.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check whether the current request is a WooCommerce password reset.
 */
function bsc_is_account_password_reset_request(): bool {
	global $wp;

	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'lost-password' ) ) {
		return true;
	}

	return isset( $wp->query_vars['lost-password'] );
}

/**
 * Redirect anonymous account-page visitors to the custom login page.
 */
function bsc_redirect_my_account_guests() {
	if (
		function_exists( 'is_account_page' )
		&& is_account_page()
		&& ! is_user_logged_in()
		&& ! bsc_is_account_password_reset_request()
	) {
		wp_safe_redirect( home_url( '/login/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'bsc_redirect_my_account_guests' );

/**
 * Check whether the current request is the local storefront oracle.
 *
 * The saved parking setting remains untouched so production keeps its configured state.
 */
function bsc_is_local_storefront_request(): bool {
	if ( 'local' !== wp_get_environment_type() ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Parsed as a hostname and compared with one exact local value.
	$raw_host = isset( $_SERVER['HTTP_HOST'] ) ? (string) wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
	$host     = wp_parse_url( 'http://' . $raw_host, PHP_URL_HOST );

	return is_string( $host ) && 'bsc.local' === strtolower( rtrim( $host, '.' ) ); // bsc-local-host-exception.
}

/**
 * Check whether the BSC parking page should be shown.
 */
function bsc_is_parking_page_enabled(): bool {
	$saved = get_option( 'bsc_parking_page_enabled', null );

	if ( null !== $saved ) {
		return 1 === (int) $saved;
	}

	if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'wc_admin_get_feature_config' ) ) {
		return false;
	}

	if ( 'yes' === get_option( 'woocommerce_coming_soon', 'no' ) ) {
		return true;
	}

	return 'coming-soon' === get_option( 'woocommerce_coming_soon_visibility', 'coming-soon' );
}

/**
 * Purge page caches whenever the BSC parking mode changes.
 *
 * WordOps can otherwise keep serving the previous storefront state from its
 * Nginx FastCGI cache even after the WordPress option has been updated.
 */
function bsc_purge_parking_page_cache(): void {
	wp_cache_flush();

	/**
	 * Ask Nginx Helper (used by WordOps FastCGI cache) to purge every cached URL.
	 * The action is harmless when Nginx Helper is not installed.
	 */
	do_action( 'rt_nginx_helper_purge_all' );
}
add_action( 'update_option_bsc_parking_page_enabled', 'bsc_purge_parking_page_cache', 10, 0 );

/**
 * Prevent the temporary parking response from entering a page cache.
 */
function bsc_send_parking_page_nocache_headers(): void {
	nocache_headers();
	header( 'X-Accel-Expires: 0' );
}

/**
 * Check whether the current request must stay accessible while parking is enabled.
 */
function bsc_is_parking_page_auth_request(): bool {
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Normalized for path comparison only.
	$request_uri   = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$request_path  = wp_parse_url( $request_uri, PHP_URL_PATH );
	$request_query = wp_parse_url( $request_uri, PHP_URL_QUERY );

	if ( ! is_string( $request_path ) ) {
		$request_path = '';
	}

	$request_action = '';

	if ( is_string( $request_query ) ) {
		parse_str( $request_query, $request_query_args );
		$request_action = isset( $request_query_args['action'] ) ? sanitize_key( (string) $request_query_args['action'] ) : '';
	}

	$request_path = '/' . trim( strtolower( rawurldecode( $request_path ) ), '/' );

	$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path = is_string( $home_path ) ? '/' . trim( strtolower( $home_path ), '/' ) : '';

	if ( '' !== $home_path && '/' !== $home_path && 0 === strpos( $request_path, $home_path . '/' ) ) {
		$request_path = substr( $request_path, strlen( $home_path ) );
	}

	if ( in_array(
		$request_path,
		array(
			'/login',
			'/register',
			'/registro-familia-bubbles',
			'/signup',
			'/sign-up',
			'/wp-signup.php',
		),
		true
	) ) {
		return true;
	}

	foreach ( array( '/my-account/lost-password', '/mi-cuenta/lost-password' ) as $lost_password_path ) {
		if ( $request_path === $lost_password_path || str_starts_with( $request_path, $lost_password_path . '/' ) ) {
			return true;
		}
	}

	$allowed_wp_login_actions = array(
		'',
		'lostpassword',
		'retrievepassword',
		'rp',
		'resetpass',
	);

	return '/wp-login.php' === $request_path && in_array( $request_action, $allowed_wp_login_actions, true );
}

/**
 * Keep WordPress and BSC auth routes available when WooCommerce coming soon is active.
 *
 * @param mixed $value WooCommerce coming soon option value.
 * @return mixed
 */
function bsc_allow_auth_routes_through_woocommerce_coming_soon( $value ) {
	return bsc_is_local_storefront_request() || bsc_is_parking_page_auth_request() ? 'no' : $value;
}
add_filter( 'option_woocommerce_coming_soon', 'bsc_allow_auth_routes_through_woocommerce_coming_soon', 0 );

/**
 * Keep WordPress and BSC auth routes out of WooCommerce coming soon visibility mode.
 *
 * @param mixed $value WooCommerce coming soon visibility option value.
 * @return mixed
 */
function bsc_allow_auth_routes_through_woocommerce_coming_soon_visibility( $value ) {
	return bsc_is_local_storefront_request() || bsc_is_parking_page_auth_request() ? 'live' : $value;
}
add_filter( 'option_woocommerce_coming_soon_visibility', 'bsc_allow_auth_routes_through_woocommerce_coming_soon_visibility', 0 );

/**
 * Check whether the current user can bypass the parking page.
 */
function bsc_current_user_can_bypass_parking_page(): bool {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers manage_woocommerce.
	return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
}

/**
 * Render the BSC parking page before public template routing.
 */
function bsc_maybe_render_parking_page(): void {
	if (
		is_admin()
		|| bsc_is_local_storefront_request()
		|| bsc_current_user_can_bypass_parking_page()
		|| bsc_is_parking_page_auth_request()
		|| ! bsc_is_parking_page_enabled()
	) {
		return;
	}

	$template = get_stylesheet_directory() . '/woocommerce/coming-soon.php';

	if ( file_exists( $template ) ) {
		bsc_send_parking_page_nocache_headers();
		include $template;
		exit;
	}
}
add_action( 'template_redirect', 'bsc_maybe_render_parking_page', 0 );
