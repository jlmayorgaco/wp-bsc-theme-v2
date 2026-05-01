(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    if (window.innerWidth >= 768) {
      return;
    }

    var orderItems = document.getElementById('bsc-order-items');

    if (orderItems) {
      orderItems.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
})();
