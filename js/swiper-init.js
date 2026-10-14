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
      autoplay: false,
      speed: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 300,
      navigation: {
        nextEl: '.bsc-swiper__nav--next',
        prevEl: '.bsc-swiper__nav--prev',
      },
    });

    // Lazy slide images can change height after Swiper has measured the slide.
    // Only the active slide may drive auto-height: loading an off-screen image
    // with a different aspect ratio must not resize the visible hero.
    wrapper.querySelectorAll('img').forEach(function (image) {
      function updateHeight() {
        var slide = image.closest('.swiper-slide');
        if (!slide || !slide.classList.contains('swiper-slide-active')) return;

        window.requestAnimationFrame(function () {
          // The active slide can change before this frame runs.
          if (slide.classList.contains('swiper-slide-active')) {
            swiper.updateAutoHeight(0);
          }
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
