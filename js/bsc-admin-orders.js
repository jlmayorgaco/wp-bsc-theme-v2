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

    $('#bsc-bulk-msg').text(strings.selectFirst || 'Selecciona al menos un pedido primero.');
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

  function isCompleteTrackingUrl(value) {
    var url = $.trim(value || '');

    if (!url) {
      return false;
    }

    try {
      var parsedUrl = new window.URL(url);

      return !!parsedUrl.host && (parsedUrl.protocol === 'https:' || parsedUrl.protocol === 'http:');
    } catch (error) {
      return false;
    }
  }

  function updateStatusBadge($row, status) {
    var $badge = $row.find('.bsc-order-badge').first();
    var cleanStatus = String(status || '').replace(/^wc-/, '');
    var labelMap = {
      pending: 'Recibido',
      'on-hold': 'Recibido',
      processing: 'Recibido',
      preparing: 'Recibido',
      shipped: 'Enviado',
      completed: 'Enviado',
      cancelled: 'Cancelado',
      failed: 'Cancelado',
      refunded: 'Cancelado',
      'bsc-archived': 'Archivado',
    };

    if (!$badge.length || !labelMap[cleanStatus]) {
      return;
    }

    $badge
      .removeClass(
        'bsc-order-badge--pending bsc-order-badge--on-hold bsc-order-badge--processing bsc-order-badge--preparing bsc-order-badge--shipped bsc-order-badge--completed bsc-order-badge--cancelled bsc-order-badge--failed bsc-order-badge--refunded'
        + ' bsc-order-badge--bsc-archived'
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

  function updateSelectionSummary() {
    var selectedCount = $('input[name="order_ids[]"]:checked').length;
    var label = selectedCount + (selectedCount === 1 ? ' pedido seleccionado' : ' pedidos seleccionados');

    $('#bsc-orders-selection-count').text(label);
    $('.bsc-orders-bulk-actions').toggleClass('is-active', selectedCount > 0);
  }

  function getCurrentStatusView() {
    return new window.URLSearchParams(window.location.search).get('order_status') || '';
  }

  function rowBelongsInCurrentView(statusKey) {
    var currentView = getCurrentStatusView();

    if (!currentView) {
      return statusKey !== 'bsc-archived';
    }

    if (currentView === 'bsc-archived') {
      return statusKey === 'bsc-archived';
    }

    return currentView === statusKey;
  }

  function removeRowIfOutsideCurrentView($row, statusKey) {
    if (rowBelongsInCurrentView(statusKey)) {
      return;
    }

    $row.fadeOut(180, function () {
      $(this).remove();
      updateSelectionSummary();
    });
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

        var newStatusKey = response.data.status_key || ('wc-' + response.data.status);

        $select.attr('data-original-status', newStatusKey);
        updateStatusPendingState($select);
        updateStatusBadge($select.closest('tr'), newStatusKey);
        removeRowIfOutsideCurrentView($select.closest('tr'), newStatusKey);
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
    var isCodeInput = $input.hasClass('bsc-tracking-code');

    if (!trackingCode && !trackingLink) {
      return;
    }

    if (trackingCode && !trackingLink && isCodeInput) {
      return;
    }

    if (trackingLink && !isCompleteTrackingUrl(trackingLink)) {
      alert(strings.trackingUrlInvalid || 'La URL de seguimiento debe empezar por https:// o http://.');
      return;
    }

    if (trackingCode && !trackingLink) {
      alert(strings.trackingUrlRequired || 'Ingresa la URL completa de seguimiento antes de guardar la guia.');
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

        if (response.data && response.data.tracking_link) {
          $cell.find('.bsc-tracking-link').val(response.data.tracking_link);
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
    updateSelectionSummary();
  });

  $(document).on('change', 'input[name="order_ids[]"]', updateSelectionSummary);

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

  $(document).on('click', '#bsc-bulk-status-btn', function (event) {
    var $status = $('#bsc-bulk-status');

    if (!requireSelection(event)) {
      return;
    }

    if (!$status.val()) {
      if (event) {
        event.preventDefault();
      }

      $('#bsc-bulk-msg').text(strings.selectStatus || 'Selecciona el estado que quieres aplicar.');
      showSaved($('#bsc-bulk-msg'));
      return;
    }

    if (!window.confirm(strings.bulkStatusConfirm || 'Vas a cambiar el estado de los pedidos seleccionados. ¿Continuar?')) {
      event.preventDefault();
      return;
    }

    $('#bsc-orders-form').removeAttr('target');
  });

  $('#bsc-bulk-msg').text(strings.selectFirst || 'Selecciona al menos un pedido primero.');

  $('.bsc-status-select').each(function () {
    updateStatusPendingState($(this));
  });

  updateSelectionSummary();
})(jQuery);
