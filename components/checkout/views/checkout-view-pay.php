<?php
defined('ABSPATH') || exit;
?>
<main class="bsc bsc__page bsc__page--order-pay">
  <div class="bsc__container bsc__container--centered">
    <img class="bsc__checkout-logo" src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc_checkout_logo.svg" alt="Bubble Skin Care">
    <h1 class="bsc__title"><strong>Completa</strong> tu pago</h1>
    <div class="bsc__order-pay-form">
      <?php do_action('woocommerce_pay_order_before_payment'); ?>
      <?php do_action('woocommerce_receipt_' . ( isset($order) ? $order->get_payment_method() : '' )); ?>
      <?php do_action('woocommerce_pay_order_after_payment'); ?>
    </div>
  </div>
</main>
