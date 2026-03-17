<?php
defined('ABSPATH') || exit;



add_action('wp_ajax_apply_coupon', 'bsc_apply_coupon');
add_action('wp_ajax_nopriv_apply_coupon', 'bsc_apply_coupon');
 
function bsc_is_valid_coupon( $code ) {
  $coupon = new WC_Coupon( sanitize_text_field( $code ) );
  return ( $coupon && $coupon->get_id() );
}

function bsc_apply_coupon() {
  check_ajax_referer('bsc_ajax_action', 'nonce');

  if ( ! isset($_POST['coupon_code']) ) {
    wp_send_json_error(['message' => 'Código de cupón no recibido.']);
  }

  $coupon_code = sanitize_text_field($_POST['coupon_code']);

  if ( ! bsc_is_valid_coupon( $coupon_code ) ) {
    wp_send_json_error(['message' => 'El cupón no existe o no es válido.']);
  }

  if ( ! WC()->cart ) {
    wp_send_json_error(['message' => 'Carrito no disponible.']);
  }

  $applied = WC()->cart->apply_coupon($coupon_code);

  if ( is_wp_error($applied) ) {
    wp_send_json_error(['message' => $applied->get_error_message()]);
  }

  WC()->cart->calculate_totals();

  wp_send_json_success([
    'message' => 'Cupón aplicado correctamente.',
    'data' => [
      'subtotal' => wc_price(WC()->cart->get_subtotal()),
      'subtotal_after_discounted' => wc_price(WC()->cart->get_subtotal() - WC()->cart->get_discount_total()),
      'shipping_total' => wc_price(WC()->cart->get_shipping_total()),
      'cart_total' => WC()->cart->get_cart_total(),
    ]
  ]);
}

add_action('wp_ajax_remove_coupon', 'bsc_remove_coupon');
add_action('wp_ajax_nopriv_remove_coupon', 'bsc_remove_coupon');

function bsc_remove_coupon() {
  check_ajax_referer('bsc_ajax_action', 'nonce');

  if ( ! isset($_POST['coupon_code']) ) {
    wp_send_json_error(['message' => 'Código de cupón no recibido.']);
  }

  $coupon_code = sanitize_text_field($_POST['coupon_code']);

  if ( ! WC()->cart ) {
    wp_send_json_error(['message' => 'Carrito no disponible.']);
  }

  WC()->cart->remove_coupon($coupon_code);
  WC()->cart->calculate_totals();

  wp_send_json_success([
    'message' => 'Cupón eliminado correctamente.',
    'data' => [
      'subtotal' => wc_price(WC()->cart->get_subtotal()),
      'subtotal_after_discounted' => wc_price(WC()->cart->get_subtotal() - WC()->cart->get_discount_total()),
      'shipping_total' => wc_price(WC()->cart->get_shipping_total()),
      'cart_total' => WC()->cart->get_cart_total(),
    ]
  ]);
}


add_action('wp_ajax_get_applied_coupons', 'bsc_get_applied_coupons');
add_action('wp_ajax_nopriv_get_applied_coupons', 'bsc_get_applied_coupons');

function bsc_get_applied_coupons() {
  check_ajax_referer('bsc_ajax_action', 'nonce');

  if ( ! WC()->cart ) {
    wp_send_json_error(['message' => 'Carrito no disponible.']);
  }

  $coupons = [];

  foreach ( WC()->cart->get_applied_coupons() as $code ) {
    $coupons[] = [
      'code' => $code,
    ];
  }

  wp_send_json_success(['coupons' => $coupons]);
}

?>