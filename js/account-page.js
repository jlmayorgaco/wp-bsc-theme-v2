(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var path = window.location.pathname || '';
    var isDashboard = /\/mi-cuenta\/?$/.test(path);

    if (isDashboard) {
      return;
    }

    var content = document.querySelector('.woocommerce-MyAccount-content');

    if (!content) {
      return;
    }

    window.setTimeout(function () {
      var top = content.getBoundingClientRect().top + window.scrollY - 80;
      window.scrollTo({ top: top, behavior: 'smooth' });
    }, 150);
  });
})();
