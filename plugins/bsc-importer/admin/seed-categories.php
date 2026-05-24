<?php

defined('ABSPATH') || exit;

class BSC_Theme_Importer_Category_Uploader
{
    private $categoriesSeeded = 0;

    public function uploadCategories(array $nodes): int
    {
        $this->outputMessage('UPLOADING JSON FILE....');
        $this->outputMessage('SIZE: ' . count($nodes));

        foreach ($nodes as $node) {
            $node['SLUG'] = sanitize_title((string) ($node['SLUG'] ?? ''));
            $node['PARENT_SLUG'] = sanitize_title((string) ($node['PARENT_SLUG'] ?? ''));
            $node['LABEL'] = sanitize_text_field((string) ($node['LABEL'] ?? ''));
            $node['DESCRIPTION'] = isset($node['DESCRIPTION']) ? wp_kses_post((string) $node['DESCRIPTION']) : '';

            if ($node['SLUG'] === '' || $node['LABEL'] === '') {
                $this->outputMessage('Category skipped: missing slug or label.');
                continue;
            }

            if ($this->categoryExists($node['SLUG'])) {
                $prod =  term_exists($node['SLUG'], 'product_cat');
                $this->outputCategoryInfo($node);
                $this->updateCategory($node);
                continue;
            } else {
                $parentId = $this->getParentCategoryId($node['PARENT_SLUG'] ?? '');
                $this->createCategory($node, $parentId);
            }

        }

        return $this->categoriesSeeded;
    }

    private function updateCategory($node){

        // Retrieve the category by its slug
        $category = get_term_by('slug', $node['SLUG'], 'product_cat');

        if (!$category) {
            $this->outputMessage("Category not found for update: " . $node['SLUG']);
            return;
        }

        // Fetch parent ID if PARENT_SLUG exists
        $parentId = 0;
        if (!empty($node['PARENT_SLUG'])) {
            $parentTerm = get_term_by('slug', $node['PARENT_SLUG'], 'product_cat');
            $parentId = $parentTerm ? $parentTerm->term_id : 0;
        }

        // Update the category attributes
        $args = [
            'name'        => $node['LABEL'],
            'description' => $node['DESCRIPTION'] ?? '',
            'parent'      => $parentId,
        ];

        $result = wp_update_term($category->term_id, 'product_cat', $args);

        if (is_wp_error($result)) {
            $this->outputMessage("Error updating category: " . $result->get_error_message());
        } else {
            $this->outputMessage("Updated category: " . $node['LABEL']);
        }

        // Update additional meta fields
        $this->updateCategoryMeta($category->term_id, $node);
    }

    private function updateCategoryMeta(int $categoryId, array $node): void
    {
        if (isset($node['BSC__HOW_TO_USE'])) {
            update_term_meta($categoryId, 'bsc__how_to_use', $node['BSC__HOW_TO_USE']);
        }

        if (isset($node['BSC__RUTINE_STEPS'])) {
            update_term_meta($categoryId, 'bsc__rutine_steps', $node['BSC__RUTINE_STEPS']);
        }

        if (isset($node['BSC__SKIN_TYPE'])) {
            update_term_meta($categoryId, 'bsc__skin_type_root', $node['BSC__SKIN_TYPE']['root'] ?? '');
            update_term_meta($categoryId, 'bsc__skin_type_desc', $node['BSC__SKIN_TYPE']['desc'] ?? '');
        }

        // Handle the PICTURE custom field
        if (isset($node['PICTURE'])) {
            update_term_meta($categoryId, 'picture', $node['PICTURE']);
        }
    }

    private function categoryExists(string $slug): bool
    {
        return term_exists($slug, 'product_cat') !== NULL && term_exists($slug, 'product_cat') !== 0;
    }

    private function getParentCategoryId(string $parentSlug): int
    {
        if (empty($parentSlug)) {
            return 0;
        }

        $parent = get_term_by('slug', $parentSlug, 'product_cat');
        return $parent ? $parent->term_id : 0;
    }

