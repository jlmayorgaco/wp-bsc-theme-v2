<?php
/**
 * Template Name: BSC Shop Landing (K-Beauty Paraíso)
 * Description: Landing hero + 6 main K-Beauty groups.
 */

get_header(); ?>

<div class="bsc bsc__shop">
  <div class="bsc__container">

    <nav class="bsc__shop-nav">
      <a class="active">K-Beauty</a>
    </nav>

    <!-- 🌈 Header block -->
    <section class="bsc-hero">
      <div class="bsc-hero__icon">
        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc_rainbow.png"
             alt="K-Beauty rainbow icon" loading="lazy">
      </div>
      <h2 class="bsc-hero__title">Paraíso de <strong>K-Beauty</strong></h2>

      <p class="bsc-hero__quote">
        “Hace más de 15 años probé mi primer producto coreano y desde entonces quedé completamente enamorada del K-Beauty.
        Con los años seguí explorando este universo: probando nuevas fórmulas, aprendiendo de las tendencias y viajando a Corea
        para conocer de cerca su increíble tecnología. Así nació BSC: escuchando a nuestra comunidad,
        soñando con un espacio donde el K-Beauty se sintiera cercano, real y confiable. <strong>Bubbles es literalmente un paraíso K-Beauty:
        aquí no solo encuentras marcas cuidadosamente seleccionadas con los más altos estándares coreanos,
        también te ayudamos a crear una rutina efectiva, personalizada y pensada para tu piel :)</strong> ”
      </p>
      <p class="bsc-hero__author">
        Male
        <img 
            src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/bsc_icon_white_heart.png" 
            alt="Corazones BSC" 
            width="50" 
            decoding="async"
        />
      </p>

      <div class="bsc-hero__divider"></div>
      <h3 class="bsc-hero__subtitle bsc__title">
        <strong>Bienvenido</strong> al paraíso del K-Beauty <strong>Bubble lover</strong> !
      </h3>
    </section>

    <?php
    // 🌸 Category showcase (6 blocks)
    $groups = [
        [
            'slug'  => 'group-skin-care',
            'title' => 'SKIN CARE',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text' => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y mas para una piel saludable todos los días !'
        ],
        [
            'slug'  => 'group-hair-care',
            'title' => 'HAIR CARE',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/2PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text' => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y mas para una piel saludable todos los días !'
        ],
        [
            'slug'  => 'group-make-up',
            'title' => 'MAKE UP',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/3PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text' => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y mas para una piel saludable todos los días !'

        ]
        /*,
        [
            'slug'  => 'dispositivos',
            'title' => 'DISPOSITIVOS',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/4PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text' => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y mas para una piel saludable todos los días !'

        ],
        [
            'slug'  => 'inner-beauty',
            'title' => 'INNER BEAUTY',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/5PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text' => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y mas para una piel saludable todos los días !'

        ],
        [
            'slug'  => 'spa-kbeauty',
            'title' => 'SPA KBEAUTY',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/6PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text' => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y mas para una piel saludable todos los días !'

        ],
        */
    ];
    ?>

    <section class="bsc-kb-grid">
      <?php foreach ( $groups as $g ) :
        $base = trailingslashit( home_url( '/product-category/' ) );
        $term_link = $base .$g['slug'] . '/';
      ?>
        <article class="bsc-kb-card">
          <a href="<?php echo esc_url( $term_link ); ?>" class="bsc-kb-card__link">
            <div class="bsc-kb-card__imgwrap">
        
                <div class="bsc-kb-card__back">
                    <img src="<?php echo esc_url( $g['image'] ); ?>" alt="<?php echo esc_attr( $g['title'] ); ?>" loading="lazy">
                </div> 

                <div class="bsc-kb-card__front">
                    <img class="bsc-kb-card__icon" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/shop/hear_icon.png" alt="" width="25px">
                    <p class="bsc-kb-text">
                        <? echo $g['text']; ?>
                    </p>
                </div>
            </div>
            <div class="bsc-kb-card__label">
              <?php echo esc_html( $g['title'] ); ?>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </section>

  </div>
</div>

<?php get_footer(); ?>
