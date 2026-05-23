<?php
/**
 * Template Name: Pagina Carrito BSC
 */

defined('ABSPATH') || exit;

get_header();

if (!WC()->cart) {
    echo '<p>El carrito no esta disponible en este momento.</p>';
    get_footer();
    exit;
}

require_once get_template_directory() . '/helpers/recommended_products.php';
require_once get_template_directory() . '/components/products/card.php';
require_once get_template_directory() . '/components/products/slider.php';

$render_cart_recommendations = static function (int $products_limit = 5): void {
    $skus = get_cart_recommendation_skus($products_limit);

    $slider = new BSC_Products_Sliders();
    $slider->setMax($products_limit);
    $slider->setSkus($skus);
    $slider->setSlug('bsc-recomended-products');

    ob_start();
    $slider->render();
    $recommendations_html = trim((string) ob_get_clean());

    if ($recommendations_html === '') {
        echo '<p class="bsc__empty-recommendations">No hay productos disponibles.</p>';
        return;
    }

    echo wp_kses_post( $recommendations_html );
};
?>

<main class="bsc bsc__cart-page">
  <div class="bsc__container">

    <?php if (!WC()->cart->is_empty()) { ?> <h1 class="bsc__title"><strong>Tu</strong> carrito</h1> <?php } ?>
    <?php if (WC()->cart->is_empty())  { ?> <h1 class="bsc__title"> Ohh ... <strong>tu carrito</strong> esta vacio </h1> <?php } ?>

    <div class="container__empty <?php echo esc_attr( WC()->cart->is_empty() ? 'is-visible' : '' ); ?> ">
        <p class="bsc__cart-empty">
            <img width="250" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc_image_empty_cart.png" alt="">
        </p>
        <a class="bsc__button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Volver a la tienda</a>

        <div class="bsc__product-recommendations bsc__cart-empty-recommendations">
          <div class="section__container">
            <h1 class="bsc__title">
              <strong>Recomendados</strong> para ti
            </h1>

            <?php $render_cart_recommendations(5); ?>
          </div>
        </div>
    </div>

    <div class="container__cols <?php echo esc_attr( ! WC()->cart->is_empty() ? 'is-visible' : '' ); ?> ">
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
                    $qty = $cart_item['quantity'];
                    ?>
                    <tr class="bsc__cart-row">
                        <td class="bsc__cart-product">
                        <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
                            <?php echo wp_kses_post( $_product->get_image('woocommerce_thumbnail') ); ?>
                            <span class="bsc__cart-name"><?php echo esc_html($_product->get_name()); ?></span>
                        </a>
                        </td>

                        <td class="bsc__cart-price">
                        <?php echo wp_kses_post( wc_price($_product->get_price()) ); ?>
                        </td>

                        <td class="bsc__cart-qty">
                        <div class="bsc__quantity-controls" data-min="0" data-product_id="<?php echo esc_attr($product_id); ?>">
                            <button class="bsc__qty-minus" type="button">&minus;</button>
                            <span class="bsc__qty-value"><?php echo esc_html($qty); ?></span>
                            <button class="bsc__qty-plus" type="button">+</button>
                        </div>
                        </td>

                        <td class="bsc__cart-subtotal">
                        <?php echo wp_kses_post( WC()->cart->get_product_subtotal($_product, $qty) ); ?>
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

    <?php if (!WC()->cart->is_empty()) : ?>
    <div class="container__recommended">
            <div class="bsc__cart-cross-sells">

                <h2 class="bsc__title"><strong>También</strong> te podría interesar</h2>

                 <?php
                    $render_cart_recommendations(4);
                 ?>
            </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php get_footer(); ?>
