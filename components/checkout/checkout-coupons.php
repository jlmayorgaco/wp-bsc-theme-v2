<?php
defined('ABSPATH') || exit;

class BSC_Checkout_Coupon {

    public function render(): void {
        echo '<div class="bsc bsc__coupon">';
        echo '  <div class="bsc__coupon-container">';
                    $this->render_coupon_input();
                    $this->render_coupon_message_and_button();
                    $this->render_applied_coupons();
        echo '  </div>';
        echo '</div>';
    }

    protected function render_coupon_input(): void {
        ?>
        <div class="bsc__coupon-field">
            <input 
                type="text" 
                id="bsc__coupon-input" 
                name="coupon_code" 
                class="bsc__input" 
                placeholder=" " 
                autocomplete="off"
            />
            <label for="bsc__coupon-input" class="bsc__label">
                ¿Tienes un código de <strong>descuento</strong>?
            </label>
        </div>
        <?php
    }

    protected function render_coupon_message_and_button(): void {
        ?>
        <div class="bsc__coupon-row">
            <div class="bsc__coupon-message">
                <p class="bsc__text">
                    ¡Muestras <strong>gratis</strong> con todos tus pedidos en BSC!
                    <img 
                        src="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/images/bsc_icon_white_heart.png" 
                        alt="Corazones BSC" 
                        width="50" 
                        decoding="async"
                    />
                </p>
            </div>
            <div class="bsc__coupon-action">
                <button type="button" id="apply_coupon" class="bsc__button">¡ Aplicar !</button>
            </div>
        </div>
        <?php
    }

    protected function render_applied_coupons(): void {
        if ( ! WC()->cart || ! WC()->cart->get_applied_coupons() ) return;

        echo '<div class="applied-coupons">';
        echo '  <br><hr><br>';
        echo '  <h4>Cupones aplicados:</h4>';
        echo '  <ul id="applied_coupons_list">';

        foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
            $this->render_coupon_item($coupon_code);
        }

        echo '  </ul>';
        echo '</div>';
    }

    protected function render_coupon_item(string $coupon_code): void {
        $code_clean = sanitize_text_field($coupon_code);
        $icon_src = get_stylesheet_directory_uri() . '/images/' . 'bsc_image_coupons' . '.png';

        ?>
        <li class="applied-coupon-item" data-coupon="<?= esc_attr($code_clean); ?>">
            <img 
                src="<?= esc_url($icon_src); ?>" 
                alt="Coupon Icon" 
                class="coupon-icon"
            />
            <strong class="coupon-code"><?= esc_html($code_clean); ?></strong>
            <label class="remove-coupon"><span>X</span></label>
        </li>
        <?php
    }
}
