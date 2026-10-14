<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_bsc_redeem_points', 'bsc_bp_ajax_redeem_points' );

function bsc_bp_ajax_redeem_points() {
	check_ajax_referer( 'bsc_redeem_points', 'nonce' );
	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'bubble_points_redeem', 5, 10 * MINUTE_IN_SECONDS );
	}

	if (!is_user_logged_in()) {
		wp_send_json_error( array( 'message' => 'Debes iniciar sesión.' ) );
	}

	$user_id = get_current_user_id();
	$points  = isset( $_POST['points'] ) ? absint( wp_unslash( $_POST['points'] ) ) : 0;

	$tiers = require dirname( __DIR__ ) . '/config/coupons.php';
	$values = array_column( $tiers, 'price', 'points' );
	if ( ! isset( $values[ $points ] ) ) {
		wp_send_json_error( array( 'message' => 'Datos inválidos para redención.' ) );
	}

	if ( ! BSC_DB_Lock::acquire( 'points-user:' . $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Hay un canje en curso. Intenta nuevamente.' ), 409 );
	}
	wp_cache_delete( $user_id, 'user_meta' );
	register_shutdown_function( static function () use ( $user_id ) { BSC_DB_Lock::release( 'points-user:' . $user_id ); wp_cache_delete( $user_id, 'user_meta' ); } );
	$request_id = isset( $_POST['request_id'] ) && is_string( $_POST['request_id'] ) ? sanitize_key( wp_unslash( $_POST['request_id'] ) ) : '';
	if ( ! preg_match( '/^[a-f0-9-]{36}$/D', $request_id ) ) { wp_send_json_error( array( 'message' => 'Recarga la página antes de canjear puntos.' ), 400 ); }
	$existing = get_posts( array( 'post_type' => 'shop_coupon', 'post_status' => 'publish', 'author' => $user_id, 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_bsc_bp_request_id', 'meta_value' => $request_id ) );
	if ( $existing ) {
		$coupon = new WC_Coupon( $existing[0] );
		wp_send_json_success( array( 'message' => 'Este canje ya se completó.', 'coupon_code' => $coupon->get_code(), 'coupon_value' => (int) $coupon->get_amount(), 'points_used' => (int) get_post_meta( $existing[0], '_bsc_bp_points_used', true ), 'balance_left' => bsc_bp_get_balance( $user_id ) ) );
	}
	// A draft coupon cannot be spent while its debit is being recorded.
	$balance = bsc_bp_get_balance( $user_id );
	if ($balance < $points) {
		wp_send_json_error( array( 'message' => 'No tienes puntos suficientes.' ) );
	}

	// 2. Convert points to coupon value before any side effects
	$coupon_value = (int) $values[ $points ];
	$coupon_code  = 'BSC_' . strtoupper( wp_generate_password( 8, false, false ) );

	$result = BSC_DB_Lock::transaction( static function () use ( $user_id, $points, $coupon_code, $coupon_value, $request_id ) {
	$coupon_id = wp_insert_post(
		array(
			'post_title'   => $coupon_code,
			'post_content' => 'Cupón generado por redención de Bubble Points',
			'post_status'  => 'draft',
			'post_type'    => 'shop_coupon',
			'post_author'  => $user_id,
		)
	);

	if (!$coupon_id || is_wp_error( $coupon_id )) {
		return new WP_Error( 'coupon_create_failed', 'No se pudo crear el cupón.' );
	}

	$metadata = array(
		'discount_type' => 'fixed_cart', 'coupon_amount' => $coupon_value,
		'individual_use' => 'yes', 'usage_limit' => 1,
		'customer_email' => array( sanitize_email( wp_get_current_user()->user_email ) ),
		'date_expires' => strtotime( '+60 days' ),
		'_bsc_bp_user_id' => $user_id, '_bsc_bp_points_used' => $points,
		'_bsc_bp_request_id' => $request_id,
	);
	foreach ( $metadata as $key => $value ) {
		if ( false === update_post_meta( $coupon_id, $key, $value ) ) {
			return new WP_Error( 'coupon_metadata_failed', 'No se pudo configurar el cupón. No se descontaron puntos.' );
		}
	}

	// 4. Deduct points only after coupon creation succeeds
	$ledger = bsc_bp_add_ledger_entry(
		$user_id,
		-$points,
		'redeem',
		null,
		array(
			'event_key'    => 'redeem:coupon:' . $coupon_id,
			'context'      => 'coupon_redeem',
			'coupon_id'    => (int) $coupon_id,
			'coupon_code'  => $coupon_code,
			'points_used'  => $points,
			'coupon_value' => $coupon_value,
			'timestamp'    => current_time( 'mysql' ),
		)
	);

	if (!$ledger['ok']) {
		return new WP_Error( 'coupon_debit_failed', 'No se pudo descontar el saldo. Intenta nuevamente.' );
	}

	if ( false === update_post_meta( $coupon_id, '_bsc_bp_ledger_id', (int) $ledger['insert_id'] ) ) {
		return new WP_Error( 'coupon_ledger_link_failed', 'No se pudo activar el cupón. No se descontaron puntos.' );
	}
	$published = wp_update_post( array( 'ID' => $coupon_id, 'post_status' => 'publish' ), true );
	if ( is_wp_error( $published ) || ! $published ) {
		return new WP_Error( 'coupon_publish_failed', 'No se pudo activar el cupón.' );
	}
	return $ledger;
	} );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 );
	}

	// 5. Return success
	wp_send_json_success(
		array(
			'message'      => 'Cupón creado correctamente.',
			'coupon_code'  => $coupon_code,
			'coupon_value' => $coupon_value,
			'points_used'  => $points,
			'balance_left' => $result['balance'],
		)
	);
}

/**
 * Convert currency (COP) to Bubble Points
 * Rule: $75,000 COP = 1,000 points
 */
function currency_to_points( $currency ) {
	return floor( ( $currency * 1000 ) / 75000 );
}

/**
 * Convert Bubble Points to currency (COP)
 * Rule: 1,000 points = $75,000 COP
 */
function points_to_currency( $points ) {
	return (int) round( ( $points * 75000 ) / 1000 );
}
