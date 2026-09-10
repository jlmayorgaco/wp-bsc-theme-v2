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
  const variantCartItems = new Map();

  function variantCartStateKey(productId, variantKey) {
    return `${productId || ''}:${variantKey || ''}`;
  }

  function rememberVariantCartItem(item) {
    const productId = item?.product_id;
    const variantKey = item?.variant_key || '';
    const quantity = parseInt(item?.quantity, 10);
    const stockTotal = parseInt(item?.stock_total, 10);

    if (!productId || !variantKey || Number.isNaN(quantity)) return;

    const stateKey = variantCartStateKey(productId, variantKey);
    if (quantity > 0) {
      variantCartItems.set(stateKey, {
        key: item.key || '',
        product_id: productId,
        variant_key: variantKey,
        quantity,
        stock_total: Number.isNaN(stockTotal) ? null : Math.max(0, stockTotal),
      });
    } else {
      variantCartItems.delete(stateKey);
    }
  }

  function applyCartItemSnapshot(items) {
    variantCartItems.clear();

    (Array.isArray(items) ? items : []).forEach((item) => {
      rememberVariantCartItem(item);
      $(`${SELECTORS.checkoutItem}[data-item-key="${item.key}"]`).find('label span').text(item.quantity);
    });

    $('[data-bsc-product-options]').each(function () {
      syncVariantOptions($(this));
    });
  }

  function setControlBusy($control, isBusy) {
    $control.toggleClass('is-busy', isBusy);
    $control.find(SELECTORS.plusBtn + ', ' + SELECTORS.minusBtn + ', ' + SELECTORS.deleteBtn)
      .prop('disabled', isBusy);

    if (!isBusy) {
      const quantity = parseInt($control.find(SELECTORS.quantityValue).text(), 10);
      const stockTotal = parseInt($control.attr('data-stock-total'), 10);
      const reachedStockLimit = !Number.isNaN(quantity)
        && !Number.isNaN(stockTotal)
        && quantity >= stockTotal;

      $control.find(SELECTORS.plusBtn)
        .prop('disabled', reachedStockLimit)
        .attr('aria-label', reachedStockLimit ? 'Stock máximo alcanzado' : 'Aumentar cantidad')
        .attr('title', reachedStockLimit ? 'Stock máximo alcanzado' : '');
    }
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

  function redirectToEmptyCheckoutState() {
    if (!isCheckoutPage()) return false;

    const checkoutUrl = window.bsc_ajax?.checkout_url || '/checkout/';
    window.location.assign(checkoutUrl);

    return true;
  }

  // BSC-017: floating cart swing animation helper
  function triggerCartSwing() {
    const $cart = $(SELECTORS.footerCart);
    $cart.addClass('is-swinging')
      .one('animationend webkitAnimationEnd', function () { $(this).removeClass('is-swinging'); });
  }

  function isValidHexColor(color) {
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(color || '');
  }

  function applySwatchColor($swatch, color) {
    if (!isValidHexColor(color)) return;

    $swatch.css('background-color', color);
  }

  function getProductOptions($btn) {
    return $btn.closest('.bsc__product--page').find('[data-bsc-product-options]').first();
  }

  function prefersReducedMotion() {
    return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches === true;
  }

  function isMobileVariantFlow() {
    return window.matchMedia?.('(max-width: 768px), (pointer: coarse)').matches === true;
  }

  function formatCopPrice(price) {
    const numericPrice = Number(price);
    if (!Number.isFinite(numericPrice)) return '';

    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(numericPrice);
  }

  function getVariantMatrix($options) {
    if (!$options.length) return [];

    if ($options.data('bscVariantMatrix') === undefined) {
      let rows = [];
      const raw = $options.find('[data-bsc-product-variant-matrix]').first().html();

      if (raw) {
        try {
          rows = JSON.parse(raw);
        } catch (e) {
          rows = [];
        }
      }

      $options.data('bscVariantMatrix', Array.isArray(rows) ? rows : []);
    }

    return $options.data('bscVariantMatrix') || [];
  }

  function variantHasColors(matrix) {
    return matrix.some((variant) => variant.color_name);
  }

  function variantHasSizes(matrix) {
    return matrix.some((variant) => variant.size_name);
  }

  function variantAvailable(variant) {
    return variant && variant.enabled !== false && Number(variant.stock_total || 0) > 0;
  }

  function variantSelectionComplete(hasColors, hasSizes, colorName, sizeName) {
    return (!hasColors || Boolean(colorName)) && (!hasSizes || Boolean(sizeName));
  }

  function variantSelectionMessage(hasColors, hasSizes, colorName, sizeName) {
    if (hasColors && hasSizes) {
      if (!colorName && !sizeName) return 'Selecciona color y Tamaño para continuar.';
      if (!colorName) return 'Selecciona un color disponible.';
      if (!sizeName) return 'Selecciona un Tamaño disponible.';
    }

    if (hasColors && !colorName) return 'Selecciona un color disponible.';
    if (hasSizes && !sizeName) return 'Selecciona un Tamaño disponible.';

    return 'Selecciona una variante disponible.';
  }

  function selectedColorName($options) {
    return $options.find('[data-bsc-selected-color-name]').val() || '';
  }

  function selectedColorHex($options) {
    return $options.find('[data-bsc-selected-color-hex]').val() || '';
  }

  function selectedSizeName($options) {
    return $options.find('[data-bsc-selected-size-name]').val() || '';
  }

  function variantMatchesSelection(variant, colorName, colorHex, sizeName, hasColors, hasSizes) {
    const colorMatches = !hasColors
      || (variant.color_name === colorName && (!colorHex || String(variant.color_hex).toLowerCase() === String(colorHex).toLowerCase()));
    const sizeMatches = !hasSizes || variant.size_name === sizeName;

    return colorMatches && sizeMatches;
  }

  function findSelectedVariant($options, allowUnavailable = true) {
    const matrix = getVariantMatrix($options);
    const hasColors = variantHasColors(matrix);
    const hasSizes = variantHasSizes(matrix);
    const colorName = selectedColorName($options);
    const colorHex = selectedColorHex($options);
    const sizeName = selectedSizeName($options);

    return matrix.find((variant) => {
      if (!allowUnavailable && !variantAvailable(variant)) return false;

      return variantMatchesSelection(variant, colorName, colorHex, sizeName, hasColors, hasSizes);
    }) || null;
  }

  function getMissingVariantGroups($options) {
    const matrix = getVariantMatrix($options);
    let $groups = $();

    if (variantHasColors(matrix) && !selectedColorName($options)) {
      $groups = $groups.add($options.find('.bsc-product-options__group--color').first());
    }

    if (variantHasSizes(matrix) && !selectedSizeName($options)) {
      $groups = $groups.add($options.find('.bsc-product-options__group--size').first());
    }

    return $groups;
  }

  function promptVariantSelection($options) {
    if (!$options.length) return;

    const $groups = getMissingVariantGroups($options);
    if (!$groups.length) return;

    const reduceMotion = prefersReducedMotion();
    const optionsElement = $options.get(0);
    const firstChoice = $groups.first()
      .find('[data-bsc-color-option]:not(:disabled), [data-bsc-size-option]:not(:disabled)')
      .get(0);

    optionsElement.scrollIntoView({
      behavior: reduceMotion ? 'auto' : 'smooth',
      block: 'center',
    });

    $groups.removeClass('is-selection-prompted');
    $groups.each(function () {
      // Restart the cue when the customer taps "Selecciona opciones" again.
      void this.offsetWidth;
      $(this).addClass('is-selection-prompted');
    });

    window.clearTimeout($options.data('bscVariantPromptTimer'));
    $options.data('bscVariantPromptTimer', window.setTimeout(() => {
      $groups.removeClass('is-selection-prompted');
    }, 560));

    window.setTimeout(() => {
      firstChoice?.focus({ preventScroll: true });
    }, reduceMotion ? 0 : 320);

    if (isMobileVariantFlow() && typeof navigator.vibrate === 'function') {
      try {
        navigator.vibrate([40, 45, 40]);
      } catch (error) {
        // Haptic feedback is optional and unsupported on some mobile browsers.
      }
    }
  }

  function variantStockSubject($options) {
    const matrix = getVariantMatrix($options);

    if (variantHasSizes(matrix)) return 'tamaño';
    if (variantHasColors(matrix)) return 'tono';

    return 'producto';
  }

  function updateVariantStockStatus($options, variant) {
    const $button = $options.closest('.bsc__product-info').find(SELECTORS.addToCart).first();
    const $status = $options.find('[data-bsc-variant-stock-status]').first();
    const variantKey = String(variant?.key || '');
    const item = variantCartItems.get(variantCartStateKey($button.data('product_id'), variantKey));
    const variantStock = parseInt(variant?.stock_total, 10);
    const itemStock = parseInt(item?.stock_total, 10);
    const stockTotal = Number.isNaN(itemStock) ? variantStock : itemStock;
    const quantityInCart = Math.max(0, parseInt(item?.quantity, 10) || 0);
    const remainingStock = Math.max(0, (Number.isNaN(stockTotal) ? 0 : stockTotal) - quantityInCart);

    $status.removeClass('is-out-of-stock');

    if (remainingStock === 0) {
      $status
        .addClass('is-out-of-stock')
        .text(`Ya no hay más stock disponible para este ${variantStockSubject($options)}.`);
      return;
    }

    $status.text(remainingStock <= 3 ? 'Pocas unidades disponibles.' : '');
  }

  function updateAddToCartAvailability($options, variant, message = '') {
    const $button = $options.closest('.bsc__product-info').find(SELECTORS.addToCart).first();
    const $status = $options.find('[data-bsc-variant-stock-status]').first();
    const available = variantAvailable(variant);

    if (!$button.length) return;

    const $quantityControls = $button.siblings(SELECTORS.quantityControls).first();
    const displayedVariantKey = String($quantityControls.attr('data-variant-key') || '');
    const selectedVariantKey = available ? String(variant.key || '') : '';

    if (displayedVariantKey && displayedVariantKey !== selectedVariantKey) {
      $quantityControls.remove();
      $button
        .removeClass('bsc__button-add-to-cart--hidden')
        .removeData('bscCartItemKey bscCartItemQuantity bscCartVariantKey');
    }

    if ($button.data('bscOriginalHtml') === undefined) {
      $button.data('bscOriginalHtml', $button.html());
      $button.data('bscOriginalAria', $button.attr('aria-label') || '');
    }

    if (available) {
      $button
        .prop('disabled', false)
        .attr('aria-disabled', 'false')
        .removeClass('is-selection-required');
      $button.html($button.data('bscOriginalHtml'));
      if ($button.data('bscOriginalAria')) {
        $button.attr('aria-label', $button.data('bscOriginalAria'));
      } else {
        $button.removeAttr('aria-label');
      }
      updateVariantStockStatus($options, variant);
      syncSelectedVariantCartState($options, variant);
      return;
    }

    if (message) {
      $button
        .prop('disabled', false)
        .attr('aria-disabled', 'true')
        .addClass('is-selection-required')
        .html('<span>Selecciona opciones</span>')
        .attr('aria-label', message);
      $status.removeClass('is-out-of-stock').text(message);
      return;
    }

    $button
      .prop('disabled', true)
      .attr('aria-disabled', 'true')
      .removeClass('is-selection-required')
      .html('<span>Agotado</span>')
      .attr('aria-label', 'Variante agotada');
    $status
      .addClass('is-out-of-stock')
      .text(`Ya no hay más stock disponible para este ${variantStockSubject($options)}.`);
  }

  function syncVariantOptions($options) {
    const matrix = getVariantMatrix($options);
    if (!matrix.length) return;

    const hasColors = variantHasColors(matrix);
    const hasSizes = variantHasSizes(matrix);
    let colorName = selectedColorName($options);
    let colorHex = selectedColorHex($options);
    let sizeName = selectedSizeName($options);
    const hasAnyStock = matrix.some(variantAvailable);

    $options.find('[data-bsc-color-option]').each(function () {
      const $option = $(this);
      const optionName = $option.data('name') || '';
      const optionHex = $option.data('hex') || '';
      const available = matrix.some((row) => {
        if (!variantAvailable(row) || row.color_name !== optionName) return false;
        if (String(row.color_hex).toLowerCase() !== String(optionHex).toLowerCase()) return false;
        return !hasSizes || !sizeName || row.size_name === sizeName;
      });

      $option
        .prop('disabled', !available)
        .prop('hidden', !available)
        .toggleClass('is-unavailable', !available);
    });

    $options.find('[data-bsc-size-option]').each(function () {
      const $option = $(this);
      const optionName = $option.data('name') || '';
      const available = matrix.some((row) => {
        if (!variantAvailable(row) || row.size_name !== optionName) return false;
        return !hasColors || !colorName || (row.color_name === colorName && (!colorHex || String(row.color_hex).toLowerCase() === String(colorHex).toLowerCase()));
      });

      $option
        .prop('disabled', !available)
        .prop('hidden', !available)
        .toggleClass('is-unavailable', !available);
    });

    if (!hasAnyStock) {
      $options.find('[data-bsc-selected-variant-key]').val('');
      updateAddToCartAvailability($options, null);
      return;
    }

    if (!variantSelectionComplete(hasColors, hasSizes, colorName, sizeName)) {
      $options.find('[data-bsc-selected-variant-key]').val('');
      updateAddToCartAvailability(
        $options,
        null,
        variantSelectionMessage(hasColors, hasSizes, colorName, sizeName)
      );
      return;
    }

    const variant = findSelectedVariant($options, true);
    $options.find('[data-bsc-selected-variant-key]').val(variantAvailable(variant) ? variant.key || '' : '');
    updateAddToCartAvailability($options, variant);
  }

  function selectedVariantPrice($options) {
    const matrix = getVariantMatrix($options);

    if (matrix.length) {
      const hasColors = variantHasColors(matrix);
      const hasSizes = variantHasSizes(matrix);
      if (!variantSelectionComplete(hasColors, hasSizes, selectedColorName($options), selectedSizeName($options))) {
        return '';
      }

      const variant = findSelectedVariant($options, true);

      return variantAvailable(variant) ? variant.price || '' : '';
    }

    const sizePrice = $options.find('[data-bsc-size-option].is-selected').data('price');
    const colorPrice = $options.find('[data-bsc-color-option].is-selected').data('price');

    return sizePrice || colorPrice || '';
  }

  function updateProductPrice($options) {
    const rawPrice = selectedVariantPrice($options);
    const $price = $options.closest('.bsc__product-info').find('.bsc__product-price .price').first();
    const $target = $price.length ? $price : $options.closest('.bsc__product-info').find('.bsc__product-price').first();

    if (!$target.length) return;
    if ($target.data('bscBaseHtml') === undefined) {
      $target.data('bscBaseHtml', $target.html());
    }

    if (rawPrice) {
      $target.html(formatCopPrice(rawPrice));
      return;
    }

    $target.html($target.data('bscBaseHtml'));
  }

  function getProductOptionPayload($btn) {
    const $options = getProductOptions($btn);
    if (!$options.length) return {};

    return {
      bsc_color_variant_name: $options.find('[data-bsc-selected-color-name]').val() || '',
      bsc_color_variant_hex: $options.find('[data-bsc-selected-color-hex]').val() || '',
      bsc_size_variant_name: $options.find('[data-bsc-selected-size-name]').val() || '',
      bsc_product_variant_key: $options.find('[data-bsc-selected-variant-key]').val() || '',
    };
  }

  function showQuantityControls($btn) {
    const productId = $btn.data('product_id');
    const cartItemKey = $btn.data('bscCartItemKey') || '';
    const variantKey = $btn.data('bscCartVariantKey') || '';
    const parsedQuantity = parseInt($btn.data('bscCartItemQuantity'), 10);
    const parsedStockTotal = parseInt($btn.data('bscVariantStockTotal'), 10);
    const quantity = Number.isNaN(parsedQuantity) ? 1 : Math.max(1, parsedQuantity);
    let $controls = $btn.siblings(SELECTORS.quantityControls).first();

    if (!$controls.length) {
      $controls = $('<div>', {
        class: 'bsc__quantity-controls',
        'data-product_id': productId,
      });

      $('<button>', {
        type: 'button',
        class: 'bsc__qty-minus',
        'aria-label': 'Disminuir cantidad',
      }).html('&minus;').appendTo($controls);

      $('<span>', {
        class: 'bsc__qty-value',
        text: quantity,
        'aria-live': 'polite',
      }).appendTo($controls);

      $('<button>', {
        type: 'button',
        class: 'bsc__qty-plus',
        'aria-label': 'Aumentar cantidad',
      }).text('+').appendTo($controls);

      $btn.parent().append($controls);
    }

    $controls
      .attr('data-item-key', cartItemKey)
      .attr('data-variant-key', variantKey)
      .attr('data-stock-total', Number.isNaN(parsedStockTotal) ? '' : parsedStockTotal)
      .find(SELECTORS.quantityValue)
      .text(quantity);

    setControlBusy($controls, false);
    $btn.siblings('.added_to_cart').remove();
    $btn.addClass('bsc__button-add-to-cart--hidden');
  }

  function showAddToCartToast($controls) {
    const $productCart = $controls.closest('.bsc__product-cart');

    if (!$productCart.closest('.bsc__product--page').length) return;

    let $toast = $productCart.find('.bsc__add-to-cart-toast').first();

    if (!$toast.length) {
      $toast = $('<div>', {
        class: 'bsc__add-to-cart-toast',
        role: 'status',
        'aria-live': 'polite',
        'aria-atomic': 'true',
        'aria-hidden': 'true',
      });

      $('<span>', {
        class: 'bsc__add-to-cart-toast__message',
        text: 'Añadido a tu carrito.',
      }).appendTo($toast);

      $('<span>', {
        class: 'bsc__add-to-cart-toast__progress',
        'aria-hidden': 'true',
      }).appendTo($toast);

      $productCart.append($toast);
    }

    const previousTimer = $toast.data('bscHideTimer');
    if (previousTimer) window.clearTimeout(previousTimer);

    $toast.removeClass('is-visible').attr('aria-hidden', 'true');
    void $toast[0].offsetWidth;
    $productCart.addClass('has-add-to-cart-toast');
    $toast.addClass('is-visible').attr('aria-hidden', 'false');

    const hideTimer = window.setTimeout(() => {
      $toast.removeClass('is-visible').attr('aria-hidden', 'true');
      $productCart.removeClass('has-add-to-cart-toast');
    }, 2000);

    $toast.data('bscHideTimer', hideTimer);
  }

  function syncSelectedVariantCartState($options, variant) {
    if (!variantAvailable(variant)) return;

    const $button = $options.closest('.bsc__product-info').find(SELECTORS.addToCart).first();
    const productId = $button.data('product_id');
    const variantKey = String(variant.key || '');
    const item = variantCartItems.get(variantCartStateKey(productId, variantKey));

    if (!item || item.quantity < 1) {
      $button.siblings(SELECTORS.quantityControls).remove();
      $button
        .removeClass('bsc__button-add-to-cart--hidden')
        .removeData('bscCartItemKey bscCartItemQuantity bscCartVariantKey');
      return;
    }

    $button.data('bscCartItemKey', item.key);
    $button.data('bscCartItemQuantity', item.quantity);
    $button.data('bscCartVariantKey', item.variant_key);
    $button.data('bscVariantStockTotal', item.stock_total);
    showQuantityControls($button);
  }

  let cartNonceRefreshRequest = null;

  function isExpiredCartNonce(error) {
    return Number(error?.status) === 403 && String(error?.responseText || '').trim() === '-1';
  }

  function rejectedCartRequest(error) {
    const deferred = $.Deferred();
    deferred.reject(error);
    return deferred.promise();
  }

  function refreshCartNonce() {
    if (cartNonceRefreshRequest) return cartNonceRefreshRequest;

    cartNonceRefreshRequest = $.post(bsc_ajax.ajax_url, {
      action: 'bsc_refresh_ajax_nonce',
    }).then((response) => {
      const nonce = response?.data?.nonce;

      if (response?.success !== true || typeof nonce !== 'string' || !nonce.trim()) {
        throw new Error('Invalid nonce refresh response');
      }

      bsc_ajax.nonce = nonce;
      if (window.bsc_search) window.bsc_search.nonce = nonce;

      return nonce;
    });

    cartNonceRefreshRequest.always(() => {
      cartNonceRefreshRequest = null;
    });

    return cartNonceRefreshRequest;
  }

  function postCartAction(payload, canRefreshNonce = true) {
    return $.post(bsc_ajax.ajax_url, {
      ...payload,
      nonce: bsc_ajax.nonce,
    }).then(undefined, (error) => {
      if (!canRefreshNonce || !isExpiredCartNonce(error)) {
        return rejectedCartRequest(error);
      }

      return refreshCartNonce().then(
        () => postCartAction(payload, false),
        () => rejectedCartRequest(error)
      );
    });
  }

  function cartErrorMessage(error) {
    const response = error?.responseJSON || error || {};
    const candidates = [
      response?.data?.error,
      response?.data?.message,
      response?.error,
      response?.message,
      error?.responseText,
    ];
    const message = candidates.find((value) => (
      typeof value === 'string' && value.trim() && value.trim() !== '-1'
    ));

    if (message) return message.trim();
    if (isExpiredCartNonce(error)) {
      return 'Tu sesión de compra venció. Recarga la página e intenta nuevamente.';
    }

    return '';
  }

  function markSelectedVariantOutOfStock($btn) {
    const $options = getProductOptions($btn);
    const variant = findSelectedVariant($options, true);
    if (!$options.length || !variant) return;

    postCartAction({
      action: 'bsc_get_cart_quantities',
    }).done(function (response) {
      if (response?.success) {
        applyCartItemSnapshot(response.data);
      }

      const productId = $btn.data('product_id');
      const cartItem = variantCartItems.get(variantCartStateKey(productId, variant.key || ''));
      if (cartItem?.quantity > 0) {
        syncSelectedVariantCartState($options, variant);
        return;
      }

      variant.stock_total = 0;

      if (variantHasSizes(getVariantMatrix($options))) {
        $options.find('[data-bsc-size-option].is-selected')
          .removeClass('is-selected')
          .attr('aria-selected', 'false');
        $options.find('[data-bsc-selected-size-name]').val('');
        $options.find('[data-bsc-size-current-label]').text('Escoge un tamaño');
      } else {
        $options.find('[data-bsc-color-option].is-selected')
          .removeClass('is-selected')
          .attr('aria-selected', 'false');
        $options.find('[data-bsc-selected-color-name], [data-bsc-selected-color-hex]').val('');
        $options.find('[data-bsc-color-current-label]').text('Escoge un tono');
      }

      syncVariantOptions($options);
      updateProductPrice($options);
    });
  }

  function closeColorPickers($except) {
    $('[data-bsc-color-picker]').not($except || $()).each(function () {
      const $picker = $(this);
      $picker.removeClass('is-open');
      $picker.find('[data-bsc-color-list]').prop('hidden', true);
      $picker.find('[data-bsc-color-trigger]').attr('aria-expanded', 'false');
    });
  }

  function initProductVariantOptions() {
    $('[data-color-hex]').each(function () {
      applySwatchColor($(this), $(this).data('colorHex'));
    });

    $('[data-bsc-product-options]').each(function () {
      syncVariantOptions($(this));
      updateProductPrice($(this));
    });

    postCartAction({
      action: 'bsc_get_cart_quantities',
    }).done(function (response) {
      if (response?.success) {
        applyCartItemSnapshot(response.data);
      }
    });
  }

  initProductVariantOptions();

  $(document).on('click', '[data-bsc-color-trigger]', function (e) {
    e.preventDefault();

    const $trigger = $(this);
    const $picker = $trigger.closest('[data-bsc-color-picker]');
    const $list = $picker.find('[data-bsc-color-list]');
    const shouldOpen = $list.prop('hidden');

    closeColorPickers($picker);
    $picker.toggleClass('is-open', shouldOpen);
    $list.prop('hidden', !shouldOpen);
    $trigger.attr('aria-expanded', shouldOpen ? 'true' : 'false');
  });

  $(document).on('click', '[data-bsc-color-option]', function (e) {
    e.preventDefault();

    const $option = $(this);
    if ($option.prop('disabled')) return;

    const $options = $option.closest('[data-bsc-product-options]');
    const colorName = $option.data('name') || '';
    const colorHex = $option.data('hex') || '';

    $options.find('[data-bsc-color-option]').removeClass('is-selected').attr('aria-selected', 'false');
    $option.addClass('is-selected').attr('aria-selected', 'true');

    $options.find('[data-bsc-selected-color-name]').val(colorName);
    $options.find('[data-bsc-selected-color-hex]').val(colorHex);
    $options.find('[data-bsc-color-current-label]').text(colorName);
    const $swatch = $options.find('[data-bsc-color-current-swatch]');
    $swatch.removeClass('is-empty');
    applySwatchColor($swatch, colorHex);
    syncVariantOptions($options);
    updateProductPrice($options);
    closeColorPickers();
  });

  $(document).on('click', '[data-bsc-size-option]', function (e) {
    e.preventDefault();

    const $option = $(this);
    if ($option.prop('disabled')) return;

    const $options = $option.closest('[data-bsc-product-options]');
    const sizeName = $option.data('name') || '';

    $options.find('[data-bsc-size-option]').removeClass('is-selected').attr('aria-selected', 'false');
    $option.addClass('is-selected').attr('aria-selected', 'true');
    $options.find('[data-bsc-selected-size-name]').val(sizeName);
    $options.find('[data-bsc-size-current-label]').text(sizeName);
    syncVariantOptions($options);
    updateProductPrice($options);
  });

  $(document).on('click', function (e) {
    if ($(e.target).closest('[data-bsc-color-picker]').length) return;

    closeColorPickers();
  });

  /**
   * Add to cart handler
   * BSC-005: listen to both pointerup (touch/mouse - no 300ms delay) and click
   * (keyboard Enter/Space on <button>). The data-processing guard prevents
   * double-firing when both events fire for the same interaction.
   */
  $(document).on('pointerup click', SELECTORS.addToCart, function (e) {
    e.preventDefault();

    const $btn = $(this);
    if ($btn.hasClass('is-selection-required')) {
      if (!$btn.data('bscVariantPrompting')) {
        $btn.data('bscVariantPrompting', true);
        promptVariantSelection(getProductOptions($btn));
        window.setTimeout(() => $btn.data('bscVariantPrompting', false), 450);
      }
      return;
    }

    if ($btn.prop('disabled')) return;
    if ($btn.data('processing')) return;
    $btn.data('processing', true);
    $btn.addClass('bsc-loading'); // BSC-019: show spinner while adding

    const productId = $btn.data('product_id');
    const quantity = $btn.data('quantity') || 1;

    postCartAction({
      action: 'bsc_add_to_cart',
      product_id: productId,
      quantity: quantity,
      ...getProductOptionPayload($btn),
    }).done((response) => {
      const addedItem = response?.bsc_cart_item;

      if (response?.success === false || !addedItem?.key) {
        const errorMessage = cartErrorMessage(response);
        if (errorMessage.includes('stock suficiente')) {
          markSelectedVariantOutOfStock($btn);
        }
        console.error('Add to cart failed:', errorMessage || 'Invalid cart response');
        return;
      }

      $btn.data('bscCartItemKey', addedItem.key);
      $btn.data('bscCartItemQuantity', addedItem.quantity);
      $btn.data('bscCartVariantKey', addedItem.variant_key || '');
      $btn.data('bscVariantStockTotal', addedItem.stock_total);
      rememberVariantCartItem(addedItem);

      const $options = getProductOptions($btn);
      if ($options.length) {
        updateVariantStockStatus($options, findSelectedVariant($options, true));
      }

      syncCartCount(response.cart_count);
      $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
    }).fail((err) => {
      if (cartErrorMessage(err).includes('stock suficiente')) {
        markSelectedVariantOutOfStock($btn);
      }
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
    const safeFragments = fragments || {};
    const hasVariantOptions = getProductOptions($btn).length > 0;

    if (hasVariantOptions) {
      showQuantityControls($btn);
      showAddToCartToast($btn.siblings(SELECTORS.quantityControls).first());

      if (safeFragments['a.cart-contents']) {
        $('a.cart-contents').replaceWith(safeFragments['a.cart-contents']);

        const updatedCart = $(safeFragments['a.cart-contents']);
        const rawCount = updatedCart.find('.count').text().match(/\d+/);
        const count = rawCount ? parseInt(rawCount[0], 10) : 0;

        syncCartCount(count);
      } else {
        refreshCartFragments();
      }

      triggerCartSwing();
      navigator.vibrate?.(80);

      if (typeof refreshReviewSummary === 'function') refreshReviewSummary();
      if (typeof window.bscCaptureAbandonedCart === 'function') window.bscCaptureAbandonedCart();
      return;
    }

    if ($btn.siblings(SELECTORS.quantityControls).length) return;

    showQuantityControls($btn);
    showAddToCartToast($btn.siblings(SELECTORS.quantityControls).first());

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

      postCartAction({ action: 'bsc_get_cart_quantities' })
        .done(function (res) {
          if (res.success && Array.isArray(res.data)) {
            applyCartItemSnapshot(res.data);
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

    postCartAction({
      action: 'update_cart_quantity',
      product_id: productId,
      cart_item_key: cartItemKey,
      quantity: isPlus ? 1 : -1,
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
      const variantKey = String($control.attr('data-variant-key') || '');

      syncCartCount(cartCount);

      if (variantKey) {
        rememberVariantCartItem({
          key: serverKey,
          quantity: wasRemoved ? 0 : serverQty,
          product_id: serverProductId,
          variant_key: variantKey,
          stock_total: parseInt($control.attr('data-stock-total'), 10),
        });

        const $options = getProductOptions($control);
        if ($options.length) {
          updateVariantStockStatus($options, findSelectedVariant($options, true));
        }
      }

      if (Number(cartCount) === 0 && redirectToEmptyCheckoutState()) {
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

    postCartAction({
      action: 'bsc_remove_cart_item',
      cart_item_key: key,
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

      const serverCartCount = res.data?.cart_count;

      if (Number(serverCartCount) === 0 && redirectToEmptyCheckoutState()) {
        syncCartCount(0);
        return;
      }

      if (emptiesCheckout) {
        syncCartCount(0);
        redirectToEmptyCheckoutState();
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
