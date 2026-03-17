<?php
defined('ABSPATH') || exit;


add_action('wp_ajax_bsc_reload_city_fields', 'bsc_reload_city_fields');
add_action('wp_ajax_nopriv_bsc_reload_city_fields', 'bsc_reload_city_fields');

function bsc_reload_city_fields() {
  check_ajax_referer('bsc_ajax_action', 'nonce');

  $checkout = WC()->checkout();
  $fields = $checkout->get_checkout_fields();
  $state = sanitize_text_field($_POST['billing_state'] ?? '');

  // (Optional) Update value based on state if needed
  $_POST['billing_state'] = $state;

  ob_start();
  ?>
    <?php
    woocommerce_form_field('billing_city', $fields['billing']['billing_city'], '');
    ?>
  <?php
  $html = ob_get_clean();

  wp_send_json_success(['html' => $html]);
}





?>