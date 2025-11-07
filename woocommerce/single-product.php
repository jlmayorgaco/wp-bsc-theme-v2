<?php
defined('ABSPATH') || exit;

get_header();

global $product;
if (!$product instanceof WC_Product) {
    $product = wc_get_product(get_the_ID());
}

require_once get_template_directory() . '/helpers/recommended_products.php';

if (!class_exists('BSC_Products_Card')) {
    require_once get_template_directory() . '/components/products/card.php';
}

if (!class_exists('BSC_Products_Sliders')) {
    require_once get_template_directory() . '/components/products/slider.php';
}

if (!class_exists('BSC_Product_Category_Meta')) {
    require_once get_template_directory() . '/components/products/categories-meta.php';
}

$card = new BSC_Products_Card();
$card->setProduct($product);

$meta_renderer = new BSC_Product_Category_Meta($product);


?>

<main class="bsc bsc__product--page">
  <div class="bsc__container">
    <nav class="bsc__breadcrumbs">
      <?php woocommerce_breadcrumb(); ?>
    </nav>

    <div class="bsc__product-layout">
      <div class="bsc__product-gallery">
        <?php woocommerce_show_product_images(); ?>
      </div>

      <div class="bsc__product-info">
        <h1 class="bsc__title bsc__title--product">
          <strong><?php the_title(); ?></strong>
        </h1>
        <div class="bsc__product-price bsc__price"><?php woocommerce_template_single_price(); ?></div>

        <div class="bsc__product-shortdesc">
          <?php the_excerpt(); ?>
        </div>

        <div class="bsc__product-cart bsc__product-cart--add-to-cart-button">
          <?php echo $card->render_button("¡Lo Quiero!"); ?>
        </div>

        <div class="bsc__product-meta">
           <?php $meta_renderer->render(); ?>
        </div>

      </div>
    </div>

    <div class="bsc__product-recommendations">
      <div class="section__container">
        <h1 class="bsc__title">
          <strong>Recomendados</strong> para ti
        </h1>

        <?php

        // Configuration
        $key = 'bsc-recomended-products';
        $product_id = get_the_ID();
        $products_limit = 5;
        $skus = get_related_product_skus($product_id, $products_limit );
        if (!empty($skus)) {
            // Render slider with SKUs
            $slider = new BSC_Products_Sliders();
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

<?php get_footer(); ?>
