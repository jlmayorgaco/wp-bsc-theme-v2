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
        + '<td>' + entry.before + ' â†’ ' + entry.after + '</td>'
        + '<td>' + entry.username + '</td>'
        + '<td>' + entry.reason + '</td>'
        + '</tr>';
    }).join('');

    return '<table class="wp-list-table widefat bsc-admin-products__history-table">'
      + '<thead><tr>'
      + '<th>Fecha</th><th>Tipo</th><th>Î”</th><th>Antes â†’ DespuÃ©s</th><th>Usuario</th><th>RazÃ³n</th>'
      + '</tr></thead><tbody>' + bodyRows + '</tbody></table>';
  }

  function getRow($target) {
    return $target.closest('.bsc-admin-products__row');
  }

  function getInputs($row) {
    return $row.find('.bsc-stock-input');
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

  function isInputValid($input) {
    var value = parseInt($input.val(), 10);
    return !isNaN(value) && value >= 0;
  }

  function syncLowStockClass($input) {
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
      var value = parseInt($input.val(), 10);

      if (isNaN(value) || value < 0) {
        invalidInput = $input;
        return false;
      }

      payload[$input.data('type')] = value;
      return undefined;
    });

    if (invalidInput) {
      invalidInput.trigger('focus');
      showToast(strings.saveError || 'No se pudo guardar el stock.', 'error');
      return;
    }

    $saveButton.prop('disabled', true).text('Guardando...');
    $inputs.prop('disabled', true);

    $.post(ajaxUrl, payload)
      .done(function (response) {
        if (!response || !response.success || !response.data) {
          showToast(strings.saveError || 'No se pudo guardar el stock.', 'error');
          return;
        }

        $inputs.each(function () {
          var $input = $(this);
          var type = $input.data('type');
          var value = response.data[type];
          $input.val(value).data('original', value).removeClass('is-pending');
          syncLowStockClass($input);
        });

        getPendingBadge($row).removeClass('is-visible');
        showToast(strings.saved || 'Stock guardado.', 'success');
      })
      .fail(function () {
        showToast(strings.connectionError || 'Error de conexiÃ³n. Intenta de nuevo.', 'error');
      })
      .always(function () {
        $saveButton.prop('disabled', false).text('Guardar');
        $inputs.prop('disabled', false);
        syncRowState($row);
      });
  }

  $(document).on('input change', '.bsc-stock-input', function () {
    syncRowState(getRow($(this)));
  });

  $(document).on('click', '.bsc-product-stock-save', function () {
    saveRow(getRow($(this)));
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
}(jQuery));
