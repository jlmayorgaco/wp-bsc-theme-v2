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
                href="https://api.whatsapp.com/send?phone=573156922859&text=Hola%20BSC%2C%20me%20gustar%C3%ADa%20tener%20m%C3%A1s%20informaci%C3%B3n."
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

<?php get_footer(); ?>