(function () {
  'use strict';

  var timer = null;
  var lastPayload = '';

  function fieldValue(selector) {
    var field = document.querySelector(selector);
    return field ? field.value.trim() : '';
  }

  function buildPayload() {
    var firstName = fieldValue('#billing_first_name');
    var lastName = fieldValue('#billing_last_name');
    var email = fieldValue('#billing_email');

    if (!email || email.indexOf('@') === -1) {
      return null;
    }

    var params = new URLSearchParams();
    params.set('action', 'bsc_capture_abandoned_cart');
    params.set('nonce', window.bsc_ajax?.nonce || '');
    params.set('email', email);
    params.set('name', [firstName, lastName].filter(Boolean).join(' '));
    params.set('phone', fieldValue('#billing_phone'));

    return params;
  }

  function captureNow() {
    if (!window.bsc_ajax?.ajax_url) return;

    var payload = buildPayload();
    if (!payload) return;

    var payloadString = payload.toString();
    if (payloadString === lastPayload) return;
    lastPayload = payloadString;

    window.fetch(window.bsc_ajax.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: payloadString,
    }).catch(function () {
      lastPayload = '';
    });
  }

  function queueCapture() {
    window.clearTimeout(timer);
    timer = window.setTimeout(captureNow, 900);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var checkout = document.querySelector('form[name="checkout"]');
    if (!checkout) return;

    checkout.addEventListener('input', function (event) {
      if (event.target && /^(billing_email|billing_phone|billing_first_name|billing_last_name)$/.test(event.target.name || '')) {
        queueCapture();
      }
    });

    checkout.addEventListener('change', function (event) {
      if (event.target && /^(billing_email|billing_phone|billing_first_name|billing_last_name)$/.test(event.target.name || '')) {
        queueCapture();
      }
    });

    document.body.addEventListener('updated_checkout', queueCapture);
    window.setTimeout(queueCapture, 1200);
  });

  window.bscCaptureAbandonedCart = function () {
    lastPayload = '';
    queueCapture();
  };
}());
