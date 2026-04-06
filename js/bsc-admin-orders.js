/**
 * BSC-031: Inline status update and tracking save for BSC Admin Orders table.
 */
(function ($) {
  'use strict';

  var ajax_url = bscOrders.ajax_url;
  var nonce    = bscOrders.nonce;

  // Show ✓ indicator next to the target element, auto-hide after 2 s
  function showSaved($el) {
    var $indicator = $el.closest('td').find('.bsc-saved-indicator');
    $indicator.stop(true).fadeIn(150, function () {
      setTimeout(function () { $indicator.fadeOut(400); }, 2000);
    });
  }

  // ── Inline status update ──────────────────────────────────────────
  $(document).on('change', '.bsc-status-select', function () {
    var $select  = $(this);
    var order_id = $select.data('order-id');
    var status   = $select.val();

    $select.prop('disabled', true);

    $.post(ajax_url, {
      action:   'bsc_update_order_status',
      nonce:    nonce,
      order_id: order_id,
      status:   status,
    })
    .done(function (res) {
      if (res.success) {
        showSaved($select);
        // Update row class to reflect new status
        $select.closest('tr').attr('class', 'status-' + res.data.status);
      } else {
        alert('Error al actualizar estado: ' + (res.data.message || 'desconocido'));
        // Revert is not trivial without knowing previous — rely on page refresh
      }
    })
    .fail(function () {
      alert('Error de conexión al actualizar estado.');
    })
    .always(function () {
      $select.prop('disabled', false);
    });
  });

  // ── Inline tracking save (on blur of either tracking input) ──────
  $(document).on('blur', '.bsc-tracking-code, .bsc-tracking-link', function () {
    var $input   = $(this);
    var $row     = $input.closest('td');
    var order_id = $input.data('order-id');

    var tracking_code = $row.find('.bsc-tracking-code').val().trim();
    var tracking_link = $row.find('.bsc-tracking-link').val().trim();

    // Only save if at least a code is present
    if (!tracking_code && !tracking_link) return;

    $row.find('.bsc-tracking-code, .bsc-tracking-link').prop('disabled', true);

    $.post(ajax_url, {
      action:        'bsc_save_tracking',
      nonce:         nonce,
      order_id:      order_id,
      tracking_code: tracking_code,
      tracking_link: tracking_link,
    })
    .done(function (res) {
      if (res.success) {
        showSaved($input);
        // If status changed to shipped, reflect in the status dropdown
        if (tracking_code) {
          var $statusSelect = $input.closest('tr').find('.bsc-status-select');
          $statusSelect.find('option[value="wc-shipped"]').prop('selected', true);
        }
      } else {
        alert('Error al guardar tracking: ' + (res.data.message || 'desconocido'));
      }
    })
    .fail(function () {
      alert('Error de conexión al guardar tracking.');
    })
    .always(function () {
      $row.find('.bsc-tracking-code, .bsc-tracking-link').prop('disabled', false);
    });
  });

  // ── Select all checkbox ───────────────────────────────────────────
  $(document).on('change', '#cb-select-all-1, #cb-select-all-2', function () {
    var checked = $(this).prop('checked');
    $('input[name="order_ids[]"]').prop('checked', checked);
    $('#cb-select-all-1, #cb-select-all-2').prop('checked', checked);
  });

})(jQuery);
