<?php
/**
 * Template Name: Página Mi Cuenta Personalizada
 */

get_header();
?>

<main class="bsc bsc__page bsc__page--account">
  <div class="bsc__container bsc__account-container">

    <div class="bsc__account-wrapper">
      <?php
        // Renderiza contenido de WooCommerce My Account (incluye dashboard, pedidos, detalles, etc.)
        echo do_shortcode('[woocommerce_my_account]');
      ?>
    </div>

  </div>
</main>

<script>
/* BSC-020: scroll to content when navigating my-account sub-pages */
(function () {
  var path = window.location.pathname;
  var isDashboard = /\/mi-cuenta\/?$/.test(path);
  if (isDashboard) return;

  var $content = document.querySelector('.woocommerce-MyAccount-content');
  if (!$content) return;

  setTimeout(function () {
    var top = $content.getBoundingClientRect().top + window.scrollY - 80;
    window.scrollTo({ top: top, behavior: 'smooth' });
  }, 150);
})();
</script>
<?php get_footer(); ?>
