/**
 * BSC Category Page — client-side product filter by subcategory slug.
 * Works with data-subcat attributes rendered in product-category.php.
 * Extracted from inline script for cacheability.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.forEach.call(document.querySelectorAll('.bsc__default-subsubcategory'), function (section) {
      var linksContainer = section.querySelector('.bsc__subsubcategory-links');
      var cards = section.querySelectorAll('.bsc-product-card');
      var mobileTrigger = section.querySelector('.bsc__subsubcategory-mobile-trigger');
      var mobileCount = section.querySelector('.bsc__subsubcategory-mobile-count');
      var mobileChip = section.querySelector('.bsc__subsubcategory-chip');
      var mobileChipLabel = section.querySelector('.bsc__subsubcategory-chip-label');
      var modal = section.querySelector('.bsc__subsubcategory-modal');
      var closeTimer = null;
      var defaultFilter = 'all';
      var defaultLabel = 'Todos';
      var activeFilter = defaultFilter;
      var activeLabel = defaultLabel;

      if (!linksContainer || !cards.length) return;

      function getLabel(btn) {
        return btn.getAttribute('data-filter-label') || btn.textContent.trim() || defaultLabel;
      }

      function updateTriggerState(isOpen) {
        if (mobileTrigger) {
          mobileTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
      }

      function closeModal() {
        if (!modal) return;

        window.clearTimeout(closeTimer);
        modal.classList.remove('is-visible');
        modal.classList.add('is-closing');
        document.documentElement.classList.remove('bsc-filter-modal-open');
        document.body.classList.remove('bsc-filter-modal-open');
        updateTriggerState(false);

        closeTimer = window.setTimeout(function () {
          modal.hidden = true;
          modal.classList.remove('is-closing');
        }, 320);
      }

      function openModal() {
        if (!modal) return;

        window.clearTimeout(closeTimer);
        modal.hidden = false;
        modal.classList.remove('is-closing');
        document.documentElement.classList.add('bsc-filter-modal-open');
        document.body.classList.add('bsc-filter-modal-open');
        updateTriggerState(true);

        window.requestAnimationFrame(function () {
          modal.classList.add('is-visible');
        });
      }

      function updateActiveButtons() {
        Array.prototype.forEach.call(section.querySelectorAll('[data-filter]'), function (btn) {
          var isActive = btn.getAttribute('data-filter') === activeFilter;

          if (btn.classList.contains('bsc__subsubcategory-link')) {
            btn.classList.toggle('bsc__subsubcategory-link--active', isActive);
          }

          if (btn.classList.contains('bsc__subsubcategory-modal-option')) {
            btn.classList.toggle('bsc__subsubcategory-modal-option--active', isActive);
          }
        });
      }

      function updateMobileUi() {
        var hasActiveFilter = activeFilter !== defaultFilter;

        if (mobileCount) {
          mobileCount.hidden = !hasActiveFilter;
          mobileCount.textContent = hasActiveFilter ? '1' : '';
        }

        if (mobileChip && mobileChipLabel) {
          mobileChip.hidden = !hasActiveFilter;
          mobileChipLabel.textContent = activeLabel;
        }
      }

      function applyFilter(filter, label) {
        activeFilter = filter || defaultFilter;
        activeLabel = label || defaultLabel;

        Array.prototype.forEach.call(cards, function (card) {
          var subcats = (card.getAttribute('data-subcat') || '').split(/\s+/).filter(Boolean);
          var show = activeFilter === defaultFilter || !activeFilter || subcats.indexOf(activeFilter) !== -1;
          card.style.display = show ? '' : 'none';
        });

        updateActiveButtons();
        updateMobileUi();
      }

      linksContainer.addEventListener('click', function (e) {
        var btn = e.target.closest('.bsc__subsubcategory-link');
        if (!btn) return;

        applyFilter(btn.getAttribute('data-filter'), getLabel(btn));
      });

      if (mobileTrigger) {
        mobileTrigger.addEventListener('click', openModal);
      }

      if (mobileChip) {
        mobileChip.addEventListener('click', function () {
          applyFilter(defaultFilter, defaultLabel);
        });
      }

      if (modal) {
        modal.addEventListener('click', function (e) {
          var closeBtn = e.target.closest('[data-filter-close]');
          var optionBtn = e.target.closest('.bsc__subsubcategory-modal-option');

          if (closeBtn) {
            closeModal();
            return;
          }

          if (!optionBtn) return;

          applyFilter(optionBtn.getAttribute('data-filter'), getLabel(optionBtn));
          closeModal();
        });
      }

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && !modal.hidden) {
          closeModal();
        }
      });

      var initialBtn = linksContainer.querySelector('.bsc__subsubcategory-link--active') || linksContainer.querySelector('.bsc__subsubcategory-link');
      if (initialBtn) {
        applyFilter(initialBtn.getAttribute('data-filter'), getLabel(initialBtn));
      } else {
        applyFilter(defaultFilter, defaultLabel);
      }
    });
  });
}());
