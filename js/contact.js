(function ($) {
  'use strict';

  $(document).ready(function () {
    var $form = $('#bsc-contact-form');

    if (!$form.length || typeof bsc_ajax === 'undefined') {
      return;
    }

    var $submit = $('#bsc-contact-submit');
    var $notice = $('#bsc-contact-notice');

    function showNotice(message, isSuccess) {
      $notice
        .removeClass('bsc__contact-notice--success bsc__contact-notice--error')
        .addClass(isSuccess ? 'bsc__contact-notice--success' : 'bsc__contact-notice--error')
        .text(message)
        .show();
    }

    $form.on('submit', function (event) {
      event.preventDefault();
      $notice.hide();
      $submit.prop('disabled', true).text('Enviando…');

      $.post(bsc_ajax.ajax_url, {
        action: 'bsc_contact_form_submit',
        nonce: bsc_ajax.nonce,
        bsc_name: $('#bsc-contact-name').val(),
        bsc_email: $('#bsc-contact-email').val(),
        bsc_message: $('#bsc-contact-message').val(),
      })
        .done(function (response) {
          if (response && response.success) {
            $form[0].reset();
            showNotice(response.data.message, true);
            return;
          }

          var message =
            response && response.data && response.data.message
              ? response.data.message
              : 'Error al enviar. Intenta nuevamente.';

          showNotice(message, false);
        })
        .fail(function () {
          showNotice('Error de conexión. Por favor intenta nuevamente.', false);
        })
        .always(function () {
          $submit.prop('disabled', false).text('Enviar mensaje');
        });
    });
  });
})(jQuery);
