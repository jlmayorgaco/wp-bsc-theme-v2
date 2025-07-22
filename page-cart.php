<?php
/**
 * Template Name: Página Carrito BSC
 */

defined('ABSPATH') || exit;

get_header();

if (!WC()->cart) {
    echo '<p>El carrito no está disponible en este momento.</p>';
    get_footer();
    exit;
}
?>

<main class="bsc bsc__cart-page">
  <div class="bsc__container">
    
    <?php if (!WC()->cart->is_empty()) { ?> <h1 class="bsc__title"><strong>Tu</strong> carrito</h1> <?php } ?>
    <?php if (WC()->cart->is_empty())  { ?> <h1 class="bsc__title"> Ohh ... <strong>tu carrito</strong> esta vacío </h1> <?php } ?>

    <div class="container__empty <?php if (WC()->cart->is_empty()) { echo 'is-visible'; }?> ">
        <p class="bsc__cart-empty">
            <img width="250px" src="<?php echo get_template_directory_uri();?>/images/bsc_image_empty_cart.png">
        </p>
        <a class="bsc__button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Volver a la tienda</a>
    </div>

    <div class="container__cols <?php if (!WC()->cart->is_empty()) { echo 'is-visible'; }?> ">
        <div class="container__col col-1">
            <form class="bsc__cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
                <table class="bsc__cart-table">
                <thead>
                    <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                    $_product = $cart_item['data'];
                    if (!$_product || !$cart_item['quantity']) continue;
                    $product_id = $cart_item['product_id'];
                    ?>
                    <tr class="bsc__cart-row">
                        <td class="bsc__cart-product">
                        <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
                            <?php echo $_product->get_image('woocommerce_thumbnail'); ?>
                            <span class="bsc__cart-name"><?php echo esc_html($_product->get_name()); ?></span>
                        </a>
                        </td>

                        <td class="bsc__cart-price">
                        <?php echo wc_price($_product->get_price()); ?>
                        </td>

                        <td class="bsc__cart-qty">
                        <?php
                            $min_value = $_product->is_sold_individually() ? 1 : 0;
                            $max_value = $_product->get_max_purchase_quantity();
                            $qty = $cart_item['quantity'];

                            // Render quantity controls
                            echo '<div class="bsc__quantity-controls" data-min="0" data-product_id="' . esc_attr($product_id) . '">';
                            echo '<button class="bsc__qty-minus">−</button>';
                            echo '<span class="bsc__qty-value">' . esc_html($qty) . '</span>';
                            echo '<button class="bsc__qty-plus">+</button>';
                            echo '</div>';
                        ?>
                        </td>

                        <td class="bsc__cart-subtotal">
                        <?php echo WC()->cart->get_product_subtotal($_product, $qty); ?>
                        </td>

                        <td class="bsc__cart-remove">
                        <a href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>" class="bsc__remove" aria-label="Eliminar <?php echo esc_attr($_product->get_name()); ?>">&times;</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
                <div class="bsc__cart-actions">
                <button type="submit" name="update_cart" class="bsc__button bsc__button--outline"><?php esc_html_e('Actualizar carrito', 'woocommerce'); ?></button>
                <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
                </div>
            </form>
        </div>
        <div class="container__col col-2">
            <div class="bsc__cart-summary">
                <h2 class="bsc__subtitle">Resumen</h2>
                <?php woocommerce_cart_totals(); ?>
            </div>
        </div>
    </div>

    <div class="container__recommended">
            <div class="bsc__cart-cross-sells">

                <h2 class="bsc__title"><strong>También</strong> te podría interesar</h2>

                 <?php
                    // Configuration
                    require_once get_template_directory() . '/components/products/card.php';
                    require_once get_template_directory() . '/components/products/slider.php';
                    require_once get_template_directory() . '/helpers/recommended_products.php';
                    $key = 'bsc-recomended-products';
                    $products_limit = 4;
                    $skus = get_cart_recommendation_skus($products_limit);

                    if (!empty($skus)) {
                        // Render slider with SKUs
                        $slider = new BSC_Products_Sliders();
                        $slider->setMax($products_limit);
                        $slider->setSkus($skus);
                        $slider->setSlug($key); // Used to create unique swiper class/ID
                        $slider->render();
                    } else {
                        echo '<p class="bsc__empty-recommendations">No hay productos disponibles.</p>';
                    }
                
                ?>
            </div>
    </div>

  </div>
</main>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const qtyWrappers = document.querySelectorAll('.bsc__qty-wrapper');

    qtyWrappers.forEach(wrapper => {
      const minusBtn = wrapper.querySelector('.bsc__qty-btn--minus');
      const plusBtn = wrapper.querySelector('.bsc__qty-btn--plus');
      const input = wrapper.querySelector('.bsc__qty-input');

      minusBtn.addEventListener('click', () => {
        const value = parseInt(input.value);
        const min = parseInt(input.min) || 1;
        if (value > min) input.value = value - 1;
      });

      plusBtn.addEventListener('click', () => {
        const value = parseInt(input.value);
        const max = parseInt(input.max) || 99;
        if (value < max) input.value = value + 1;
      });
    });
  });
</script>

<?php get_footer(); ?>
