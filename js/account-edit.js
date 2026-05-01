(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var fullNameEl = document.getElementById('account_full_name');
    var firstEl = document.getElementById('account_first_name');
    var lastEl = document.getElementById('account_last_name');
    var displayEl = document.getElementById('account_display_name');

    if (!fullNameEl || !firstEl || !lastEl || !displayEl) {
      return;
    }

    function syncHidden() {
      var full = (fullNameEl.value || '').trim();

      if (!full) {
        return;
      }

      var parts = full.split(/\s+/).filter(Boolean);
      var first = parts.shift() || full;
      var last = parts.join(' ').trim() || '-';

      firstEl.value = first;
      lastEl.value = last;
      displayEl.value = full;
    }

    syncHidden();
    fullNameEl.addEventListener('input', syncHidden);

    var form = fullNameEl.closest('form');
    if (form) {
      form.addEventListener('submit', syncHidden);
    }
  });
})();
