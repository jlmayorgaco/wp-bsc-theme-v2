<?php
/**
 * Template Name: Preguntas Frecuentes
 * BSC: FAQ â€” Preguntas frecuentes de la tienda.
 */

get_header();
?>

<main class="bsc bsc__page page-faq">

  <section class="bsc__static-hero">
    <div class="bsc__static-hero__container">
      <h1 class="bsc__static-hero__title">Preguntas Frecuentes</h1>
      <p class="bsc__static-hero__subtitle">Resolvemos tus dudas mÃ¡s comunes</p>
    </div>
  </section>

  <section class="bsc__static-content">
    <div class="bsc__static-content__container">

      <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <div class="bsc__static-body">
          <?php the_content(); ?>
        </div>
      <?php endwhile; else : ?>

        <!-- Contenido base â€” editable desde WP Admin â†’ PÃ¡ginas â†’ Preguntas Frecuentes -->
        <div class="bsc__static-body bsc__faq">

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">Â¿Realizan envÃ­os a todo Colombia?</summary>
            <div class="bsc__faq-answer">
              <p>SÃ­, enviamos a todo el territorio colombiano. El tiempo de entrega varÃ­a entre 3 y 5 dÃ­as hÃ¡biles dependiendo de tu ciudad.</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">Â¿Los productos son originales?</summary>
            <div class="bsc__faq-answer">
              <p>Todos nuestros productos son 100% originales y certificados. Trabajamos directamente con distribuidores autorizados de las marcas K-Beauty que ofrecemos.</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">Â¿CÃ³mo sÃ© quÃ© productos son para mi tipo de piel?</summary>
            <div class="bsc__faq-answer">
              <p>En cada producto encontrarÃ¡s la informaciÃ³n de tipo de piel recomendado. TambiÃ©n puedes escribirnos por WhatsApp y te asesoramos personalmente. ðŸŒ¸</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">Â¿Puedo devolver un producto?</summary>
            <div class="bsc__faq-answer">
              <p>SÃ­, tienes 15 dÃ­as calendario desde la fecha de recibo para solicitar una devoluciÃ³n, siempre que el producto estÃ© sin usar y en su empaque original.
              Consulta nuestra <a href="<?php echo esc_url( bsc_get_shipping_returns_url() ); ?>">polÃ­tica de devoluciones</a> para mÃ¡s informaciÃ³n.</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">Â¿CÃ³mo puedo hacer un seguimiento de mi pedido?</summary>
            <div class="bsc__faq-answer">
              <p>Te enviamos un nÃºmero de guÃ­a por correo electrÃ³nico una vez que tu pedido es despachado. TambiÃ©n puedes consultar el estado en <a href="<?php echo esc_url( bsc_get_account_orders_url() ); ?>">Mis Pedidos</a>.</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">Â¿Tienen envÃ­o gratis?</summary>
            <div class="bsc__faq-answer">
              <p>SÃ­. Los pedidos superiores a <strong>$150.000 COP</strong> tienen envÃ­o gratis a cualquier ciudad de Colombia.</p>
            </div>
          </details>

        </div>

      <?php endif; ?>

    </div>
  </section>

</main>

<?php get_footer(); ?>

