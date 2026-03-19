<?php
/**
 * Template Name: Preguntas Frecuentes
 * BSC: FAQ — Preguntas frecuentes de la tienda.
 */

get_header();
?>

<main class="bsc bsc__page page-faq">

  <section class="bsc__static-hero">
    <div class="bsc__static-hero__container">
      <h1 class="bsc__static-hero__title">Preguntas Frecuentes</h1>
      <p class="bsc__static-hero__subtitle">Resolvemos tus dudas más comunes</p>
    </div>
  </section>

  <section class="bsc__static-content">
    <div class="bsc__static-content__container">

      <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <div class="bsc__static-body">
          <?php the_content(); ?>
        </div>
      <?php endwhile; else : ?>

        <!-- Contenido base — editable desde WP Admin → Páginas → Preguntas Frecuentes -->
        <div class="bsc__static-body bsc__faq">

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">¿Realizan envíos a todo Colombia?</summary>
            <div class="bsc__faq-answer">
              <p>Sí, enviamos a todo el territorio colombiano. El tiempo de entrega varía entre 3 y 5 días hábiles dependiendo de tu ciudad.</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">¿Los productos son originales?</summary>
            <div class="bsc__faq-answer">
              <p>Todos nuestros productos son 100% originales y certificados. Trabajamos directamente con distribuidores autorizados de las marcas K-Beauty que ofrecemos.</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">¿Cómo sé qué productos son para mi tipo de piel?</summary>
            <div class="bsc__faq-answer">
              <p>En cada producto encontrarás la información de tipo de piel recomendado. También puedes escribirnos por WhatsApp y te asesoramos personalmente. 🌸</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">¿Puedo devolver un producto?</summary>
            <div class="bsc__faq-answer">
              <p>Sí, tienes 15 días calendario desde la fecha de recibo para solicitar una devolución, siempre que el producto esté sin usar y en su empaque original.
              Consulta nuestra <a href="/shipping-returns/">política de devoluciones</a> para más información.</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">¿Cómo puedo hacer un seguimiento de mi pedido?</summary>
            <div class="bsc__faq-answer">
              <p>Te enviamos un número de guía por correo electrónico una vez que tu pedido es despachado. También puedes consultar el estado en <a href="/mi-cuenta/orders/">Mis Pedidos</a>.</p>
            </div>
          </details>

          <details class="bsc__faq-item">
            <summary class="bsc__faq-question">¿Tienen envío gratis?</summary>
            <div class="bsc__faq-answer">
              <p>Sí. Los pedidos superiores a <strong>$150.000 COP</strong> tienen envío gratis a cualquier ciudad de Colombia.</p>
            </div>
          </details>

        </div>

      <?php endif; ?>

    </div>
  </section>

</main>

<?php get_footer(); ?>
