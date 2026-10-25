(function ($) {
  var config = window.bscShowroomAdmin || {};
  var strings = config.strings || {};
  var cart = {};
  var selectedProduct = null;
  var searchTimer = null;
  var requestId = null;
  var appliedCoupon = '';
  var couponRequest = null;
  var couponVersion = 0;
  var salePending = false;

  var $search = $('#bsc-product-search');
  var $qty = $('#bsc-product-qty');
  var $results = $('#bsc-search-results');
  var $cartTable = $('#bsc-cart-table');
  var $cartBody = $('#bsc-cart-body');
  var $cartEmpty = $('#bsc-cart-empty');
  var $cartTotal = $('#bsc-cart-total');
  var $notice = $('#bsc-showroom-notice');
  var $registerButton = $('#bsc-register-sale');

  if (!$search.length) {
    return;
  }

  function formatPrice(value) {
    return '$' + Number(value || 0).toLocaleString('es-CO');
  }

  function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
  }

  function createRequestId() {
    if (typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID();
    }
    var bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 15) | 64;
    bytes[8] = (bytes[8] & 63) | 128;
    return Array.from(bytes, function (value, index) {
      return ([4, 6, 8, 10].indexOf(index) !== -1 ? '-' : '') + value.toString(16).padStart(2, '0');
    }).join('');
  }

  function hideResults() {
    $results.empty().addClass('bsc-showroom__search-results--hidden');
  }

  function showNotice(message, type) {
    $notice
      .removeClass('bsc-showroom__notice--hidden bsc-showroom__notice--success bsc-showroom__notice--error')
      .addClass(type === 'success' ? 'bsc-showroom__notice--success' : 'bsc-showroom__notice--error')
      .html(message);
  }

  function renderSearchResults(products) {
    $results.empty();

    if (!products.length) {
      $results
        .append(
          $('<div>', {
            class: 'bsc-showroom__search-empty',
            text: strings.emptyResults || 'Sin resultados.',
          })
        )
        .removeClass('bsc-showroom__search-results--hidden');
      return;
    }

    products.forEach(function (product) {
      var $row = $('<div>', {
        class: 'bsc-showroom__search-result',
        tabindex: 0,
      });

      $row.append($('<strong>', { text: product.name }));
      $row.append(document.createTextNode(' '));
      $row.append(
        $('<span>', {
          class: 'bsc-showroom__search-result-meta',
          text: (strings.skuLabel || 'SKU: ') + (product.sku || '-'),
        })
      );
      $row.append(document.createTextNode(' '));
      $row.append(
        $('<span>', {
          class: 'bsc-showroom__search-result-stock',
          text: (strings.storeStockLabel || 'Stock tienda: ') + product.stock_tienda,
        })
      );

      $row.on('click keydown', function (event) {
        if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
          return;
        }

        event.preventDefault();
        selectedProduct = product;
        $search.val(product.name);
        hideResults();
      });

      $results.append($row);
    });

    $results.removeClass('bsc-showroom__search-results--hidden');
  }

  function renderCart() {
    clearCoupon();
    var keys = Object.keys(cart);
    var total = 0;

    $cartBody.empty();

    if (!keys.length) {
      $cartTable.addClass('bsc-showroom__cart-table--hidden');
      $cartEmpty.show();
      return;
    }

    $cartTable.removeClass('bsc-showroom__cart-table--hidden');
    $cartEmpty.hide();

    keys.forEach(function (productId) {
      var item = cart[productId];
      var subtotal = Number(item.price) * Number(item.qty);
      total += subtotal;

      var $row = $('<tr>', { 'data-pid': productId });
      $row.append(
        $('<td>', {
          class: 'bsc-showroom__cart-cell',
          text: item.name,
        })
      );
      $row.append(
        $('<td>', {
          class: 'bsc-showroom__cart-cell bsc-showroom__cart-cell--center',
          text: Number(item.stock_tienda) > 0 ? 'Stock' : 'Encargo',
        })
      );
      $row.append(
        $('<td>', { class: 'bsc-showroom__cart-cell bsc-showroom__cart-cell--center' }).append(
          $('<input>', {
            type: 'number',
            class: 'cart-qty bsc-showroom__cart-qty-input',
            'data-pid': productId,
            min: 1,
            value: item.qty,
          })
        )
      );
      $row.append(
        $('<td>', {
          class: 'bsc-showroom__cart-cell bsc-showroom__cart-cell--right',
          text: formatPrice(item.price),
        })
      );
      $row.append(
        $('<td>', {
          class: 'bsc-showroom__cart-cell bsc-showroom__cart-cell--right',
          text: formatPrice(subtotal),
        })
      );
      $row.append(
        $('<td>', { class: 'bsc-showroom__cart-cell bsc-showroom__cart-cell--center bsc-showroom__cart-cell--compact' }).append(
          $('<button>', {
            type: 'button',
            class: 'remove-item button bsc-showroom__remove-item',
            'data-pid': productId,
            text: 'x',
          })
        )
      );

      $cartBody.append($row);
    });

    $cartTotal.text(formatPrice(total));
  }

  function cartItems() {
    return Object.keys(cart).map(function (key) {
      return { id: cart[key].id, variant_key: cart[key].variant_key, qty: cart[key].qty };
    });
  }

  function clearCoupon() {
    couponVersion += 1;
    if (couponRequest) {
      couponRequest.abort();
      couponRequest = null;
    }
    appliedCoupon = '';
    $('#bsc-remove-coupon').prop('hidden', true);
    $('#bsc-apply-coupon').prop('disabled', false).text('Aplicar');
    $('#bsc-coupon-notice').text('');
    $registerButton.prop('disabled', salePending);
  }

  $('#bsc-apply-coupon').on('click', function () {
    renderCart();
    var code = $('#bsc-coupon-code').val().trim();
    if (!cartItems().length || !code) {
      $('#bsc-coupon-notice').text('Agrega productos e ingresa un cupón.');
      return;
    }
    var version = couponVersion;
    requestId = null;
    $(this).prop('disabled', true).text('Aplicando...');
    $registerButton.prop('disabled', true);
    couponRequest = $.post(config.ajaxUrl, {
      action: 'bsc_showroom_coupon',
      nonce: config.nonce,
      items: JSON.stringify(cartItems()),
      customer_email: $('#bsc-customer-email').val().trim(),
      coupon_code: code,
    }).done(function (response) {
      if (version !== couponVersion) { return; }
      if (response && response.success) {
        appliedCoupon = response.data.code;
        $('#bsc-coupon-notice').text('Cupón ' + appliedCoupon + ' aplicado. Descuento: ' + formatPrice(response.data.discount));
        $('#bsc-remove-coupon').prop('hidden', false);
        $cartTotal.text(formatPrice(response.data.total));
      } else {
        $('#bsc-coupon-notice').text((response && response.data && response.data.message) || 'No se pudo aplicar el cupón.');
      }
    }).fail(function (xhr, status) {
      if (version !== couponVersion || status === 'abort') { return; }
      $('#bsc-coupon-notice').text((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'No se pudo aplicar el cupón. Intenta nuevamente.');
    }).always(function () {
      if (version !== couponVersion) { return; }
      couponRequest = null;
      $('#bsc-apply-coupon').prop('disabled', false).text('Aplicar');
      $registerButton.prop('disabled', false);
    });
  });

  $('#bsc-remove-coupon').on('click', function () {
    requestId = null;
    $('#bsc-coupon-code').val('');
    renderCart();
  });

  $('#bsc-coupon-code, #bsc-customer-email').on('input', function () {
    var hadCoupon = Boolean(appliedCoupon);
    requestId = null;
    renderCart();
    if (hadCoupon) { $('#bsc-coupon-notice').text('Los datos cambiaron. Aplica el cupón nuevamente.'); }
  });

  $('#bsc-customer-name, #bsc-customer-phone, input[name="bsc-payment"]').on('change', function () {
    requestId = null;
  });

  $search.on('input', function () {
    var query = $search.val().trim();

    clearTimeout(searchTimer);

    if (query.length < 2) {
      hideResults();
      return;
    }

    searchTimer = setTimeout(function () {
      $.post(config.ajaxUrl, {
        action: 'bsc_showroom_search',
        nonce: config.nonce,
        q: query,
      }).done(function (response) {
        var products = response && response.success && Array.isArray(response.data) ? response.data : [];
        renderSearchResults(products);
      });
    }, 300);
  });

  $(document).on('click', function (event) {
    if (!$(event.target).closest('#bsc-product-search, #bsc-search-results').length) {
      hideResults();
    }
  });

  $('#bsc-add-product').on('click', function () {
    var quantity = Math.max(1, parseInt($qty.val(), 10) || 1);
    var productId;

    if (!selectedProduct) {
      showNotice(strings.selectProduct || 'Selecciona un producto primero.', 'error');
      return;
    }

    productId = selectedProduct.id + ':' + (selectedProduct.variant_key || '');
    requestId = null;

    if (cart[productId]) {
      cart[productId].qty += quantity;
    } else {
      cart[productId] = {
        id: selectedProduct.id,
        variant_key: selectedProduct.variant_key || '',
        name: selectedProduct.name,
        price: selectedProduct.price,
        qty: quantity,
        stock_tienda: selectedProduct.stock_tienda,
      };
    }

    renderCart();
    selectedProduct = null;
    $search.val('').focus();
    $qty.val(1);
    hideResults();
  });

  $(document).on('change', '.cart-qty', function () {
    var productId = $(this).data('pid');
    var quantity = Math.max(1, parseInt($(this).val(), 10) || 1);

    if (cart[productId]) {
      requestId = null;
      cart[productId].qty = quantity;
      renderCart();
    }
  });

  $(document).on('click', '.remove-item', function () {
    requestId = null;
    delete cart[$(this).data('pid')];
    renderCart();
  });

  $registerButton.on('click', function () {
    if (salePending || couponRequest) { return; }
    var items = Object.keys(cart).map(function (productId) {
      return {
        id: cart[productId].id,
        variant_key: cart[productId].variant_key,
        qty: cart[productId].qty,
      };
    });
    var payment = $('input[name="bsc-payment"]:checked').val();

    if (!items.length) {
      showNotice(strings.addProductFirst || 'Agrega al menos un producto.', 'error');
      return;
    }

    salePending = true;
    var $saleControls = $('.bsc-showroom__card').find('input, button').filter(':enabled');
    $saleControls.prop('disabled', true);
    $registerButton.text(strings.registering || 'Registrando...');
    requestId = requestId || createRequestId();

    $.post(config.ajaxUrl, {
      action: 'bsc_register_showroom_sale',
      nonce: config.nonce,
      items: JSON.stringify(items),
      request_id: requestId,
      payment_method: payment,
      customer_name: $('#bsc-customer-name').val().trim(),
      customer_phone: $('#bsc-customer-phone').val().trim(),
      customer_email: $('#bsc-customer-email').val().trim(),
      coupon_code: appliedCoupon,
    })
      .done(function (response) {
        if (response && response.success) {
          showNotice(
            'Venta registrada. Pedido <a href="' +
              escapeHtml(response.data.edit_url) +
              '" target="_blank">#' +
              escapeHtml(response.data.order_id) +
              '</a>',
            'success'
          );
          cart = {};
          renderCart();
          setTimeout(function () {
            window.location.reload();
          }, 2000);
          return;
        }

        showNotice(
          'Error: ' +
            escapeHtml(
              (response && response.data && response.data.message) ||
                strings.unknownError ||
                'desconocido'
            ),
          'error'
        );
        salePending = false;
        $saleControls.prop('disabled', false);
        $registerButton.text(strings.registerSale || 'Registrar venta');
      })
      .fail(function (xhr) {
        var response = xhr.responseJSON;
        showNotice(escapeHtml((response && response.data && response.data.message) || strings.connectionError || 'Error de conexión. Intenta nuevamente para consultar el resultado de esta venta.'), 'error');
        salePending = false;
        $saleControls.prop('disabled', false);
        $registerButton.text(strings.registerSale || 'Registrar venta');
      });
  });
})(jQuery);
