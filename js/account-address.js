(function () {
  'use strict';

  function toArray(list) {
    return Array.prototype.slice.call(list);
  }

  function getControl(row) {
    return row.querySelector('input:not([type="hidden"]), select, textarea');
  }

  function isEmpty(control) {
    if (!control || control.disabled) {
      return false;
    }

    if (control.tagName === 'SELECT') {
      var selectedOption = control.options[control.selectedIndex];
      return !control.value || (
        control.selectedIndex === 0 &&
        selectedOption &&
        selectedOption.value === ''
      );
    }

    return !(control.value || '').trim();
  }

  function isInvalid(row, control) {
    if (!control || control.disabled) {
      return false;
    }

    if (row.classList.contains('validate-required') && isEmpty(control)) {
      return true;
    }

    if (control.hasAttribute('required') && isEmpty(control)) {
      return true;
    }

    return control.type === 'email' && control.value && !control.checkValidity();
  }

  function triggerWiggle(row) {
    row.classList.remove('wiggle-animation');
    void row.offsetWidth;
    row.classList.add('wiggle-animation');
  }

  function markError(row, control) {
    row.classList.add('has-error');
    control.setAttribute('aria-invalid', 'true');
    triggerWiggle(row);
  }

  function clearError(row, control) {
    row.classList.remove('has-error');
    row.classList.remove('woocommerce-invalid');
    row.classList.remove('woocommerce-invalid-required-field');
    control.removeAttribute('aria-invalid');
  }

  function getNotice(form) {
    var notice = form.querySelector('.bsc__address-form-notice');
    var fields = form.querySelector('.woocommerce-address-fields');

    if (notice || !fields) {
      return notice;
    }

    notice = document.createElement('div');
    notice.className = 'bsc__address-form-notice';
    notice.setAttribute('role', 'alert');
    fields.insertBefore(notice, fields.firstChild);

    return notice;
  }

  function showNotice(form) {
    var notice = getNotice(form);

    if (!notice) {
      return;
    }

    notice.textContent = 'Completa los campos obligatorios.';
    notice.classList.add('is-visible');
  }

  function hideNotice(form) {
    var notice = form.querySelector('.bsc__address-form-notice');

    if (notice) {
      notice.classList.remove('is-visible');
    }
  }

  function bindField(row, form) {
    var control = getControl(row);

    if (!control) {
      return;
    }

    function maybeClear() {
      if (!isInvalid(row, control)) {
        clearError(row, control);
      }

      if (!form.querySelector('.form-row.has-error')) {
        hideNotice(form);
      }
    }

    control.addEventListener('input', maybeClear);
    control.addEventListener('change', maybeClear);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form.bsc__shipping-address');

    if (!form) {
      return;
    }

    toArray(form.querySelectorAll('.form-row')).forEach(function (row) {
      bindField(row, form);
    });

    form.addEventListener('submit', function (event) {
      var rows = toArray(form.querySelectorAll('.form-row'));
      var firstErrorControl = null;
      var hasError = false;

      rows.forEach(function (row) {
        var control = getControl(row);

        if (!isInvalid(row, control)) {
          return;
        }

        hasError = true;
        markError(row, control);

        if (!firstErrorControl) {
          firstErrorControl = control;
        }
      });

      if (!hasError) {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();
      showNotice(form);

      if (firstErrorControl) {
        firstErrorControl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstErrorControl.focus({ preventScroll: true });
      }
    });
  });
})();
