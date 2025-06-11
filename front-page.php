<?php
get_header();
?>

<main class="bsc bsc__page page-home">


    <section class="section home__swiper--section">
        <?php require_once get_template_directory() . '/components/swiper.php'; ?>
    </section>

    <section class="section home__products--section">
        <div class="section__container">
            <div class="products products--last-products">
              <h1 class="bsc__title"><strong>Últimos</strong> Lanzamientos K-Beauty</h1>

            </div>
            <div class="products products--favorites">
              <h1 class="bsc__title"><strong>Favoritos</strong> en BSC</h1>
            </div>
            <a class="bsc__button" href="/shop">¡Ver todos!</a>
        </div>
    </section>



  <section class="hero">
    <h1>Bienvenidos a Bubbles Skin Care</h1>
    <p>Explora nuestros productos y rutinas favoritas</p>
  </section>

  <section class="featured-products">
    <h2>Productos destacados</h2>
    <?php echo do_shortcode('[products limit="4" columns="4" visibility="featured"]'); ?>
  </section>
</main>

<?php get_footer(); ?>

