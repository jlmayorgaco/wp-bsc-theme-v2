
<?php
$current_path = $_SERVER['REQUEST_URI']; // e.g. /product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos/

// Eliminar posibles parámetros y slashes finales
$clean_path = parse_url($current_path, PHP_URL_PATH);
$trimmed_path = trim($clean_path, '/');

// Separar segmentos
$segments = explode('/', $trimmed_path);

// Comprobar si estamos dentro de /product-category/
$category_index = array_search('product-category', $segments);

// Verificar cuántos niveles de categoría hay después de 'product-category'
$category_depth = 0;
if ($category_index !== false) {
  $category_depth = count($segments) - $category_index - 1;
}

// Mostrar filtros solo si hay 3 niveles de categorías
$showFilters = ($category_depth >= 3);
?>

<?php
require_once get_template_directory() . '/components/products/card.php';
require_once get_template_directory() . '/components/products/filters.php';

// Obtener la categoría actual
$product_cat = get_queried_object(); // WP_Term o null

// Obtener jerarquía de categorías
$parent = $product_cat && $product_cat->parent ? get_term($product_cat->parent, 'product_cat') : null;
$grandparent = $parent && $parent->parent ? get_term($parent->parent, 'product_cat') : null;

?>

<div class="bsc bsc__shop">
  <div class="bsc__container">

    <?php if ($product_cat && $parent && $grandparent): ?>
      <!-- 🌿 VISTA FINAL DEL PRODUCTO (tercer nivel de jerarquía) -->

      <!-- 🔗 Breadcrumbs -->
      <nav class="bsc__shop-nav">
        <a href="<?= get_term_link($grandparent); ?>"><?= esc_html($grandparent->name); ?></a> →
        <a href="<?= get_term_link($parent); ?>"><?= esc_html($parent->name); ?></a> →
        <a class="active"><?= esc_html($product_cat->name); ?></a>
      </nav>

      <!-- 📝 Título y Descripción -->
      <div class="shop__header">
        <h1 class="bsc__title"><strong><?= esc_html($product_cat->name); ?></strong></h1>
        <p class="bsc__description"><?= esc_html($product_cat->description); ?></p>
      </div>

      <!-- 🧩 Layout principal -->
      <div class="shop__main">

      <?php if ($showFilters): ?>
        <aside class="shop__sidebar">
          <?php bsc_render_custom_filters_sidebar(); ?>
        </aside>
      <?php endif; ?>

        <section class="shop__content">
          <div id="bscProductsContainer" class="shop__products">
            <?php
            $query = new WP_Query([
              'post_type'      => 'product',
              'post_status'    => 'publish',
              'posts_per_page' => -1,
              'tax_query'      => [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $product_cat->slug,
              ]],
            ]);

            if ($query->have_posts()):
              while ($query->have_posts()): $query->the_post();
                global $product;
                if ($product instanceof WC_Product) {
                  $card = new BSC_Products_Card();
                  $card->setProduct($product);
                  $card->render();
                }
              endwhile;
              wp_reset_postdata();
            else:
              echo '<p>No products found in this category.</p>';
            endif;
            ?>
          </div>

          <div class="shop__pagination">
            <?= paginate_links(['total' => $query->max_num_pages]); ?>
          </div>
        </section>
      </div>

    <?php elseif ($product_cat && $parent && !$grandparent): ?>
      <!-- 🧭 SEGUNDO NIVEL (subgrupos de una categoría principal) -->
    
      <!-- 🔗 Breadcrumbs -->
      <nav class="bsc__shop-nav">
        <a href="<?= get_term_link($parent); ?>"><?= esc_html($parent->name); ?></a> →
        <a class="active"><?= esc_html($product_cat->name); ?></a>
      </nav>

      <h2 class="bsc__title">Subcategorías de <?= esc_html($product_cat->name); ?></h2>
      <p class="bsc__description">  <?= esc_html($product_cat->description); ?></p>

      <div class="bsc__category-grid">
        <?php
        $subcats = get_terms([
          'taxonomy'   => 'product_cat',
          'hide_empty' => false,
          'parent'     => $product_cat->term_id,
        ]);

        foreach ($subcats as $cat) {
            $thumbnail_id = get_term_meta($cat->term_id, 'thumbnail_id', true);
            $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : get_template_directory_uri() . '/images/bsc_default_category.jpeg';

            echo '<div class="bsc__category-card">';
                echo '<a class="category-card__link" href="' . esc_url(get_term_link($cat)) . '">';
                    echo '<img class="category-card__image" src="' . esc_url($image_url) . '" alt="' . esc_attr($cat->name) . '">';
                    echo '<h2 class="category-card__title">' . esc_html($cat->name) . '</h2>';
                echo '</a>';
            echo '</div>';
        }

        ?>
      </div>

    <?php elseif ($product_cat && !$parent): ?>

        <!-- 🔗 Breadcrumbs -->
        <nav class="bsc__shop-nav">
            <a class="active"><?= esc_html($product_cat->name); ?></a>
        </nav>

        <!-- 🧱 PRIMER NIVEL (categorías principales: Skin Care, Hair Care, etc.) -->
        <h1 class="bsc__title"><strong><?= esc_html($product_cat->name); ?></strong></h1>
        <p class="bsc__description">  <?= esc_html($product_cat->description); ?></p>

        <div class="bsc__category-grid">
        <?php
        $groups = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $product_cat->term_id,
        ]);

        foreach ($groups as $group) {
            // Get thumbnail ID and image URL
            $thumbnail_id = get_term_meta($group->term_id, 'thumbnail_id', true);
            $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : get_template_directory_uri() . '/images/bsc_default_category.jpeg';

            echo '<div class="bsc__category-card">';
            echo '<a class="category-card__link" href="' . esc_url(get_term_link($group)) . '">';
                echo '<img class="category-card__image" src="' . esc_url($image_url) . '" alt="' . esc_attr($group->name) . '">';
                echo '<h2 class="category-card__title">' . esc_html($group->name) . '</h2>';
            echo '</a>';
            echo '</div>';
        }
        ?>
        </div>

      
    <?php else: ?>
      <p>Error: categoría no encontrada o inválida.</p>
    <?php endif; ?>

  </div>
</div>
