



jQuery(function($) {
  // === SETTINGS ===
  const requiredFieldLabels = {
    billing_first_name: 'Nombres',
    billing_last_name: 'Apellidos',
    billing_cedula: 'Número de cédula',
    billing_email: 'Correo electrónico',
    billing_phone: 'Número de teléfono',
    billing_country: 'Selecciona un país',
    billing_state: 'Selecciona un departamento',
    billing_city: 'Selecciona una ciudad',
    billing_postcode: 'Código postal',
    billing_address_1: 'Dirección de entrega',
    // billing_address_2 is optional — complemento de dirección no debe ser requerido
  };

  const shippingToggleSelector = '#ship_to_different_address';

  const requiredFieldLabelsIfShippingEnabled = {
    shipping_country: 'Selecciona un país',
    shipping_state: 'Selecciona un departamento',
    shipping_postcode: 'Código postal',
    shipping_address_1: 'Dirección de entrega',
  }

  // === HELPERS ===
  function triggerWiggle($el) {
    $el.removeClass('wiggle-animation');
    void $el[0].offsetWidth;
    $el.addClass('wiggle-animation');
  }

  function markError($input) {
    const $wrapper = $input.closest('.bsc__field').length ? $input.closest('.bsc__field') : $input.closest('p');
    $wrapper.addClass('has-error');
    triggerWiggle($wrapper);
    $input.on('input change', () => $wrapper.removeClass('has-error'));
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

  // === Validation ===
  function validateRequiredFields($form) {
    let hasError = false;

    // Always validate billing fields
    $.each(requiredFieldLabels, function (fieldName, label) {
      const $input = $form.find(`[name="${fieldName}"]`);
      if ($input.length && isFieldEmpty($input)) {
        markError($input);
        hasError = true;
      }
    });

    // Conditionally validate shipping fields
    if ($(shippingToggleSelector).is(':checked')) {
      $.each(requiredFieldLabelsIfShippingEnabled, function (fieldName, label) {
        const $input = $form.find(`[name="${fieldName}"]`);
        if ($input.length && isFieldEmpty($input)) {
          markError($input);
          hasError = true;
        }
      });
    }

    return hasError;
  }

  // === AJAX: LOAD CITY FIELD ===
  function setupCityLoader() {
    $('#billing_state').on('change', function () {
      const state = $(this).val();
      $.ajax({
        url: bsc_ajax.ajax_url,
        method: 'POST',
        data: { action: 'bsc_reload_city_fields', billing_state: state, nonce: bsc_ajax.nonce },
        beforeSend: () => $('#billing_city_field').html('<p>Cargando ciudad…</p>'),
        success: function (response) {
          if (response.success) {
            console.log('✅ AJAX city field loaded');
            setTimeout(() => {
              $('#billing_city_field').replaceWith(response.data.html);
              updateCityPlaceholder();
              $(document.body).trigger('update_checkout');
            }, 300);
          } else {
            alert('Error al cargar las ciudades.');
          }
        },
        error: () => alert('Hubo un problema con la petición AJAX.')
      });
    });

    $(document.body).on('change', '#billing_city, #billing_state', function () {
      console.log(`📍 ${this.id} changed —> update_checkout`);
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

  // === REVIEW SUMMARY AJAX REFRESH ===
  function refreshReviewSummary() {
    console.log('🔁 Refreshing Review Summary...');
    $.ajax({
      url: bsc_ajax.ajax_url,
      method: 'POST',
      data: { action: 'bsc_get_review_summary', nonce: bsc_ajax.nonce },
      success: function (response) {
        if (response.success && response.data.html) {
          console.log(' ')
          console.log(' ')
          console.log(' ')
          console.log(' // === REVIEW SUMMARY AJAX REFRESH === ')
          console.log(' response.data ')
          console.log(response.data)
          console.log(' ')
          console.log(' ')
          $('#bsc-review-summary').html(response.data.html);
        } else {
          console.warn('⚠️ Invalid review summary response');
        }
      },
      error: function () {
        console.error('❌ Error al refrescar el resumen del pedido.');
      }
    });
  }

 

  // === INIT ALL ===
  function init() {
    console.log('🚀 Init BSC Checkout');
    setupCityLoader();
    setupShippingToggle();
    updateCityPlaceholder();
  }

  init();

  // === WooCommerce Trigger Hook ===
  $(document.body).on('updated_checkout', function () {

    console.log('📦 WC Checkout event');

    //setupOrderButtonValidation();
    refreshReviewSummary();

  });

  $('form[name="checkout"]').on('submit', function (e) {
    const $form = $(this);
    console.log('🧪 Validando checkout...');

    const hasError = validateRequiredFields($form);

    if (hasError) {
      // Prevent ALL submission (native + WooCommerce AJAX) and show inline errors
      e.preventDefault();
      e.stopImmediatePropagation();
      console.log('❌ Validación fallida — campos requeridos vacíos.');
      scrollToFirstError($form);
    }
    // If no errors: don't preventDefault — WooCommerce's own checkout AJAX takes over
  });




});

