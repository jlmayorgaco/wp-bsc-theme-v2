<?php
defined('ABSPATH') || exit;

require_once get_template_directory() . '/components/orders/order-progress-bar.php';

// Order validation
$order_id = apply_filters('woocommerce_thankyou_order_id', absint(get_query_var('order-received')));
$order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

if ( ! $order_id ) {
    echo '<p class="bsc__message-error">No se encontró la orden. Por favor contacta soporte.</p>';
    return;
}

$order = wc_get_order($order_id);

if ( ! $order || $order->get_order_key() !== $order_key ) {
    echo '<p class="bsc__message-error">Orden inválida o no encontrada.</p>';
    return;
}

// Get brand info from attribute or category
function get_brand_data($product): array {
    $brand = $product->get_attribute('brand') ?: 'Sin marca';
    $categories = get_the_terms($product->get_id(), 'product_cat');

    if (!is_wp_error($categories) && !empty($categories)) {
        foreach ($categories as $cat) {
            if (strpos($cat->slug, '-marca') !== false) {
                return [$cat->name, get_term_link($cat)];
            }
        }
    }

    return [$brand, '#'];
}
?>

<main class="bsc bsc__page bsc__page--thankyou">
  <div class="bsc__container bsc__thankyou-container">

    <img class="bsc__thankyou-logo" 
         src="<?php echo get_template_directory_uri(); ?>/images/bsc_rainbow.png" 
         alt="Bubble Skin Care">

    <h1 class="bsc__title bsc__title--centered">¡Gracias por tu compra! 🎉</h1>

    <p class="bsc__thankyou-message">
      Tu orden <strong>#<?php echo esc_html($order->get_order_number()); ?></strong> ha sido recibida correctamente.
    </p>

    <!-- Order Summary -->
    <ul class="bsc__order-overview">
      <li class="bsc__order-overview__item bsc__order-overview__item--order">
        <div class="bsc__order-overview__title">Número de orden:</div>
        <div class="bsc__order-overview__content">#<?php echo esc_html($order->get_order_number()); ?></div>
      </li>

      <li class="bsc__order-overview__item bsc__order-overview__item--date">
        <div class="bsc__order-overview__title">Fecha:</div>
        <div class="bsc__order-overview__content"><?php echo wc_format_datetime($order->get_date_created()); ?></div>
      </li>

      <li class="bsc__order-overview__item bsc__order-overview__item--progress" style="width: 325px">
        <div class="bsc__order-overview__title">Estado:</div>
        <div class="bsc__order-overview__content">
          <?php
            $bar = new BSC_Order_Progress_Bar();
            $bar->setStatus(bsc_map_order_status_to_bar($order->get_status()));
            $bar->render();
          ?>
        </div>
      </li>
    </ul>

    <div class="bsc__order-review product-details-and-shipping">

      <!-- Product List -->
      <div class="bsc__order-review product-details">

        <?php foreach ( $order->get_items() as $item_id => $item ) :

          $product        = $item->get_product();
          $product_name   = $item->get_name();
          $product_qty    = $item->get_quantity();
          $product_total  = $item->get_total();
          $product_price  = wc_price($product_total);
          $product_id     = $product->get_id();
          $product_link   = get_permalink($product_id);
          [$brand_name, $brand_link] = get_brand_data($product);

          $thumbnail_url  = get_template_directory_uri() . '/images/product-placeholder-wp.jpg';
          if ( $product && $product->get_image_id() ) {
              $image_src = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' );
              if ( $image_src ) {
                  $thumbnail_url = $image_src[0];
              }
          }
        ?>

          <div class="bsc__order-review__item">

            <a href="<?php echo esc_url($product_link); ?>" class="bsc__order-review__image-link">
              <img 
                src="<?php echo esc_url($thumbnail_url); ?>" 
                alt="<?php echo esc_attr($product_name); ?>" 
                class="bsc__order-review__image" 
                loading="lazy" 
                decoding="async"
              />
              <div class="bsc__order-review__badge"> <span> <?php echo $product_qty; ?> </span> </div>
            </a>

            <div class="bsc__order-review__info">
              <div class="bsc__order-review__name">
                <p>
                  <a href="<?php echo esc_url($product_link); ?>">
                    <?php echo esc_html($product_name); ?>
                  </a> 
                  <strong>x<?php echo esc_html($product_qty); ?></strong>
                </p>

                <?php if ( $brand_name ) : ?>
                  <p class="bsc__order-review__brand">
                    <a href="<?php echo esc_url($brand_link); ?>"><?php echo esc_html($brand_name); ?></a>
                  </p>
                <?php endif; ?>
              </div>

              <div class="bsc__order-review__price">
                <?php echo $product_price; ?>
              </div>
            </div>

          </div>

        <?php endforeach; ?>


        <?php
            $summary_nquantity        = $order->get_item_count();
            $summary_total_amount     = $order->get_subtotal();
            $summary_discounts_amount = $order->get_discount_total();
            $summary_shipping_amount  = $order->get_shipping_total();
        ?>

        <div class="bsc__order-summary">
          <div class="summary__row summary__quantity-items">
            <div class="summary__title"><?php echo $summary_nquantity; ?> productos</div>
            <div class="summary__content"><?php echo wc_price($summary_total_amount); ?></div>
          </div>

          <div class="summary__row summary__total-discounts">
            <div class="summary__title">descuento adicional</div>
            <div class="summary__content"><?php echo wc_price($summary_discounts_amount); ?></div>
          </div>

          <div class="summary__row summary__shipping-cost">
            <div class="summary__title">envío</div>
            <div class="summary__content"><?php echo wc_price($summary_shipping_amount); ?></div>
          </div>

          <div class="summary__divider"></div>

          <h1 class="bsc__order-summary__total">
            Total <strong><?php echo wc_price($summary_total_amount + $summary_shipping_amount - $summary_discounts_amount); ?></strong>
          </h1>
        </div>
      </div>




      <div class="bsc__order-review shipping-details">
      <h2 class="shipping-details__title">
        Datos de <strong>entrega</strong>
      </h2>

      <?php
          $nombre    = trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());
          // Fallback to billing name if shipping name is empty
          if (empty($nombre)) {
              $nombre = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
          }
          $documento = $order->get_meta('_billing_cedula');
          $ciudad    = $order->get_shipping_city() ?: $order->get_billing_city();
          $direccion = trim($order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2());
          if (empty($direccion)) {
              $direccion = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
          }
          $telefono  = $order->get_billing_phone();

          $bubble_points = function_exists( 'bsc_get_order_bubble_points_balance' )
              ? bsc_get_order_bubble_points_balance( $order )
              : (int) get_user_meta( (int) $order->get_user_id(), 'bsc_bubble_points', true );
      ?>

      <ul class="shipping-details__list">
        <li class="shipping-details__item"><strong>Nombre:</strong> <?php echo esc_html($nombre); ?></li>
        <?php if ($documento) : ?>
        <li class="shipping-details__item"><strong>Documento:</strong> <?php echo esc_html($documento); ?></li>
        <?php endif; ?>
        <li class="shipping-details__item"><strong>Ciudad:</strong> <?php echo esc_html($ciudad); ?></li>
        <li class="shipping-details__item"><strong>Dirección:</strong> <?php echo esc_html($direccion); ?></li>
        <li class="shipping-details__item"><strong>Teléfono:</strong> <?php echo esc_html($telefono); ?></li>
      </ul>

      <?php if ($bubble_points > 0) : ?>
      <hr class="shipping-details__divider">
      <p class="shipping-details__subtitle">Puntos acumulados en esta compra</p>
      <div class="bsc__points">
        <img class="bsc__points__icon" src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc_checkout_points.png" alt="Bubble Points" width="48" height="48" loading="lazy">
        <h3 class="bsc__points__text">¡ <strong><?php echo esc_html($bubble_points); ?></strong> Bubble Points !</h3>
      </div>
      <?php endif; ?>
    </div>

    
    </div> 

    <!-- Action Buttons -->
    <div class="bsc__thankyou-actions">
      <a class="bsc__button" href="<?php echo esc_url(home_url()); ?>">Volver al inicio</a>
      <a class="bsc__button bsc__button--secondary" href="<?php echo esc_url(wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'))); ?>">Ver mis pedidos</a>
    </div>

  </div>
</main>
