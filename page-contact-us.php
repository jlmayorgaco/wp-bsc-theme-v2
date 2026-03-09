<?php
/**
 * Template Name: Contacto
 * BSC: Página de contacto.
 */

get_header();
?>

<main class="bsc bsc__page page-contact-us">

  <section class="bsc__static-hero">
    <div class="bsc__static-hero__container">
      <h1 class="bsc__static-hero__title">Contacto</h1>
      <p class="bsc__static-hero__subtitle">Estamos aquí para ayudarte 🌸</p>
    </div>
  </section>

  <section class="bsc__contact-section">
    <div class="bsc__contact-container">

      <div class="bsc__contact-cols">

        <div class="bsc__contact-col">
          <h2 class="bsc__contact-heading">Escríbenos</h2>
          <p class="bsc__contact-text">Puedes contactarnos directamente por WhatsApp o por correo y te respondemos lo antes posible.</p>

          <div class="bsc__contact-items">

            <a
              class="bsc__contact-item"
              href="https://api.whatsapp.com/send?phone=573156922859&text=Hola%20BSC%2C%20me%20gustar%C3%ADa%20tener%20m%C3%A1s%20informaci%C3%B3n."
              target="_blank"
              rel="noopener noreferrer"
            >
              <span class="bsc__contact-item__icon"><i class="fab fa-whatsapp"></i></span>
              <span class="bsc__contact-item__label">WhatsApp</span>
              <span class="bsc__contact-item__value">+57 315 692 2859</span>
            </a>

            <a
              class="bsc__contact-item"
              href="https://www.instagram.com/bubbles.skincare/"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span class="bsc__contact-item__icon"><i class="fab fa-instagram"></i></span>
              <span class="bsc__contact-item__label">Instagram</span>
              <span class="bsc__contact-item__value">@bubbles.skincare</span>
            </a>

            <a
              class="bsc__contact-item"
              href="https://www.tiktok.com/@bubblesskincare"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span class="bsc__contact-item__icon"><i class="fab fa-tiktok"></i></span>
              <span class="bsc__contact-item__label">TikTok</span>
              <span class="bsc__contact-item__value">@bubblesskincare</span>
            </a>

          </div>
        </div>

        <div class="bsc__contact-col bsc__contact-col--image">
          <img
            src="<?php echo get_template_directory_uri(); ?>/images/bsc_contact_final_image.png"
            alt="Contacto Bubbles Skin Care"
            class="bsc__contact-image"
          />
        </div>

      </div>

    </div>
  </section>

</main>

<style>
.page-contact-us .bsc__contact-section {
  background-color: var(--color-bg-contact, #C1DFE9);
  padding: 4rem 1.5rem;
}
.page-contact-us .bsc__contact-container {
  max-width: 1100px;
  margin: 0 auto;
}
.page-contact-us .bsc__contact-cols {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 3rem;
  flex-wrap: wrap;
}
.page-contact-us .bsc__contact-col {
  flex: 1 1 300px;
}
.page-contact-us .bsc__contact-heading {
  font-family: Helvetica, sans-serif;
  font-size: 2rem;
  font-weight: 700;
  color: var(--color--text, #333);
  margin-bottom: 0.75rem;
}
.page-contact-us .bsc__contact-text {
  font-size: 16px;
  color: var(--color--text, #333);
  margin-bottom: 2rem;
  line-height: 1.6;
}
.page-contact-us .bsc__contact-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.page-contact-us .bsc__contact-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: #fff;
  border-radius: 12px;
  text-decoration: none;
  color: var(--color--text, #333);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.page-contact-us .bsc__contact-item:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
.page-contact-us .bsc__contact-item__icon {
  font-size: 1.5rem;
  width: 2rem;
  text-align: center;
  color: var(--color--main, #F7C0CD);
}
.page-contact-us .bsc__contact-item__label {
  font-weight: 700;
  font-size: 15px;
  min-width: 90px;
}
.page-contact-us .bsc__contact-item__value {
  font-size: 15px;
  color: #555;
}
.page-contact-us .bsc__contact-col--image {
  display: flex;
  justify-content: center;
}
.page-contact-us .bsc__contact-image {
  max-width: 340px;
  width: 100%;
  border-radius: 16px;
}
@media (max-width: 768px) {
  .page-contact-us .bsc__contact-col--image {
    display: none;
  }
}
</style>

<?php get_footer(); ?>
