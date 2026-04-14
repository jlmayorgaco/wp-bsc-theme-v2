jQuery(function ($) {
  const selectors = {
    input:       '#bsc__coupon-input',
    applyBtn:    '#apply_coupon',
    couponList:  '#applied_coupons_list',
    section:     '.applied-coupons',
    subtotal:    '#review__summary--subtotal',
    discounted:  '#review__summary--subtotal-discounted',
    shipping:    '#review__summary--shipping',
    total:       '#review__summary--total',
    noticeWrap:  '#bsc__coupon-notice',
  };

  const $input         = $(selectors.input);
  const $applyBtn      = $(selectors.applyBtn);
  const $couponList    = $(selectors.couponList);
  const $couponSection = $(selectors.section);

  let noticeTimer = null;

  function hideNotice() {
    $(selectors.noticeWrap).removeClass('is-visible');
  }

  function ensureNotice() {
    let $notice = $(selectors.noticeWrap);

    if (!$notice.length) {
      $notice = $(`
        <div id="bsc__coupon-notice" class="bsc__coupon-notice" role="status" aria-live="polite" aria-atomic="true">
          <div class="bsc__coupon-notice-content">
            <strong class="bsc__coupon-notice-title"></strong>
            <span class="bsc__coupon-notice-message"></span>
          </div>
          <button type="button" class="bsc__coupon-notice-close" aria-label="Cerrar mensaje">Cerrar</button>
        </div>
      `);

      $('body').append($notice);
      $notice.find('.bsc__coupon-notice-close').on('click', hideNotice);
    }

    return $notice;
  }

  function showNotice(message, type = 'info') {
    const labels = {
      success: 'Cupón aplicado',
      error: 'Revisa el cupón',
      warning: 'Falta el cupón',
      info: 'Cupón',
    };
    const safeType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    const $notice = ensureNotice();

    $notice
      .attr('class', 'bsc__coupon-notice bsc__coupon-notice--' + safeType)
      .find('.bsc__coupon-notice-title')
      .text(labels[safeType]);

    $notice.find('.bsc__coupon-notice-message').text(message);

    window.requestAnimationFrame(() => {
      $notice.addClass('is-visible');
    });

    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(hideNotice, 4500);
  }

  function getCouponIconPath() {
    const themeUri = (window.bsc_ajax && window.bsc_ajax.theme_uri)
      ? window.bsc_ajax.theme_uri
      : window.location.origin + '/wp-content/themes/wp-bsc-theme-v2';
    return themeUri + '/images/bsc_image_coupons.png';
  }

  const ajaxPost = (action, data = {}, callback) => (
    $.post(bsc_ajax.ajax_url, { action, nonce: bsc_ajax.nonce, ...data }, callback, 'json')
  );

  const updateTotals = (data) => {
    if (!data) return;
    const { subtotal, subtotal_after_discounted, shipping_total, cart_total } = data;
    if (subtotal)                  $(selectors.subtotal).html(subtotal);
    if (subtotal_after_discounted) $(selectors.discounted).html(subtotal_after_discounted);
    if (shipping_total)            $(selectors.shipping).html(shipping_total);
    if (cart_total)                $(selectors.total).html(`<strong>${cart_total}</strong>`);
  };

  const renderCouponItem = (coupon) => {
    const iconPath = getCouponIconPath();
    return `
      <li class="applied-coupon-item" data-coupon="${coupon.code}">
        <img src="${iconPath}" alt="Cupón" class="coupon-icon" />
        <strong class="coupon-code">${coupon.code}</strong>
        <label class="remove-coupon"><span>X</span></label>
      </li>
    `;
  };

  const applyCoupon = () => {
    const code = $input.val().trim();
    if (!code) {
      showNotice('Ingresa un código de cupón para aplicarlo.', 'warning');
      return;
    }

    $applyBtn.addClass('bsc-loading').prop('disabled', true); // BSC-019

    ajaxPost('apply_coupon', { coupon_code: code }, (res) => {
      $applyBtn.removeClass('bsc-loading').prop('disabled', false); // BSC-019

      if (!res.success) {
        showNotice(res.data?.message || 'No pudimos aplicar este cupón.', res.data?.status || 'error');
        return;
      }

      showNotice(res.data?.message || 'Cupón agregado exitosamente.', res.data?.status || 'success');
      $input.val('');
      updateTotals(res.data);
      fetchCoupons();
    }).fail(() => {
      $applyBtn.removeClass('bsc-loading').prop('disabled', false); // BSC-019
      showNotice('Hubo un problema al validar el cupón. Inténtalo de nuevo.', 'error');
    });
  };

  const removeCoupon = (code) => {
    ajaxPost('remove_coupon', { coupon_code: code }, (res) => {
      if (!res.success) {
        showNotice(res.data?.message || 'No se pudo eliminar el cupón.', res.data?.status || 'error');
        return;
      }

      showNotice(res.data?.message || 'Cupón eliminado.', res.data?.status || 'success');
      updateTotals(res.data);
      fetchCoupons();
    }).fail(() => {
      showNotice('Hubo un problema al eliminar el cupón. Inténtalo de nuevo.', 'error');
    });
  };

  const fetchCoupons = () => {
    ajaxPost('get_applied_coupons', {}, (res) => {
      if (!res.success || !Array.isArray(res.data?.coupons) || res.data.coupons.length === 0) {
        $couponSection.hide();
        $couponList.empty();
        return;
      }

      $couponList.empty();
      res.data.coupons.forEach((coupon) => {
        $couponList.append(renderCouponItem(coupon));
      });

      $couponSection.show();
      $('body').trigger('update_checkout');
    });
  };

  $input.on('keypress', (e) => {
    if (e.which === 13) {
      e.preventDefault();
      applyCoupon();
    }
  });

  $applyBtn.on('click', (e) => {
    e.preventDefault();
    applyCoupon();
  });

  $(document).on('click', '.remove-coupon', function () {
    const code = $(this).closest('li').data('coupon');
    if (code) removeCoupon(code);
  });

  fetchCoupons();
});
