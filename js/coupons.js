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

  const $input        = $(selectors.input);
  const $applyBtn     = $(selectors.applyBtn);
  const $couponList   = $(selectors.couponList);
  const $couponSection = $(selectors.section);

  // ── Inline notice (replaces native alert()) ──────────────────────────────
  let noticeTimer = null;

  function showNotice(message, type) {

    alert(message);
    
    let $notice = $(selectors.noticeWrap);

    if (!$notice.length) {
      $notice = $('<div id="bsc__coupon-notice"></div>');
      $input.closest('form, .coupon-form, .bsc__coupon-form').append($notice);
      if (!$notice.closest('form, .coupon-form, .bsc__coupon-form').length) {
        $input.after($notice);
      }
    }

    $notice
      .attr('class', 'bsc__coupon-notice bsc__coupon-notice--' + type)
      .text(message)
      .stop(true).fadeIn(200);

    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(() => $notice.fadeOut(400), 4000);
  }

  // ── Dynamic icon path (uses theme_uri localized from PHP) ────────────────
  function getCouponIconPath() {
    const themeUri = (window.bsc_ajax && window.bsc_ajax.theme_uri)
      ? window.bsc_ajax.theme_uri
      : window.location.origin + '/wp-content/themes/wp-bsc-theme-v2';
    return themeUri + '/images/bsc_image_coupons.png';
  }

  // ── Helpers ──────────────────────────────────────────────────────────────
  const ajaxPost = (action, data = {}, callback) => {
    $.post(bsc_ajax.ajax_url, { action, nonce: bsc_ajax.nonce, ...data }, callback, 'json');
  };

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

  // ── Apply coupon ─────────────────────────────────────────────────────────
  const applyCoupon = () => {
    const code = $input.val().trim();
    if (!code) {
      showNotice('Por favor, ingresa un código de cupón.', 'error');
      return;
    }

    $applyBtn.addClass('bsc-loading').prop('disabled', true); // BSC-019

    ajaxPost('apply_coupon', { coupon_code: code }, (res) => {
      $applyBtn.removeClass('bsc-loading').prop('disabled', false); // BSC-019
      if (!res.success) {
        showNotice(res.data?.message || 'Error al aplicar el cupón.', 'error');
        return;
      }
      showNotice('¡Cupón aplicado exitosamente!', 'success');
      $input.val('');
      updateTotals(res.data);
      fetchCoupons();
    });
  };

  // ── Remove coupon ────────────────────────────────────────────────────────
  const removeCoupon = (code) => {
    ajaxPost('remove_coupon', { coupon_code: code }, (res) => {
      if (!res.success) {
        showNotice(res.data?.message || 'No se pudo eliminar el cupón.', 'error');
        return;
      }
      showNotice('Cupón eliminado.', 'success');
      updateTotals(res.data);
      fetchCoupons();
    });
  };

  // ── Fetch and render applied coupons ─────────────────────────────────────
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

  // ── Event bindings ───────────────────────────────────────────────────────
  $input.on('keypress', (e) => {
    if (e.which === 13) { e.preventDefault(); applyCoupon(); }
  });

  $applyBtn.on('click', (e) => { e.preventDefault(); applyCoupon(); });

  $(document).on('click', '.remove-coupon', function () {
    const code = $(this).closest('li').data('coupon');
    if (code) removeCoupon(code);
  });

  // ── Init ─────────────────────────────────────────────────────────────────
  fetchCoupons();
});
