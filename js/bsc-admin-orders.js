/**
 * BSC Admin Orders
 * - inline status updates
 * - tracking persistence
 * - guarded bulk actions
 */
(function ($) {
  'use strict';

  var ajaxUrl = bscOrders.ajax_url;
  var nonce = bscOrders.nonce;

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

  function withNewTabTarget($form, callback) {
    $form.attr('target', '_blank');
    callback();

    window.setTimeout(function () {
      $form.removeAttr('target');
    }, 300);
  }

  $(document).on('change', '.bsc-status-select', function () {
    var $select = $(this);
    var orderId = $select.data('order-id');
    var status = $select.val();
    var $indicator = $select.siblings('.bsc-saved-indicator').first();

    $select.prop('disabled', true);

    $.post(ajaxUrl, {
      action: 'bsc_update_order_status',
      nonce: nonce,
      order_id: orderId,
      status: status,
    })
      .done(function (response) {
        if (!response.success) {
          alert('Error al actualizar estado: ' + ((response.data && response.data.message) || 'desconocido'));
          return;
        }

        showSaved($indicator);
        $select.closest('tr').attr('class', 'status-' + response.data.status);
      })
      .fail(function () {
        alert('Error de conexión al actualizar estado.');
      })
      .always(function () {
        $select.prop('disabled', false);
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
          alert('Error al guardar tracking: ' + ((response.data && response.data.message) || 'desconocido'));
          return;
        }

        showSaved($indicator);

        if (trackingCode) {
          $input
            .closest('tr')
            .find('.bsc-status-select')
            .find('option[value="wc-shipped"]')
            .prop('selected', true);
        }
      })
      .fail(function () {
        alert('Error de conexión al guardar tracking.');
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
})(jQuery);
