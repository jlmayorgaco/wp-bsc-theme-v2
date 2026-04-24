<?php
/**
 * Template: Custom View Order Page for WooCommerce
 */

defined('ABSPATH') || exit;

$order = wc_get_order($order_id);
if (!$order) return;

do_action('woocommerce_before_view_order', $order);

require_once get_template_directory() . '/components/orders/order-progress-bar.php';

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

    <ul class="bsc__order-overview">
      <li class="bsc__order-overview__item">
        <div class="bsc__order-overview__title">Número de orden:</div>
        <div class="bsc__order-overview__content">#<?php echo esc_html($order->get_order_number()); ?></div>
      </li>
      <li class="bsc__order-overview__item">
        <div class="bsc__order-overview__title">Fecha:</div>
        <div class="bsc__order-overview__content"><?php echo wc_format_datetime($order->get_date_created()); ?></div>
      </li>
      <li class="bsc__order-overview__item bsc__order-overview__item--progress">
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

      <div class="bsc__order-review product-details" id="bsc-order-items">
        <?php foreach ($order->get_items() as $item_id => $item):
          $product = $item->get_product();
          $product_name = $item->get_name();
          $product_qty = $item->get_quantity();
          $product_price = wc_price($item->get_total());
          $product_id = $product->get_id();
          $product_link = get_permalink($product_id);
          [$brand_name, $brand_link] = get_brand_data($product);
          $thumbnail_url = $product->get_image_id()
            ? wp_get_attachment_image_src($product->get_image_id(), 'woocommerce_thumbnail')[0]
            : get_template_directory_uri() . '/images/product-placeholder-wp.jpg';
        ?>
          <div class="bsc__order-review__item">
            <a href="<?php echo esc_url($product_link); ?>" class="bsc__order-review__image-link">
              <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($product_name); ?>" class="bsc__order-review__image" loading="lazy" decoding="async" />
              <div class="bsc__order-review__badge"><span><?php echo $product_qty; ?></span></div>
            </a>
            <div class="bsc__order-review__info">
              <div class="bsc__order-review__name">
                <p><a href="<?php echo esc_url($product_link); ?>"><?php echo esc_html($product_name); ?></a> <strong>x<?php echo esc_html($product_qty); ?></strong></p>
                <?php if ($brand_name): ?><p class="bsc__order-review__brand"><a href="<?php echo esc_url($brand_link); ?>"><?php echo esc_html($brand_name); ?></a></p><?php endif; ?>
              </div>
              <div class="bsc__order-review__price"><?php echo $product_price; ?></div>
            </div>
          </div>
        <?php endforeach; ?>

        <?php
        $summary_nquantity = $order->get_item_count();
        $summary_total = $order->get_subtotal();
        $summary_discount = $order->get_discount_total();
        $summary_shipping = $order->get_shipping_total();
        ?>

        <div class="bsc__order-summary">
          <div class="summary__row summary__quantity-items">
            <div class="summary__title"><?php echo $summary_nquantity; ?> productos</div>
            <div class="summary__content"><?php echo wc_price($summary_total); ?></div>
          </div>
          <div class="summary__row summary__total-discounts">
            <div class="summary__title">descuento adicional</div>
            <div class="summary__content"><?php echo wc_price($summary_discount); ?></div>
          </div>
          <div class="summary__row summary__shipping-cost">
            <div class="summary__title">envío</div>
            <div class="summary__content"><?php echo wc_price($summary_shipping); ?></div>
          </div>
          <div class="summary__divider"></div>
          <h1 class="bsc__order-summary__total">Total <strong><?php echo wc_price($summary_total + $summary_shipping - $summary_discount); ?></strong></h1>
        </div>
  
      </div>

      <div class="bsc__order-review shipping-details">
        <h2 class="shipping-details__title">Datos de <strong>entrega</strong></h2>
        <?php
        $nombre = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
        $documento = $order->get_meta('_billing_cedula');
        $ciudad = $order->get_shipping_city();
        $direccion = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
        $telefono = $order->get_billing_phone();
        $bubble_points = function_exists( 'bsc_get_order_bubble_points_balance' )
          ? bsc_get_order_bubble_points_balance( $order )
          : (int) get_user_meta( (int) $order->get_user_id(), 'bsc_bubble_points', true );
        ?>
        <ul class="shipping-details__list">
          <li><strong>Nombre:</strong> <?php echo esc_html( $nombre ); ?></li>
          <li><strong>Documento:</strong> <?php echo esc_html( $documento ); ?></li>
          <li><strong>Ciudad:</strong> <?php echo esc_html( $ciudad ); ?></li>
          <li><strong>Dirección:</strong> <?php echo esc_html( $direccion ); ?></li>
          <li><strong>Teléfono:</strong> <?php echo esc_html( $telefono ); ?></li>
        </ul>
        <hr class="shipping-details__divider">
        <p class="shipping-details__subtitle">Puntos acumulados en esta compra</p>
        <div class="bsc__points">
          <img class="bsc__points__icon" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc_checkout_points.png" alt="Bubble Points">
          <h3 class="bsc__points__text">¡ <strong><?php echo esc_html( $bubble_points ); ?></strong> Bubble Points !</h3>
        </div>
      </div>
    </div>

    <div class="bsc__thankyou-actions">
      <a class="bsc__button" href="/shop">¡ Ir a la tienda !</a>
    </div>
  </div>
</main>

<?php do_action('woocommerce_after_view_order', $order); ?>
<script>
if (window.innerWidth < 768) {
    var el = document.getElementById('bsc-order-items');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>
