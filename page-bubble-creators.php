<?php
/**
 * Template Name: Bubble Creators
 * BSC-018: Landing page para creadores de contenido / influencers.
 */

get_header();
?>

<main class="bsc bsc__page page-bubble-creators">

  <!-- ─── HERO ─────────────────────────────────────────────── -->
  <section class="bc__hero">
    <div class="bc__hero-content">
      <div class="bc__hero-hearts">
        <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_icon_white_heart.png" alt="">
        <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_icon_white_heart.png" alt="">
        <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_icon_white_heart.png" alt="">
      </div>
      <h1 class="bc__hero-title">Bubble Creators</h1>
      <p class="bc__hero-subtitle">
        El programa de colaboración de <strong>Bubble Skin Care</strong> para creadores de contenido y embajadoras de la marca.
      </p>
      <a href="#bc-form" class="bc__cta-btn">Quiero ser Bubble Creator</a>
    </div>
    <div class="bc__hero-image">
      <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_contact_final_image.png" alt="Bubble Creators">
    </div>
  </section>

  <!-- ─── DESCRIPCIÓN ──────────────────────────────────────── -->
  <section class="bc__about">
    <div class="bc__about-container">
      <h2 class="bsc__title"><strong>¿Qué es</strong> Bubble Creators?</h2>
      <div class="bc__about-cards">

        <div class="bc__about-card">
          <img class="bc__about-icon" src="<?php echo get_template_directory_uri(); ?>/images/bsc_home_about_icon1.png" alt="">
          <h3 class="bc__about-card-title">Contenido auténtico</h3>
          <p class="bc__about-card-text">Crea contenido real con productos K-Beauty que amamos y que tú ya conoces.</p>
        </div>

        <div class="bc__about-card">
          <img class="bc__about-icon" src="<?php echo get_template_directory_uri(); ?>/images/bsc_home_about_icon2.png" alt="">
          <h3 class="bc__about-card-title">Colaboración con la marca</h3>
          <p class="bc__about-card-text">Trabaja directamente con el equipo de BSC en campañas, lanzamientos y experiencias exclusivas.</p>
        </div>

        <div class="bc__about-card">
          <img class="bc__about-icon" src="<?php echo get_template_directory_uri(); ?>/images/bsc_home_about_icon3.png" alt="">
          <h3 class="bc__about-card-title">Beneficios reales</h3>
          <p class="bc__about-card-text">Accede a productos, descuentos y comisiones especiales para creadores seleccionados.</p>
        </div>

      </div>
    </div>
  </section>

  <!-- ─── TEXTO CENTRAL ────────────────────────────────────── -->
  <section class="bc__pitch">
    <div class="bc__pitch-container">
      <p class="bc__pitch-text">
        ¿Te gustaría crear contenido con nuestros productos y colaborar con Bubble Skin Care?<br>
        <strong>Queremos conocer creadoras e influencers alineadas con nuestra marca.</strong><br><br>
        Si tienes una comunidad, creas contenido de skincare o K-Beauty, y te identificas con los valores de BSC,
        este programa es para ti.
      </p>
      <img class="bc__pitch-rainbow" src="<?php echo get_template_directory_uri(); ?>/images/bsc_rainbow.png" alt="">
    </div>
  </section>

  <!-- ─── FORMULARIO ───────────────────────────────────────── -->
  <section class="bc__form-section" id="bc-form">
    <div class="bc__form-container">
      <h2 class="bsc__title"><strong>Regístrate</strong> como Creator</h2>
      <p class="bc__form-description">
        Completa el formulario y el equipo de BSC se pondrá en contacto contigo.
      </p>

      <form class="bc__form" id="bc-creator-form" action="#">
        <div class="bc__form-row">
          <div class="bc__form-field">
            <label for="bc-name">Nombre completo</label>
            <input type="text" id="bc-name" name="nombre" placeholder="Tu nombre" required>
          </div>
          <div class="bc__form-field">
            <label for="bc-email">Correo electrónico</label>
            <input type="email" id="bc-email" name="email" placeholder="tu@correo.com" required>
          </div>
        </div>
        <div class="bc__form-row">
          <div class="bc__form-field">
            <label for="bc-instagram">Instagram</label>
            <input type="text" id="bc-instagram" name="instagram" placeholder="@tu_usuario">
          </div>
          <div class="bc__form-field">
            <label for="bc-tiktok">TikTok</label>
            <input type="text" id="bc-tiktok" name="tiktok" placeholder="@tu_usuario">
          </div>
        </div>
        <div class="bc__form-field bc__form-field--full">
          <label for="bc-message">Cuéntanos sobre ti y tu contenido</label>
          <textarea id="bc-message" name="mensaje" rows="4" placeholder="¿Qué tipo de contenido creas? ¿Por qué quieres colaborar con BSC?"></textarea>
        </div>

        <p class="bc__form-feedback bc__form-feedback--error" id="bc-form-error" style="display:none;"></p>

        <button type="submit" class="bc__form-submit" id="bc-form-submit">Enviar solicitud</button>
      </form>

      <div class="bc__form-success" id="bc-form-success" style="display:none;">
        <div class="bc__form-success-inner">
          <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_icon_white_heart.png" alt="">
          <p id="bc-form-success-msg"></p>
        </div>
      </div>
    </div>
  </section>

</main>

<script>
(function($){
  $('#bc-creator-form').on('submit', function(e) {
    e.preventDefault();
    var $btn   = $('#bc-form-submit');
    var $error = $('#bc-form-error');

    $error.hide().text('');
    $btn.prop('disabled', true).text('Enviando…');

    $.post(bsc_ajax.ajax_url, {
      action:    'bsc_creator_apply',
      nonce:     bsc_ajax.nonce,
      nombre:    $('#bc-name').val().trim(),
      email:     $('#bc-email').val().trim(),
      instagram: $('#bc-instagram').val().trim(),
      tiktok:    $('#bc-tiktok').val().trim(),
      mensaje:   $('#bc-message').val().trim()
    }).done(function(res) {
      if (res.success) {
        $('#bc-creator-form').hide();
        $('#bc-form-success-msg').text(res.data.message);
        $('#bc-form-success').show();
      } else {
        $error.text(res.data.message || 'Hubo un error. Intenta nuevamente.').show();
        $btn.prop('disabled', false).text('Enviar solicitud');
      }
    }).fail(function() {
      $error.text('Error de conexión. Por favor intenta nuevamente.').show();
      $btn.prop('disabled', false).text('Enviar solicitud');
    });
  });
})(jQuery);
</script>

<?php get_footer(); ?>
