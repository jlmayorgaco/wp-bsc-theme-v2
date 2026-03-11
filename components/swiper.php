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
          <?php foreach ($repeated_slides as $slide): ?>
            <div class="swiper-slide bsc-swiper__slide">
              <div class="slide__image">
                <img src="<?php echo esc_url($slide['image']); ?>" alt="<?php echo esc_html($slide['title']); ?>">
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
          <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc__placeholder_product.jpg" alt="Bubbles Skin Care">
        </div>
        <div class="slide__content">
          <div class="slide__container">
            <div class="slide__hero fade-in">
              <h1 class="hero__title">K-Beauty para tu piel</h1>
              <p class="hero__text">Descubre nuestra selección de skincare coreano</p>
              <a class="hero__button" href="/shop/">Ver tienda</a>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php endif; ?>


<script>
  document.addEventListener("DOMContentLoaded", function () {
    if (!document.querySelector('.bsc__home-swiper .swiper-wrapper')) return;
    new Swiper(".bsc__home-swiper", {
      loop: false,
      autoplay: {
        delay: 4000,
        disableOnInteraction: true
      },
  navigation: {
    nextEl: ".bsc-swiper__nav--next",
    prevEl: ".bsc-swiper__nav--prev"
  },
      on: {
        init: () => {
        const initialHero = document.querySelector('.swiper-slide-active .slide__hero');
        if (initialHero) {
          initialHero.classList.add('fade-in');
        }
      },
        slideChangeTransitionStart: () => {
          const currentHero = document.querySelectorAll('.slide__hero');
          currentHero.forEach(el => el.classList.remove('fade-in'));
        },
        slideChangeTransitionEnd: () => {
          const activeSlide = document.querySelector('.swiper-slide-active .slide__hero');
          if (activeSlide) {
            activeSlide.classList.add('fade-in');
          }
        }
      }
    });
  });
</script>
