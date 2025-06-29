<?php
defined('ABSPATH') || exit;

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

      <li class="bsc__order-overview__item bsc__order-overview__item--payment">
        <div class="bsc__order-overview__title">Método de Pago:</div>
        <div class="bsc__order-overview__content"><?php echo esc_html($order->get_payment_method_title()); ?></div>
      </li>

      <li class="bsc__order-overview__item bsc__order-overview__item--total">
        <div class="bsc__order-overview__title">Total:</div>
        <div class="bsc__order-overview__content"><?php echo $order->get_formatted_order_total(); ?></div>
      </li>
    </ul>

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

        $thumbnail_url  = get_template_directory_uri() . '/images/bsc__product_placeholder.webp';
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

      <br><br><br><br>



    </div>

    <!-- Action Buttons -->
    <div class="bsc__thankyou-actions">
      <a class="bsc__button" href="<?php echo esc_url(home_url()); ?>">Volver al inicio</a>
      <a class="bsc__button bsc__button--secondary" href="<?php echo esc_url(wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'))); ?>">Ver mis pedidos</a>
    </div>

  </div>
</main>