    private function createCategory(array $node, int $parentId): void
    {
        $args = [
            'slug' => $node['SLUG'],
            'description' => $node['DESCRIPTION'] ?? '',
        ];

        if ($parentId) {
            $args['parent'] = $parentId;
        }

        $result = wp_insert_term($node['LABEL'], 'product_cat', $args);

        if (is_wp_error($result)) {
            $this->handleCategoryError($result, $node);
        } else {
            $this->categoriesSeeded++;
            $this->addCategoryMeta($result['term_id'], $node);
        }
    }

    private function addCategoryMeta(int $categoryId, array $node): void
    {
        if (isset($node['BSC__HOW_TO_USE'])) {
            add_term_meta($categoryId, 'bsc__how_to_use', $node['BSC__HOW_TO_USE']);
        }
        if (isset($node['BSC__RUTINE_STEPS'])) {
            add_term_meta($categoryId, 'bsc__rutine_steps', $node['BSC__RUTINE_STEPS']);
        }
        if (isset($node['BSC__SKIN_TYPE'])) {
            add_term_meta($categoryId, 'bsc__skin_type_root', $node['BSC__SKIN_TYPE']['root'] ?? '');
            add_term_meta($categoryId, 'bsc__skin_type_desc', $node['BSC__SKIN_TYPE']['desc'] ?? '');
        }
        if (isset($node['PICTURE'])) {
            add_term_meta($categoryId, 'picture', $node['PICTURE']);
        }
    }

    private function handleCategoryError($error, array $node): void
    {
        $errorMessage = $error->get_error_message();
        $this->outputMessage("ERROR INSERTING CATEGORY: {$node['LABEL']} | Slug: {$node['SLUG']}");
        $this->outputMessage("Error: $errorMessage");
    }

    private function outputMessage(string $message): void
    {
        echo '<h4>' . esc_html($message) . '</h4><br>';
    }

    private function outputCategoryInfo(array $node): void
    {
        $this->outputMessage("Category exists: " . print_r($node, true));
    }
}

class BSC_Theme_Importer_Category_Menu
{
    private $menuName = 'BSC Header Nav Menu';

    public function createMenu(): void
    {
        $menuPayloads = [
            'Skin Care' => $this->getChildCategoriesForGroup('group-skin-care'),
            'Hair Care' => $this->getChildCategoriesForGroup('group-hair-care'),
            'Make Up' => $this->getChildCategoriesForGroup('group-make-up'),
        ];

        $this->deleteExistingMenu();
        $menuId = wp_create_nav_menu($this->menuName);

        foreach ($menuPayloads as $title => $categories) {
            $parentMenuId = $this->addMenuItem($menuId, $title, '#');
            $this->addChildCategoriesToMenu($menuId, $parentMenuId, $categories);
        }

        $this->setPrimaryMenuLocation($menuId);
    }

    private function deleteExistingMenu(): void
    {
        if ($menu = wp_get_nav_menu_object($this->menuName)) {
            wp_delete_term($menu->term_id, 'nav_menu');
        }
    }

    private function setPrimaryMenuLocation(int $menuId): void
    {
        $locations = get_theme_mod('nav_menu_locations');
        $locations['primary'] = $menuId;
        set_theme_mod('nav_menu_locations', $locations);
    }

    private function addMenuItem(int $menuId, string $title, string $url): int
    {
        return wp_update_nav_menu_item($menuId, 0, [
            'menu-item-title' => $title,
            'menu-item-url' => $url,
            'menu-item-status' => 'publish'
        ]);
    }

    private function addChildCategoriesToMenu(int $menuId, int $parentId, array $categories): void
    {
        foreach ($categories as $category) {
            $childId = $this->addMenuItem($menuId, $category['label'], $category['link']);

            if (!empty($category['children'])) {
                $this->addChildCategoriesToMenu($menuId, $childId, $category['children']);
            }
        }
    }

    private function getChildCategoriesForGroup(string $groupSlug): array
    {
        $group = get_term_by('slug', $groupSlug, 'product_cat');
        if (!$group) {
            return [];
        }

        return $this->getChildCategories($group->term_id);
    }

