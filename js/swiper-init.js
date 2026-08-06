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
    new Swiper('.bsc__home-swiper', {
      loop: false,
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
      on: {
        init: function () {
          var initial = document.querySelector('.swiper-slide-active .slide__hero');
          if (initial) initial.classList.add('fade-in');
        },
        slideChangeTransitionStart: function () {
          document.querySelectorAll('.slide__hero')
            .forEach(function (el) { el.classList.remove('fade-in'); });
        },
        slideChangeTransitionEnd: function () {
          var active = document.querySelector('.swiper-slide-active .slide__hero');
          if (active) active.classList.add('fade-in');
        },
      },
    });
  });
}());
