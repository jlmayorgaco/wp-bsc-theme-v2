/**
 * BSC Hero Slider — Swiper initialization.
 * Extracted from swiper.php inline script for cacheability.
 * Depends on: swiper-js (vendor/swiper/swiper-bundle.min.js)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var wrapper = document.querySelector('.bsc__home-swiper .swiper-wrapper');
    if (!wrapper) return;

    /* global Swiper */
    var swiper = new Swiper('.bsc__home-swiper', {
      loop: true,
      autoHeight: true,
      initialSlide: 0,
      autoplay: {
        delay: 4000,
        disableOnInteraction: true,
      },
      navigation: {
        nextEl: '.bsc-swiper__nav--next',
        prevEl: '.bsc-swiper__nav--prev',
      },
    });

    // Lazy slide images can change height after Swiper has measured the slide.
    // Re-measure the active slide when any banner finishes loading.
    wrapper.querySelectorAll('img').forEach(function (image) {
      function updateHeight() {
        window.requestAnimationFrame(function () {
          swiper.updateAutoHeight(0);
        });
      }

      if (image.complete) {
        if (image.naturalWidth) updateHeight();
      } else {
        image.addEventListener('load', updateHeight, { once: true });
      }
    });
  });
}());
