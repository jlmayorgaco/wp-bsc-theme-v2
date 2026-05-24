<?php
// BSC-013: "3 ninas" - main category showcase cards (Skin Care, Hair Care, Make Up)
$bsc_shop_groups = [
    [
        'slug'  => 'group-skin-care',
        'title' => 'SKIN CARE',
        'image' => 'images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
        'text'  => 'Tu rutina <strong>coreana empieza aqu&iacute;</strong>: limpiadores, esencias, serums, contornos, mascarillas y m&aacute;s para una piel saludable todos los d&iacute;as.',
    ],
    [
        'slug'  => 'group-hair-care',
        'title' => 'HAIR CARE',
        'image' => 'images/shop/2PAG_INTERNAR_IMAGENES_WEB.jpg',
        'text'  => 'Cuida tu cabello con la <strong>tecnolog&iacute;a coreana</strong>: champ&uacute;s, acondicionadores, tratamientos y m&aacute;s para un cabello sano y brillante.',
    ],
    [
        'slug'  => 'group-make-up',
        'title' => 'MAKE UP',
        'image' => 'images/shop/3PAG_INTERNAR_IMAGENES_WEB.jpg',
        'text'  => 'El <strong>K-Beauty make up</strong> que cuida mientras embellece: bases, tintes, labiales y mucho m&aacute;s.',
    ],
];
?>

<section class="section bsc-kb-grid bsc-kb-grid--home bsc-kb-grid--shop">
    <?php foreach ($bsc_shop_groups as $g) :
        $term_link = get_term_link($g['slug'], 'product_cat');
        if (is_wp_error($term_link)) {
            $term_link = trailingslashit(home_url('/product-category/')) . $g['slug'] . '/';
        }
    ?>
        <article class="bsc-kb-card">
            <a href="<?php echo esc_url($term_link); ?>" class="bsc-kb-card__link">
                <div class="bsc-kb-card__imgwrap">
                    <div class="bsc-kb-card__back">
                        <?php
                        bsc_responsive_theme_image(
                            $g['image'],
                            $g['title'],
                            [
                                'loading' => 'lazy',
                                'width'   => '600',
                                'height'  => '480',
                            ],
                            '(max-width: 768px) 100vw, 33vw'
                        );
                        ?>
                    </div>
                    <div class="bsc-kb-card__front">
                        <img
                            class="bsc-kb-card__icon"
                            src="<?php echo esc_url(get_template_directory_uri()); ?>/images/shop/hear_icon.png"
                            alt=""
                            width="25"
                            height="25"
                        >
                        <p class="bsc-kb-text">
                            <?php echo wp_kses(html_entity_decode($g['text'], ENT_QUOTES, 'UTF-8'), ['strong' => []]); ?>
                        </p>
                    </div>
                </div>
                <div class="bsc-kb-card__label">
                    <?php echo esc_html($g['title']); ?>
                </div>
            </a>
        </article>
    <?php endforeach; ?>
</section>

<div class="custom-shop-wrapper">
  <h1 class="shop-title">Nuestra tienda Bubbles</h1>

  <p class="custom-shop-intro">Explora nuestros productos por categor&iacute;a. Encuentra la rutina ideal para tu tipo de piel y cabello.</p>

<?php
$group_slugs = ['group-skin-care', 'group-hair-care', 'group-make-up'];

foreach ($group_slugs as $group_slug) {
    $group = get_term_by('slug', $group_slug, 'product_cat');

    if (!$group || is_wp_error($group)) {
        continue;
    }

    echo '<h2 class="custom-shop-group-title">&#10024; ' . esc_html($group->name) . '</h2>';

    $subcategories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $group->term_id,
    ]);

    if (!empty($subcategories) && !is_wp_error($subcategories)) {
        foreach ($subcategories as $subcategory) {
            echo '<h3 class="custom-shop-subcategory-title">' . esc_html($subcategory->name) . '</h3>';

            $subsubcategories = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => $subcategory->term_id,
            ]);

            if (!empty($subsubcategories) && !is_wp_error($subsubcategories)) {
                echo '<div class="bsc-subcat-grid">';

                foreach ($subsubcategories as $subsubcat) {
                    $thumbnail_id = get_term_meta($subsubcat->term_id, 'thumbnail_id', true);
                    $image_url = wp_get_attachment_url($thumbnail_id);
                    $link = get_term_link($subsubcat);

                    echo '<a href="' . esc_url($link) . '" class="bsc-subcat-card">';

                    if ($image_url) {
                        echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($subsubcat->name) . '" class="bsc-subcat-card__image">';
                    }

                    echo '<div class="bsc-subcat-card__body">';
                    echo '<h4 class="bsc-subcat-card__title">' . esc_html($subsubcat->name) . '</h4>';
                    echo '</div>';

                    echo '</a>';
                }

                echo '</div>';
            } else {
                echo '<p class="custom-shop-empty">No hay subcategor&iacute;as.</p>';
            }
        }
    }
}
?>

</div>
