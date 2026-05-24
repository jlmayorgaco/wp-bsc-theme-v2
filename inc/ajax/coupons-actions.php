<?php
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/checkout-review-summary-helpers.php';

add_action( 'wp_ajax_apply_coupon', 'bsc_apply_coupon' );
add_action( 'wp_ajax_nopriv_apply_coupon', 'bsc_apply_coupon' );

function bsc_apply_coupon() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );
	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'coupon_apply', 20, MINUTE_IN_SECONDS );
	}

	if ( ! WC()->cart ) {
		wp_send_json_error(
			array(
				'message' => 'Carrito no disponible.',
				'status'  => 'error',
			)
		);
	}

	$coupon_code = bsc_get_posted_coupon_code();

	if ( $coupon_code === '' ) {
		wp_send_json_error(
			array(
				'message' => 'Ingresa un codigo de cupon para aplicarlo.',
				'status'  => 'warning',
			)
		);
	}

	$coupon = new WC_Coupon( $coupon_code );

	if ( ! $coupon->get_id() || ! wc_is_same_coupon( $coupon->get_code(), $coupon_code ) ) {
		wp_send_json_error(
			array(
				'message' => 'El cupon ingresado no existe o no es valido.',
				'status'  => 'error',
			)
		);
	}

	if ( WC()->cart->has_discount( $coupon_code ) ) {
		wp_send_json_error(
			array(
				'message' => 'Este cupon ya esta aplicado.',
				'status'  => 'warning',
			)
		);
	}

	$expires = $coupon->get_date_expires();
	if ( $expires && time() > $expires->getTimestamp() ) {
		wp_send_json_error(
			array(
				'message' => 'Este cupon ya expiro.',
				'status'  => 'error',
			)
		);
	}

	if ( function_exists( 'wc_clear_notices' ) ) {
		wc_clear_notices();
	}

	$applied = WC()->cart->apply_coupon( $coupon_code );

	if ( ! $applied ) {
		$message = bsc_get_coupon_notice_message( 'No pudimos aplicar este cupon. Revisa las condiciones e intentalo de nuevo.' );

		if ( function_exists( 'wc_clear_notices' ) ) {
			wc_clear_notices();
		}

		wp_send_json_error(
			array(
				'message' => $message,
				'status'  => 'error',
			)
		);
	}

	bsc_recalculate_checkout_totals( true );

	if ( function_exists( 'wc_clear_notices' ) ) {
		wc_clear_notices();
	}

	wp_send_json_success(
		array_merge(
			array(
				'message' => 'Cupon agregado exitosamente.',
				'status'  => 'success',
			),
			bsc_get_coupon_totals_payload()
		)
	);
}

add_action( 'wp_ajax_remove_coupon', 'bsc_remove_coupon' );
add_action( 'wp_ajax_nopriv_remove_coupon', 'bsc_remove_coupon' );

function bsc_remove_coupon() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );
	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'coupon_remove', 30, MINUTE_IN_SECONDS );
	}

	$coupon_code = bsc_get_posted_coupon_code();

	if ( '' === $coupon_code ) {
		wp_send_json_error(
			array(
				'message' => 'No encontramos un cupon para eliminar.',
				'status'  => 'warning',
			)
		);
	}

	if ( ! WC()->cart ) {
		wp_send_json_error(
			array(
				'message' => 'Carrito no disponible.',
				'status'  => 'error',
			)
		);
	}

	WC()->cart->remove_coupon( $coupon_code );
	bsc_recalculate_checkout_totals( true );

	wp_send_json_success(
		array_merge(
			array(
				'message' => 'Cupon eliminado.',
				'status'  => 'success',
			),
			bsc_get_coupon_totals_payload()
		)
	);
}

add_action( 'wp_ajax_get_applied_coupons', 'bsc_get_applied_coupons' );
add_action( 'wp_ajax_nopriv_get_applied_coupons', 'bsc_get_applied_coupons' );

function bsc_get_applied_coupons() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );
	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'coupon_list', 60, MINUTE_IN_SECONDS );
	}

	if ( ! WC()->cart ) {
		wp_send_json_error(
			array(
				'message' => 'Carrito no disponible.',
				'status'  => 'error',
			)
		);
	}

	$coupons = array();

	foreach ( WC()->cart->get_applied_coupons() as $code ) {
		$coupons[] = array(
			'code' => esc_html( $code ),
		);
	}

	wp_send_json_success( array( 'coupons' => $coupons ) );
}

function bsc_get_coupon_totals_payload(): array {
	$summary = bsc_get_checkout_summary_payload();

	return array(
		'subtotal'                  => $summary['subtotal_html'],
		'subtotal_after_discounted' => $summary['subtotal_discounted_html'],
		'shipping_total'            => $summary['shipping_total_html'],
		'cart_total'                => $summary['cart_total_html'],
		'cart_count'                => $summary['cart_count'],
	);
}

function bsc_get_posted_coupon_code(): string {
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- AJAX callers validate bsc_ajax_action before reading the coupon code.
	$raw_coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( ! is_scalar( $raw_coupon_code ) ) {
		return '';
	}

	return wc_format_coupon_code( (string) $raw_coupon_code );
}

function bsc_get_coupon_notice_message( string $fallback ): string {
	if ( ! function_exists( 'wc_get_notices' ) ) {
		return $fallback;
	}

	$notices = wc_get_notices( 'error' );
	if ( empty( $notices ) || ! is_array( $notices ) ) {
		return $fallback;
	}

	$last_notice = end( $notices );
	if ( is_array( $last_notice ) && isset( $last_notice['notice'] ) ) {
		return wp_strip_all_tags( (string) $last_notice['notice'] );
	}

	if ( is_string( $last_notice ) ) {
		return wp_strip_all_tags( $last_notice );
	}

	return $fallback;
}
