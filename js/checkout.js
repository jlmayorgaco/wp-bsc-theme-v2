jQuery(function ($) {
  const requiredFieldLabels = {
    billing_first_name: 'Nombres',
    billing_last_name: 'Apellidos',
    billing_cedula: 'Numero de cedula',
    billing_email: 'Correo electronico',
    billing_phone: 'Numero de telefono',
    billing_country: 'Selecciona un pais',
    billing_state: 'Selecciona un departamento',
    billing_city: 'Selecciona una ciudad',
    billing_postcode: 'Codigo postal',
    billing_address_1: 'Direccion de entrega',
  };

  const shippingToggleSelector = '#ship_to_different_address';

  const requiredFieldLabelsIfShippingEnabled = {
    shipping_country: 'Selecciona un pais',
    shipping_state: 'Selecciona un departamento',
    shipping_postcode: 'Codigo postal',
    shipping_address_1: 'Direccion de entrega',
  };

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

  function showCheckoutNotice(message, type) {
    let $notice = $('#bsc-checkout-notice');

    if (!$notice.length) {
      $notice = $('<div id="bsc-checkout-notice" class="bsc__coupon-notice"></div>');
      $('form[name="checkout"]').prepend($notice);
    }

    $notice
      .attr('class', 'bsc__coupon-notice bsc__coupon-notice--' + type)
      .text(message)
      .stop(true)
      .fadeIn(200);

    setTimeout(() => $notice.fadeOut(400), 5000);
  }

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

  function toggleShippingVisibility() {
    const state = $('#billing_state').val();
    const $shippingRow = $('#review-summary__shipping').closest('.review-summary__row');
    const msgId = 'bsc-shipping-pending-msg';

    if (!state) {
      $shippingRow.hide();
      if (!$('#' + msgId).length) {
        $shippingRow.after(
          '<div id="' + msgId + '" class="review-summary__row bsc-shipping-pending-row">' +
          '<div class="review-summary__label bsc-shipping-pending-notice">' +
          'Selecciona tu departamento para ver el valor del envio.' +
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
  let isReloadingBillingCity = false;
  let cityReloadRequest = null;
  let cityReloadSequence = 0;

  function queueShippingSummaryRefresh() {
    clearTimeout(shippingRefreshTimer);
    shippingRefreshTimer = setTimeout(() => {
      if (typeof window.refreshReviewSummary === 'function') {
        window.refreshReviewSummary();
      }
    }, 180);
  }

  function setupCityLoader() {
    $(document.body).on('change', '#billing_state', function () {
      const state = $(this).val();
      const country = $('#billing_country').val() || 'CO';
      const requestSequence = ++cityReloadSequence;

      if (cityReloadRequest && cityReloadRequest.readyState !== 4) {
        cityReloadRequest.abort();
      }

      cityReloadRequest = $.ajax({
        url: bsc_ajax.ajax_url,
        method: 'POST',
        data: {
          action: 'bsc_reload_city_fields',
          billing_country: country,
          billing_state: state,
          nonce: bsc_ajax.nonce,
        },
        beforeSend: () => {
          isReloadingBillingCity = true;

          const $city = $('#billing_city');
          if ($city.length) {
            $city
              .prop('disabled', true)
              .empty()
              .append($('<option>', { value: '', text: 'Cargando ciudad...' }));
          }

          toggleShippingVisibility();
          queueShippingSummaryRefresh();
          $(document.body).trigger('update_checkout');
        },
        success: function (response) {
          if (requestSequence !== cityReloadSequence) {
            return;
          }

          if (!response.success) {
            isReloadingBillingCity = false;
            showCheckoutNotice('Error al cargar las ciudades. Por favor recarga la pagina.', 'error');
            return;
          }

          $('#billing_city_field').replaceWith(response.data.html);
          isReloadingBillingCity = false;
          $(document.body).trigger('city_to_select');
          updateCityPlaceholder();
          toggleShippingVisibility();
          queueShippingSummaryRefresh();
          $(document.body).trigger('update_checkout');
        },
        error: (_xhr, statusText) => {
          if (statusText === 'abort' || requestSequence !== cityReloadSequence) {
            return;
          }

          isReloadingBillingCity = false;
          showCheckoutNotice('Hubo un problema al cargar las ciudades.', 'error');
        },
        complete: () => {
          if (requestSequence === cityReloadSequence) {
            cityReloadRequest = null;
          }
        },
      });
    });

    $(document.body).on('change', '#billing_city, #billing_state', function () {
      if (this.id === 'billing_state' && isReloadingBillingCity) {
        toggleShippingVisibility();
        return;
      }

      toggleShippingVisibility();
      queueShippingSummaryRefresh();
      $(document.body).trigger('update_checkout');
    });
  }

  function updateCityPlaceholder() {
    $('#billing_city option:first-child').text('Selecciona una ciudad');
  }

  function setupShippingToggle() {
    const $toggle = $('input[name="ship_to_different_address"]');
    const $shippingFields = $('.bsc__shipping-fields');

    $toggle.on('change', () =>
      $toggle.is(':checked') ? $shippingFields.slideDown() : $shippingFields.slideUp()
    );

    $toggle.trigger('change');
  }

  function init() {
    setupCityLoader();
    setupShippingToggle();
    updateCityPlaceholder();
    toggleShippingVisibility();
  }

  init();

  $(document.body).on('updated_checkout', function () {
    toggleShippingVisibility();
    if (typeof refreshReviewSummary === 'function') {
      refreshReviewSummary();
    }
  });

  $('form[name="checkout"]').on('submit', function (e) {
    const $form = $(this);
    const hasError = validateRequiredFields($form);

    if (hasError) {
      e.preventDefault();
      e.stopImmediatePropagation();
      scrollToFirstError($form);
    }
  });
});
