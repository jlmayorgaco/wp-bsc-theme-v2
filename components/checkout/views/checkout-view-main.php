<?php defined('ABSPATH') || exit; ?>

<form id="checkout" class="" name="checkout" method="post" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
    <main class="bsc bsc__page bsc__page--dual bsc__page--checkout <?php if (!WC()->cart->is_empty()) echo 'is-visible'; ?>">

        <!-- Left panel: billing form + trust signals -->
        <div class="bsc__page-1 bg-white">
            <div class="bsc__container bsc__container--to-right">
                <img class="bsc__checkout-logo"
                     src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc_checkout_logo.svg"
                     alt="Bubble Skin Care Checkout"
                     width="300"  loading="eager">
                <?php require_once get_template_directory() . '/components/checkout/checkout-form.php'; ?>

                <!-- Trust signals bar -->
                <div class="bsc__trust-signals" role="list" aria-label="Garantías de compra">
                    <div class="bsc__trust-item" role="listitem">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <span>Pago seguro</span>
                    </div>
                    <div class="bsc__trust-item" role="listitem">
                        <i class="fas fa-shield-halved" aria-hidden="true"></i>
                        <span>Datos protegidos</span>
                    </div>
                    <div class="bsc__trust-item" role="listitem">
                        <i class="fas fa-truck" aria-hidden="true"></i>
                        <span>Envío garantizado</span>
                    </div>
                    <div class="bsc__trust-item" role="listitem">
                        <i class="fab fa-whatsapp" aria-hidden="true"></i>
                        <span>Soporte WhatsApp</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel: cart summary + payment -->
        <div class="bsc__page-2 bg-accent">
            <div class="bsc__container bsc__container--to-left">
                <?php
                    require_once get_template_directory() . '/components/checkout/checkout-cart.php';
                    $cart_renderer = new BSC_Checkout_Cart();
                    $cart_renderer->render();
                ?>
                <?php
                    require_once get_template_directory() . '/components/checkout/checkout-coupons.php';
                    $coupons_renderer = new BSC_Checkout_Coupon();
                    $coupons_renderer->render();
                ?>
                <?php
                    require_once get_template_directory() . '/components/checkout/checkout-summary.php';
                    $review_summary_renderer = new BSC_Checkout_Review_Summary();
                    $review_summary_renderer->render();
                ?>

                <div class="bsc bsc__review-order">
                    <div class="review-order__container">
                        <div id="order_review" class="woocommerce-checkout-review-order">
                            <?php do_action('woocommerce_checkout_order_review'); ?>
                        </div>
                    </div>
                </div>

                <?php do_action('woocommerce_checkout_after_customer_details'); ?>
                <?php do_action('woocommerce_after_checkout_form', $checkout ?? WC()->checkout()); ?>
            </div>
        </div>

    </main>
</form>
