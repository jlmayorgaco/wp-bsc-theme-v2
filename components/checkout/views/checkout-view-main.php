  

    <form id="checkout" class="" name="checkout" method="post"  action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
        <main class="bsc bsc__page bsc__page--dual bsc__page--checkout <?php if (!WC()->cart->is_empty()) { echo 'is-visible'; }?>">
            <div class="bsc__page-1 bg-white">
                    <div class="bsc__container bsc__container--to-right">
                        <img class="bsc__checkout-logo" src="<?php echo get_template_directory_uri();?>/images/bsc_logo_checkout.png" alt="Bubble Skin Care Checkout">
                        <?php //do_action('woocommerce_before_checkout_form', $checkout); ?>
                        <?php require_once get_template_directory() . '/components/checkout/checkout-form.php'; ?>
                    </div>
            </div>
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
                        <div class="review-order_container">
                        <div id="order_review" class="woocommerce-checkout-review-order">
                            <?php do_action('woocommerce_checkout_order_review'); ?>
                        </div>
                        </div>
                    </div>

                    
                    <?php //require_once get_template_directory() . '/components/checkout/checkout-payment.php'; ?>


                    <?php do_action('woocommerce_checkout_after_customer_details'); ?>

                    <?php do_action('woocommerce_after_checkout_form', $checkout); ?>
                </div>
            </div>
        </main>
    </form>