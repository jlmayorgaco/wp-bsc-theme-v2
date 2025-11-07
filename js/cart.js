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

  /**
   * Add to cart handler
   */
  $(document).on('click', SELECTORS.addToCart, function (e) {
    e.preventDefault();

    const $btn = $(this);
    const productId = $btn.data('product_id');
    const quantity = $btn.data('quantity') || 1;

    $.post(bsc_ajax.ajax_url, {
      action: 'woocommerce_ajax_add_to_cart',
      product_id: productId,
      quantity: quantity,
    }).done((response) => {
      $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
    }).fail((err) => console.error('Add to cart failed:', err));
  });

  /**
   * After add to cart: Inject quantity controls and update cart UI
   */
  $(document.body).on('added_to_cart', function (e, fragments, hash, $btn) {
    if ($btn.siblings(SELECTORS.quantityControls).length) return;

    const productId = $btn.data('product_id');
    const quantityControls = `
      <div class="bsc__quantity-controls" data-product_id="${productId}">
        <button class="bsc__qty-minus">−</button>
        <span class="bsc__qty-value">1</span>
        <button class="bsc__qty-plus">+</button>
      </div>
    `;

    $btn.siblings('.added_to_cart').remove();
    $btn.parent().append(quantityControls);
    $btn.hide();

    const updatedCart = $(fragments['a.cart-contents']);
    const count = parseInt(updatedCart.find('.count').text().match(/\d+/)) || 0;

    $(SELECTORS.footerCount).text(count);
    $(SELECTORS.footerCart).attr('aria-label', `Shopping Cart with ${count} items`);


        jQuery(document.body).trigger('update_checkout');
  });

  /**
   * Refresh WooCommerce cart fragments and update counters
   */
  const refreshCartFragments = () => {
    $.get('?wc-ajax=get_refreshed_fragments', function (cart) {
      const rawHtml = cart?.fragments?.['a.cart-contents'];
      if (!rawHtml) return console.error('No cart contents fragment');

      const $cartContents = $('<div>').append(rawHtml);
      const cleanCount = $cartContents.find('.count').text().replace(/\D/g, '') || '0';

      $(SELECTORS.footerCount).text(cleanCount);
      $(SELECTORS.footerCart).attr('aria-label', `Shopping Cart with ${cleanCount} items`);


        jQuery(document.body).trigger('update_checkout');

      $.post(bsc_ajax.ajax_url, { action: 'bsc_get_cart_quantities' }, function (res) {
        if (res.success && Array.isArray(res.data)) {
          res.data.forEach(({ key, quantity }) => {
            $(`${SELECTORS.checkoutItem}[data-item-key="${key}"]`).find('label span').text(quantity);
          });
        }

      });
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
    const min = Number($control.data('min')) || 0;
    const productId = $control.data('product_id');

    let current = parseInt($value.text(), 10);
    const isPlus = $btn.hasClass('bsc__qty-plus');
    const newQty = isPlus ? current + 1 : current - 1;

    if (newQty == 0) {
      $(`.checkout-cart__item[data-product_id="${productId}"]`).remove();
    }

    if (newQty < 0) return;
 
    $value.text(newQty);

    $.post(bsc_ajax.ajax_url, {
      action: 'update_cart_quantity',
      product_id: productId,
      quantity: isPlus ? 1 : -1,
    }).done(() => {
      refreshCartFragments();
      if (newQty === 0) {
        $(`.checkout-cart__item[data-product_id="${productId}"]`).remove();
        $(`a[data-product_id="${productId}"].bsc__button-add-to-cart`).show();
        $control.remove();
        $control.siblings('.added_to_cart.wc-forward').remove();
      }
    }).fail((xhr) => console.error('❌ Update failed:', xhr.responseText));
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
    if (!key) return console.error('❌ No item key found');

    $btn.prop('disabled', true).addClass('loading');

    $.post(bsc_ajax.ajax_url, {
      action: 'bsc_remove_cart_item',
      cart_item_key: key,
    }).done((res) => {
      $item.slideUp(300, function () { $(this).remove(); });
      if (res.fragments) {
        $.each(res.fragments, (selector, html) => $(selector).replaceWith(html));
        if (typeof refreshCartFragments === 'function') refreshCartFragments();
        if (typeof refreshReviewSummary === 'function') refreshReviewSummary();
        jQuery(document.body).trigger('update_checkout');
      }
    }).fail(() => alert('Hubo un error al eliminar el producto del carrito.'))
      .always(() => $btn.prop('disabled', false).removeClass('loading'));
  });
});


// keep these OUTSIDE the wrapper if you want, but use jQuery not $
jQuery(document.body).on('update_checkout.bsc', () => {
  console.log('🔥 update_checkout triggered');
  refreshReviewSummary();
});

function refreshReviewSummary() {
  console.log('🔁 Refreshing Review Summary...');
  if (!window.bsc_ajax || !bsc_ajax.ajax_url) return;   // guard

  jQuery.ajax({
    url: bsc_ajax.ajax_url,
    method: 'POST',
    data: { action: 'bsc_get_review_summary' },
  })
  .done(res => {
    if (res?.success && res?.data?.html) {
      jQuery('#bsc-review-summary').html(res.data.html);
    } else {
      console.warn('⚠️ Invalid review summary response', res);
    }
  })
  .fail(xhr => console.error('❌ Error al refrescar el resumen del pedido.', xhr?.responseText));
}
