(function () {
  'use strict';

  var analyticsConfig = window.bsc_analytics || {};

  var analyticsCookie = 'bsc_analytics_consent';
  var metaCookie = 'bsc_meta_consent';
  var preferenceChange = 'bsc_cookie_preferences_changed';

  function consentState(name) {
    var prefix = name + '=';
    var cookie = document.cookie.split(';').map(function (entry) { return entry.trim(); })
      .find(function (entry) { return entry.startsWith(prefix); });
    var value = cookie ? cookie.slice(prefix.length) : '';
    return value === 'granted' || value === 'denied' ? value : 'unknown';
  }

  function hasAnalyticsConsent() {
    return !analyticsConfig.consent_required || consentState(analyticsCookie) === 'granted';
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

  function writeCookie(name, value, maxAge, domain) {
    var secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = name + '=' + value + '; Max-Age=' + maxAge + '; Path=/; SameSite=Lax' + secure + (domain ? '; Domain=' + domain : '');
  }

  function revokeMeta() {
    if (typeof window.fbq === 'function') window.fbq('consent', 'revoke');
    ['_fbp', '_fbc', 'wc_facebook_signals_state'].forEach(function (name) {
      writeCookie(name, '', 0);
      writeCookie(name, '', 0, window.location.hostname);
    });
    try { window.sessionStorage.removeItem('wc_facebook_signals_seen_event_ids'); } catch (_error) { /* Storage may be blocked. */ }
  }

  function refreshMetaView(metaAllowed, privateView) {
    var url = new URL(window.location.href);
    var isPrivate = url.searchParams.has(privateView);

    if (metaAllowed && !isPrivate && !analyticsConfig.meta_tracking) {
      url.searchParams.set(privateView, '1');
    } else if (!metaAllowed && (isPrivate || analyticsConfig.meta_tracking)) {
      url.searchParams.delete(privateView);
    } else {
      return false;
    }

    window.location.replace(url.href);
    return true;
  }

  function updateTracking() {
    var measurementId = String(analyticsConfig.measurement_id || '');
    var allowAnalytics = hasAnalyticsConsent();
    if (measurementId) window['ga-disable-' + measurementId] = !allowAnalytics;
    if (allowAnalytics) startAnalytics();
  }

  function initPreferences() {
    var panel = document.querySelector('[data-bsc-cookie-panel]');
    if (!panel) {
      updateTracking();
      return;
    }

    var form = panel.querySelector('form');
    var fields = Array.from(form.querySelectorAll('input[type="checkbox"]'));
    var open = document.querySelector('[data-bsc-cookie-open]');
    var metaField = form.elements.namedItem(metaCookie);
    var privateView = panel.dataset.metaPrivateView;

    function showPanel(visible, moveFocus) {
      panel.hidden = !visible;
      if (open) {
        open.setAttribute('aria-expanded', String(visible));
        if (!visible && moveFocus) open.focus({ preventScroll: true });
      }
      if (visible && moveFocus) panel.querySelector('h2').focus({ preventScroll: true });
    }

    function syncPreferences() {
      var metaAllowed = !!metaField && consentState(metaCookie) === 'granted';
      if (metaField && !metaAllowed && analyticsConfig.meta_tracking) revokeMeta();
      if (metaField && refreshMetaView(metaAllowed, privateView)) return;
      updateTracking();

      var pending = false;
      fields.forEach(function (field) {
        var state = consentState(field.name);
        field.checked = state === 'granted';
        pending = pending || state === 'unknown';
      });
      showPanel(pending, false);
    }

    function savePreferences(reject) {
      fields.forEach(function (field) {
        writeCookie(field.name, !reject && field.checked ? 'granted' : 'denied', 180 * 24 * 60 * 60);
      });

      var metaAllowed = !!metaField && consentState(metaCookie) === 'granted';
      if (metaField && !metaAllowed) revokeMeta();
      updateTracking();
      showPanel(false, true);

      try { window.localStorage.setItem(preferenceChange, String(Date.now())); } catch (_error) { /* Cookies still apply. */ }
      if (metaField) refreshMetaView(metaAllowed, privateView);
    }

    if (open) {
      open.hidden = false;
      open.addEventListener('click', function () { showPanel(true, true); });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      savePreferences(false);
    });
    panel.querySelector('[data-bsc-cookie-reject]').addEventListener('click', function () { savePreferences(true); });
    panel.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !fields.some(function (field) { return consentState(field.name) === 'unknown'; })) {
        showPanel(false, true);
      }
    });
    window.addEventListener('storage', function (event) {
      if (event.key === preferenceChange) syncPreferences();
    });
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) syncPreferences();
    });
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden && metaField && analyticsConfig.meta_tracking && consentState(metaCookie) !== 'granted') {
        syncPreferences();
      }
    });
    syncPreferences();
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

  initPreferences();
  window.bscPushEcommerceEvent = pushEcommerceEvent;
}());
