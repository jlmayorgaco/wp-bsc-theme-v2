<?php
function my_custom_menu() {
    register_nav_menus(
        array(
            'my-custom-menu' => _( 'My Custom Menu' ),
            'my-custom-menu-2' =>_('My Second Custom Menu')
        )
    );
}

function enqueue_fontawesome() {
	wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', [], '6.5.0' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_fontawesome' );

function bsc_enqueue_swiper_assets() {
  // Swiper solo se usa en el hero del home — no cargar en otras páginas
  if ( ! is_front_page() ) {
    return;
  }
  wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');
  wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'bsc_enqueue_swiper_assets');








// Handler bsc_add_to_cart movido a inc/ajax/cart-actions.php

//add_action( 'init', 'my_custom_menu' );

/*
function get_child_categories_for_group($group_slug) {

    // Get the term for the top-level category
    $group_term = get_term_by('slug', $group_slug, 'product_cat');

    var_dump($group_term );

    if(!$group_term)
    {
        echo 'No categories found for group: ' . $group_slug;
        return null;
    }

    // Get subcategories (children) of the group
    $subcategories = get_terms(array(
        'taxonomy' => 'product_cat',
        'parent'   => $group_term->term_id,  // Get children of the group
        'hide_empty' => false,
    ));

    // Display subcategories and their child categories
    if (!empty($subcategories)) {
        echo '<ul>';
        foreach ($subcategories as $subcategory) {
            echo '<li>' . $subcategory->name . ' (' . $subcategory->slug . ')';

            // Get child categories (grandchildren)
            $grandchildren = get_terms(array(
                'taxonomy' => 'product_cat',
                'parent'   => $subcategory->term_id,  // Get children of the subcategory
                'hide_empty' => false,
            ));

            if (!empty($grandchildren)) {
                echo '<ul>';
                foreach ($grandchildren as $grandchild) {
                    echo '<li>' . $grandchild->name . ' (' . $grandchild->slug . ')</li>';
                }
                echo '</ul>';
            }

            echo '</li>';
        }
        echo '</ul>';
    }
    
}
    */

// Example usage for group-skin-care, group-hair-care, and group-make-up
// get_child_categories_for_group('group-skin-care');
// get_child_categories_for_group('group-hair-care');
// get_child_categories_for_group('group-make-up');

?>



