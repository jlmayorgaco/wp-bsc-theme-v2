<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_apply_coupon', 'bsc_apply_coupon');
add_action('wp_ajax_nopriv_apply_coupon', 'bsc_apply_coupon');

function bsc_apply_coupon() {
  check_ajax_referer('bsc_ajax_action', 'nonce');

  if ( ! WC()->cart ) {
    wp_send_json_error([
      'message' => 'Carrito no disponible.',
      'status'  => 'error',
    ]);
  }

  $raw_coupon_code = $_POST['coupon_code'] ?? '';
  $coupon_code = is_scalar( $raw_coupon_code )
    ? wc_format_coupon_code( wp_unslash( (string) $raw_coupon_code ) )
    : '';

  if ( $coupon_code === '' ) {
    wp_send_json_error([
      'message' => 'Ingresa un código de cupón para aplicarlo.',
      'status'  => 'warning',
    ]);
  }

  $coupon = new WC_Coupon( $coupon_code );

  if ( ! $coupon->get_id() || ! wc_is_same_coupon( $coupon->get_code(), $coupon_code ) ) {
    wp_send_json_error([
      'message' => 'El cupón ingresado no existe o no es válido.',
      'status'  => 'error',
    ]);
  }

  if ( WC()->cart->has_discount( $coupon_code ) ) {
    wp_send_json_error([
      'message' => 'Este cupón ya está aplicado.',
      'status'  => 'warning',
    ]);
  }

  $expires = $coupon->get_date_expires();
  if ( $expires && time() > $expires->getTimestamp() ) {
    wp_send_json_error([
      'message' => 'Este cupón ya expiró.',
      'status'  => 'error',
    ]);
  }

  if ( function_exists( 'wc_clear_notices' ) ) {
    wc_clear_notices();
  }

  $applied = WC()->cart->apply_coupon( $coupon_code );

  if ( ! $applied ) {
    $message = bsc_get_coupon_notice_message( 'No pudimos aplicar este cupón. Revisa las condiciones e inténtalo de nuevo.' );

    if ( function_exists( 'wc_clear_notices' ) ) {
      wc_clear_notices();
    }

    wp_send_json_error([
      'message' => $message,
      'status'  => 'error',
    ]);
  }

  WC()->cart->calculate_totals();

  if ( function_exists( 'wc_clear_notices' ) ) {
    wc_clear_notices();
  }

  wp_send_json_success( array_merge(
    [
      'message' => 'Cupón agregado exitosamente.',
      'status'  => 'success',
    ],
    bsc_get_coupon_totals_payload()
  ) );
}

add_action('wp_ajax_remove_coupon', 'bsc_remove_coupon');
add_action('wp_ajax_nopriv_remove_coupon', 'bsc_remove_coupon');

function bsc_remove_coupon() {
  check_ajax_referer('bsc_ajax_action', 'nonce');

  if ( ! isset($_POST['coupon_code']) ) {
    wp_send_json_error([
      'message' => 'No encontramos un cupón para eliminar.',
      'status'  => 'warning',
    ]);
  }

  $raw_coupon_code = $_POST['coupon_code'];
  $coupon_code = is_scalar( $raw_coupon_code )
    ? wc_format_coupon_code( wp_unslash( (string) $raw_coupon_code ) )
    : '';

  if ( ! WC()->cart ) {
    wp_send_json_error([
      'message' => 'Carrito no disponible.',
      'status'  => 'error',
    ]);
  }

  WC()->cart->remove_coupon( $coupon_code );
  WC()->cart->calculate_totals();

  wp_send_json_success( array_merge(
    [
      'message' => 'Cupón eliminado.',
      'status'  => 'success',
    ],
    bsc_get_coupon_totals_payload()
  ) );
}

add_action('wp_ajax_get_applied_coupons', 'bsc_get_applied_coupons');
add_action('wp_ajax_nopriv_get_applied_coupons', 'bsc_get_applied_coupons');

function bsc_get_applied_coupons() {
  check_ajax_referer('bsc_ajax_action', 'nonce');

  if ( ! WC()->cart ) {
    wp_send_json_error([
      'message' => 'Carrito no disponible.',
      'status'  => 'error',
    ]);
  }

  $coupons = [];

  foreach ( WC()->cart->get_applied_coupons() as $code ) {
    $coupons[] = [
      'code' => $code,
    ];
  }

  wp_send_json_success(['coupons' => $coupons]);
}

function bsc_get_coupon_totals_payload(): array {
  return [
    'subtotal' => wc_price(WC()->cart->get_subtotal()),
    'subtotal_after_discounted' => wc_price(WC()->cart->get_subtotal() - WC()->cart->get_discount_total()),
    'shipping_total' => wc_price(WC()->cart->get_shipping_total()),
    'cart_total' => WC()->cart->get_cart_total(),
  ];
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

?>
