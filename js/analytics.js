(function () {
  'use strict';

  function pushEcommerceEvent(eventName, ecommerce) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    window.dataLayer.push({
      event: eventName,
      ecommerce: ecommerce || {},
    });
  }

  function buttonItem($button, quantity) {
    var rawPrice = parseFloat($button.data('product_price'));
    return {
      item_id: String($button.attr('data-product_sku') || $button.data('product_id') || ''),
      item_name: String($button.data('product_name') || ''),
      item_brand: String($button.data('product_brand') || ''),
      price: Number.isFinite(rawPrice) ? rawPrice : 0,
      quantity: quantity || 1,
    };
  }

  if (window.jQuery) {
    window.jQuery(function ($) {
      $(document.body).on('added_to_cart', function (_event, _fragments, _hash, $button) {
        if (!$button || !$button.length) return;

        var quantity = parseInt($button.data('quantity'), 10);
        if (!Number.isFinite(quantity) || quantity < 1) quantity = 1;

        pushEcommerceEvent('add_to_cart', {
          currency: window.bsc_analytics?.currency || 'COP',
          value: buttonItem($button, quantity).price * quantity,
          items: [buttonItem($button, quantity)],
        });
      });

      $('form[name="checkout"]').on('change', 'input[name="payment_method"]', function () {
        pushEcommerceEvent('add_payment_info', {
          currency: window.bsc_analytics?.currency || 'COP',
          payment_type: $(this).val() || '',
        });
      });
    });
  }

  window.bscPushEcommerceEvent = pushEcommerceEvent;
}());
