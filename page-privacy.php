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
      <h1 class="bsc__static-hero__title">POLÍTICAS DE PRIVACIDAD</h1>
      <p class="bsc__static-hero__subtitle"> </p>
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

          <p>
            Durante el proceso de compra en www.bubblesskincare.com solicitaremos algunos datos personales a nuestros clientes. BSC – Bubbles Skin Care informa que es responsable de la administración de dichos datos. Según nuestras políticas de tratamiento de datos personales, los mecanismos a través de los cuales hacemos uso de éstos son seguros y confidenciales, pues contamos con los medios tecnológicos idóneos para asegurar que sean almacenados para evitar el acceso indeseado por parte de terceras personas, y en ese mismo orden aseguramos la confidencialidad de los mismos.
Cuando compras algo de nuestra tienda online, como parte del proceso de compra venta, nosotros recolectamos la información personal que nos das tales como nombre, cédula, celular, dirección y correo electrónico. Usaremos esta información para realizar seguimiento de carritos abandonados, el procesamiento y entrega de los pedidos, enviar notificaciones acerca de pedidos abandonados, el estado de pedidos activos y para enviar información que pueda ser útil para ti o que hayas solicitado específicamente, incluida información sobre nuestros productos y servicios, a menos que nos hayas comunicado que te opones a ser contactado para estos fines.
Podemos compartir su información personal con terceros que nos prestan servicios, como procesadores de pagos y proveedores de servicios de envío.
También podemos compartir su información personal en respuesta a una orden judicial o solicitud gubernamental, o si creemos de buena fe que dicha divulgación es necesaria para cumplir con la ley o para proteger nuestros derechos, propiedad o seguridad, o la de otros.

          </p>

        </div>

      <?php endif; ?>

    </div>
  </section>

</main>

<?php get_footer(); ?>
