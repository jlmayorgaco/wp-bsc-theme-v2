(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var balance = document.getElementById('bubble-points-balance');
    var isMobileViewport = window.matchMedia
      && window.matchMedia('(max-width: 768px)').matches;

    if (!balance || !isMobileViewport) {
      return;
    }

    var reduceMotion = window.matchMedia
      && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.setTimeout(function () {
      var top = balance.getBoundingClientRect().top + window.scrollY - 80;

      window.scrollTo({
        top: Math.max(0, top),
        behavior: reduceMotion ? 'auto' : 'smooth',
      });
    }, 150);
  });
})();
