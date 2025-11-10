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

        $this->showFilters = $this->computeShowFilters();
    }

    private function computeShowFilters(): bool
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $segments = explode('/', trim(parse_url($path, PHP_URL_PATH) ?? '', '/'));
        $index = array_search('product-category', $segments, true);
        $depth = ($index !== false) ? count($segments) - $index - 1 : 0;
        return $depth >= 3;
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
            printf('<a href="%s">%s</a> → ', esc_url(get_term_link($grandparent)), esc_html($grandparent->name));
        }
        if ($parent) {
            printf('<a href="%s">%s</a> → ', esc_url(get_term_link($parent)), esc_html($parent->name));
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
      <p class="bsc-hero__author">Male  <img 
                        src="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/images/bsc_icon_white_heart.png" 
                        alt="Corazones BSC" 
                        width="50" 
                        decoding="async"
                    /></p>

      <div class="bsc-hero__divider"></div>
      <h3 class="bsc-hero__subtitle bsc__title">
       <strong>Bienvenido</strong> al paraíso del K-Beauty <strong style="">Bubble lover</strong> !
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
        ],
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
        $this->renderBreadcrumbs(null, $this->parent, $cat);

        echo "<h2 class='bsc__title'>Subcategorías de " . esc_html($cat->name) . "</h2>";
        if ($cat->description) {
            echo "<p class='bsc__description'>" . esc_html($cat->description) . "</p>";
        }

        $subcats = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $cat->term_id,
        ]);
        $this->renderCategoryGrid($subcats);
    }

    private function renderLevel3(): void
    {
        $cat = $this->category;
        $this->renderBreadcrumbs($this->grandparent, $this->parent, $cat);

        echo "<div class='shop__header'>";
        echo "<h1 class='bsc__title'><strong>" . esc_html($cat->name) . "</strong></h1>";
        if ($cat->description) {
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
        $this->renderProducts($cat);
        echo "</div><div class='shop__pagination'>";
        echo paginate_links();
        echo "</div></section></div>";
    }

    private function renderProducts(WP_Term $category): void
    {
        $query = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
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
            echo '<p>No products found in this category.</p>';
        }
    }

    // ------------------------------
    // MAIN ENTRY POINT
    // ------------------------------
    public function render(): void
    {
        echo '<div class="bsc bsc__shop"><div class="bsc__container">';

        if (!$this->category) {
            echo '<p>Error: categoría no encontrada o inválida.</p>';
        } elseif ($this->category && $this->parent && $this->grandparent) {
            $this->renderLevel3();
        } elseif ($this->category && $this->parent && !$this->grandparent) {
            $this->renderLevel2();
        } elseif ($this->category && !$this->parent) {
            $this->renderLevel1();
        } else {
            echo '<p>Error: estructura de categoría no válida.</p>';
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


<style>


  </style>