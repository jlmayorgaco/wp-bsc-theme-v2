<?php
/**
 * Clean OOP refactor of product category template
 * Following SOLID, DRY, and KISS principles — single file version
 */

require_once get_template_directory() . '/components/products/card.php';
require_once get_template_directory() . '/components/products/filters.php';




class BSCShopPage
{
    private ?WP_Term $category;
    private ?WP_Term $parent;
    private ?WP_Term $grandparent;
    private bool $showFilters;
    private int $urlDepth; // NEW: depth after "product-category" in the URL

    // ------------------------------
    // CONSTRUCTOR & CONTEXT
    // ------------------------------
    public function __construct()
    {
        $this->category = get_queried_object();
        $this->parent = ($this->category && $this->category->parent)
            ? get_term($this->category->parent, 'product_cat')
            : null;
        $this->grandparent = ($this->parent && $this->parent->parent)
            ? get_term($this->parent->parent, 'product_cat')
            : null;

        $this->urlDepth    = $this->computeUrlDepth();
        $this->showFilters = $this->computeShowFilters();
    }


    /**
     * Map "group-*" slugs to their default child slug.
     * Example: group-skin-care → skin-care-rutina
     */
    private function getDefaultChildTermForGroup(WP_Term $cat): ?WP_Term
    {
 
        // Dictionary: parent group slug → child slug
        $map = [
            'group-skin-care' => 'sk-rutina',
            'group-hair-care' => 'hc-rutina',
            'group-make-up'   => 'mk-productos',
        ];

        if (!isset($map[$cat->slug])) {
            return null;
        }

        $childSlug = $map[$cat->slug];
        $childTerm = get_term_by('slug', $childSlug, 'product_cat');

        return ($childTerm instanceof WP_Term) ? $childTerm : null;
    }

    /**
     * Depth from URL: number of segments after "product-category"
     * /product-category/                      → depth 0
     * /product-category/group-skin-care/      → depth 1
     * /product-category/group-skin-care/foo/  → depth 2
     */
    private function computeUrlDepth(): int
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $cleanPath = trim(parse_url($path, PHP_URL_PATH) ?? '', '/');
        $segments = explode('/', $cleanPath);

        $index = array_search('product-category', $segments, true);
        if ($index === false) {
            return 0;
        }

