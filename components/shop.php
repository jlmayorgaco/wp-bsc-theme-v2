
<?php
// BSC-013: "3 niñas" — main category showcase cards (Skin Care, Hair Care, Make Up)
$bsc_shop_groups = [
    [
        'slug'  => 'group-skin-care',
        'title' => 'SKIN CARE',
        'image' => get_template_directory_uri() . '/images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
        'text'  => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y más para una piel saludable todos los días.',
    ],
    [
        'slug'  => 'group-hair-care',
        'title' => 'HAIR CARE',
        'image' => get_template_directory_uri() . '/images/shop/2PAG_INTERNAR_IMAGENES_WEB.jpg',
        'text'  => 'Cuida tu cabello con la <strong>tecnología coreana</strong>: champús, acondicionadores, tratamientos y más para un cabello sano y brillante.',
    ],
    [
        'slug'  => 'group-make-up',
        'title' => 'MAKE UP',
        'image' => get_template_directory_uri() . '/images/shop/3PAG_INTERNAR_IMAGENES_WEB.jpg',
        'text'  => 'El <strong>K-Beauty make up</strong> que cuida mientras embellece: bases, tintes, labiales y mucho más.',
    ],
];
?>

<section class="section bsc-kb-grid bsc-kb-grid--home bsc-kb-grid--shop">
    <?php foreach ( $bsc_shop_groups as $g ) :
        $term_link = esc_url( trailingslashit( home_url( '/product-category/' ) ) . $g['slug'] . '/' );
    ?>
        <article class="bsc-kb-card">
            <a href="<?php echo $term_link; ?>" class="bsc-kb-card__link">
                <div class="bsc-kb-card__imgwrap">
                    <div class="bsc-kb-card__back">
                        <img
                            src="<?php echo esc_url( $g['image'] ); ?>"
                            alt="<?php echo esc_attr( $g['title'] ); ?>"
                            loading="lazy"
                            width="600"
                            height="480"
                        >
                    </div>
                    <div class="bsc-kb-card__front">
                        <img
                            class="bsc-kb-card__icon"
                            src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/shop/hear_icon.png"
                            alt=""
                            width="25"
                            height="25"
                        >
                        <p class="bsc-kb-text">
                            <?php echo wp_kses( $g['text'], [ 'strong' => [] ] ); ?>
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

<div class="custom-shop-wrapper" style="padding: 2rem; max-width: 1200px; margin: auto;">
  <h1 class="shop-title" style="font-size: 2.5rem; margin-bottom: 2rem;">Nuestra tienda Bubbles</h1>

  <p style="margin-bottom: 3rem;">Explora nuestros productos por categoría. Encuentra la rutina ideal para tu tipo de piel y cabello.</p>

<?php
$group_slugs = ['group-skin-care', 'group-hair-care', 'group-make-up'];

foreach ( $group_slugs as $group_slug ) {
  $group = get_term_by('slug', $group_slug, 'product_cat');

  if ( ! $group || is_wp_error($group) ) continue;

  echo '<h2 style="margin-top: 3rem; font-size: 1.8rem;">✨ ' . esc_html($group->name) . '</h2>';

  // Categorías hijas del grupo (nivel 1)
  $subcategories = get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'parent'     => $group->term_id,
  ]);

  if ( ! empty($subcategories) && ! is_wp_error($subcategories) ) {

    foreach ( $subcategories as $subcategory ) {
      echo '<h3 style="margin: 2rem 0 1rem 0; font-size: 1.3rem;">' . esc_html($subcategory->name) . '</h3>';

      // Sub-subcategorías del nivel 2
      $subsubcategories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $subcategory->term_id,
      ]);

      if ( ! empty($subsubcategories) && ! is_wp_error($subsubcategories) ) {
        echo '<div class="bsc-subcat-grid" style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">';

        foreach ( $subsubcategories as $subsubcat ) {
          $thumbnail_id = get_term_meta( $subsubcat->term_id, 'thumbnail_id', true );
          $image_url = wp_get_attachment_url( $thumbnail_id );
          $link = get_term_link( $subsubcat );

          echo '<a href="' . esc_url($link) . '" class="bsc-subcat-card" style="border: 1px solid #eee; border-radius: 12px; overflow: hidden; text-decoration: none; background: white; transition: box-shadow 0.3s ease;">';

          if ( $image_url ) {
            echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $subsubcat->name ) . '" style="width: 100%; height: 180px; object-fit: cover;">';
          }

          echo '<div style="padding: 1rem;">';
          echo '<h4 style="font-size: 1.1rem; margin: 0; color: #333;">' . esc_html( $subsubcat->name ) . '</h4>';
          echo '</div>';

          echo '</a>';
        }

        echo '</div>';
      } else {
        echo '<p style="color: #999;">No hay subcategorías.</p>';
      }
    }
  }
}
?>

</div>