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
  });

  /**
   * Refresh WooCommerce cart fragments and update counters
   */
  const refreshCartFragments = () => {
    $.get('?wc-ajax=get_refreshed_fragments', function (cart) {
      const rawHtml = cart?.fragments?.['a.cart-contents'];
      if (!rawHtml) {
        // BSC-004: si WC no devuelve el fragmento (carrito vacío), poner badge en 0
        $(SELECTORS.footerCount).text('0');
        $(SELECTORS.footerCart).attr('aria-label', 'Shopping Cart with 0 items');
        return;
      }

      const $cartContents = $('<div>').append(rawHtml);
      const cleanCount = $cartContents.find('.count').text().replace(/\D/g, '') || '0';

      $(SELECTORS.footerCount).text(cleanCount);
      $(SELECTORS.footerCart).attr('aria-label', `Shopping Cart with ${cleanCount} items`);

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
    }).done((response) => {
      // BSC-004: actualizar badge inmediatamente desde la respuesta del servidor (fuente de verdad)
      // evita depender del fragmento WC que puede no devolver el conteo cuando el carrito queda vacío
      const cartCount = response?.data?.cart_count;
      if (cartCount !== undefined) {
        $(SELECTORS.footerCount).text(cartCount);
        $(SELECTORS.footerCart).attr('aria-label', `Shopping Cart with ${cartCount} items`);
      }
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


// refreshReviewSummary — called directly after cart item removal.
// On checkout pages, checkout.js handles this via WC's 'updated_checkout' event.
function refreshReviewSummary() {
  if (!window.bsc_ajax || !bsc_ajax.ajax_url) return;

  jQuery.ajax({
    url: bsc_ajax.ajax_url,
    method: 'POST',
    data: { action: 'bsc_get_review_summary' },
  })
  .done(res => {
    if (res?.success && res?.data?.html) {
      jQuery('#bsc-review-summary').html(res.data.html);
    }
  })
  .fail(xhr => console.error('❌ Error al refrescar el resumen del pedido.', xhr?.responseText));
}
