<?php
/**
 * BSC custom single product layout.
 *
 * Reviewed against WooCommerce single-product.php 1.6.4.
 *
 * @package WooCommerce\Templates
 * @version 1.6.4
 */

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

$product_id = get_the_ID();
?>

<main class="bsc bsc__product--page">
  <div class="bsc__container">
    <nav class="bsc__breadcrumbs">
      <?php
      $group_slugs = ['group-skin-care', 'group-hair-care', 'group-make-up'];
      $group_term  = null;
      $terms       = get_the_terms($product_id, 'product_cat');

      if ($terms && !is_wp_error($terms)) {
          foreach ($terms as $term) {
              if (in_array($term->slug, $group_slugs, true)) {
                  $group_term = $term;
                  break;
              }

              foreach (get_ancestors($term->term_id, 'product_cat') as $ancestor_id) {
                  $ancestor = get_term($ancestor_id, 'product_cat');
                  if ($ancestor && !is_wp_error($ancestor) && in_array($ancestor->slug, $group_slugs, true)) {
                      $group_term = $ancestor;
                      break 2;
                  }
              }
          }
      }
      ?>
      <nav class="woocommerce-breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
        <?php if ($group_term) :
          $group_link = get_term_link($group_term);
        ?>
          &nbsp;/&nbsp;<a href="<?php echo esc_url(is_wp_error($group_link) ? '#' : $group_link); ?>"><?php echo esc_html($group_term->name); ?></a>
        <?php endif; ?>
        &nbsp;/&nbsp;<?php the_title(); ?>
      </nav>
    </nav>

    <div class="bsc__product-layout">
      <div class="bsc__product-gallery">
        <?php woocommerce_show_product_images(); ?>
      </div>

      <div class="bsc__product-info">
        <h1 class="bsc__title bsc__title--product">
          <strong><?php the_title(); ?></strong>
        </h1>

        <div class="bsc__product-price bsc__price">
          <?php woocommerce_template_single_price(); ?>
        </div>

        <div class="bsc__product-shortdesc">
          <?php the_excerpt(); ?>
        </div>

        <div class="bsc__product-cart bsc__product-cart--add-to-cart-button">
          <?php $card->render_button(); ?>
        </div>

        <div class="bsc__product-meta">
          <?php $meta_renderer->render(); ?>
        </div>
      </div>
    </div>

    <?php
    $cover_desktop_id = (int) get_post_meta($product_id, 'bsc_cover_desktop', true);
    $cover_mobile_id  = (int) get_post_meta($product_id, 'bsc_cover_mobile', true);

    // Fallback: mobile → desktop, desktop → mobile
    $render_desktop = $cover_desktop_id ?: $cover_mobile_id;
    $render_mobile  = $cover_mobile_id ?: $cover_desktop_id;

    $extra_images = [];
    for ($i = 1; $i <= 3; $i++) {
        $extra_id = (int) get_post_meta($product_id, "_bsc_extra_image_{$i}", true);
        if ($extra_id > 0) {
            $extra_images[$i] = $extra_id;
        }
    }
    ?>

    <?php if ($render_desktop || $render_mobile) : ?>
      <div class="bsc__product-cover">
        <?php if ($render_desktop) : ?>
          <div class="bsc__product-cover--desktop">
            <?php
            echo wp_get_attachment_image(
                $render_desktop,
                'full',
                false,
                [
                    'class'    => 'bsc__product-cover-img',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                ]
            );
            ?>
          </div>
        <?php endif; ?>

        <?php if ($render_mobile) : ?>
          <div class="bsc__product-cover--mobile">
            <?php
            echo wp_get_attachment_image(
                $render_mobile,
                'full',
                false,
                [
                    'class'    => 'bsc__product-cover-img',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                ]
            );
            ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($extra_images)) : ?>
      <div class="bsc__product-extra-images">
        <?php foreach ($extra_images as $index => $extra_image_id) : ?>
          <div class="bsc__product-extra-image bsc__product-extra-image--<?php echo esc_attr($index); ?>">
            <?php
            echo wp_get_attachment_image(
                $extra_image_id,
                'full',
                false,
                [
                    'class'    => 'bsc__product-extra-image-img',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                ]
            );
            ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="bsc__product-recommendations">
      <div class="section__container">
        <h1 class="bsc__title">
          <strong>Recomendados</strong> para ti
        </h1>

        <?php
        $key            = 'bsc-recomended-products';
        $products_limit = 5;
        $skus           = get_related_product_skus($product_id, $products_limit);

        if (!empty($skus)) {
            $slider = new BSC_Products_Sliders();
            $slider->setSkus($skus);
            $slider->setSlug($key);
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
