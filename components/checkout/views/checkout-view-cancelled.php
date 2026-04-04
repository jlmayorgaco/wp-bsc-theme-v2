<?php
defined('ABSPATH') || exit;
?>
<main class="bsc bsc__page bsc__page--cancelled">
  <div class="bsc__container bsc__container--centered">
    <img class="bsc__empty-logo" alt="" src="<?php echo esc_url(get_template_directory_uri()); ?>/images/bsc_image_empty_cart.png">
    <h1 class="bsc__title">Pedido <strong>cancelado</strong></h1>
    <p class="bsc__subtitle">Tu pedido fue cancelado. Si crees que esto es un error, contáctanos por WhatsApp.</p>
    <div class="bsc__cancelled-actions">
      <a class="bsc__button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Volver a la tienda</a>
      <a class="bsc__button bsc__button--secondary" target="_blank" rel="noopener noreferrer"
         href="<?php echo esc_url( bsc_get_whatsapp_url( 'order' ) ); ?>">
        Contactar por WhatsApp
      </a>
    </div>
  </div>
</main>
