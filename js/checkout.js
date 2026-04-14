jQuery(function ($) {
  // === SETTINGS ===
  const requiredFieldLabels = {
    billing_first_name: 'Nombres',
    billing_last_name:  'Apellidos',
    billing_cedula:     'Número de cédula',
    billing_email:      'Correo electrónico',
    billing_phone:      'Número de teléfono',
    billing_country:    'Selecciona un país',
    billing_state:      'Selecciona un departamento',
    billing_city:       'Selecciona una ciudad',
    billing_postcode:   'Código postal',
    billing_address_1:  'Dirección de entrega',
    // billing_address_2 is optional
  };

  const shippingToggleSelector = '#ship_to_different_address';

  const requiredFieldLabelsIfShippingEnabled = {
    shipping_country:   'Selecciona un país',
    shipping_state:     'Selecciona un departamento',
    shipping_postcode:  'Código postal',
    shipping_address_1: 'Dirección de entrega',
  };

  // === HELPERS ===
  function triggerWiggle($el) {
    $el.removeClass('wiggle-animation');
    void $el[0].offsetWidth;
    $el.addClass('wiggle-animation');
  }

  function markError($input) {
    const $wrapper = $input.closest('.bsc__field').length
      ? $input.closest('.bsc__field')
      : $input.closest('p');
    $wrapper.addClass('has-error');
    triggerWiggle($wrapper);
    $input.one('input change', () => $wrapper.removeClass('has-error'));
  }

  function scrollToFirstError($form) {
    const $firstError = $form.find('.has-error').first();
    if ($firstError.length) {
      $firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function isFieldEmpty($input) {
    return $input.is('select')
      ? !$input.val() || $input.prop('selectedIndex') === 0
      : !$input.val().trim();
  }

  // BSC-016: inline notice — replaces alert() in checkout AJAX callbacks
  function showCheckoutNotice(message, type) {
    let $notice = $('#bsc-checkout-notice');
    if (!$notice.length) {
      $notice = $('<div id="bsc-checkout-notice" class="bsc__coupon-notice"></div>');
      $('form[name="checkout"]').prepend($notice);
    }
    $notice
      .attr('class', 'bsc__coupon-notice bsc__coupon-notice--' + type)
      .text(message)
      .stop(true).fadeIn(200);
    setTimeout(() => $notice.fadeOut(400), 5000);
  }

  // === VALIDATION ===
  function validateRequiredFields($form) {
    let hasError = false;

    $.each(requiredFieldLabels, function (fieldName) {
      const $input = $form.find(`[name="${fieldName}"]`);
      if ($input.length && isFieldEmpty($input)) {
        markError($input);
        hasError = true;
      }
    });

    if ($(shippingToggleSelector).is(':checked')) {
      $.each(requiredFieldLabelsIfShippingEnabled, function (fieldName) {
        const $input = $form.find(`[name="${fieldName}"]`);
        if ($input.length && isFieldEmpty($input)) {
          markError($input);
          hasError = true;
        }
      });
    }

    return hasError;
  }

  // === BSC-058: Hide shipping until state + city are both selected ===
  // The custom BSC checkout renders shipping as #review-summary__shipping inside .review-summary__row
  function toggleShippingVisibility() {
    const state = $('#billing_state').val();
    const city  = $('#billing_city').val();
    // BSC custom checkout uses a div-based summary, not WC table rows
    const $shippingRow = $('#review-summary__shipping').closest('.review-summary__row');
    const msgId = 'bsc-shipping-pending-msg';

    if ( !state || !city ) {
      $shippingRow.hide();
      if ( !$('#' + msgId).length ) {
        $shippingRow.after(
          '<div id="' + msgId + '" class="review-summary__row bsc-shipping-pending-row">' +
          '<div class="review-summary__label bsc-shipping-pending-notice">' +
          'Selecciona tu departamento y ciudad para ver las opciones de envío.' +
          '</div></div>'
        );
      }
    } else {
      $shippingRow.show();
      $('#' + msgId).remove();
    }
  }

  window.bscToggleShippingVisibility = toggleShippingVisibility;

  let shippingRefreshTimer = null;
  function queueShippingSummaryRefresh() {
    clearTimeout(shippingRefreshTimer);
    shippingRefreshTimer = setTimeout(() => {
      if (typeof window.refreshReviewSummary === 'function') {
        window.refreshReviewSummary();
      }
    }, 180);
  }

  // === CITY FIELD AJAX LOADER ===
  function setupCityLoader() {
    $('#billing_state').on('change', function () {
      const state = $(this).val();
      const country = $('#billing_country').val() || 'CO';
      $.ajax({
        url: bsc_ajax.ajax_url,
        method: 'POST',
        data: { action: 'bsc_reload_city_fields', billing_country: country, billing_state: state, nonce: bsc_ajax.nonce },
        beforeSend: () => $('#billing_city_field').html('<p>Cargando ciudad…</p>'),
        success: function (response) {
          if (response.success) {
            setTimeout(() => {
              $('#billing_city_field').replaceWith(response.data.html);
              $(document.body).trigger('city_to_select');
              updateCityPlaceholder();
              toggleShippingVisibility();
              queueShippingSummaryRefresh();
              $(document.body).trigger('update_checkout');
            }, 300);
          } else {
            showCheckoutNotice('Error al cargar las ciudades. Por favor recarga la página.', 'error');
          }
        },
        error: () => showCheckoutNotice('Hubo un problema al cargar las ciudades.', 'error'),
      });
    });

    // BSC-016: trigger shipping recalculation on city/state change
    // BSC-058: also toggle shipping visibility
    $(document.body).on('change', '#billing_city, #billing_state', function () {
      toggleShippingVisibility();
      queueShippingSummaryRefresh();
      $(document.body).trigger('update_checkout');
    });
  }

  function updateCityPlaceholder() {
    $('#billing_city option:first-child').text('Selecciona una ciudad');
  }

  // === SHIPPING FIELDS TOGGLE ===
  function setupShippingToggle() {
    const $toggle = $('input[name="ship_to_different_address"]');
    const $shippingFields = $('.bsc__shipping-fields');

    $toggle.on('change', () =>
      $toggle.is(':checked') ? $shippingFields.slideDown() : $shippingFields.slideUp()
    );
    $toggle.trigger('change');
  }

  // === INIT ===
  function init() {
    setupCityLoader();
    setupShippingToggle();
    updateCityPlaceholder();
    toggleShippingVisibility(); // BSC-058: hide on load if no state/city yet
  }

  init();

  // === WC CHECKOUT EVENT ===
  // BSC-016: refreshReviewSummary is a global function defined in cart.js (loaded on all pages)
  // BSC-058: re-apply shipping visibility after WC updates checkout
  $(document.body).on('updated_checkout', function () {
    toggleShippingVisibility();
    if (typeof refreshReviewSummary === 'function') refreshReviewSummary();
  });

  // === FORM SUBMIT VALIDATION ===
  $('form[name="checkout"]').on('submit', function (e) {
    const $form = $(this);
    const hasError = validateRequiredFields($form);

    if (hasError) {
      e.preventDefault();
      e.stopImmediatePropagation();
      scrollToFirstError($form);
    }
    // If no errors: don't preventDefault — WooCommerce's own checkout AJAX takes over
  });
});
