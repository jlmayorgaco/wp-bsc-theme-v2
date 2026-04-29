(function ($) {
  'use strict';

  var config = window.bscProductsAdmin || {};
  var ajaxUrl = config.ajaxUrl || window.ajaxurl || '';
  var nonce = config.nonce || '';
  var strings = config.strings || {};

  function modal() {
    return $('#bsc-stock-modal');
  }

  function modalBody() {
    return $('#bsc-stock-modal-body');
  }

  function openModal() {
    modal().addClass('is-open');
  }

  function closeModal() {
    modal().removeClass('is-open');
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
      + '<th>Fecha</th><th>Tipo</th><th>Δ</th><th>Antes→Después</th><th>Usuario</th><th>Razón</th>'
      + '</tr></thead><tbody>' + bodyRows + '</tbody></table>';
  }

  $(document).on('change blur', '.bsc-stock-input', function () {
    var $input = $(this);
    var productId = $input.data('product-id');
    var type = $input.data('type');
    var value = parseInt($input.val(), 10);

    if (isNaN(value) || value < 0) {
      $input.val($input.data('original'));
      return;
    }

    $input.prop('disabled', true);

    $.post(ajaxUrl, {
      action: 'bsc_update_product_stock',
      nonce: nonce,
      product_id: productId,
      type: type,
      value: value,
    }).done(function (response) {
      if (!response.success) {
        $input.val($input.data('original'));
        return;
      }

      $input.data('original', response.data.new_value);
      $input.addClass('is-saved');
      window.setTimeout(function () {
        $input.removeClass('is-saved');
      }, 1200);
    }).fail(function () {
      $input.val($input.data('original'));
    }).always(function () {
      $input.prop('disabled', false);
    });
  });

  $(document).on('click', '.bsc-stock-history-btn', function () {
    var productId = $(this).data('product-id');
    var productName = $(this).data('product-name');

    $('#bsc-stock-modal-title').text((strings.historyTitlePrefix || 'Historial: ') + productName);
    modalBody().html('<p>' + (strings.loading || 'Cargando…') + '</p>');
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
}(jQuery));
