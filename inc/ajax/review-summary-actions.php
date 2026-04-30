<?php
defined('ABSPATH') || exit;

require_once get_template_directory() . '/inc/checkout-review-summary-helpers.php';

add_action('wp_ajax_bsc_get_review_summary', 'bsc_get_review_summary');
add_action('wp_ajax_nopriv_bsc_get_review_summary', 'bsc_get_review_summary');

function bsc_get_review_summary() {
  check_ajax_referer('bsc_ajax_action', 'nonce');

  if ( ! function_exists('WC') || ! WC()->cart ) {
      wp_send_json_error(['message' => 'Cart is unavailable']);
      wp_die();
    }

    if ( function_exists('bsc_sync_customer_shipping_destination') ) {
        bsc_sync_customer_shipping_destination();
    }

    require_once get_template_directory() . '/components/checkout/checkout-summary.php';

    ob_start();
    $renderer = new BSC_Checkout_Review_Summary();
    $renderer->render();
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
    wp_die();
}


?>
