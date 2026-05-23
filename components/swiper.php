<?php
$slides_query = new WP_Query([
    'post_type'      => 'home_slide',
    'posts_per_page' => 10,
]);

$slides = [];

if ($slides_query->have_posts()) {
    while ($slides_query->have_posts()) {
        $slides_query->the_post();

        // BSC-003: use WP featured image; fallback to placeholder if none is set.
        // To use real banners: upload Home-01-100.jpg / Home-02-100.jpg to WP Media
        // and set as Featured Image on each Home Slide CPT post.
        $thumb = get_the_post_thumbnail_url(get_the_ID(), 'full');
        $slides[] = [
            'title'       => get_the_title(),
            'subtitle'    => get_post_meta(get_the_ID(), '_slide_subtitle', true),
            'image'       => $thumb ?: get_template_directory_uri() . '/images/bsc__placeholder_product.jpg',
            'button_text' => get_post_meta(get_the_ID(), '_slide_button_text', true),
            'button_link' => get_post_meta(get_the_ID(), '_slide_button_link', true),
        ];
    }
    wp_reset_postdata();
}

$repeated_slides = $slides;

if (count($repeated_slides) > 0) : ?>

    <div class="bsc bsc__home-swiper">

        <div class="swiper-wrapper">
          <?php foreach ($repeated_slides as $i => $slide): ?>
            <div class="swiper-slide bsc-swiper__slide">
              <div class="slide__image">
                <img
                  src="<?php echo esc_url($slide['image']); ?>"
                  alt="<?php echo esc_html($slide['title']); ?>"
                  width="1440"
                  height="700"
                  <?php if ($i === 0) : ?>loading="eager" fetchpriority="high"<?php else : ?>loading="lazy"<?php endif; ?>
                  decoding="<?php echo esc_attr( $i === 0 ? 'sync' : 'async' ); ?>"
                >
              </div>
              <div class="slide__content">
                <div class="slide__container">
                  <div class="slide__hero">
                    <h1 class="hero__title"><?php echo esc_html($slide['title']); ?></h1>
                    <p class="hero__text"><?php echo esc_html($slide['subtitle']); ?></p>
                    <a class="hero__button" href="<?php echo esc_url($slide['button_link']); ?>">
                      <?php echo esc_html($slide['button_text']); ?>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Navigation Arrows -->
        <div class="swiper-button-prev bsc-swiper__nav bsc-swiper__nav--prev"></div>
        <div class="swiper-button-next bsc-swiper__nav bsc-swiper__nav--next"></div>

    </div>

<?php else : ?>

    <!-- Fallback hero: shown when no Home Slide CPT posts exist.
         To replace: WP Admin → Home Slides → Add New → set title, subtitle, featured image, button. -->
    <div class="bsc bsc__home-swiper bsc__home-swiper--fallback">
      <div class="swiper-slide bsc-swiper__slide">
        <div class="slide__image">
          <img
            src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc__placeholder_product.jpg"
            alt="Bubbles Skin Care"
            width="1440"
            height="700"
            loading="eager"
            fetchpriority="high"
            decoding="sync"
          >
        </div>
        <div class="slide__content">
          <div class="slide__container">
            <div class="slide__hero fade-in">
              <h1 class="hero__title">K-Beauty para tu piel</h1>
              <p class="hero__text">Descubre nuestra selección de skincare coreano</p>
              <a class="hero__button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Ver tienda</a>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php endif; ?>


<!-- Swiper init enqueued via js/swiper-init.js -->
