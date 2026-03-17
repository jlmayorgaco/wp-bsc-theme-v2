/**
 * BSC Newsletter subscription form handler.
 * Requires: bsc_ajax (ajax_url, nonce) localized via wp_localize_script.
 */
(function ($) {
  'use strict';

  $(document).ready(function () {
    var $form   = $('#bsc-newsletter-form');
    if (!$form.length) return;

    var $email   = $('#bsc-newsletter-email');
    var $submit  = $('#bsc-newsletter-submit');
    var $error   = $('#bsc-newsletter-error');
    var $success = $('#bsc-newsletter-success');
    var $msg     = $('#bsc-newsletter-success-msg');

    $form.on('submit', function (e) {
      e.preventDefault();

      var email = $email.val().trim();
      $error.hide().text('');

      if (!email) {
        $error.text('Por favor ingresa tu correo electrónico.').show();
        return;
      }

      // Basic email format check before sending
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        $error.text('Por favor ingresa un correo electrónico válido.').show();
        return;
      }

      $submit.prop('disabled', true).val('Enviando…');

      $.post(bsc_ajax.ajax_url, {
        action : 'bsc_newsletter_subscribe',
        email  : email,
        nonce  : bsc_ajax.nonce,
      })
      .done(function (res) {
        if (res && res.success) {
          $form.hide();
          $msg.text(res.data.message || '¡Suscripción exitosa!');
          $success.show();
        } else {
          var errMsg = (res && res.data && res.data.message)
            ? res.data.message
            : 'Hubo un error. Intenta nuevamente.';
          $error.text(errMsg).show();
          $submit.prop('disabled', false).val('¡Quiero Ser Parte !');
        }
      })
      .fail(function () {
        $error.text('Error de conexión. Por favor intenta nuevamente.').show();
        $submit.prop('disabled', false).val('¡Quiero Ser Parte !');
      });
    });
  });
})(jQuery);
