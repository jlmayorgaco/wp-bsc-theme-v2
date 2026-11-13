(function ($) {
  var config = window.bscShowroomAdmin || {};
  var strings = config.strings || {};
  var cart = {};
  var selectedProduct = null;
  var searchTimer = null;
  var searchVersion = 0;
  var requestId = null;
  var appliedCoupon = '';
  var couponRequest = null;
  var couponVersion = 0;
  var salePending = false;

  var $search = $('#bsc-product-search');
  var $qty = $('#bsc-product-qty');
  var $source = $('#bsc-product-source');
  var $backorder = $('#bsc-product-backorder');
  var $addButton = $('#bsc-add-product');
  var $availability = $('#bsc-product-availability');
  var $origin = $('#bsc-sale-origin');
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

  var sourceLabels = { bodega: 'Bodega', tienda: 'Tienda', encargo: 'Encargo' };

  function lineKey(product, source) {
    return product.id + ':' + (product.variant_key || '') + ':' + source;
  }

  function availableStock(product, source) {
    var line = cart[lineKey(product, source)];
    return Math.max(0, Number(product['stock_' + source] || 0) - (line ? line.qty : 0));
  }

  function quantityFrom($input) {
    var value = Number($input.val());
    return Number.isInteger(value) && value > 0 && value <= 999 ? value : 0;
  }

  function updateProductControls() {
    var backorder = $backorder.prop('checked');
    var selectedSource = $source.val();
    var quantity = quantityFrom($qty);
    var bodega = selectedProduct ? availableStock(selectedProduct, 'bodega') : 0;
    var tienda = selectedProduct ? availableStock(selectedProduct, 'tienda') : 0;
    var stock = selectedSource === 'bodega' ? bodega : tienda;

    $source.find('[value="bodega"]').prop('disabled', bodega === 0).text('Bodega (' + bodega + ')');
    $source.find('[value="tienda"]').prop('disabled', tienda === 0).text('Tienda (' + tienda + ')');
    $source.prop('disabled', salePending || !selectedProduct || backorder || (!bodega && !tienda));
    if (selectedSource && !stock) {
      $source.val('');
      selectedSource = '';
    }

    var hasPrice = selectedProduct && typeof selectedProduct.price === 'number' && selectedProduct.price >= 0;
    var canAdd = hasPrice && quantity && (backorder || (selectedSource && quantity <= stock));
    $addButton.prop('disabled', salePending || !canAdd);
    $results.find('[data-no-stock]').prop('disabled', !backorder);

    var message = '';
    if (selectedProduct) {
      if (!hasPrice) {
        message = 'Configura un precio para este producto antes de agregarlo.';
      } else if (backorder) {
        message = formatPrice(selectedProduct.price) + ' por unidad · Encargo sin movimiento de stock.';
      } else if (!bodega && !tienda) {
        message = 'Sin existencias disponibles. Marca Encargo para agregarlo.';
      } else {
        message = formatPrice(selectedProduct.price) + ' por unidad · Bodega: ' + bodega + ' · Tienda: ' + tienda;
        if (selectedSource && quantity > stock) {
          message += '. La cantidad supera el stock de ' + sourceLabels[selectedSource] + '.';
        }
      }
    }
    $availability.text(message);
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
      var bodega = availableStock(product, 'bodega');
      var tienda = availableStock(product, 'tienda');
      var outOfStock = !bodega && !tienda;
      var $row = $('<button>', {
        class: 'bsc-showroom__search-result',
        type: 'button',
      });
      if (outOfStock) {
        $row.attr('data-no-stock', '');
      }

      if (product.thumbnail) {
        $row.append($('<img>', { class: 'bsc-showroom__thumb', src: product.thumbnail, alt: '', width: 44, height: 44, loading: 'lazy' }));
      }
      var $details = $('<span>', { class: 'bsc-showroom__search-result-details' }).append(
        $('<strong>', { text: product.name }),
        $('<span>', {
          class: 'bsc-showroom__search-result-meta',
          text: (strings.skuLabel || 'SKU: ') + (product.sku || '-'),
        }),
        $('<span>', {
          class: 'bsc-showroom__search-result-stock',
          text: 'Bodega: ' + bodega + ' · Tienda: ' + tienda + (outOfStock ? ' · Solo por encargo' : ''),
        })
      );
      $row.append($details);

      $row.on('click', function () {
        selectedProduct = product;
        $search.val(product.name);
        $source.val('');
        hideResults();
        updateProductControls();
        ($source.prop('disabled') ? $qty : $source).trigger('focus');
      });

      $results.append($row);
    });

    $results.removeClass('bsc-showroom__search-results--hidden');
    updateProductControls();
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
          text: sourceLabels[item.source],
        })
      );
      $row.append(
        $('<td>', { class: 'bsc-showroom__cart-cell bsc-showroom__cart-cell--center' }).append(
          $('<input>', {
            type: 'number',
            class: 'cart-qty bsc-showroom__cart-qty-input',
            'data-pid': productId,
            'aria-label': 'Cantidad de ' + item.name + ' (' + sourceLabels[item.source] + ')',
            min: 1,
            max: item.source === 'encargo' ? 999 : Math.min(item.stock, 999),
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
            'aria-label': 'Quitar ' + item.name + ' (' + sourceLabels[item.source] + ')',
            text: '×',
          })
        )
      );

      $cartBody.append($row);
    });

    $cartTotal.text(formatPrice(total));
  }

  function cartItems() {
    return Object.keys(cart).map(function (key) {
      var item = cart[key];
      return { id: item.id, variant_key: item.variant_key, qty: item.qty, source: item.source };
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
      customer_address: $('#bsc-customer-address').val().trim(),
      customer_city: $('#bsc-customer-city').val().trim(),
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

  $('#bsc-coupon-code, #bsc-customer-email, #bsc-customer-address, #bsc-customer-city').on('input', function () {
    var hadCoupon = Boolean(appliedCoupon);
    requestId = null;
    renderCart();
    if (hadCoupon) { $('#bsc-coupon-notice').text('Los datos cambiaron. Aplica el cupón nuevamente.'); }
  });

  $('#bsc-customer-name, #bsc-customer-phone, #bsc-sale-origin, input[name="bsc-payment"]').on('change', function () {
    requestId = null;
  });

  $search.on('input', function () {
    var query = $search.val().trim();
    var version = ++searchVersion;

    clearTimeout(searchTimer);
    selectedProduct = null;
    $source.val('');
    hideResults();
    updateProductControls();

    if (query.length < 2) {
      return;
    }

    searchTimer = setTimeout(function () {
      $.post(config.ajaxUrl, {
        action: 'bsc_showroom_search',
        nonce: config.nonce,
        q: query,
      }).done(function (response) {
        if (version !== searchVersion || salePending) { return; }
        var products = response && response.success && Array.isArray(response.data) ? response.data : [];
        renderSearchResults(products);
      });
    }, 300);
  });

  $(document).on('click', function (event) {
    if (!$(event.target).closest('#bsc-product-search, #bsc-search-results, .bsc-showroom__backorder-option').length) {
      searchVersion += 1;
      hideResults();
    }
  });

  $source.add($backorder).on('change', updateProductControls);
  $qty.on('input', updateProductControls);

  $addButton.on('click', function () {
    if (!selectedProduct) {
      showNotice(strings.selectProduct || 'Selecciona un producto primero.', 'error');
      return;
    }
    var quantity = quantityFrom($qty);
    var source = $backorder.prop('checked') ? 'encargo' : $source.val();
    var productId = lineKey(selectedProduct, source);
    var existingQty = cart[productId] ? cart[productId].qty : 0;
    if (!quantity || quantity + existingQty > 999 || (source !== 'encargo' && (!source || quantity > availableStock(selectedProduct, source)))) {
      showNotice('Revisa la cantidad y el origen del stock. Puedes agregar un máximo de 999 unidades por línea.', 'error');
      return;
    }

    requestId = null;

    if (cart[productId]) {
      cart[productId].qty += quantity;
      cart[productId].stock = Number(selectedProduct['stock_' + source] || 0);
    } else {
      cart[productId] = {
        id: selectedProduct.id,
        variant_key: selectedProduct.variant_key || '',
        name: selectedProduct.name,
        price: selectedProduct.price,
        qty: quantity,
        stock: Number(selectedProduct['stock_' + source] || 0),
        source: source,
      };
    }

    renderCart();
    selectedProduct = null;
    $source.val('');
    $backorder.prop('checked', false);
    $search.val('').focus();
    $qty.val(1);
    hideResults();
    updateProductControls();
  });

  $(document).on('change', '.cart-qty', function () {
    var productId = $(this).data('pid');
    var quantity = quantityFrom($(this));

    if (cart[productId]) {
      var item = cart[productId];
      if (!quantity || (item.source !== 'encargo' && quantity > item.stock)) {
        $(this).val(item.qty);
        showNotice('Ingresa una cantidad válida para las existencias de esta línea.', 'error');
        return;
      }
      requestId = null;
      item.qty = quantity;
      renderCart();
      updateProductControls();
    }
  });

  $(document).on('click', '.remove-item', function () {
    requestId = null;
    delete cart[$(this).data('pid')];
    renderCart();
    updateProductControls();
  });

  $registerButton.on('click', function () {
    if (salePending || couponRequest) { return; }
    var items = cartItems();
    var payment = $('input[name="bsc-payment"]:checked').val();

    if (!items.length) {
      showNotice(strings.addProductFirst || 'Agrega al menos un producto.', 'error');
      return;
    }
    if (!$origin[0].reportValidity()) {
      return;
    }

    var origin = $origin.val();
    salePending = true;
    var $saleControls = $('.bsc-showroom__card').find('input, button, select').filter(':enabled');
    $saleControls.prop('disabled', true);
    $registerButton.text(strings.registering || 'Registrando...');
    requestId = requestId || createRequestId();

    $.post(config.ajaxUrl, {
      action: 'bsc_register_showroom_sale',
      nonce: config.nonce,
      items: JSON.stringify(items),
      request_id: requestId,
      payment_method: payment,
      sale_origin: origin,
      customer_name: $('#bsc-customer-name').val().trim(),
      customer_phone: $('#bsc-customer-phone').val().trim(),
      customer_email: $('#bsc-customer-email').val().trim(),
      customer_address: $('#bsc-customer-address').val().trim(),
      customer_city: $('#bsc-customer-city').val().trim(),
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
        updateProductControls();
        $registerButton.text(strings.registerSale || 'Registrar venta');
      })
      .fail(function (xhr) {
        var response = xhr.responseJSON;
        showNotice(escapeHtml((response && response.data && response.data.message) || strings.connectionError || 'Error de conexión. Intenta nuevamente para consultar el resultado de esta venta.'), 'error');
        salePending = false;
        $saleControls.prop('disabled', false);
        updateProductControls();
        $registerButton.text(strings.registerSale || 'Registrar venta');
      });
  });
})(jQuery);
