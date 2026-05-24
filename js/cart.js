/**
 * BSC Custom WooCommerce Cart Handler
 * Organized using SOLID, DRY, and KISS principles
 */


jQuery(function ($) {
  /**
   * Constants for DOM selectors
   */
  const SELECTORS = {
    addToCart: '.bsc__button-add-to-cart',
    quantityControls: '.bsc__quantity-controls',
    quantityValue: '.bsc__qty-value',
    plusBtn: '.bsc__qty-plus',
    minusBtn: '.bsc__qty-minus',
    deleteBtn: '.delete-btn',
    footerCount: '.footer__cart-count',
    footerCart: '.footer__shopping-cart',
    checkoutItem: '.checkout-cart__item',
  };

  function setControlBusy($control, isBusy) {
    $control.toggleClass('is-busy', isBusy);
    $control.find(SELECTORS.plusBtn + ', ' + SELECTORS.minusBtn + ', ' + SELECTORS.deleteBtn)
      .prop('disabled', isBusy);
  }

  function syncCartCount(count) {
    if (count === undefined || count === null) return;

    $(SELECTORS.footerCount).text(count);
    $(SELECTORS.footerCart).attr('aria-label', `Shopping Cart with ${count} items`);
  }

  function isCheckoutPage() {
    const currentPath = window.location.pathname.replace(/\/+$/, '');

    return $('form[name="checkout"], form.woocommerce-checkout, .bsc__page--checkout').length > 0
      || currentPath.endsWith('/checkout');
  }

  function redirectToEmptyCartFromCheckout() {
    if (!isCheckoutPage()) return false;

    const cartUrl = window.bsc_ajax?.cart_url || '/cart/';
    window.location.assign(cartUrl);

    return true;
  }

  // BSC-017: floating cart swing animation helper
  function triggerCartSwing() {
    const $cart = $(SELECTORS.footerCart);
    $cart.addClass('is-swinging')
      .one('animationend webkitAnimationEnd', function () { $(this).removeClass('is-swinging'); });
  }

  /**
   * Add to cart handler
   * BSC-005: listen to both pointerup (touch/mouse - no 300ms delay) and click
   * (keyboard Enter/Space on <button>). The data-processing guard prevents
   * double-firing when both events fire for the same interaction.
   */
  $(document).on('pointerup click', SELECTORS.addToCart, function (e) {
    e.preventDefault();

    const $btn = $(this);
    if ($btn.data('processing')) return;
    $btn.data('processing', true);
    $btn.addClass('bsc-loading'); // BSC-019: show spinner while adding

    const productId = $btn.data('product_id');
    const quantity = $btn.data('quantity') || 1;

    $.post(bsc_ajax.ajax_url, {
      action: 'bsc_add_to_cart',
      product_id: productId,
      quantity: quantity,
      nonce: bsc_ajax.nonce,
    }).done((response) => {
      $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
    }).fail((err) => {
      console.error('Add to cart failed:', err);
    }).always(() => {
      $btn.data('processing', false);
      $btn.removeClass('bsc-loading'); // BSC-019: remove spinner
    });
  });

  /**
   * After add to cart: Inject quantity controls and update cart UI
   */
  $(document.body).on('added_to_cart', function (e, fragments, hash, $btn) {
    if ($btn.siblings(SELECTORS.quantityControls).length) return;
    const safeFragments = fragments || {};

    const productId = $btn.data('product_id');
    const quantityControls = `
      <div class="bsc__quantity-controls" data-product_id="${productId}">
        <button class="bsc__qty-minus">&minus;</button>
        <span class="bsc__qty-value">1</span>
        <button class="bsc__qty-plus">+</button>
      </div>
    `;

    $btn.siblings('.added_to_cart').remove();
    $btn.parent().append(quantityControls);
    $btn.addClass('bsc__button-add-to-cart--hidden');

    // a.cart-contents is not rendered in the BSC header; replaceWith is a no-op
    // but kept for forward-compatibility if header ever adds the fragment
    if (safeFragments['a.cart-contents']) {
      $('a.cart-contents').replaceWith(safeFragments['a.cart-contents']);

      const updatedCart = $(safeFragments['a.cart-contents']);
      const rawCount = updatedCart.find('.count').text().match(/\d+/);
      const count = rawCount ? parseInt(rawCount[0], 10) : 0;

      $(SELECTORS.footerCount).text(count);
      $(SELECTORS.footerCart).attr('aria-label', `Shopping Cart with ${count} items`);
    } else {
      refreshCartFragments();
    }

    // BSC-017: swing animation (mobile floating cart button)
    triggerCartSwing();

    // BSC-MOB-002: haptic feedback on compatible devices
    navigator.vibrate?.(80);

    if (typeof refreshReviewSummary === 'function') refreshReviewSummary();
    if (typeof window.bscCaptureAbandonedCart === 'function') window.bscCaptureAbandonedCart();
  });

  /**
   * Refresh WooCommerce cart fragments and update counters
   */
  const refreshCartFragments = () => {
    $.ajax({
      url: '?wc-ajax=get_refreshed_fragments',
      method: 'GET',
      timeout: 8000, // 8 s timeout - prevents indefinite stall on slow network
    })
    .done(function (cart) {
      const rawHtml = cart?.fragments?.['a.cart-contents'];
      if (!rawHtml) {
        // BSC-004: carrito vacio - WC no devuelve fragmento, poner badge en 0
        $(SELECTORS.footerCount).text('0');
        $(SELECTORS.footerCart).attr('aria-label', 'Shopping Cart with 0 items');
        return;
      }

      // BSC-004: replace the header fragment so WC mini-cart stays in sync
      $('a.cart-contents').replaceWith(rawHtml);

      const $cartContents = $('<div>').append(rawHtml);
      const cleanCount = $cartContents.find('.count').text().replace(/\D/g, '') || '0';

      $(SELECTORS.footerCount).text(cleanCount);
      $(SELECTORS.footerCart).attr('aria-label', `Shopping Cart with ${cleanCount} items`);

      $.post(bsc_ajax.ajax_url, { action: 'bsc_get_cart_quantities', nonce: bsc_ajax.nonce })
        .done(function (res) {
          if (res.success && Array.isArray(res.data)) {
            res.data.forEach(({ key, quantity }) => {
              $(`${SELECTORS.checkoutItem}[data-item-key="${key}"]`).find('label span').text(quantity);
            });
          }
        });
    })
    .fail(function () {
      // Timeout or network error - badge already updated from POST response (BSC-004)
      console.warn('BSC: cart fragment refresh failed or timed out.');
    });
  };

  /**
   * Handle plus and minus quantity button clicks
   */
  const handleQtyChange = (e) => {
    e.preventDefault();

    const $btn = $(e.currentTarget);
    const $control = $btn.closest(SELECTORS.quantityControls + ', .bsc-checkout-cart--controls');
    const $value = $control.find(SELECTORS.quantityValue);
    const productId = $control.data('product_id');
    const cartItemKey = $control.data('item-key') || $control.closest(SELECTORS.checkoutItem).data('item-key') || '';

    if ($control.data('processing')) return;

    let current = parseInt($value.text(), 10);
    if (Number.isNaN(current)) current = 0;

    const isPlus = $btn.hasClass('bsc__qty-plus');
    const newQty = isPlus ? current + 1 : current - 1;

    if (newQty < 0) return;

    $value.text(newQty);
    $btn.addClass('bsc-loading');
    $control.data('processing', true);
    setControlBusy($control, true);

    $.post(bsc_ajax.ajax_url, {
      action: 'update_cart_quantity',
      product_id: productId,
      cart_item_key: cartItemKey,
      quantity: isPlus ? 1 : -1,
      nonce: bsc_ajax.nonce,
    }).done((response) => {
      if (!response?.success) {
        $value.text(current);
        return;
      }

      const cartCount = response?.data?.cart_count;
      const itemTotal = response?.data?.item_total;
      const serverQty = response?.data?.new_qty;
      const serverKey = response?.data?.cart_item_key || cartItemKey;
      const serverProductId = response?.data?.product_id || productId;
      const wasRemoved = response?.data?.removed === true;

      syncCartCount(cartCount);

      if (Number(cartCount) === 0 && redirectToEmptyCartFromCheckout()) {
        return;
      }

      if (serverQty !== undefined) {
        $value.text(serverQty);
      }

      if (itemTotal) {
        $control.closest('.checkout-cart__item').find('.item__total').html(itemTotal);
        $control.closest('tr').find('.bsc__cart-subtotal').html(itemTotal);
      }

      $control.closest('.checkout-cart__item').find('label span').text(serverQty || newQty);

      if (isPlus) triggerCartSwing();

      refreshCartFragments();
      if (typeof refreshReviewSummary === 'function') refreshReviewSummary();
      if (typeof window.bscCaptureAbandonedCart === 'function') window.bscCaptureAbandonedCart();

      if (wasRemoved || newQty === 0) {
        if (serverKey) {
          $(".checkout-cart__item[data-item-key=\"" + serverKey + "\"]").remove();
        } else {
          $(".checkout-cart__item[data-product_id=\"" + serverProductId + "\"]").remove();
        }

        $control.closest('tr').remove();
        $("[data-product_id=\"" + serverProductId + "\"].bsc__button-add-to-cart").removeClass('bsc__button-add-to-cart--hidden');
        $control.remove();
        $control.siblings('.added_to_cart.wc-forward').remove();
      }
    }).fail((xhr) => {
      console.error('BSC: cart quantity update failed:', xhr.responseText);
      $value.text(current);
    }).always(() => {
      $btn.removeClass('bsc-loading');
      $control.data('processing', false);
      setControlBusy($control, false);
    });
  };

  $(document).on('click', SELECTORS.plusBtn + ', ' + SELECTORS.minusBtn, handleQtyChange);

  /**
   * Handle delete from cart
   */
  $(document).on('click', SELECTORS.deleteBtn, function (e) {
    e.preventDefault();
    const $btn = $(this);
    const $item = $btn.closest(SELECTORS.checkoutItem);
    const key = $item.data('item-key');
    if (!key) return console.error('BSC: No item key found');

    const emptiesCheckout = isCheckoutPage() && $(SELECTORS.checkoutItem).length <= 1;

    $btn.prop('disabled', true).addClass('loading');

    $.post(bsc_ajax.ajax_url, {
      action: 'bsc_remove_cart_item',
      cart_item_key: key,
      nonce: bsc_ajax.nonce,
    }).done((res) => {
      // I-1: check server success BEFORE touching the DOM.
      // bsc_remove_cart_item sends wp_send_json_error({success:false}) on failure,
      // or WC_AJAX::get_refreshed_fragments() (no success field) on success.
      if (res.success === false) {
        const msg = res.data?.message || 'Error al eliminar el producto.';
        const $notice = $('<div class="bsc__coupon-notice bsc__coupon-notice--error"></div>').text(msg);
        $('body').append($notice);
        setTimeout(() => $notice.remove(), 4000);
        return;
      }

      if (emptiesCheckout) {
        syncCartCount(0);
        redirectToEmptyCartFromCheckout();
        return;
      }

      $item.slideUp(300, function () { $(this).remove(); });

      if (res.fragments) {
        $.each(res.fragments, (selector, html) => $(selector).replaceWith(html));

        // Parse count from fragment HTML and update footer badge immediately
        // (a.cart-contents is not in BSC header DOM so $.each replaceWith is a no-op)
        if (res.fragments['a.cart-contents']) {
          const $frag = $('<div>').append(res.fragments['a.cart-contents']);
          const count = $frag.find('.count').text().replace(/\D/g, '') || '0';
          $(SELECTORS.footerCount).text(count);
          $(SELECTORS.footerCart).attr('aria-label', `Shopping Cart with ${count} items`);
        } else {
          // Cart is now empty - WC returns no fragment for empty cart
          $(SELECTORS.footerCount).text('0');
          $(SELECTORS.footerCart).attr('aria-label', 'Shopping Cart with 0 items');
        }

        if (typeof refreshCartFragments === 'function') refreshCartFragments();
        if (typeof refreshReviewSummary === 'function') refreshReviewSummary();
        if (typeof window.bscCaptureAbandonedCart === 'function') window.bscCaptureAbandonedCart();
        jQuery(document.body).trigger('update_checkout');
      }
    }).fail(() => {
      console.error('BSC: Error al eliminar el producto del carrito.');
      const $notice = $('<div class="bsc__coupon-notice bsc__coupon-notice--error">Hubo un error. Intenta nuevamente.</div>');
      $('body').append($notice);
      setTimeout(() => $notice.remove(), 4000);
    })
      .always(() => $btn.prop('disabled', false).removeClass('loading'));
  });
});


