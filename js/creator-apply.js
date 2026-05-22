(function ($) {
  'use strict';

  $(document).ready(function () {
    var $form = $('#bc-creator-form');

    if (!$form.length || typeof bsc_ajax === 'undefined') {
      return;
    }

    var $button = $('#bc-form-submit');
    var $error = $('#bc-form-error');
    var $success = $('#bc-form-success');
    var $successMessage = $('#bc-form-success-msg');
    var socialLinkRules = {
      instagram: {
        host: 'instagram.com',
        message: 'Por favor ingresa un link valido de Instagram.',
      },
      tiktok: {
        host: 'tiktok.com',
        message: 'Por favor ingresa un link valido de TikTok.',
      },
    };

    function isPlatformUrl(value, expectedHost) {
      var parsedUrl;
      var host;

      try {
        parsedUrl = new URL(value);
      } catch (error) {
        return false;
      }

      host = parsedUrl.hostname.toLowerCase();

      return (
        (parsedUrl.protocol === 'https:' || parsedUrl.protocol === 'http:') &&
        (host === expectedHost || host.slice(-expectedHost.length - 1) === '.' + expectedHost)
      );
    }

    $form.on('submit', function (event) {
      event.preventDefault();
      $error.hide().text('');

      var fields = {
        nombre: $('#bc-name').val().trim(),
        email: $('#bc-email').val().trim(),
        instagram: $('#bc-instagram').val().trim(),
        tiktok: $('#bc-tiktok').val().trim(),
        mensaje: $('#bc-message').val().trim(),
      };
      var $firstEmptyField = $form
        .find('input[required], textarea[required]')
        .filter(function () {
          return !$(this).val().trim();
        })
        .first();

      if ($firstEmptyField.length) {
        $error.text('Por favor completa todos los campos requeridos.').show();
        $firstEmptyField.trigger('focus');
        return;
      }

      if (!isPlatformUrl(fields.instagram, socialLinkRules.instagram.host)) {
        $error.text(socialLinkRules.instagram.message).show();
        $('#bc-instagram').trigger('focus');
        return;
      }

      if (!isPlatformUrl(fields.tiktok, socialLinkRules.tiktok.host)) {
        $error.text(socialLinkRules.tiktok.message).show();
        $('#bc-tiktok').trigger('focus');
        return;
      }

      $button.prop('disabled', true).text('Enviando...');

      $.post(bsc_ajax.ajax_url, {
        action: 'bsc_creator_apply',
        nonce: bsc_ajax.nonce,
        nombre: fields.nombre,
        email: fields.email,
        instagram: fields.instagram,
        tiktok: fields.tiktok,
        mensaje: fields.mensaje,
      })
        .done(function (response) {
          if (response && response.success) {
            $form.hide();
            $successMessage.text(response.data.message);
            $success.show();
            return;
          }

          $error
            .text(
              response && response.data && response.data.message
                ? response.data.message
                : 'Hubo un error. Intenta nuevamente.'
            )
            .show();
          $button.prop('disabled', false).text('Enviar solicitud');
        })
        .fail(function () {
          $error.text('Error de conexion. Por favor intenta nuevamente.').show();
          $button.prop('disabled', false).text('Enviar solicitud');
        });
    });
  });
})(jQuery);