    private function getChildCategories(int $parentId): array
    {
        $categories = [];
        $terms = get_terms(['taxonomy' => 'product_cat', 'parent' => $parentId, 'hide_empty' => false]);

        foreach ($terms as $term) {
            $categories[] = [
                'label' => $term->name,
                'slug' => $term->slug,
                'link' => get_term_link($term),
                'children' => $this->getChildCategories($term->term_id),
            ];
        }

        return $categories;
    }
}




// Function to get child categories by parent slug
function bsc_theme_importer_categories_by_parent_slug($parent_slug) {

    // Get the parent category
    $parent_category = get_term_by( 'slug', $parent_slug, 'product_cat' );

    // If parent category exists, fetch child categories
    if ($parent_category) {
        $args = [
            'taxonomy'   => 'product_cat',
            'child_of'   => $parent_category->term_id,
            'hide_empty' => false,
        ];


        return get_terms($args);
    }
    // Return an empty array if no parent category is found
    return [];
}


// Function to filter out categories that include "complemento"
function bsc_theme_importer_filter_non_complementos($categories) {
    $filtered_categories = array_filter($categories, function($category) {
        return strpos($category->slug, 'complemento') === false;
    });

    // Sort categories based on the numeric ID extracted from the slug
    usort($filtered_categories, function($a, $b) {
        // Use regular expression to extract the numeric ID after 'sk-rutina-s'
        preg_match('/sk-rutina-s(\d+)-/', $a->slug, $matches_a);
        preg_match('/sk-rutina-s(\d+)-/', $b->slug, $matches_b);

        // Convert matched IDs to integers
        $id_a = isset($matches_a[1]) ? (int) $matches_a[1] : 0;
        $id_b = isset($matches_b[1]) ? (int) $matches_b[1] : 0;

        // Sort by the extracted ID
        return $id_a - $id_b;
    });

    return $filtered_categories;
}

// Function to filter out categories that no include "complemento"
function bsc_theme_importer_filter_complementos($categories) {
    $filtered_categories = array_filter($categories, function($category) {
        return !(strpos($category->slug, 'complemento') === false);
    });

    // Sort categories based on the numeric ID extracted from the slug
    usort($filtered_categories, function($a, $b) {
        // Use regular expression to extract the numeric ID after 'sk-rutina-s'
        preg_match('/sk-rutina-s(\d+)-/', $a->slug, $matches_a);
        preg_match('/sk-rutina-s(\d+)-/', $b->slug, $matches_b);

        // Convert matched IDs to integers
        $id_a = isset($matches_a[1]) ? (int) $matches_a[1] : 0;
        $id_b = isset($matches_b[1]) ? (int) $matches_b[1] : 0;

        // Sort by the extracted ID
        return $id_a - $id_b;
    });

    return $filtered_categories;

}
function bsc_theme_importer_generate_menu_slug($menu_name) {
    // Remove accents and diacritics using iconv
    $normalized_name = iconv('UTF-8', 'ASCII//TRANSLIT', $menu_name);

    // Remove any non-alphanumeric characters (except spaces)
    $normalized_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $normalized_name);

    // Replace spaces with underscores
    $slug = str_replace(' ', '_', $normalized_name);

    // Convert the final string to uppercase and prefix with BSC_MENU_
    return strtoupper('BSC_MENU_' . $slug);
}

function bsc_theme_importer_create_custom_menus($menus) {

    // Loop through the menus and create them
    foreach ($menus as $menu_name => $menu_data) {
        $menu_slug = bsc_theme_importer_generate_menu_slug($menu_name);

        // Check if menu exists, if not, create it
        $existing_menu = wp_get_nav_menu_object($menu_slug);
        if ($existing_menu) {
            wp_delete_nav_menu($existing_menu->term_id);
        }

        $menu_id = wp_create_nav_menu($menu_slug);

        // Loop through children and add them as menu items
        foreach ($menu_data['children'] as $child) {
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title' => $child['name'],
                'menu-item-url' => home_url($child['url']),
                'menu-item-status' => 'publish'
            ));
        }

    }
}



function bsc_theme_importer_upload_categories(array $nodes){
    $uploader = new BSC_Theme_Importer_Category_Uploader();
    return $uploader->uploadCategories($nodes);

}

