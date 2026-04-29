<?php
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/components/orders/order-progress-bar.php';

class BSC_Order_View {
    private WC_Order $order;

    public function __construct( WC_Order $order ) {
        $this->order = $order;
    }

    public function render(): void {
        ?>
        <main class="bsc bsc__page bsc__page--thankyou bsc__page--view-order">
          <div class="bsc__container bsc__thankyou-container">
            <?php $this->render_overview(); ?>

            <div class="bsc__order-review product-details-and-shipping">
              <div class="bsc__order-review product-details" id="bsc-order-items">
                <?php $this->render_items(); ?>
                <?php $this->render_summary(); ?>
              </div>

              <div class="bsc__order-review shipping-details">
                <?php $this->render_shipping_details(); ?>
              </div>
            </div>

            <div class="bsc__thankyou-actions">
              <a class="bsc__button" href="<?php echo esc_url( $this->get_shop_url() ); ?>">¡ Ir a la tienda !</a>
            </div>
          </div>
        </main>
        <?php
    }

    private function render_overview(): void {
        ?>
        <ul class="bsc__order-overview">
          <li class="bsc__order-overview__item">
            <div class="bsc__order-overview__title">Número de orden:</div>
            <div class="bsc__order-overview__content">#<?php echo esc_html( $this->order->get_order_number() ); ?></div>
          </li>
          <li class="bsc__order-overview__item">
            <div class="bsc__order-overview__title">Fecha:</div>
            <div class="bsc__order-overview__content"><?php echo esc_html( wc_format_datetime( $this->order->get_date_created() ) ); ?></div>
          </li>
          <li class="bsc__order-overview__item bsc__order-overview__item--progress">
            <div class="bsc__order-overview__title">Estado:</div>
            <div class="bsc__order-overview__content">
              <?php
              $bar = new BSC_Order_Progress_Bar();
              $bar->setStatus( bsc_map_order_status_to_bar( $this->order->get_status() ) );
              $bar->render();
              ?>
            </div>
          </li>
        </ul>
        <?php
    }

