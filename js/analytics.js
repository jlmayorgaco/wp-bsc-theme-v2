(function () {
  'use strict';

  var analyticsConfig = window.bsc_analytics || {};

  function analyticsConsentState() {
    var match = document.cookie.match(/(?:^|;\s*)bsc_analytics_consent=([^;]*)/);
    var value = match ? match[1] : '';
    return value === 'granted' || value === 'denied' ? value : 'unknown';
  }

  function hasAnalyticsConsent() {
    return !analyticsConfig.consent_required || analyticsConsentState() === 'granted';
  }

  function startAnalytics() {
    var measurementId = String(analyticsConfig.measurement_id || '');
    if (!/^G-[A-Z0-9]+$/.test(measurementId) || document.querySelector('script[data-bsc-ga4]')) return;

    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', measurementId);

    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
    script.setAttribute('data-bsc-ga4', '');
    document.head.appendChild(script);
  }

  function removeConsentBanner() {
    var banner = document.querySelector('[data-bsc-analytics-consent-banner]');
    if (banner) banner.remove();
  }

  var consentBanner = document.querySelector('[data-bsc-analytics-consent-banner]');
  if (consentBanner) {
    if (analyticsConsentState() !== 'unknown' || !analyticsConfig.consent_required) {
      consentBanner.remove();
    } else {
      consentBanner.hidden = false;
    }
  }
  analyticsConfig.consent_granted = hasAnalyticsConsent();
  if (analyticsConfig.consent_granted) startAnalytics();

  function pushEcommerceEvent(eventName, ecommerce) {
    if (!hasAnalyticsConsent()) return;

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

  function setAnalyticsConsent(value) {
    var maxAge = 180 * 24 * 60 * 60;
    var secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = 'bsc_analytics_consent=' + encodeURIComponent(value) + '; Max-Age=' + maxAge + '; Path=/; SameSite=Lax' + secure;
    analyticsConfig.consent_granted = value === 'granted';
    removeConsentBanner();
    if (hasAnalyticsConsent()) startAnalytics();
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

  document.addEventListener('click', function (event) {
    var target = event.target.closest('[data-bsc-analytics-consent]');
    if (!target) return;
    setAnalyticsConsent(target.getAttribute('data-bsc-analytics-consent') === 'granted' ? 'granted' : 'denied');
  });

  window.bscPushEcommerceEvent = pushEcommerceEvent;
}());