// refreshReviewSummary - called directly after cart item removal.
// On checkout pages, checkout.js handles this via WC's 'updated_checkout' event.
let reviewSummaryRequest = null;

function refreshReviewSummary() {
  if (!window.bsc_ajax || !bsc_ajax.ajax_url || !jQuery('#bsc-review-summary').length) {
    return null;
  }

  const formData = jQuery('form[name="checkout"]').length
    ? jQuery('form[name="checkout"]').serializeArray()
    : [];

  if (reviewSummaryRequest && reviewSummaryRequest.readyState !== 4) {
    reviewSummaryRequest.abort();
  }

  reviewSummaryRequest = jQuery.ajax({
    url: bsc_ajax.ajax_url,
    method: 'POST',
    data: [
      { name: 'action', value: 'bsc_get_review_summary' },
      { name: 'nonce', value: bsc_ajax.nonce },
    ].concat(formData),
  })
    .done((res) => {
      if (res?.success && res?.data?.html) {
        jQuery('#bsc-review-summary').replaceWith(res.data.html);
        if (typeof window.bscToggleShippingVisibility === 'function') {
          window.bscToggleShippingVisibility();
        }
      }
    })
    .fail((xhr, statusText) => {
      if (statusText !== 'abort') {
        console.error('BSC: Error al refrescar el resumen del pedido.', xhr?.responseText);
      }
    })
    .always(() => {
      reviewSummaryRequest = null;
    });

  return reviewSummaryRequest;
}

window.refreshReviewSummary = refreshReviewSummary;
