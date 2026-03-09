<?php
/**
 * Template Name: Envíos y Devoluciones
 * BSC: Política de envíos y devoluciones.
 */

get_header();
?>

<main class="bsc bsc__page page-shipping-returns">

  <section class="bsc__static-hero">
    <div class="bsc__static-hero__container">
      <h1 class="bsc__static-hero__title">Envíos y Devoluciones</h1>
      <p class="bsc__static-hero__subtitle">Todo lo que necesitas saber sobre tus pedidos</p>
    </div>
  </section>

  <section class="bsc__static-content">
    <div class="bsc__static-content__container">

      <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <div class="bsc__static-body">
          <?php the_content(); ?>
        </div>
      <?php endwhile; else : ?>

        <!-- Contenido base — editable desde WP Admin → Páginas → Envíos y Devoluciones -->
        <div class="bsc__static-body">

          <div class="bsc__info-block">
            <h2>📦 Envíos</h2>
            <ul>
              <li>Realizamos envíos a todo Colombia.</li>
              <li>El tiempo de entrega es de <strong>3 a 5 días hábiles</strong> para ciudades principales.</li>
              <li>Para pedidos superiores a <strong>$150.000 COP</strong>, el envío es gratis.</li>
              <li>Recibirás un número de guía por correo electrónico una vez despachado tu pedido.</li>
            </ul>
          </div>

          <div class="bsc__info-block">
            <h2>🔄 Devoluciones</h2>
            <ul>
              <li>Tienes <strong>15 días calendario</strong> desde la fecha de recibo para solicitar una devolución.</li>
              <li>El producto debe estar sin usar y en su empaque original.</li>
              <li>Para iniciar una devolución escríbenos al WhatsApp o al correo.</li>
            </ul>
          </div>

          <div class="bsc__info-block">
            <h2>📬 Contáctanos</h2>
            <p>
              Si tienes alguna duda sobre tu pedido, escríbenos por
              <a href="https://api.whatsapp.com/send?phone=573156922859" target="_blank" rel="noopener noreferrer">WhatsApp</a>
              y te ayudamos con gusto. 🌸
            </p>
          </div>

        </div>

      <?php endif; ?>

    </div>
  </section>

</main>

<?php get_footer(); ?>
