/**
 * BSC Category Page — client-side product filter by subcategory slug.
 * Works with data-subcat attributes rendered in product-category.php.
 * Extracted from inline script for cacheability.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var linksContainer = document.querySelector('.bsc__subsubcategory-links');
    var cards          = document.querySelectorAll('.bsc-product-card');

    if (!linksContainer || !cards.length) return;

    linksContainer.addEventListener('click', function (e) {
      var btn = e.target.closest('.bsc__subsubcategory-link');
      if (!btn) return;

      // Update active state
      linksContainer.querySelectorAll('.bsc__subsubcategory-link--active')
        .forEach(function (el) { el.classList.remove('bsc__subsubcategory-link--active'); });
      btn.classList.add('bsc__subsubcategory-link--active');

      var filter = btn.dataset.filter; // slug or 'all'

      // Use display toggle with a CSS class for smoother transitions
      cards.forEach(function (card) {
        var subcats = (card.dataset.subcat || '').split(' ').filter(Boolean);
        var show = filter === 'all' || !filter || subcats.indexOf(filter) !== -1;
        card.style.display = show ? '' : 'none';
      });
    });
  });
}());
