(function ($) {
  'use strict';

  var config = window.bscProductsAdmin || {};
  var ajaxUrl = config.ajaxUrl || window.ajaxurl || '';
  var nonce = config.nonce || '';
  var strings = config.strings || {};
  var lowStockThreshold = Number(config.lowStockThreshold || 0);
  var toastTimer = null;

  function modal() {
    return $('#bsc-stock-modal');
  }

  function modalBody() {
    return $('#bsc-stock-modal-body');
  }

  function toast() {
    return $('#bsc-admin-products-toast');
  }

  function openModal() {
    modal().addClass('is-open');
  }

  function closeModal() {
    modal().removeClass('is-open');
  }

  function showToast(message, type) {
    var $toast = toast();

    if (!$toast.length) {
      return;
    }

    window.clearTimeout(toastTimer);
    $toast
      .text(message)
      .removeClass('bsc-admin-toast--error is-visible')
      .toggleClass('bsc-admin-toast--error', type === 'error')
      .addClass('is-visible');

    toastTimer = window.setTimeout(function () {
      $toast.removeClass('is-visible bsc-admin-toast--error');
    }, 2200);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function buildHistoryTable(rows) {
    var bodyRows = rows.map(function (entry) {
      var delta = entry.delta > 0 ? '+' + entry.delta : entry.delta;
      var deltaClass = entry.delta > 0
        ? 'bsc-admin-products__delta bsc-admin-products__delta--positive'
        : 'bsc-admin-products__delta bsc-admin-products__delta--negative';

      return '<tr>'
        + '<td>' + entry.date + '</td>'
        + '<td>' + entry.type + '</td>'
        + '<td class="' + deltaClass + '">' + delta + '</td>'
        + '<td>' + entry.before + ' → ' + entry.after + '</td>'
        + '<td>' + entry.username + '</td>'
        + '<td>' + entry.reason + '</td>'
        + '</tr>';
    }).join('');

    return '<table class="wp-list-table widefat bsc-admin-products__history-table">'
      + '<thead><tr>'
      + '<th>Fecha</th><th>Tipo</th><th>Δ</th><th>Antes → Después</th><th>Usuario</th><th>Razón</th>'
      + '</tr></thead><tbody>' + bodyRows + '</tbody></table>';
  }

  function variantLabel(variant) {
    var parts = [];

    if (variant.color_name) {
      parts.push(variant.color_name);
    }
    if (variant.size_name) {
      parts.push(variant.size_name);
    }

    return parts.join(' / ') || 'Variante';
  }

  function buildVariantMatrixForm(variants, productId) {
    if (!variants.length) {
      return '<p>' + escapeHtml(strings.variantsEmpty || 'Este producto no tiene variantes configuradas.') + '</p>';
    }

    var rows = variants.map(function (variant, index) {
      var checked = variant.enabled ? ' checked' : '';
      var disabledClass = variant.enabled ? '' : ' is-disabled';
      var swatch = variant.color_hex
        ? '<span class="bsc-admin-products__variant-swatch" data-color-hex="' + escapeHtml(variant.color_hex) + '"></span>'
        : '';

      return '<tr class="bsc-admin-products__variant-row' + disabledClass + '" data-bsc-products-variant-row>'
        + '<td>'
        + '<input type="hidden" data-field="color_name" value="' + escapeHtml(variant.color_name || '') + '">'
        + '<input type="hidden" data-field="color_hex" value="' + escapeHtml(variant.color_hex || '') + '">'
        + '<input type="hidden" data-field="size_name" value="' + escapeHtml(variant.size_name || '') + '">'
        + '<span class="bsc-admin-products__variant-name">' + swatch + '<span>' + escapeHtml(variantLabel(variant)) + '</span></span>'
        + '</td>'
        + '<td><input type="number" min="0" step="1" inputmode="numeric" data-field="regular_price" value="' + escapeHtml(variant.regular_price || '') + '"></td>'
        + '<td><input type="number" min="0" step="1" inputmode="numeric" data-field="sale_price" value="' + escapeHtml(variant.sale_price || '') + '"></td>'
        + '<td><input type="number" min="0" step="1" data-field="stock_bodega" value="' + escapeHtml(variant.stock_bodega || 0) + '"></td>'
        + '<td><input type="number" min="0" step="1" data-field="stock_tienda" value="' + escapeHtml(variant.stock_tienda || 0) + '"></td>'
        + '<td><label class="bsc-admin-products__variant-enabled"><input type="checkbox" data-field="enabled" value="1"' + checked + '> Activa</label></td>'
        + '</tr>';
    }).join('');

    return '<div class="bsc-admin-products__variant-editor" data-bsc-products-variant-editor data-product-id="' + escapeHtml(productId) + '">'
      + '<div class="bsc-admin-products__variant-table-wrap">'
      + '<table class="wp-list-table widefat striped bsc-admin-products__variant-table">'
      + '<thead><tr>'
      + '<th>Variante</th><th>Regular</th><th>Oferta</th><th>Bodega</th><th>Tienda</th><th>Estado</th>'
      + '</tr></thead><tbody>' + rows + '</tbody></table>'
      + '</div>'
      + '<div class="bsc-admin-products__variant-actions">'
      + '<button type="button" class="button button-primary" data-bsc-products-variant-save>Guardar variantes</button>'
      + '</div>'
      + '</div>';
  }

  function applyVariantSwatches($scope) {
    $scope.find('[data-color-hex]').each(function () {
      var color = String($(this).attr('data-color-hex') || '').trim();

      if (/^#[0-9a-f]{6}$/i.test(color)) {
        $(this).css('background-color', color);
      }
    });
  }

  function collectVariantMatrix($editor) {
    return $editor.find('[data-bsc-products-variant-row]').map(function () {
      var $row = $(this);

      return {
        color_name: $row.find('[data-field="color_name"]').val() || '',
        color_hex: $row.find('[data-field="color_hex"]').val() || '',
        size_name: $row.find('[data-field="size_name"]').val() || '',
        regular_price: $row.find('[data-field="regular_price"]').val() || '',
        sale_price: $row.find('[data-field="sale_price"]').val() || '',
        stock_bodega: $row.find('[data-field="stock_bodega"]').val() || '0',
        stock_tienda: $row.find('[data-field="stock_tienda"]').val() || '0',
        enabled: $row.find('[data-field="enabled"]').is(':checked') ? '1' : '0',
      };
    }).get();
  }

  function getRow($target) {
    return $target.closest('.bsc-admin-products__row');
  }

  function getInputs($row) {
    return $row.find('.bsc-product-inline-input');
  }

  function productTable() {
    return $('.bsc-admin-products__table');
  }

  function discountControls() {
    return $('#bsc-discount-controls');
  }

  function discountCheckboxes() {
    return $('.bsc-product-discount-checkbox');
  }

  function selectedDiscountCheckboxes() {
    return discountCheckboxes().filter(':checked');
  }

  function discountPercentInput() {
    return $('#bsc-discount-percent');
  }

  function applyDiscountButton() {
    return $('#bsc-apply-discount');
  }

  function selectAllDiscountCheckbox() {
    return $('#bsc-discount-select-all');
  }

  function getPendingBadge($row) {
    return $row.find('[data-role="pending"]');
  }

  function getSaveButton($row) {
    return $row.find('[data-role="save"]');
  }

  function isInputChanged($input) {
    return String($input.val()) !== String($input.data('original'));
  }

  function isStockInput($input) {
    return $input.hasClass('bsc-stock-input');
  }

  function isPriceInput($input) {
    return $input.hasClass('bsc-price-input');
  }

  function isLocationCodeInput($input) {
    return $input.hasClass('bsc-location-code-input');
  }

  function isInputValid($input) {
    var rawValue = String($input.val()).trim();
    var value;

    if (isLocationCodeInput($input)) {
      return rawValue === '' || /^COD-M[0-9]+-E[0-9]+$/.test(rawValue);
    }

    value = isStockInput($input)
      ? parseInt(rawValue, 10)
      : Number(rawValue);

    return rawValue !== '' && !isNaN(value) && value >= 0;
  }

  function syncLowStockClass($input) {
    if (!isStockInput($input)) {
      return;
    }

    var value = parseInt($input.val(), 10);
    if (isNaN(value) || !lowStockThreshold) {
      return;
    }

    $input.toggleClass('is-low', value < lowStockThreshold);
  }

  function syncRowState($row) {
    var changed = false;

    getInputs($row).each(function () {
      var $input = $(this);
      var inputChanged = isInputChanged($input);

      $input.toggleClass('is-pending', inputChanged);
      syncLowStockClass($input);
      changed = changed || inputChanged;
    });

    getPendingBadge($row).toggleClass('is-visible', changed);
    getSaveButton($row).prop('disabled', !changed);
  }

  function saveRow($row) {
    var $saveButton = getSaveButton($row);
    var $inputs = getInputs($row);
    var payload = {
      action: 'bsc_update_product_stocks',
      nonce: nonce,
      product_id: $row.data('product-id')
    };
    var invalidInput = null;

    $inputs.each(function () {
      var $input = $(this);
      var value = String($input.val()).trim();

      if (!isInputValid($input)) {
        invalidInput = $input;
        return false;
      }

      if (isStockInput($input)) {
        payload[$input.data('type')] = parseInt(value, 10);
      } else if (isPriceInput($input)) {
        payload[$input.data('field')] = value;
      } else if (isLocationCodeInput($input)) {
        payload.location_code = value;
      }

      return undefined;
    });

    if (invalidInput) {
      invalidInput.trigger('focus');
      if (invalidInput.get(0) && typeof invalidInput.get(0).reportValidity === 'function') {
        invalidInput.get(0).reportValidity();
      }
      showToast(
        isLocationCodeInput(invalidInput)
          ? strings.locationCodeError || 'Location code invalido. Usa el formato COD-M4-E1.'
          : strings.priceError || strings.saveError || 'No se pudieron guardar los cambios.',
        'error'
      );
      return;
    }

    $saveButton.prop('disabled', true).text('Guardando...');
    $inputs.prop('disabled', true);

    $.post(ajaxUrl, payload)
      .done(function (response) {
        if (!response || !response.success || !response.data) {
          showToast(
            (response && response.data && response.data.message) || strings.saveError || 'No se pudieron guardar los cambios.',
            'error'
          );
          return;
        }

        $inputs.each(function () {
          var $input = $(this);
          var key = isStockInput($input) ? $input.data('type') : $input.data('field');
          var value = response.data[key];
          $input.val(value).data('original', value).removeClass('is-pending');
          syncLowStockClass($input);
        });

        getPendingBadge($row).removeClass('is-visible');
        showToast(strings.saved || 'Cambios guardados.', 'success');
      })
      .fail(function (xhr) {
        var message = xhr.responseJSON && xhr.responseJSON.data
          ? xhr.responseJSON.data.message
          : '';

        if (message) {
          showToast(message, 'error');
          return;
        }
        showToast(strings.connectionError || 'Error de conexión. Intenta de nuevo.', 'error');
      })
      .always(function () {
        $saveButton.prop('disabled', false).text('Guardar');
        $inputs.prop('disabled', false);
        syncRowState($row);
      });
  }

  function toggleProductStatus($button) {
    if ($button.prop('disabled') || $button.attr('aria-disabled') === 'true') {
      return;
    }

    var $row = getRow($button);
    var $label = $button.find('[data-role="status-label"]');
    var published = $button.attr('aria-checked') === 'true';
    var publishedLabel = strings.statusPublished || 'Publicado';
    var draftLabel = strings.statusDraft || 'Borrador';
    var errorMessage = strings.statusSaveError || 'No se pudo guardar el estado. Intenta de nuevo.';

    $button.attr({ 'aria-disabled': 'true', 'aria-busy': 'true' });
    $label.text(strings.statusSaving || 'Guardando…');

    $.post(ajaxUrl, {
      action: 'bsc_update_product_status',
      nonce: nonce,
      product_id: $row.data('product-id'),
      status: published ? 'draft' : 'publish'
    }).done(function (response) {
      if (!response || !response.success || !response.data ||
          ['publish', 'draft'].indexOf(response.data.status) === -1) {
        showToast((response && response.data && response.data.message) || errorMessage, 'error');
        return;
      }

      published = response.data.status === 'publish';
      $button.attr('aria-checked', published ? 'true' : 'false');
      if (response.data.permalink) {
        $row.find('[data-role="view-product"]').attr('href', response.data.permalink);
      }
      if (response.data.counts) {
        ['publish', 'draft'].forEach(function (status) {
          $('[data-bsc-product-count="' + status + '"]').text(response.data.counts[status]);
        });
      }
      showToast(published
        ? strings.statusPublishedSaved || 'Producto publicado.'
        : strings.statusDraftSaved || 'Producto guardado como borrador.', 'success');
    }).fail(function (xhr) {
      var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
      showToast(message || errorMessage, 'error');
    }).always(function () {
      $label.text(published ? publishedLabel : draftLabel);
      $button.removeAttr('aria-disabled aria-busy');
    });
  }

  function setDiscountMode(enabled) {
    productTable().toggleClass('is-discount-mode', enabled);
    discountControls().prop('hidden', !enabled);
    discountCheckboxes().prop('disabled', !enabled).prop('checked', false);
    selectAllDiscountCheckbox().prop('disabled', !enabled).prop('checked', false);
    discountPercentInput().prop('disabled', !enabled).val('');

    if (!enabled) {
      applyDiscountButton().prop('disabled', true).text('Guardar descuento');
    }

    updateDiscountState();
  }

  function updateDiscountState() {
    var selectedCount = selectedDiscountCheckboxes().length;
    var rawPercent = String(discountPercentInput().val()).trim();
    var percent = Number(rawPercent);
    var validPercent = rawPercent !== '' && !isNaN(percent) && percent >= 0 && percent < 100;

    $('#bsc-discount-selection-count').text(
      selectedCount + (selectedCount === 1 ? ' seleccionado' : ' seleccionados')
    );
    applyDiscountButton().prop('disabled', !selectedCount || !validPercent);
    selectAllDiscountCheckbox().prop(
      'checked',
      discountCheckboxes().length > 0 && selectedCount === discountCheckboxes().length
    );
  }

  function applyDiscountToSelection() {
    var selectedIds = selectedDiscountCheckboxes().map(function () {
      return $(this).val();
    }).get();
    var rawPercent = String(discountPercentInput().val()).trim();
    var percent = Number(rawPercent);
    var $applyButton = applyDiscountButton();

    if (!selectedIds.length) {
      showToast(strings.discountNoSelection || 'Selecciona al menos un producto.', 'error');
      return;
    }

    if (rawPercent === '' || isNaN(percent) || percent < 0 || percent >= 100) {
      discountPercentInput().trigger('focus');
      showToast(strings.discountInvalidPercent || 'Ingresa un descuento entre 0% y 99%.', 'error');
      return;
    }

    $applyButton.prop('disabled', true).text('Aplicando...');
    discountCheckboxes().prop('disabled', true);
    discountPercentInput().prop('disabled', true);

    $.post(ajaxUrl, {
      action: 'bsc_apply_product_discount',
      nonce: nonce,
      product_ids: selectedIds,
      discount_percent: percent
    }).done(function (response) {
      var updated = response && response.data ? Number(response.data.updated || 0) : 0;
      var prices = response && response.data && response.data.prices ? response.data.prices : {};

      if (!response || !response.success || !updated) {
        showToast(
          (response && response.data && response.data.message) || strings.saveError || 'No se pudieron guardar los cambios.',
          'error'
        );
        return;
      }

      Object.keys(prices).forEach(function (productId) {
        var productPrice = prices[productId];
        var $row = $('.bsc-admin-products__row[data-product-id="' + productId + '"]');
        var $note = $row.find('[data-role="sale-price-note"]');

        if (productPrice.sale_price_label) {
          $note
            .text('Oferta: ' + productPrice.sale_price_label)
            .removeClass('is-hidden');
        } else {
          $note
            .text('')
            .addClass('is-hidden');
        }
      });

      showToast(
        (strings.discountApplied || 'Descuento aplicado.') + ' ' + updated + (updated === 1 ? ' producto.' : ' productos.'),
        'success'
      );
      setDiscountMode(false);
    }).fail(function (xhr) {
      var message = xhr.responseJSON && xhr.responseJSON.data
        ? xhr.responseJSON.data.message
        : '';

      showToast(message || strings.connectionError || 'Error de conexion. Intenta de nuevo.', 'error');
    }).always(function () {
      $applyButton.text('Guardar descuento');

      if (productTable().hasClass('is-discount-mode')) {
        discountCheckboxes().prop('disabled', false);
        discountPercentInput().prop('disabled', false);
        updateDiscountState();
      }
    });
  }

  function getDeleteConfirmMessage(productName) {
    var message = strings.deleteConfirm || 'Borrar "%s"? El producto se enviara a la papelera.';

    if (message.indexOf('%s') !== -1) {
      return message.replace('%s', productName || 'este producto');
    }

    return message;
  }

  function removeProductRow($row) {
    var $tbody = $row.closest('tbody');

    $row.fadeOut(150, function () {
      $row.remove();

      if (!$tbody.find('.bsc-admin-products__row').length) {
        $tbody.append('<tr><td colspan="9" class="bsc-admin-products__empty">No hay productos.</td></tr>');
      }

      updateDiscountState();
    });
  }

  function trashProduct($button) {
    var $row = getRow($button);
    var productId = $button.data('product-id') || $row.data('product-id');
    var productName = String($button.data('product-name') || '').trim();
    var originalLabel = $button.text();
    var removed = false;

    if (!productId) {
      showToast(strings.deleteError || 'No se pudo borrar el producto.', 'error');
      return;
    }

    if (!window.confirm(getDeleteConfirmMessage(productName))) {
      return;
    }

    $button.prop('disabled', true).text(strings.deleting || 'Borrando...');

    $.post(ajaxUrl, {
      action: 'bsc_trash_product',
      nonce: nonce,
      product_id: productId
    }).done(function (response) {
      if (!response || !response.success) {
        showToast(
          (response && response.data && response.data.message) || strings.deleteError || 'No se pudo borrar el producto.',
          'error'
        );
        return;
      }

      removed = true;
      removeProductRow($row);
      showToast(
        (response.data && response.data.message) || strings.deleted || 'Producto enviado a la papelera.',
        'success'
      );
    }).fail(function (xhr) {
      var message = xhr.responseJSON && xhr.responseJSON.data
        ? xhr.responseJSON.data.message
        : '';

      showToast(message || strings.connectionError || strings.deleteError || 'No se pudo borrar el producto.', 'error');
    }).always(function () {
      if (!removed) {
        $button.prop('disabled', false).text(originalLabel);
      }
    });
  }

  function openVariantMatrix($button) {
    var productId = $button.data('product-id');
    var productName = $button.data('product-name') || '';

    if (!productId) {
      showToast(strings.variantsSaveError || 'No se pudieron cargar las variantes.', 'error');
      return;
    }

    $('#bsc-stock-modal-title').text((strings.variantsTitlePrefix || 'Variantes: ') + productName);
    modalBody().html('<p>' + (strings.loading || 'Cargando...') + '</p>');
    openModal();

    $.get(ajaxUrl, {
      action: 'bsc_get_product_variant_matrix',
      nonce: nonce,
      product_id: productId,
    }).done(function (response) {
      if (!response || !response.success || !response.data) {
        modalBody().html('<p>' + escapeHtml((response && response.data && response.data.message) || strings.loadError || 'No se pudo cargar el historial.') + '</p>');
        return;
      }

      modalBody().html(buildVariantMatrixForm(response.data.variants || [], productId));
      applyVariantSwatches(modalBody());
    }).fail(function (xhr) {
      var message = xhr.responseJSON && xhr.responseJSON.data
        ? xhr.responseJSON.data.message
        : '';

      modalBody().html('<p>' + escapeHtml(message || strings.loadError || 'No se pudo cargar el historial.') + '</p>');
    });
  }

  function saveVariantMatrix($button) {
    var $editor = $button.closest('[data-bsc-products-variant-editor]');
    var productId = $editor.data('product-id');
    var variants = collectVariantMatrix($editor);

    if (!productId || !variants.length) {
      showToast(strings.variantsSaveError || 'No se pudieron guardar las variantes.', 'error');
      return;
    }

    $button.prop('disabled', true).text('Guardando...');
    $editor.find('input').prop('disabled', true);

    $.post(ajaxUrl, {
      action: 'bsc_save_product_variant_matrix',
      nonce: nonce,
      product_id: productId,
      variants: variants,
    }).done(function (response) {
      var $row;

      if (!response || !response.success || !response.data) {
        showToast(
          (response && response.data && response.data.message) || strings.variantsSaveError || 'No se pudieron guardar las variantes.',
          'error'
        );
        return;
      }

      $row = $('.bsc-admin-products__row[data-product-id="' + productId + '"]');
      $row.find('[data-type="bodega"]').val(response.data.bodega).data('original', response.data.bodega);
      $row.find('[data-type="tienda"]').val(response.data.tienda).data('original', response.data.tienda);
      syncRowState($row);

      modalBody().html(buildVariantMatrixForm(response.data.variants || [], productId));
      applyVariantSwatches(modalBody());
      showToast(strings.variantsSaved || 'Variantes guardadas.', 'success');
    }).fail(function (xhr) {
      var message = xhr.responseJSON && xhr.responseJSON.data
        ? xhr.responseJSON.data.message
        : '';

      showToast(message || strings.variantsSaveError || 'No se pudieron guardar las variantes.', 'error');
    }).always(function () {
      $button.prop('disabled', false).text('Guardar variantes');
      $editor.find('input').prop('disabled', false);
    });
  }

  $(document).on('input change', '.bsc-product-inline-input', function () {
    var $input = $(this);

    if (isLocationCodeInput($input)) {
      $input.val(String($input.val()).toUpperCase());
    }

    syncRowState(getRow($input));
  });

  $(document).on('click', '.bsc-product-row-save', function () {
    saveRow(getRow($(this)));
  });

  $(document).on('click', '.bsc-admin-products__status-switch', function () {
    toggleProductStatus($(this));
  });

  $(document).on('click', '#bsc-discount-mode-toggle', function () {
    setDiscountMode(true);
  });

  $(document).on('click', '#bsc-discount-mode-cancel', function () {
    setDiscountMode(false);
  });

  $(document).on('change', '#bsc-discount-select-all', function () {
    discountCheckboxes().prop('checked', $(this).prop('checked'));
    updateDiscountState();
  });

  $(document).on('change', '.bsc-product-discount-checkbox', updateDiscountState);
  $(document).on('input change', '#bsc-discount-percent', updateDiscountState);

  $(document).on('click', '#bsc-apply-discount', applyDiscountToSelection);

  $(document).on('click', '.bsc-product-delete-btn', function () {
    trashProduct($(this));
  });

  $(document).on('click', '.bsc-product-variants-btn', function () {
    openVariantMatrix($(this));
  });

  $(document).on('click', '[data-bsc-products-variant-save]', function () {
    saveVariantMatrix($(this));
  });

  $(document).on('change', '[data-field="enabled"]', function () {
    $(this).closest('[data-bsc-products-variant-row]').toggleClass('is-disabled', !$(this).is(':checked'));
  });

  $(document).on('click', '.bsc-stock-history-btn', function () {
    var productId = $(this).data('product-id');
    var productName = $(this).data('product-name');

    $('#bsc-stock-modal-title').text((strings.historyTitlePrefix || 'Historial: ') + productName);
    modalBody().html('<p>' + (strings.loading || 'Cargando...') + '</p>');
    openModal();

    $.get(ajaxUrl, {
      action: 'bsc_get_stock_log',
      nonce: nonce,
      product_id: productId,
    }).done(function (response) {
      if (!response.success || !response.data.log.length) {
        modalBody().html('<p>' + (strings.emptyLog || 'Sin movimientos registrados.') + '</p>');
        return;
      }

      modalBody().html(buildHistoryTable(response.data.log));
    }).fail(function () {
      modalBody().html('<p>' + (strings.loadError || 'No se pudo cargar el historial.') + '</p>');
    });
  });

  $(document).on('click', '#bsc-stock-modal-close, #bsc-stock-modal-overlay', closeModal);

  $('.bsc-admin-products__row').each(function () {
    syncRowState($(this));
  });

  updateDiscountState();
}(jQuery));
