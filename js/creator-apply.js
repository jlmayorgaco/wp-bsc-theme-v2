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

    $form.on('submit', function (event) {
      event.preventDefault();
      $error.hide().text('');
      $button.prop('disabled', true).text('Enviando…');

      $.post(bsc_ajax.ajax_url, {
        action: 'bsc_creator_apply',
        nonce: bsc_ajax.nonce,
        nombre: $('#bc-name').val().trim(),
        email: $('#bc-email').val().trim(),
        instagram: $('#bc-instagram').val().trim(),
        tiktok: $('#bc-tiktok').val().trim(),
        mensaje: $('#bc-message').val().trim(),
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
          $error.text('Error de conexión. Por favor intenta nuevamente.').show();
          $button.prop('disabled', false).text('Enviar solicitud');
        });
    });
  });
})(jQuery);
