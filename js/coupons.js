jQuery(function ($) {
  const selectors = {
    input: '#bsc__coupon-input',
    applyBtn: '#apply_coupon',
    couponList: '#applied_coupons_list',
    section: '.applied-coupons',
    subtotal: '#review-summary__subtotal',
    discounted: '#review-summary__subtotal-discounted',
    shipping: '#review-summary__shipping',
    total: '#review-summary__total',
    cartCount: '#review-summary__cart-count',
    noticeWrap: '#bsc__coupon-notice',
  };

  const $input = $(selectors.input);
  const $applyBtn = $(selectors.applyBtn);

  if (!$input.length || !$applyBtn.length) {
    return;
  }

  let noticeTimer = null;
  let couponsRequest = null;
  let couponMutationId = 0;

  function getCouponList() {
    return $(selectors.couponList).first();
  }

  function getCouponSection() {
    return $(selectors.section).first();
  }

  function hideNotice() {
    $(selectors.noticeWrap).removeClass('is-visible');
  }

  function ensureNotice() {
    let $notice = $(selectors.noticeWrap);

    if (!$notice.length) {
      $notice = $(
        '<div id="bsc__coupon-notice" class="bsc__coupon-notice" role="status" aria-live="polite" aria-atomic="true">' +
          '<div class="bsc__coupon-notice-content">' +
            '<strong class="bsc__coupon-notice-title"></strong>' +
            '<span class="bsc__coupon-notice-message"></span>' +
          '</div>' +
          '<button type="button" class="bsc__coupon-notice-close" aria-label="Cerrar mensaje">Cerrar</button>' +
        '</div>'
      );

      $('body').append($notice);
      $notice.find('.bsc__coupon-notice-close').on('click', hideNotice);
    }

    return $notice;
  }

  function showNotice(message, type = 'info') {
    const labels = {
      success: 'Cup\u00f3n aplicado',
      error: 'Revisa el cup\u00f3n',
      warning: 'Falta el cup\u00f3n',
      info: 'Cup\u00f3n',
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
    const themeUri = window.bsc_ajax && window.bsc_ajax.theme_uri
      ? window.bsc_ajax.theme_uri
      : window.location.origin + '/wp-content/themes/wp-bsc-theme-v2';

    return themeUri + '/images/bsc_image_coupons.png';
  }

  function ajaxPost(action, data = {}, callback) {
    return $.post(bsc_ajax.ajax_url, { action, nonce: bsc_ajax.nonce, ...data }, callback, 'json');
  }

  function updateTotals(data) {
    if (!data) {
      return;
    }

    const hasOwn = (key) => Object.prototype.hasOwnProperty.call(data, key);
    const { subtotal, subtotal_after_discounted, shipping_total, cart_total, cart_count } = data;

    if (hasOwn('subtotal') && $(selectors.subtotal).length) {
      $(selectors.subtotal).html(subtotal);
    }

    if (hasOwn('subtotal_after_discounted') && $(selectors.discounted).length) {
      $(selectors.discounted).html(subtotal_after_discounted);
    }

    if (hasOwn('shipping_total') && $(selectors.shipping).length) {
      $(selectors.shipping).html(shipping_total);
    }

    if (hasOwn('cart_total') && $(selectors.total).length) {
      $(selectors.total).html('<strong>' + cart_total + '</strong>');
    }

    if (hasOwn('cart_count') && $(selectors.cartCount).length) {
      $(selectors.cartCount).text(cart_count);
    }
  }

  function syncCheckoutState(payload = null) {
    updateTotals(payload);

    if ($('form[name="checkout"]').length) {
      $('body').trigger('update_checkout');
    }

    if (typeof window.refreshReviewSummary === 'function') {
      return window.refreshReviewSummary();
    }

    return null;
  }

  function renderCouponItem(coupon) {
    const iconPath = getCouponIconPath();

    return (
      '<li class="applied-coupon-item" data-coupon="' + coupon.code + '">' +
        '<img src="' + iconPath + '" alt="Cup\u00f3n" class="coupon-icon" />' +
        '<strong class="coupon-code">' + coupon.code + '</strong>' +
        '<button type="button" class="remove-coupon bsc__coupon-remove" aria-label="Eliminar cup\u00f3n ' + coupon.code + '"><span aria-hidden="true">X</span></button>' +
      '</li>'
    );
  }

  function fetchCoupons(mutationId = null) {
    if (couponsRequest && couponsRequest.readyState !== 4) {
      couponsRequest.abort();
    }

    couponsRequest = ajaxPost('get_applied_coupons', {}, (res) => {
      if (mutationId !== null && mutationId !== couponMutationId) {
        return;
      }

      const $couponSection = getCouponSection();
      const $couponList = getCouponList();

      if (!res.success || !Array.isArray(res.data?.coupons) || res.data.coupons.length === 0) {
        $couponSection.removeClass('is-visible').hide();
        $couponList.empty();
        return;
      }

      $couponList.empty();
      res.data.coupons.forEach((coupon) => {
        $couponList.append(renderCouponItem(coupon));
      });

      $couponSection.addClass('is-visible').show();
    }).always(() => {
      couponsRequest = null;
    });
  }

  function applyCoupon() {
    const code = $input.val().trim();

    if (!code) {
      showNotice('Ingresa un c\u00f3digo de cup\u00f3n para aplicarlo.', 'warning');
      return;
    }

    $applyBtn.addClass('bsc-loading').prop('disabled', true);

    ajaxPost('apply_coupon', { coupon_code: code }, (res) => {
      $applyBtn.removeClass('bsc-loading').prop('disabled', false);

      if (!res.success) {
        showNotice(res.data?.message || 'No pudimos aplicar este cup\u00f3n.', res.data?.status || 'error');
        return;
      }

      showNotice(res.data?.message || 'Cup\u00f3n agregado exitosamente.', res.data?.status || 'success');
      $input.val('');
      couponMutationId += 1;
      syncCheckoutState(res.data);
      fetchCoupons(couponMutationId);
    }).fail(() => {
      $applyBtn.removeClass('bsc-loading').prop('disabled', false);
      showNotice('Hubo un problema al validar el cup\u00f3n. Int\u00e9ntalo de nuevo.', 'error');
    });
  }

  function removeCoupon(code) {
    ajaxPost('remove_coupon', { coupon_code: code }, (res) => {
      if (!res.success) {
        showNotice(res.data?.message || 'No se pudo eliminar el cup\u00f3n.', res.data?.status || 'error');
        return;
      }

      const $couponSection = getCouponSection();
      const $couponList = getCouponList();
      const normalizedCode = String(code).toLowerCase();
      $couponList
        .find('.applied-coupon-item')
        .filter(function () {
          return String($(this).data('coupon')).toLowerCase() === normalizedCode;
        })
        .remove();

      if (!$couponList.children().length) {
        $couponSection.removeClass('is-visible').hide();
      }

      showNotice(res.data?.message || 'Cup\u00f3n eliminado.', res.data?.status || 'success');
      couponMutationId += 1;
      syncCheckoutState(res.data);
      fetchCoupons(couponMutationId);
    }).fail(() => {
      showNotice('Hubo un problema al eliminar el cup\u00f3n. Int\u00e9ntalo de nuevo.', 'error');
    });
  }

  $input.on('keypress', (event) => {
    if (event.which === 13) {
      event.preventDefault();
      applyCoupon();
    }
  });

  $applyBtn.on('click', (event) => {
    event.preventDefault();
    applyCoupon();
  });

  $(document).on('click', '.remove-coupon', function () {
    const code = $(this).closest('li').data('coupon');
    if (code) {
      removeCoupon(code);
    }
  });

  fetchCoupons();
});
