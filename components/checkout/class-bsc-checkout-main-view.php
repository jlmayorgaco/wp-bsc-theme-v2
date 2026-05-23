<?php

defined('ABSPATH') || exit;

class BSC_Checkout_Main_View {

    protected $checkout;
    protected $trust_items = [
        [
            'icon' => '1ICONOS_CHECKOUT.png',
            'copy' => '<strong>Pago seguro</strong> y env&iacute;os a toda Colombia',
        ],
        [
            'icon' => '2ICONOS_CHECKOUT.png',
            'copy' => 'Importados directamente de <strong>Corea del Sur</strong>',
        ],
        [
            'icon' => '3ICONOS_CHECKOUT.png',
            'copy' => '<strong>100% productos originales</strong> con c&oacute;digo',
        ],
        [
            'icon' => '4ICONOS_CHECKOUT.png',
            'copy' => '<strong>Muestras coreanas gratis</strong> en cada pedido',
        ],
        [
            'icon' => '5ICONOS_CHECKOUT.png',
            'copy' => '<strong>Env&iacute;o gratis</strong> por compras mayores a $300.000',
        ],
        [
            'icon' => '6ICONOS_CHECKOUT.png',
            'copy' => 'M&aacute;s de <strong>5 a&ntilde;os de experiencia</strong> en Kbeauty',
        ],
        [
            'icon' => '7ICONOS_CHECKOUT.png',
            'copy' => '<strong>Soporte</strong> especializado en <strong>Whatsapp</strong>',
        ],
    ];

    public function __construct() {
        $this->checkout = WC()->checkout();

        require_once get_template_directory() . '/components/checkout/checkout-cart.php';
        require_once get_template_directory() . '/components/checkout/checkout-coupons.php';
        require_once get_template_directory() . '/components/checkout/checkout-summary.php';
    }

    public function render(): void {
        $is_visible = WC()->cart && ! WC()->cart->is_empty();
        ?>
        <form id="checkout" class="checkout woocommerce-checkout" name="checkout" method="post" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
            <main class="bsc bsc__page bsc__page--dual bsc__page--checkout <?php echo esc_attr( $is_visible ? 'is-visible' : '' ); ?>">
                <div class="bsc__page-1 bg-white">
                    <div class="bsc__container bsc__container--to-right">
                        <img class="bsc__checkout-logo"
                             src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc_checkout_logo.svg"
                             alt="Bubble Skin Care Checkout"
                             width="300"  loading="eager">
                        <?php require get_template_directory() . '/components/checkout/checkout-form.php'; ?>
                        <?php $this->render_trust_signals(); ?>
                    </div>
                </div>

                <div class="bsc__page-2 bg-accent">
                    <div class="bsc__container bsc__container--to-left">
                        <?php
                            $cart_renderer = new BSC_Checkout_Cart();
                            $cart_renderer->render();

                            $coupons_renderer = new BSC_Checkout_Coupon();
                            $coupons_renderer->render();

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
                        <?php do_action('woocommerce_after_checkout_form', $this->checkout); ?>
                    </div>
                </div>
            </main>
        </form>
        <?php
    }

    protected function render_trust_signals(): void {
        ?>
        <div class="bsc__trust-signals" role="list" aria-label="<?php echo esc_attr(html_entity_decode('Garant&iacute;as de compra', ENT_QUOTES, 'UTF-8')); ?>">
            <?php foreach ($this->trust_items as $trust_item) : ?>
                <div class="bsc__trust-item" role="listitem">
                    <div class="trust-item__img" aria-hidden="true">
                        <img
                            src="<?php echo esc_url(get_template_directory_uri() . '/images/checkout/' . $trust_item['icon']); ?>"
                            alt=""
                            width="200"
                            height="200"
                            loading="lazy"
                            decoding="async">
                    </div>
                    <span class="trust-item__copy"><?php echo wp_kses(html_entity_decode($trust_item['copy'], ENT_QUOTES, 'UTF-8'), ['strong' => []]); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
