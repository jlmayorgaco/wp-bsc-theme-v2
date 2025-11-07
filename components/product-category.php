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
        para conocer de cerca su increíble tecnología. Así nació <strong>BSC</strong>: escuchando a nuestra comunidad,
        soñando con un espacio donde el K-Beauty se sintiera cercano, real y confiable. <br><br>
        <strong>Bubbles</strong> es literalmente un paraíso K-Beauty:
        aquí no solo encuentras marcas cuidadosamente seleccionadas con los más altos estándares coreanos,
        también te ayudamos a crear una rutina efectiva, personalizada y pensada para tu piel :)”
      </p>
      <p class="bsc-hero__author">Male ♡♡♡</p>

      <div class="bsc-hero__divider"></div>
      <h3 class="bsc-hero__subtitle bsc__title">
        Bienvenido al paraíso del <strong>K-Beauty</strong> Bubble lover !
      </h3>
    </section>

    <?php
    // 🌸 Category showcase (6 blocks)
    $groups = [
        [
            'slug'  => 'skin-care',
            'title' => 'SKIN CARE',
            'image' => get_template_directory_uri() . '/images/kb_skin.jpg',
        ],
        [
            'slug'  => 'hair-care',
            'title' => 'HAIR CARE',
            'image' => get_template_directory_uri() . '/images/kb_hair.jpg',
        ],
        [
            'slug'  => 'make-up',
            'title' => 'MAKE UP',
            'image' => get_template_directory_uri() . '/images/kb_makeup.jpg',
        ],
        [
            'slug'  => 'dispositivos',
            'title' => 'DISPOSITIVOS',
            'image' => get_template_directory_uri() . '/images/kb_devices.jpg',
        ],
        [
            'slug'  => 'inner-beauty',
            'title' => 'INNER BEAUTY',
            'image' => get_template_directory_uri() . '/images/kb_inner.jpg',
        ],
        [
            'slug'  => 'spa-kbeauty',
            'title' => 'SPA KBEAUTY',
            'image' => get_template_directory_uri() . '/images/kb_spa.jpg',
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
/* =========================================
   K-Beauty Level 1 Landing Styles
   (BubbleSkinCare)
   ========================================= */

/* ---- Global container ---- */
.bsc__shop {
  background-color: #fff;
  color: #1a1a1a;
  font-family: "Poppins", "Noto Sans KR", sans-serif;
  line-height: 1.5;
  overflow-x: hidden;
}

.bsc__container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 2rem 1.5rem 5rem;
}

/* =========================================
   🌈 HERO SECTION
   ========================================= */
.bsc-hero {
  text-align: center;
  margin-bottom: 3rem;
  color: #222;
}

.bsc-hero__icon img {
  width: 160px;
  height: auto;
  margin: 0 auto 1rem;
}

.bsc-hero__title {
  font-size: 1.5rem;
  font-weight: 200;
  margin-bottom: 1.2rem;
}
.bsc-hero__title::after {
      content: '';
      display: block;
      width: 120px;
      height: 20px; 
      margin: 30px auto 0;
      background-image: url('/wp-content/themes/wp-bsc-theme-v2/images/bsc_title_underline.png');
      background-repeat: no-repeat;
      background-position: center;
      background-size: contain;
}

.bsc-hero__quote {
  max-width: 800px;
  margin: 0 auto 1.5rem;
  font-size: 0.95rem;
  font-weight: 400;
  line-height: 1.7;
  color: #444;
}

.bsc-hero__quote strong {
  color: #000;
}

.bsc-hero__author {
  font-weight: 600;
  color: #555;
  font-size: 0.95rem;
  margin-bottom: 1rem;
}

.bsc-hero__divider {
  width: 80%;
  max-width: 600px;
  height: 1px;
  background-color: #e0e0e0;
  margin: 2rem auto;
}

.bsc-hero__subtitle {
  font-size: 1.3rem;
  font-weight: 500;
  color: #000;
  text-align: center;
  margin-bottom: 3rem;
}

/* =========================================
   🌸 CATEGORY GRID
   ========================================= */
.bsc-kb-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 2rem;
  justify-content: center;
  align-items: start;
      max-width: 800px;
    margin: 0 auto 1.5rem;
}

.bsc-kb-card {
  position: relative;
  border-radius: 0px;
  border: 1px solid #333333;
  background: linear-gradient(135deg, #f4f4f4, #d9d9d9);
  height: 340px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  transition: all 0.35s ease;
  cursor: pointer;
}

.bsc-kb-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}

.bsc-kb-card__link {
  display: block;
  text-decoration: none;
  color: inherit;
}

.bsc-kb-card__imgwrap {
  width: 100%;
  aspect-ratio: 3 / 4;
  overflow: hidden;
}

.bsc-kb-card__imgwrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.bsc-kb-card:hover .bsc-kb-card__imgwrap img {
  transform: scale(1.04);
}

/* ---- Category label ---- */
.bsc-kb-card__label {
  position: absolute;
  top: -20px;
  right: -0px;
  background-color: #d8e4ea;
  color: #1b1b1b;
  font-family: "Poppins", sans-serif;
  font-size: 0.9rem;
  letter-spacing: 1px;
  font-weight: 600;
  padding: 6px 14px;
  border-radius: 0px;
  border: 1px solid #333333;
}
.bsc-kb-card__label::before {
  content: "";
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    45deg,
    #efefef,
    #efefef 20px,
    #f9f9f9 20px,
    #f9f9f9 40px
  );
  opacity: 0.3;
}
/* Optional pastel color accents (category tags) */
.bsc-kb-card:nth-child(1) .bsc-kb-card__label { background-color: #F4C8CF; } /* Skin Care */
.bsc-kb-card:nth-child(2) .bsc-kb-card__label { background-color: #F8EEC0; } /* Hair Care */
.bsc-kb-card:nth-child(3) .bsc-kb-card__label { background-color: #C9DCE6; } /* Make Up */
.bsc-kb-card:nth-child(4) .bsc-kb-card__label { background-color: #E7A99A; } /* Dispositivos */
.bsc-kb-card:nth-child(5) .bsc-kb-card__label { background-color: #CDE0D4; } /* Inner Beauty */
.bsc-kb-card:nth-child(6) .bsc-kb-card__label { background-color: #D6C4E0; } /* Spa KBeauty */


/* =========================================
   🧩 RESPONSIVE
   ========================================= */
@media (max-width: 992px) {
  .bsc-hero__title {
    font-size: 1.3rem;
  }
  .bsc-hero__quote {
    font-size: 0.9rem;
  }
}

@media (max-width: 600px) {
  .bsc-hero__icon img {
    width: 70px;
  }
  .bsc-hero__title {
    font-size: 1.2rem;
  }
  .bsc-hero__subtitle {
    font-size: 1.1rem;
  }
  .bsc-kb-grid {
    gap: 1.25rem;
  }
  .bsc-kb-card__label {
    bottom: 0.7rem;
    left: 0.7rem;
    font-size: 0.8rem;
  }
}

/* =========================================
   🌿 Optional soft fade-in animation
   ========================================= */
.bsc-kb-card {
  opacity: 0;
  transform: translateY(10px);
  animation: fadeUp 0.6s ease forwards;
}
.bsc-kb-card:nth-child(2) { animation-delay: 0.1s; }
.bsc-kb-card:nth-child(3) { animation-delay: 0.2s; }
.bsc-kb-card:nth-child(4) { animation-delay: 0.3s; }
.bsc-kb-card:nth-child(5) { animation-delay: 0.4s; }
.bsc-kb-card:nth-child(6) { animation-delay: 0.5s; }

@keyframes fadeUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

  </style>