<?php
/**
 * BSC custom product archive shell.
 *
 * Reviewed against WooCommerce archive-product.php 8.6.0.
 *
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined('ABSPATH') || exit;

get_header('shop'); ?>

<main class="bsc__shop">

    <?php if (!is_product_category()) : ?>

        <?php
        // BSC-013: 3-girls decorative grid — shown only on /shop/, not on category archives
        $bsc_groups = [
            [
                'slug'  => 'group-skin-care',
                'title' => 'SKIN CARE',
                'image' => 'images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
                'text'  => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y más.',
            ],
            [
                'slug'  => 'group-hair-care',
                'title' => 'HAIR CARE',
                'image' => 'images/shop/2PAG_INTERNAR_IMAGENES_WEB.jpg',
                'text'  => 'Cuida tu cabello con lo mejor del <strong>K-Beauty</strong>: mascarillas, aceites y tratamientos coreanos.',
            ],
            [
                'slug'  => 'group-make-up',
                'title' => 'MAKE UP',
                'image' => 'images/shop/3PAG_INTERNAR_IMAGENES_WEB.jpg',
                'text'  => 'Realza tu belleza con maquillaje <strong>K-Beauty</strong>: labiales, bases, blushes y más.',
            ],
        ];
        ?>

        <section class="bsc-kb-grid bsc-kb-grid--shop">
            <?php foreach ($bsc_groups as $g) :
                $term_link = trailingslashit(home_url('/product-category/' . $g['slug'] . '/'));
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
                                        'width'   => '400',
                                        'height'  => '500',
                                    ],
                                    '(max-width: 768px) 100vw, 33vw'
                                );
                                ?>
                            </div>
                            <div class="bsc-kb-card__front">
                                <img class="bsc-kb-card__icon"
                                     src="<?php echo esc_url(get_template_directory_uri()); ?>/images/shop/hear_icon.png"
                                     alt="" width="25" height="25" aria-hidden="true">
                                <p class="bsc-kb-text"><?php echo wp_kses($g['text'], ['strong' => []]); ?></p>
                            </div>
                        </div>
                        <div class="bsc-kb-card__label"><?php echo esc_html($g['title']); ?></div>
                    </a>
                </article>
            <?php endforeach; ?>
        </section>

        

    <?php else : ?>

        <?php require_once get_template_directory() . '/components/product-category.php'; ?>

    <?php endif; ?>

</main>




<?php get_footer('shop'); ?>
