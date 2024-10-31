<?php
function my_custom_menu() {
    register_nav_menus(
        array(
            'my-custom-menu' => _( 'My Custom Menu' ),
            'my-custom-menu-2' =>_('My Second Custom Menu')
        )
    );
}
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



