<?php
$slides_query = new WP_Query([
    'post_type'      => 'home_slide',
    'posts_per_page' => -1,
]);

$slides = [];

if ($slides_query->have_posts()) {
    while ($slides_query->have_posts()) {
        $slides_query->the_post();

        $slides[] = [
            'title'       => get_the_title(),
            'subtitle'    => get_post_meta(get_the_ID(), '_slide_subtitle', true),
            'image'       => get_the_post_thumbnail_url(get_the_ID(), 'full'),
            'button_text' => get_post_meta(get_the_ID(), '_slide_button_text', true),
            'button_link' => get_post_meta(get_the_ID(), '_slide_button_link', true),
        ];
    }
    wp_reset_postdata();
}

// 🧪 For testing, repeat the array 3 times
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

    </div>
<?php endif; ?>


<script>
  document.addEventListener("DOMContentLoaded", function () {
    new Swiper(".bsc__home-swiper", {
      loop: true,
      autoplay: {
        delay: 4000,
        disableOnInteraction: true
      },
      on: {
        init: () => {
        const initialHero = document.querySelector('.swiper-slide-active .slide__hero');
        if (initialHero) {
          initialHero.classList.add('fade-in');
        }
      },
        slideChangeTransitionStart: () => {
          console.log('slideChangeTransitionStart');
          const currentHero = document.querySelectorAll('.slide__hero');
          currentHero.forEach(el => el.classList.remove('fade-in'));
        },
        slideChangeTransitionEnd: () => {
          console.log('slideChangeTransitionEnd');
          const activeSlide = document.querySelector('.swiper-slide-active .slide__hero');
          if (activeSlide) {
            activeSlide.classList.add('fade-in');
          }
        }
      }
    });
  });
</script>
