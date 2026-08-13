(function () {
  'use strict';

  function initAccountEdit() {
    var profileDetails = document.getElementById('account-profile-details');
    var isMobileViewport = window.matchMedia
      && window.matchMedia('(max-width: 768px)').matches;

    if (profileDetails && isMobileViewport) {
      var reduceMotion = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      window.setTimeout(function () {
        var top = profileDetails.getBoundingClientRect().top + window.scrollY - 80;

        window.scrollTo({
          top: Math.max(0, top),
          behavior: reduceMotion ? 'auto' : 'smooth',
        });
      }, 150);
    }

    var fullNameEl = document.getElementById('account_full_name');
    var firstEl = document.getElementById('account_first_name');
    var lastEl = document.getElementById('account_last_name');
    var displayEl = document.getElementById('account_display_name');
    var passwordEl = document.getElementById('bsc_account_password');
    var passwordConfirmEl = document.getElementById('bsc_account_password_confirm');

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

    function validatePasswords() {
      if (!passwordEl || !passwordConfirmEl) {
        return true;
      }

      var password = passwordEl.value || '';
      var confirmation = passwordConfirmEl.value || '';

      passwordEl.setCustomValidity('');
      passwordConfirmEl.setCustomValidity('');

      if (!password && !confirmation) {
        return true;
      }

      if (!password || !confirmation) {
        passwordConfirmEl.setCustomValidity('Completa los dos campos de contraseña para cambiarla.');
        return false;
      }

      if (password !== confirmation) {
        passwordConfirmEl.setCustomValidity('Las contraseñas no coinciden.');
        return false;
      }

      return true;
    }

    syncHidden();
    fullNameEl.addEventListener('input', syncHidden);

    if (passwordEl && passwordConfirmEl) {
      passwordEl.addEventListener('input', validatePasswords);
      passwordConfirmEl.addEventListener('input', validatePasswords);
    }

    var form = fullNameEl.closest('form');
    if (form) {
      form.addEventListener('submit', function (event) {
        syncHidden();

        if (!validatePasswords()) {
          event.preventDefault();
          passwordConfirmEl.reportValidity();
        }
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAccountEdit, { once: true });
  } else {
    initAccountEdit();
  }
})();