    private function render_items(): void {
        foreach ( $this->order->get_items() as $item ) {
            $product = $item->get_product();

            if ( ! $product instanceof WC_Product ) {
                continue;
            }

            $product_name  = $item->get_name();
            $product_qty   = $item->get_quantity();
            $product_price = wc_price( $item->get_total() );
            $product_id    = $product->get_id();
            $product_link  = get_permalink( $product_id );
            $brand_data    = $this->get_brand_data( $product );
            ?>
            <div class="bsc__order-review__item">
              <a href="<?php echo esc_url( $product_link ); ?>" class="bsc__order-review__image-link">
                <img src="<?php echo esc_url( $this->get_thumbnail_url( $product ) ); ?>" alt="<?php echo esc_attr( $product_name ); ?>" class="bsc__order-review__image" loading="lazy" decoding="async" />
                <div class="bsc__order-review__badge"><span><?php echo esc_html( $product_qty ); ?></span></div>
              </a>
              <div class="bsc__order-review__info">
                <div class="bsc__order-review__name">
                  <p><a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product_name ); ?></a> <strong>x<?php echo esc_html( $product_qty ); ?></strong></p>
                  <?php if ( $brand_data['name'] ) : ?>
                    <p class="bsc__order-review__brand"><a href="<?php echo esc_url( $brand_data['link'] ); ?>"><?php echo esc_html( $brand_data['name'] ); ?></a></p>
                  <?php endif; ?>
                </div>
                <div class="bsc__order-review__price"><?php echo wp_kses_post( $product_price ); ?></div>
              </div>
            </div>
            <?php
        }
    }

    private function render_summary(): void {
        $summary_nquantity = $this->order->get_item_count();
        $summary_total     = $this->order->get_subtotal();
        $summary_discount  = $this->order->get_discount_total();
        $summary_shipping  = $this->order->get_shipping_total();
        $grand_total       = $summary_total + $summary_shipping - $summary_discount;
        ?>
        <div class="bsc__order-summary">
          <div class="summary__row summary__quantity-items">
            <div class="summary__title"><?php echo esc_html( $summary_nquantity ); ?> productos</div>
            <div class="summary__content"><?php echo wp_kses_post( wc_price( $summary_total ) ); ?></div>
          </div>
          <div class="summary__row summary__total-discounts">
            <div class="summary__title">descuento adicional</div>
            <div class="summary__content"><?php echo wp_kses_post( wc_price( $summary_discount ) ); ?></div>
          </div>
          <div class="summary__row summary__shipping-cost">
            <div class="summary__title">envío</div>
            <div class="summary__content"><?php echo wp_kses_post( wc_price( $summary_shipping ) ); ?></div>
          </div>
          <div class="summary__divider"></div>
          <h1 class="bsc__order-summary__total">Total <strong><?php echo wp_kses_post( wc_price( $grand_total ) ); ?></strong></h1>
        </div>
        <?php
    }

    private function render_shipping_details(): void {
        $bubble_points = function_exists( 'bsc_get_order_bubble_points_earned' )
            ? bsc_get_order_bubble_points_earned( $this->order )
            : max( 0, (int) floor( (float) $this->order->get_total() / 1000 ) );
        ?>
        <h2 class="shipping-details__title">Datos de <strong>entrega</strong></h2>
        <ul class="shipping-details__list">
          <?php foreach ( $this->get_shipping_rows() as $row ) : ?>
            <li><strong><?php echo esc_html( $row['label'] ); ?>:</strong> <?php echo esc_html( $row['value'] ); ?></li>
          <?php endforeach; ?>
        </ul>
        <hr class="shipping-details__divider">
        <p class="shipping-details__subtitle">Bubble Points generados en esta compra</p>
        <div class="bsc__points">
          <img class="bsc__points__icon" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc_checkout_points.png" alt="Bubble Points">
          <h3 class="bsc__points__text">¡ <strong><?php echo esc_html( $bubble_points ); ?></strong> Bubble Points !</h3>
        </div>
        <?php
    }

    private function get_brand_data( WC_Product $product ): array {
        $brand      = $product->get_attribute( 'brand' ) ?: 'Sin marca';
        $categories = get_the_terms( $product->get_id(), 'product_cat' );

        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            foreach ( $categories as $cat ) {
                if ( strpos( $cat->slug, '-marca' ) !== false ) {
                    return [
                        'name' => $cat->name,
                        'link' => get_term_link( $cat ),
                    ];
                }
            }
        }

        return [
            'name' => $brand,
            'link' => '#',
        ];
    }

    private function get_thumbnail_url( WC_Product $product ): string {
        if ( $product->get_image_id() ) {
            $image = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' );

            if ( is_array( $image ) && ! empty( $image[0] ) ) {
                return $image[0];
            }
        }

        return get_template_directory_uri() . '/images/product-placeholder-wp.jpg';
    }

    private function get_shipping_rows(): array {
        return [
            [
                'label' => 'Nombre',
                'value' => trim( $this->order->get_shipping_first_name() . ' ' . $this->order->get_shipping_last_name() ),
            ],
            [
                'label' => 'Documento',
                'value' => (string) $this->order->get_meta( '_billing_cedula' ),
            ],
            [
                'label' => 'Ciudad',
                'value' => (string) $this->order->get_shipping_city(),
            ],
            [
                'label' => 'Dirección',
                'value' => trim( $this->order->get_shipping_address_1() . ' ' . $this->order->get_shipping_address_2() ),
            ],
            [
                'label' => 'Teléfono',
                'value' => (string) $this->order->get_billing_phone(),
            ],
        ];
    }

    private function get_shop_url(): string {
        if ( function_exists( 'wc_get_page_permalink' ) ) {
            return wc_get_page_permalink( 'shop' );
        }

        return home_url( '/shop/' );
    }
}
