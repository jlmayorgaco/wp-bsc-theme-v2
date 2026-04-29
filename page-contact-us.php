<?php
/**
 * Template Name: Contacto
 * BSC: Página de contacto.
 */

get_header();
?>

<main class="bsc bsc__page page-contact-us">

  <section class="bsc__static-hero bsc__static-hero--contact">
    <div class="bsc__static-hero__container">
      <div class="bsc__static-hero__hearts" aria-hidden="true">
        <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc_icon_white_heart.png" alt="">
        <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc_icon_white_heart.png" alt="">
        <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc_icon_white_heart.png" alt="">
      </div>

      <h1 class="bsc__static-hero__title">Contacto</h1>
      <p class="bsc__static-hero__subtitle">Estamos aquí para ayudarte 🌸</p>
    </div>
  </section>

  <section class="bsc__contact-section">
    <div class="bsc__contact-container">

      <div class="bsc__contact-shell">
        <div class="bsc__contact-cols">

          <div class="bsc__contact-col bsc__contact-col--content">
            <span class="bsc__contact-eyebrow">Bubble Skin Care</span>

            <h2 class="bsc__contact-heading">Escríbenos</h2>

            <p class="bsc__contact-text">
              Si tienes dudas sobre productos, pedidos, rutinas o colaboraciones,
              puedes escribirnos por WhatsApp o nuestras redes sociales.
              Te responderemos lo antes posible.
            </p>

            <div class="bsc__contact-items">

              <a
                class="bsc__contact-item"
                href="<?php echo esc_url( bsc_get_whatsapp_url( 'support' ) ); ?>"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Escribir por WhatsApp a Bubble Skin Care"
              >
                <span class="bsc__contact-item__icon">
                  <i class="fab fa-whatsapp" aria-hidden="true"></i>
                </span>

                <span class="bsc__contact-item__content">
                  <span class="bsc__contact-item__label">WhatsApp</span>
                  <span class="bsc__contact-item__value">+57 315 692 2859</span>
                </span>

                <span class="bsc__contact-item__arrow" aria-hidden="true">↗</span>
              </a>

              <a
                class="bsc__contact-item"
                href="https://www.instagram.com/bubbles.skincare/"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Ir al Instagram de Bubble Skin Care"
              >
                <span class="bsc__contact-item__icon">
                  <i class="fab fa-instagram" aria-hidden="true"></i>
                </span>

                <span class="bsc__contact-item__content">
                  <span class="bsc__contact-item__label">Instagram</span>
                  <span class="bsc__contact-item__value">@bubbles.skincare</span>
                </span>

                <span class="bsc__contact-item__arrow" aria-hidden="true">↗</span>
              </a>

              <a
                class="bsc__contact-item"
                href="https://www.tiktok.com/@bubblesskincare"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Ir al TikTok de Bubble Skin Care"
              >
                <span class="bsc__contact-item__icon">
                  <i class="fab fa-tiktok" aria-hidden="true"></i>
                </span>

                <span class="bsc__contact-item__content">
                  <span class="bsc__contact-item__label">TikTok</span>
                  <span class="bsc__contact-item__value">@bubblesskincare</span>
                </span>

                <span class="bsc__contact-item__arrow" aria-hidden="true">↗</span>
              </a>

            </div>

            <div class="bsc__contact-actions">
              <a class="bsc__contact-btn" href="/shop/">Visitar tienda</a>
            </div>

            <!-- BSC-008: contact form -->
            <div class="bsc__contact-form-wrap">
              <h3 class="bsc__contact-form-heading">Envíanos un mensaje</h3>
              <form id="bsc-contact-form" class="bsc__contact-form" novalidate>
                <div class="bsc__contact-field">
                  <label for="bsc-contact-name">Nombre</label>
                  <input type="text" id="bsc-contact-name" name="bsc_name" required placeholder="Tu nombre" autocomplete="name">
                </div>
                <div class="bsc__contact-field">
                  <label for="bsc-contact-email">Correo electrónico</label>
                  <input type="email" id="bsc-contact-email" name="bsc_email" required placeholder="tucorreo@ejemplo.com" autocomplete="email">
                </div>
                <div class="bsc__contact-field">
                  <label for="bsc-contact-message">Mensaje</label>
                  <textarea id="bsc-contact-message" name="bsc_message" required placeholder="¿En qué podemos ayudarte?" rows="4"></textarea>
                </div>
                <div id="bsc-contact-notice" class="bsc__contact-notice" aria-live="polite"></div>
                <button type="submit" id="bsc-contact-submit" class="bsc__button bsc__contact-submit">Enviar mensaje</button>
              </form>
            </div>
          </div>

          <div class="bsc__contact-col bsc__contact-col--image">
            <div class="bsc__contact-image-wrap">
              <img
                src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc_contact_final_image.png"
                alt="Contacto Bubbles Skin Care"
                class="bsc__contact-image"
              />
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>

</main>

<script>
(function($){
  var $form   = $('#bsc-contact-form');
  var $submit = $('#bsc-contact-submit');
  var $notice = $('#bsc-contact-notice');

  function showNotice(msg, isSuccess) {
    $notice
      .removeClass('bsc__contact-notice--success bsc__contact-notice--error')
      .addClass(isSuccess ? 'bsc__contact-notice--success' : 'bsc__contact-notice--error')
      .text(msg)
      .show();
  }

  $form.on('submit', function(e) {
    e.preventDefault();
    $notice.hide();
    $submit.prop('disabled', true).text('Enviando…');

    $.post(bsc_ajax.ajax_url, {
      action      : 'bsc_contact_form_submit',
      nonce       : bsc_ajax.nonce,
      bsc_name    : $('#bsc-contact-name').val(),
      bsc_email   : $('#bsc-contact-email').val(),
      bsc_message : $('#bsc-contact-message').val(),
    }).done(function(res) {
      if (res && res.success) {
        $form[0].reset();
        showNotice(res.data.message, true);
      } else {
        var msg = (res && res.data && res.data.message) ? res.data.message : 'Error al enviar. Intenta nuevamente.';
        showNotice(msg, false);
      }
    }).fail(function() {
      showNotice('Error de conexión. Por favor intenta nuevamente.', false);
    }).always(function() {
      $submit.prop('disabled', false).text('Enviar mensaje');
    });
  });
})(jQuery);
</script>

<?php get_footer(); ?>
