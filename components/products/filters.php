<?php
function bsc_render_custom_filters_sidebar() {
    $current_url = $_SERVER['REQUEST_URI'];

    // Filter config per product group
    $filter_config = [
        'group-skin-care' => [
            ['title' => 'Tipo de Piel', 'slug' => 'sk-tipo-piel', 'multiple' => true, 'name' => 'piel'],
            ['title' => 'Necesidad', 'slug' => 'sk-necesidades', 'multiple' => false, 'name' => 'necesidad'],
            ['title' => 'Ingredientes', 'slug' => 'sk-ingredientes', 'multiple' => true, 'name' => 'ingredientes'],
            ['title' => 'Marca', 'slug' => 'sk-marcas', 'multiple' => false, 'name' => 'marca'],
        ],
        'group-hair-care' => [
            ['title' => 'Necesidad', 'slug' => 'hc-necesidades', 'multiple' => false, 'name' => 'necesidad'],
            ['title' => 'Rutina', 'slug' => 'hc-rutina', 'multiple' => true, 'name' => 'rutina'],
            ['title' => 'Marca', 'slug' => 'hc-marca', 'multiple' => false, 'name' => 'marca'],
        ],
        'group-make-up' => [
            ['title' => 'Producto', 'slug' => 'mk-productos', 'multiple' => false, 'name' => 'producto'],
            ['title' => 'Marca', 'slug' => 'mk-marcas', 'multiple' => false, 'name' => 'marca'],
        ],
    ];

    // Identify group by URL
    //"/product-category/$group_slug/&subgroup_slug/$category_slug"

    $group_slug = '';
    $subgroup_slug = '';
    $category_slug = '';

    // Extract URL path segments
    $path = trim(parse_url($current_url, PHP_URL_PATH), '/');
    $segments = explode('/', $path);

    // Find slugs after "product-category"
    $category_index = array_search('product-category', $segments);
    if ($category_index !== false) {
        $group_slug = $segments[$category_index + 1] ?? '';
        $subgroup_slug = $segments[$category_index + 2] ?? '';
        $category_slug = $segments[$category_index + 3] ?? '';
    }

    if (!$group_slug) return;

    echo '<form id="bscFiltersForm" class="bsc__filters" >';



    if ($group_slug)    echo '<input hidden type="text" value="'.$group_slug.'" name="group">';
    if ($subgroup_slug) echo '<input hidden type="text" value="'.$subgroup_slug.'" name="subgroup">';
    if ($category_slug) echo '<input hidden type="text" value="'.$category_slug.'" name="category">';

    foreach ($filter_config[$group_slug] as $filter) {
        $parent = get_term_by('slug', $filter['slug'], 'product_cat');
        if (!$parent) continue;

        $children = get_terms(['hide_empty' => false , 'taxonomy' => 'product_cat', 'parent' => $parent->term_id, 'hide_empty' => false]);
        if (empty($children)) continue;

        echo '<details class="bsc__filters-group">';
        echo '<summary class="bsc__filters-group-title">' . esc_html($filter['title']) . '</summary>';
        echo '<div class="bsc__filters-options">';

        $selected = $_GET[$filter['name']] ?? [];

        foreach ($children as $child) {
            $value = esc_attr($child->slug);
            $label = esc_html($child->name);
            $name = $filter['name'];

            echo '<label class="bsc__filters-option">';
            if ($filter['multiple']) {
                $checked = (is_array($selected) && in_array($value, $selected)) ? 'checked' : '';
                echo "<input class='bsc__filters-input bsc__filters-input--checkbox' type='checkbox' name='{$name}[]' value='$value' $checked> $label";
            } else {
                $checked = ($selected === $value) ? 'checked' : '';
                echo "<input class='bsc__filters-input bsc__filters-input--radio' type='radio' name='$name' value='$value' $checked> $label";
            }
            echo '</label>';
        }

        echo '</div>';
        echo '</details>';
    }

    // Price filter
    $min_price = $_GET['min_price'] ?? 0;
    $max_price = $_GET['max_price'] ?? 200000;

    echo '<div class="bsc__filters-group bsc__filters-group--price">';
    echo '';
    echo '<div class="bsc__filters-price-wrapper">';

    echo "<input class='bsc__filters-range bsc__filters-range--min' type='range' min='0' max='200000' step='1000' name='min_price' id='min_price' value='" . esc_attr($min_price) . "'>";
    echo "<input class='bsc__filters-range bsc__filters-range--max' type='range' min='0' max='200000' step='1000' name='max_price' id='max_price' value='" . esc_attr($max_price) . "'>";

    echo '<div class="bsc__filters-price-values">';
    echo '<label class="bsc__filters-price-label"><output class="bsc__filters-price-output" id="min_price_output">' . esc_html($min_price) . '</output>$</label>';
    echo '<label class="bsc__filters-price-label"><output class="bsc__filters-price-output" id="max_price_output">' . esc_html($max_price) . '</output>$</label>';
    echo '</div>';

    echo '</div>';
    echo '</div>';

    echo '</form>';
}
?>

