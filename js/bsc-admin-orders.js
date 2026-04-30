/**
 * BSC Admin Orders
 * - explicit status save per row
 * - tracking persistence
 * - guarded bulk actions
 */
(function ($) {
  'use strict';

  var ajaxUrl = bscOrders.ajax_url;
  var nonce = bscOrders.nonce;
  var strings = bscOrders.strings || {};

  function showSaved($indicator) {
    $indicator.stop(true, true).fadeIn(150, function () {
      window.setTimeout(function () {
        $indicator.fadeOut(400);
      }, 2000);
    });
  }

  function requireSelection(event) {
    if ($('input[name="order_ids[]"]:checked').length > 0) {
      return true;
    }

    if (event) {
      event.preventDefault();
    }

    showSaved($('#bsc-bulk-msg'));
    return false;
  }

  function ensureToast() {
    var $toast = $('#bsc-admin-orders-toast');

    if ($toast.length) {
      return $toast;
    }

    $toast = $('<div id="bsc-admin-orders-toast" class="bsc-admin-orders-toast" role="status" aria-live="polite"></div>');
    $('body').append($toast);

    return $toast;
  }

  function showToast(message) {
    var $toast = ensureToast();

    $toast.text(message).addClass('is-visible');

    window.clearTimeout($toast.data('toast-timeout'));

    $toast.data(
      'toast-timeout',
      window.setTimeout(function () {
        $toast.removeClass('is-visible');
      }, 2200)
    );
  }

  function updateStatusBadge($row, status) {
    var $badge = $row.find('.bsc-order-badge').first();
    var cleanStatus = String(status || '').replace(/^wc-/, '');
    var labelMap = {
      processing: 'Recibido',
      completed: 'Terminado',
    };

    if (!$badge.length || !labelMap[cleanStatus]) {
      return;
    }

    $badge
      .removeClass(
        'bsc-order-badge--pending bsc-order-badge--on-hold bsc-order-badge--processing bsc-order-badge--preparing bsc-order-badge--shipped bsc-order-badge--completed bsc-order-badge--cancelled bsc-order-badge--failed bsc-order-badge--refunded'
      )
      .addClass('bsc-order-badge--' + cleanStatus)
      .text(labelMap[cleanStatus]);
  }

  function updateStatusPendingState($select) {
    var currentValue = String($select.val() || '');
    var originalValue = String($select.attr('data-original-status') || '');
    var isPending = currentValue !== originalValue;
    var $control = $select.closest('.bsc-status-control');
    var $button = $control.find('.bsc-status-save').first();
    var $badge = $control.find('.bsc-status-pending').first();

    $select.toggleClass('is-pending', isPending);
    $control.toggleClass('is-pending', isPending);
    $button.prop('disabled', !isPending);
    $badge.toggleClass('is-visible', isPending);
  }

  function withNewTabTarget($form, callback) {
    $form.attr('target', '_blank');
    callback();

    window.setTimeout(function () {
      $form.removeAttr('target');
    }, 300);
  }

  $(document).on('change', '.bsc-status-select', function () {
    updateStatusPendingState($(this));
  });

  $(document).on('click', '.bsc-status-save', function () {
    var $button = $(this);
    var $control = $button.closest('.bsc-status-control');
    var $select = $control.find('.bsc-status-select').first();
    var orderId = $select.data('order-id');
    var status = $select.val();
    var originalLabel = strings.save || 'Guardar';

    if ($button.prop('disabled')) {
      return;
    }

    $select.prop('disabled', true);
    $button.prop('disabled', true).text(strings.saving || 'Guardando...');

    $.post(ajaxUrl, {
      action: 'bsc_update_order_status',
      nonce: nonce,
      order_id: orderId,
      status: status,
    })
      .done(function (response) {
        if (!response.success) {
          alert((strings.saveError || 'Error al actualizar estado') + ': ' + ((response.data && response.data.message) || 'desconocido'));
          return;
        }

        $select.attr('data-original-status', 'wc-' + response.data.status);
        updateStatusPendingState($select);
        updateStatusBadge($select.closest('tr'), response.data.status);
        showToast(strings.saved || 'Elemento guardado');
      })
      .fail(function () {
        alert(strings.connectionError || 'Error de conexion. Intenta de nuevo.');
      })
      .always(function () {
        $select.prop('disabled', false);
        $button.text(originalLabel);
        updateStatusPendingState($select);
      });
  });

  $(document).on('blur', '.bsc-tracking-code, .bsc-tracking-link', function () {
    var $input = $(this);
    var $cell = $input.closest('td');
    var orderId = $input.data('order-id');
    var trackingCode = $.trim($cell.find('.bsc-tracking-code').val());
    var trackingLink = $.trim($cell.find('.bsc-tracking-link').val());
    var $indicator = $cell.find('.bsc-saved-indicator').first();

    if (!trackingCode && !trackingLink) {
      return;
    }

    $cell.find('.bsc-tracking-code, .bsc-tracking-link').prop('disabled', true);

    $.post(ajaxUrl, {
      action: 'bsc_save_tracking',
      nonce: nonce,
      order_id: orderId,
      tracking_code: trackingCode,
      tracking_link: trackingLink,
    })
      .done(function (response) {
        if (!response.success) {
          alert((strings.trackingError || 'Error al guardar tracking') + ': ' + ((response.data && response.data.message) || 'desconocido'));
          return;
        }

        showSaved($indicator);
      })
      .fail(function () {
        alert(strings.connectionError || 'Error de conexion. Intenta de nuevo.');
      })
      .always(function () {
        $cell.find('.bsc-tracking-code, .bsc-tracking-link').prop('disabled', false);
      });
  });

  $(document).on('change', '#cb-select-all-1, #cb-select-all-2', function () {
    var checked = $(this).prop('checked');
    $('input[name="order_ids[]"]').prop('checked', checked);
    $('#cb-select-all-1, #cb-select-all-2').prop('checked', checked);
  });

  $(document).on('click', '#bsc-packing-btn', function (event) {
    var $form = $('#bsc-orders-form');

    if (!requireSelection(event)) {
      return;
    }

    withNewTabTarget($form, function () {});
  });

  $(document).on('click', '#bsc-labels-btn', function (event) {
    var $form = $('#bsc-orders-form');

    if (!requireSelection(event)) {
      return;
    }

    withNewTabTarget($form, function () {});
  });

  $(document).on('click', '#bsc-csv-btn', function (event) {
    if (!requireSelection(event)) {
      return;
    }

    $('#bsc-orders-form').removeAttr('target');
  });

  $('#bsc-bulk-msg').text(strings.selectFirst || 'Selecciona al menos un pedido primero.');

  $('.bsc-status-select').each(function () {
    updateStatusPendingState($(this));
  });
})(jQuery);
