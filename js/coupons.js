jQuery(function ($) {
  const selectors = {
    input: '#bsc__coupon-input',
    applyBtn: '#apply_coupon',
    couponList: '#applied_coupons_list',
    section: '.applied-coupons',
    subtotal: '#review__summary--subtotal',
    discounted: '#review__summary--subtotal-discounted',
    shipping: '#review__summary--shipping',
    total: '#review__summary--total'
  };

  const $input = $(selectors.input);
  const $applyBtn = $(selectors.applyBtn);
  const $couponList = $(selectors.couponList);
  const $couponSection = $(selectors.section);

  const ajaxPost = (action, data = {}, callback) => {
    $.post(bsc_ajax.ajax_url, { action, ...data }, callback, 'json');
  };

  const alertIfError = (response, fallback = 'Error desconocido.') => {
    if (!response.success) alert(response.data?.message || fallback);
    return response.success;
  };

  const updateTotals = (data) => {
    if (!data) return;

    const { subtotal, subtotal_after_discounted, shipping_total, cart_total } = data;

    if (subtotal) $(selectors.subtotal).html(subtotal);
    if (subtotal_after_discounted) $(selectors.discounted).html(subtotal_after_discounted);
    if (shipping_total) $(selectors.shipping).html(shipping_total);
    if (cart_total) $(selectors.total).html(`<strong>${cart_total}</strong>`);
  };

  const renderCouponItem = (coupon) => {
    const iconName = coupon.code + '.png';
    const iconPath = `${window.location.origin}/wp-content/themes/wp-bsc-theme-v2/images/bsc_image_coupons.png`;

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
    if (!code) return alert('Por favor, ingresa un código de cupón.');

    ajaxPost('apply_coupon', { coupon_code: code }, (res) => {
      if (!alertIfError(res, 'Error al aplicar el cupón.')) return;
      alert('¡Cupón aplicado exitosamente!');
      updateTotals(res.data);
      fetchCoupons();
    });
  };

  const removeCoupon = (code) => {
    ajaxPost('remove_coupon', { coupon_code: code }, (res) => {
      if (!alertIfError(res, 'No se pudo eliminar el cupón.')) return;
      alert('Cupón eliminado con éxito.');
      updateTotals(res.data);
      fetchCoupons();
    });
  };

  const fetchCoupons = () => {
    ajaxPost('get_applied_coupons', {}, (res) => {
      if (!res.success || !Array.isArray(res.data?.coupons) || res.data.coupons.length === 0) {
        $couponSection.hide();
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

  // Bind UI events
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

  // Init
  fetchCoupons();

  //bindRemoveCouponEvents();


  $(document).on('click', '.remove-coupon', function () {
      const code = $(this).closest('li').data('coupon');
removeCoupon(code);
      console.log(' COUPON CODE ', code);
    });
});
