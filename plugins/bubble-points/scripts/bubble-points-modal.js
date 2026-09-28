jQuery(function ($) {
  let selected = null;
  let busy = false;
  let opener = null;
  const $modal = $('#bscCouponModal');
  const $confirm = $('#bscModalConfirm');
  const $cancel = $('#bscModalCancel');

  function closeModal() {
    if (busy) return;
    $modal.removeClass('is-open').attr('aria-hidden', 'true');
    selected = null;
    opener?.focus();
  }

  $('.js-bsc-coupon.is-normal').on('click', function () {
    if (busy) return;
    selected = { points: $(this).data('points'), value: $(this).data('value'), requestId: crypto.randomUUID() };
    opener = this.querySelector('button');
    $('#bscCouponText').text(`¿Deseas redimir ${selected.points} puntos por un cupón de $${selected.value}?`);
    $modal.addClass('is-open').attr('aria-hidden', 'false');
    $cancel.trigger('focus');
  });

  $cancel.on('click', closeModal);
  $modal.on('click', function (event) { if (event.target === this) closeModal(); });
  $modal.on('keydown', function (event) {
    if (event.key === 'Escape') { event.preventDefault(); closeModal(); }
    if (event.key === 'Tab') {
      if (busy) { event.preventDefault(); return; }
      if (event.shiftKey && document.activeElement === $cancel[0]) { event.preventDefault(); $confirm.trigger('focus'); }
      else if (!event.shiftKey && document.activeElement === $confirm[0]) { event.preventDefault(); $cancel.trigger('focus'); }
    }
  });

  $confirm.on('click', function () {
    if (!selected || busy) return;
    busy = true;
    $confirm.add($cancel).prop('disabled', true);
    $modal.attr('aria-busy', 'true');
    $.post(bsc_points.ajax_url, {
      action: 'bsc_redeem_points', points: selected.points,
      request_id: selected.requestId, nonce: bsc_points.nonce
    }).done(function (response) {
      if (response.success) {
        alert('¡Cupón creado! Revisa tus cupones disponibles.');
        location.reload();
      } else {
        $('#bscCouponText').text(response.data?.message || 'No se pudo canjear el cupón. Intenta nuevamente.');
      }
    }).fail(function (xhr) {
      $('#bscCouponText').text(xhr.responseJSON?.data?.message || 'No pudimos confirmar el resultado. Intenta nuevamente para consultar este mismo canje.');
    }).always(function () {
      busy = false;
      $confirm.add($cancel).prop('disabled', false);
      $modal.removeAttr('aria-busy');
      $confirm.trigger('focus');
    });
  });
});
