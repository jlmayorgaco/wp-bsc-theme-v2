(function () {
  'use strict';

  function toArray(list) {
    return Array.prototype.slice.call(list);
  }

  function getControl(row) {
    return row.querySelector('input:not([type="hidden"]), select, textarea');
  }

  var fieldPlaceholders = {
    billing_first_name: 'Nombres',
    billing_last_name: 'Apellidos',
    billing_country: 'Selecciona un pais',
    billing_address_1: 'Direccion de entrega',
    billing_address_2: 'Complemento de direccion',
    billing_state: 'Selecciona un departamento',
    billing_city: 'Selecciona una ciudad',
    billing_postcode: 'Codigo postal',
    billing_phone: 'Numero de telefono',
    billing_email: 'Correo electronico',
    shipping_first_name: 'Nombres',
    shipping_last_name: 'Apellidos',
    shipping_country: 'Selecciona un pais',
    shipping_address_1: 'Direccion de entrega',
    shipping_address_2: 'Complemento de direccion',
    shipping_state: 'Selecciona un departamento',
    shipping_city: 'Selecciona una ciudad',
    shipping_postcode: 'Codigo postal',
    shipping_phone: 'Numero de telefono',
    shipping_email: 'Correo electronico'
  };

  function updateRenderedSelectText(form, control, placeholder) {
    var rendered = form.querySelector('#select2-' + control.id + '-container');

    if (!rendered || control.value) {
      return;
    }

    rendered.textContent = placeholder;
    rendered.setAttribute('title', placeholder);
  }

  function applyFieldPlaceholders(form) {
    Object.keys(fieldPlaceholders).forEach(function (id) {
      var control = form.querySelector('#' + id);
      var placeholder = fieldPlaceholders[id];

      if (!control) {
        return;
      }

      control.setAttribute('placeholder', placeholder);
      control.setAttribute('data-placeholder', placeholder);

      if (control.tagName === 'SELECT' && control.options.length) {
        var firstOption = control.options[0];

        if (!firstOption.value) {
          firstOption.textContent = placeholder;
        }

        updateRenderedSelectText(form, control, placeholder);
      }
    });
  }

  function scheduleFieldPlaceholderRefresh(form) {
    applyFieldPlaceholders(form);
    window.setTimeout(function () { applyFieldPlaceholders(form); }, 100);
    window.setTimeout(function () { applyFieldPlaceholders(form); }, 500);

    if (window.jQuery) {
      window.jQuery(document.body).on('country_to_state_changed updated_checkout', function () {
        window.setTimeout(function () { applyFieldPlaceholders(form); }, 0);
      });
    }
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

  function initAccountAddress() {
    var addressSummary = document.getElementById('account-address-summary');
    var form = document.querySelector('form.bsc__shipping-address');
    var isMobileViewport = window.matchMedia
      && window.matchMedia('(max-width: 768px)').matches;
    var scrollTarget = addressSummary;

    if (form && form.classList.contains('bsc__account-address-v2--billing')) {
      scrollTarget = form;
    }

    if (scrollTarget && isMobileViewport) {
      var reduceMotion = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      window.setTimeout(function () {
        var top = scrollTarget.getBoundingClientRect().top + window.scrollY - 80;

        window.scrollTo({
          top: Math.max(0, top),
          behavior: reduceMotion ? 'auto' : 'smooth',
        });
      }, 150);
    }

    if (!form) {
      return;
    }

    scheduleFieldPlaceholderRefresh(form);

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
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAccountAddress, { once: true });
  } else {
    initAccountAddress();
  }
})();