function bsc_theme_importer_after_upload_categories() {

    // Create Full Menu
    $menu = new BSC_Theme_Importer_Category_Menu();
    $menu->createMenu();

    // Load product categories dynamically
    $sk_rutina_categories = bsc_theme_importer_categories_by_parent_slug('sk-rutina');

    // Filter categories that do not have 'complementos' in the slug
    $filtered_sk_rutina_non_complementos_categories = bsc_theme_importer_filter_non_complementos($sk_rutina_categories);

    // Filter categories that have 'complementos' in the slug
    $filtered_sk_rutina_complementos_categories = bsc_theme_importer_filter_complementos($sk_rutina_categories);

    // Load 'Tipo de Piel' categories
    $sk_tipo_piel_categories = bsc_theme_importer_categories_by_parent_slug('sk-tipo-piel');





    // Fill the menus dynamically based on the categories retrieved
    $menus = [
        'Rutina Coreana' => [
            'slug' => 'bsc-menu-rutina-coreana-1',
            'children' => array_map(function($category) {
                return [
                    'name' => $category->name,
                    'url' => '/product-category/group-skin-care/sk-rutina/'.$category->slug
                ];
            }, $filtered_sk_rutina_non_complementos_categories),
        ],
        'Complementos' => [
            'slug' => 'bsc-menu-rutina-coreana-2',
            'children' => array_map(function($category) {
                return [
                    'name' => $category->name,
                    'url' => '/product-category/group-skin-care/sk-rutina/'.$category->slug
                ];
            }, $filtered_sk_rutina_complementos_categories),
        ],
        'Tipo de Piel' => [
            'slug' => 'bsc-menu-tipo-piel',
            'children' => array_map(function($category) {
                return [
                    'name' => $category->name,
                    'url' => '/product-category/group-skin-care/sk-tipo-piel/'.$category->slug
                ];
            }, $sk_tipo_piel_categories),
        ],
        // Non-dynamically generated sections with name and slug structure
        'Rutina Básica' => [
            'slug' => 'bsc-menu-rutina-basica',
            'children' => [
                ['name' => 'Limpiador Acuoso', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'],
                ['name' => 'Tónico', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'],
                ['name' => 'Hidratante', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'],
                ['name' => 'Protector Solar', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema'],
            ],
        ],
        'Rutina Intermedia' => [
            'slug' => 'bsc-menu-rutina-intermedia',
            'children' => [
                ['name' => 'Limpiador Aceitoso', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos'],
                ['name' => 'Limpiador Acuoso', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'],
                ['name' => 'Tónico', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'],
                ['name' => 'Serum', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums'],
                ['name' => 'Hidratante', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'],
                ['name' => 'Protector Solar', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema'],
            ],
        ],
        'Rutina Experta' => [
            'slug' => 'bsc-menu-rutina-experta',
            'children' => [
                ['name' => 'Limpiador Aceitoso', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos'],
                ['name' => 'Limpiador Acuoso', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'],
                ['name' => 'Exfoliante', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s3-exfoliantes'],
                ['name' => 'Tónico', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'],
                ['name' => 'Mascarilla', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s5-mascarillas'],
                ['name' => 'Esencias', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s6-esencias'],
                ['name' => 'Serum', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums'],
                ['name' => 'Contorno de Ojos', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums'],
                ['name' => 'Hidratante', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'],
                ['name' => 'Protector Solar', 'url' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema'],
            ],
        ],
        'BLOG' => [
            'slug' => 'bsc-menu-blog',
            'children' => [
                ['name' => 'Entrevistas', 'url' => '/pages/interviews'],
                ['name' => 'Reseñas', 'url' => '/pages/reviews'],
                ['name' => 'Tendencias', 'url' => '/pages/trends'],
                ['name' => 'Skin Care', 'url' => '/product-category/group-skin-care'],
                ['name' => 'Hair Care', 'url' => '/product-category/group-hair-care'],
                ['name' => 'Maquillaje', 'url' => '/product-category/group-make-up'],
            ],
        ],
    ];

    bsc_theme_importer_create_custom_menus($menus);
}
