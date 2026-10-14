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
				'message' => 'Ingresa un código de cupón para aplicarlo.',
				'status'  => 'warning',
			)
		);
	}

	$coupon = new WC_Coupon( $coupon_code );

	if ( ! $coupon->get_id() || ! wc_is_same_coupon( $coupon->get_code(), $coupon_code ) ) {
		wp_send_json_error(
			array(
				'message' => 'El cupón ingresado no existe o no es válido.',
				'status'  => 'error',
			)
		);
	}

	if ( WC()->cart->has_discount( $coupon_code ) ) {
		wp_send_json_error(
			array(
				'message' => 'Este cupón ya está aplicado.',
				'status'  => 'warning',
			)
		);
	}

	$expires = $coupon->get_date_expires();
	if ( $expires && time() > $expires->getTimestamp() ) {
		wp_send_json_error(
			array(
				'message' => 'Este cupón ya venció.',
				'status'  => 'error',
			)
		);
	}

	if ( function_exists( 'wc_clear_notices' ) ) {
		wc_clear_notices();
	}

	// WooCommerce may return English error text (and encoded price markup) here.
	// Translate by error code while the coupon is being validated.
	$translated_error = '';
	$translate_error  = static function ( $message, $error_code, $error_coupon ) use ( $coupon, &$translated_error ) {
		$translated_error = bsc_coupon_error_in_spanish( (int) $error_code, $error_coupon instanceof WC_Coupon ? $error_coupon : $coupon );
		return $translated_error;
	};
	add_filter( 'woocommerce_coupon_error', $translate_error, PHP_INT_MAX, 3 );
	try {
		$applied = WC()->cart->apply_coupon( $coupon_code );
	} finally {
		remove_filter( 'woocommerce_coupon_error', $translate_error, PHP_INT_MAX );
	}

	if ( ! $applied ) {
		$message = $translated_error ?: 'No pudimos aplicar este cupón. Revisa las condiciones e inténtalo de nuevo.';

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
				'message' => 'Cupón aplicado correctamente.',
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
				'message' => 'No encontramos un cupón para eliminar.',
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
				'message' => 'Cupón eliminado.',
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

function bsc_coupon_plain_text( string $message ): string {
	// WooCommerce prices contain markup and encoded currency symbols.
	return trim( wp_strip_all_tags( html_entity_decode( $message, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

function bsc_coupon_error_in_spanish( int $error_code, WC_Coupon $coupon ): string {
	$code = bsc_coupon_plain_text( $coupon->get_code() );

	switch ( $error_code ) {
		case WC_Coupon::E_WC_COUPON_NOT_EXIST:
			return 'Este cupón no existe.';
		case WC_Coupon::E_WC_COUPON_EXPIRED:
			return 'Este cupón venció.';
		case WC_Coupon::E_WC_COUPON_USAGE_LIMIT_REACHED:
		case WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK:
		case WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK_GUEST:
			return 'Este cupón alcanzó su límite de usos.';
		case WC_Coupon::E_WC_COUPON_ALREADY_APPLIED:
			return 'Este cupón ya está aplicado.';
		case WC_Coupon::E_WC_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY:
			return 'Este cupón no se puede combinar con otros cupones.';
		case WC_Coupon::E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET:
			return 'Para usar este cupón, el pedido debe ser de al menos ' . bsc_coupon_plain_text( wc_price( $coupon->get_minimum_amount() ) ) . '.';
		case WC_Coupon::E_WC_COUPON_MAX_SPEND_LIMIT_MET:
			return 'Este cupón solo aplica a pedidos de hasta ' . bsc_coupon_plain_text( wc_price( $coupon->get_maximum_amount() ) ) . '.';
		case WC_Coupon::E_WC_COUPON_NOT_VALID_SALE_ITEMS:
			return 'Este cupón no aplica a productos en oferta.';
		case WC_Coupon::E_WC_COUPON_EXCLUDED_PRODUCTS:
		case WC_Coupon::E_WC_COUPON_EXCLUDED_CATEGORIES:
		case WC_Coupon::E_WC_COUPON_NOT_APPLICABLE:
			return 'Este cupón no aplica a los productos de tu carrito.';
		case WC_Coupon::E_WC_COUPON_NOT_YOURS_REMOVED:
			return 'Ingresa un correo electrónico válido para usar este cupón.';
		case WC_Coupon::E_WC_COUPON_PLEASE_ENTER:
			return 'Ingresa un código de cupón.';
		default:
			return $code ? sprintf( 'El cupón «%s» no se puede aplicar a este pedido.', $code ) : 'No pudimos aplicar este cupón. Revisa las condiciones e inténtalo de nuevo.';
	}
}