        // number of segments after "product-category"
        return count($segments) - $index - 1;
    }

    private function computeShowFilters(): bool
    {
        // Show filters only from depth >= 2:
        // /product-category/.../... → Level3 (products with filters)
        return $this->urlDepth >= 2;
    }

    // ------------------------------
    // HELPERS
    // ------------------------------
    private function getImageUrl(WP_Term $term): string
    {
        $thumb = get_term_meta($term->term_id, 'thumbnail_id', true);
        $fallback = get_template_directory_uri() . '/images/bsc_default_category.jpeg';
        return $thumb ? wp_get_attachment_url($thumb) : $fallback;
    }

    private function renderBreadcrumbs(?WP_Term $grandparent, ?WP_Term $parent, ?WP_Term $current): void
    {
        echo '<nav class="bsc__shop-nav">';
        if ($grandparent) {
            printf(
                '<a href="%s">%s</a> → ',
                esc_url(get_term_link($grandparent)),
                esc_html($grandparent->name)
            );
        }
        if ($parent) {
            printf(
                '<a href="%s">%s</a> → ',
                esc_url(get_term_link($parent)),
                esc_html($parent->name)
            );
        }
        if ($current) {
            printf('<a class="active">%s</a>', esc_html($current->name));
        }
        echo '</nav>';
    }

    private function renderCategoryGrid(array $terms): void
    {
        echo '<div class="bsc__category-grid">';
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }

            $image = $this->getImageUrl($term);
            printf(
                '<div class="bsc__category-card">
                    <a class="category-card__link" href="%s">
                        <img class="category-card__image" src="%s" alt="%s">
                        <h2 class="category-card__title">%s</h2>
                    </a>
                </div>',
                esc_url(get_term_link($term)),
                esc_url($image),
                esc_attr($term->name),
                esc_html($term->name)
            );
        }
        echo '</div>';
    }

    // ------------------------------
    // RENDER METHODS
    // ------------------------------
    private function renderLevel1(): void
    {
        $cat = $this->category;
        // En /product-category/ normalmente no hay término, pero por si acaso:
        $this->renderBreadcrumbs(null, null, $cat);

        // 🌈 Header block
        ?>
        <section class="bsc-hero">
          <div class="bsc-hero__icon">
            <img src="<?= get_template_directory_uri(); ?>/images/bsc_rainbow.png"
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
                src="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/images/bsc_icon_white_heart.png" 
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
                'slug'  => 'skin-care',
                'title' => 'SKIN CARE',
                'image' => esc_url(get_stylesheet_directory_uri()) . '/images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
            ],
            [
                'slug'  => 'hair-care',
                'title' => 'HAIR CARE',
                'image' => esc_url(get_stylesheet_directory_uri()) . '/images/shop/2PAG_INTERNAR_IMAGENES_WEB.jpg',
            ],
            [
                'slug'  => 'make-up',
                'title' => 'MAKE UP',
                'image' => esc_url(get_stylesheet_directory_uri()) . '/images/shop/3PAG_INTERNAR_IMAGENES_WEB.jpg',
            ]
            /*
            [
                'slug'  => 'dispositivos',
                'title' => 'DISPOSITIVOS',
                'image' => esc_url(get_stylesheet_directory_uri()) . '/images/shop/4PAG_INTERNAR_IMAGENES_WEB.jpg',
            ],
            [
                'slug'  => 'inner-beauty',
                'title' => 'INNER BEAUTY',
                'image' => esc_url(get_stylesheet_directory_uri()) . '/images/shop/5PAG_INTERNAR_IMAGENES_WEB.jpg',
            ],
            [
                'slug'  => 'spa-kbeauty',
                'title' => 'SPA KBEAUTY',
                'image' => esc_url(get_stylesheet_directory_uri()) . '/images/shop/6PAG_INTERNAR_IMAGENES_WEB.jpg',
            ],
            */
        ];
     
        echo '<section class="bsc-kb-grid">';
        foreach ($groups as $g) {
            $term_link = '#'; // fallback if category not found
            $term = get_term_by('slug', $g['slug'], 'product_cat');
            if ($term) {
                $term_link = get_term_link($term);
            }

            printf(
                '<article class="bsc-kb-card">
                    <a href="%s" class="bsc-kb-card__link">
                        <div class="bsc-kb-card__imgwrap">
                            <img src="%s" alt="%s" loading="lazy">
                        </div>
                        <div class="bsc-kb-card__label">%s</div>
                    </a>
                </article>',
                esc_url($term_link),
                esc_url($g['image']),
                esc_attr($g['title']),
                esc_html($g['title'])
            );
        }
        echo '</section>';
    }

    private function renderLevel2(): void
    {
        $cat = $this->category;
        if (!$cat instanceof WP_Term) {
            echo '<p>Error: categoría no válida.</p>';
            return;
        }

        $this->renderBreadcrumbs(null, null, $cat);

        echo "<h2 class='bsc__title bsc-hero__subtitle bsc__title--subcategory'>" . esc_html($cat->name) . "</h2>";

        if (!empty($cat->description)) {
            echo "<p class='bsc__description bsc__description--description-category'>" . wp_kses_post($cat->description) . "</p>";
        }

        // 1) Subcats directas del grupo (si las quieres usar luego)
        $subcats = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $cat->term_id,
        ]);
        // $this->renderCategoryGrid($subcats); // si quieres el grid normal

        // 2) Default child según diccionario (skin-care-rutina, etc.)
        $defaultChild = $this->getDefaultChildTermForGroup($cat);

        if (!($defaultChild instanceof WP_Term)) {
            echo '<p>No hay categoría por defecto configurada para este grupo.</p>';
            return;
        }

        // --- sub-subcategorías (botones) ---
        $subsubcats = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $defaultChild->term_id,
        ]);

        // Para la lógica de filtrado, queremos todos los descendientes
        $descendants = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'child_of'   => $defaultChild->term_id,
        ]);

        $descendant_slugs = [];
        if (!is_wp_error($descendants)) {
            foreach ($descendants as $t) {
                if ($t instanceof WP_Term) {
                    $descendant_slugs[] = $t->slug;
                }
            }
        }

        echo "<section class='bsc__default-subsubcategory'>";

        if (is_array($subsubcats) && !empty($subsubcats)) {

            echo "<div class='bsc__subsubcategory-links'>";

            // Botón "Todos"
            echo '<button type="button" class="bsc__subsubcategory-link bsc__subsubcategory-link--active" data-filter="all">Todos</button>';

            // Sort subsubcats by slug
            usort($subsubcats, function ($a, $b) {
                return strnatcmp($a->slug, $b->slug);
            });

            foreach ($subsubcats as $term) {
                if (!$term instanceof WP_Term) {
                    continue;
                }

                printf(
                    '<button type="button" class="bsc__subsubcategory-link" data-filter="%s">%s</button>',
                    esc_attr($term->slug),
                    esc_html($term->name)
                );
            }

            echo "</div>"; // .bsc__subsubcategory-links
        }

        // --- productos del defaultChild + todos sus descendientes ---
        // Cap at 120 products: client-side filter needs all records upfront,
        // but -1 causes full table scan and OOM on large catalogues.
        $products_query = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 120,
            'no_found_rows'  => true, // skip COUNT(*) — pagination not needed here
            'tax_query'      => [[
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => [$defaultChild->term_id],
                'include_children' => true,
            ]],
        ]);

        echo "<div id='bscProductsContainer' class='shop__products'>";

        if ($products_query->have_posts()) {

            while ($products_query->have_posts()) {
                $products_query->the_post();
                global $product;

                if (!$product instanceof WC_Product) {
                    continue;
                }

                // Categorías del producto para usar en data-subcat
                $prod_terms = get_the_terms($product->get_id(), 'product_cat');
                $slugs_for_data = [];

                if (!is_wp_error($prod_terms) && !empty($prod_terms)) {
                    foreach ($prod_terms as $pt) {
                        if (!$pt instanceof WP_Term) {
                            continue;
                        }
                        // Sólo nos interesan los descendientes de $defaultChild
                        if (in_array($pt->slug, $descendant_slugs, true)) {
                            $slugs_for_data[] = $pt->slug;
                        }
                    }
                }

                // Si no encontramos descendiente, al menos marca la categoría default
                if (empty($slugs_for_data)) {
                    $slugs_for_data[] = $defaultChild->slug;
                }

                $data_subcat = implode(' ', $slugs_for_data);

                echo '<div class="bsc-product-card" data-subcat="' . esc_attr($data_subcat) . '">';

                // Render card normal
                $card = new BSC_Products_Card();
                $card->setProduct($product);
                $card->render();

                echo '</div>';
            }

            wp_reset_postdata();

        } else {
            echo '<p>No hay productos en esta categoría.</p>';
        }

        echo "</div>"; // #bscProductsContainer
        echo "</section>";
    }


    private function renderLevel3(): void
    {
        $cat = $this->category;
        if (!$cat instanceof WP_Term) {
            echo '<p>Error: categoría no válida.</p>';
            return;
        }

        // Aquí sí usamos parent y grandparent si existen
        $this->renderBreadcrumbs($this->grandparent, $this->parent, $cat);

        echo "<div class='shop__header'>";
        echo "<h1 class='bsc__title'><strong>" . esc_html($cat->name) . "</strong></h1>";
        if (!empty($cat->description)) {
            echo "<p class='bsc__description'>" . esc_html($cat->description) . "</p>";
        }
        echo "</div>";

        echo "<div class='shop__main'>";
        if ($this->showFilters && function_exists('bsc_render_custom_filters_sidebar')) {
            echo "<aside class='shop__sidebar'>";
            bsc_render_custom_filters_sidebar();
            echo "</aside>";
        }

        echo "<section class='shop__content'><div id='bscProductsContainer' class='shop__products'>";
        $products_query = $this->renderProducts($cat);
        echo "</div>";

        // Pagination — only show if more than 1 page
        if ($products_query->max_num_pages > 1) {
            $paged = max(1, get_query_var('paged'));
            echo "<div class='shop__pagination'>";
            echo paginate_links([
                'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format'    => '?paged=%#%',
                'current'   => $paged,
                'total'     => $products_query->max_num_pages,
                'prev_text' => '&laquo; Anterior',
                'next_text' => 'Siguiente &raquo;',
            ]);
            echo "</div>";
        }

        echo "</section></div>";
    }

    private function renderProducts(WP_Term $category): WP_Query
    {
        $paged = max(1, get_query_var('paged'));

        $query = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 24,
            'paged'          => $paged,
            'tax_query'      => [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category->slug,
            ]],
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                global $product;
                if ($product instanceof WC_Product) {
                    $card = new BSC_Products_Card();
                    $card->setProduct($product);
                    $card->render();
                }
            }
            wp_reset_postdata();
        } else {
            echo '<p class="bsc__empty-category">No hay productos en esta categoría.</p>';
        }

        return $query;
    }

    // ------------------------------
    // MAIN ENTRY POINT
    // ------------------------------
    public function render(): void
    {
        echo '<div class="bsc bsc__shop"><div class="bsc__container">';

        // Mapeo por profundidad de URL:
        // depth 0: /product-category/                 → Level1
        // depth 1: /product-category/group-skin-care/ → Level2
        // depth 2+: /product-category/.../...         → Level3
        if ($this->urlDepth === 0) {
            // En /product-category/ a veces no hay término; igual renderizamos Level1
            $this->renderLevel1();
        } elseif ($this->urlDepth === 1) {
            $this->renderLevel2();
        } else {
            $this->renderLevel3();
        }

        echo '</div></div>';
    }
}

// --------------------------------------------------
// 🏁 ENTRY POINT
// --------------------------------------------------
$page = new BSCShopPage();
$page->render();

?>

<!-- Category filter script enqueued via js/category-filter.js -->
