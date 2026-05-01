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
        <div class="bsc__field bsc__field--floating bsc__coupon-field">
            <input
                type="text"
                id="bsc__coupon-input"
                name="coupon_code"
                class="bsc__input bsc__coupon-input"
                placeholder=" "
                autocomplete="off"
            />
            <label for="bsc__coupon-input" class="bsc__label bsc__label--floating">
                &iquest;Tienes un c&oacute;digo de <strong>descuento</strong>?
            </label>
        </div>
        <?php
    }

    protected function render_coupon_message_and_button(): void {
        ?>
        <div class="bsc__coupon-row">
            <div class="bsc__coupon-message">
                <p class="bsc__text">
                    &iexcl;Muestras <strong>gratis</strong> con todos tus pedidos en BSC!
                    <img
                        src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/bsc_icon_white_heart.png"
                        alt="Corazones BSC"
                        width="50"
                        decoding="async"
                    />
                </p>
            </div>
            <div class="bsc__coupon-action">
                <button type="button" id="apply_coupon" class="bsc__button bsc__button--capsule bsc__button--coupon">
                    &iexcl; Aplicar !
                </button>
            </div>
        </div>
        <?php
    }

    protected function render_applied_coupons(): void {
        $has_coupons = WC()->cart && WC()->cart->get_applied_coupons();
        $section_class = 'applied-coupons' . ( $has_coupons ? ' is-visible' : '' );

        echo '<div class="' . esc_attr( $section_class ) . '">';
        echo '  <br><hr class="bsc__coupon-divider"><br>';
        echo '  <h4 class="bsc__coupon-applied-title">Cupones aplicados:</h4>';
        echo '  <ul id="applied_coupons_list" class="bsc__coupon-applied-list">';

        if ( $has_coupons ) {
            foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
                $this->render_coupon_item( $coupon_code );
            }
        }

        echo '  </ul>';
        echo '</div>';
    }

    protected function render_coupon_item( string $coupon_code ): void {
        $code_clean = sanitize_text_field( $coupon_code );
        $icon_src = get_stylesheet_directory_uri() . '/images/bsc_image_coupons.png';

        ?>
        <li class="applied-coupon-item" data-coupon="<?php echo esc_attr( $code_clean ); ?>">
            <img
                src="<?php echo esc_url( $icon_src ); ?>"
                alt="Coupon Icon"
                class="coupon-icon"
            />
            <strong class="coupon-code"><?php echo esc_html( $code_clean ); ?></strong>
            <button
                type="button"
                class="remove-coupon bsc__coupon-remove"
                aria-label="Eliminar cupon <?php echo esc_attr( $code_clean ); ?>"
            >
                <span aria-hidden="true">X</span>
            </button>
        </li>
        <?php
    }
}