
<?php
    $menu_name = 'BSC Header Nav Menu'; 
    $menu_object = wp_get_nav_menu_object($menu_name);
    $menu_structure = [];
    // Check if the menu exists
    if ($menu_object) {
        // Get all menu items for the specified menu
        $menu_items = wp_get_nav_menu_items($menu_object->term_id);
        // Loop through each menu item and build the structure
        foreach ($menu_items as $menu_item) {
            // Parent items (Top level)
            if (!$menu_item->menu_item_parent) {
                $menu_structure[$menu_item->ID] = [
                    'title' => $menu_item->title,
                    'url' => $menu_item->url,
                    'children' => []
                ];
            } else {
                // Child items (Submenus)
                
                $menu_structure[$menu_item->menu_item_parent]['children'][] = [
                    'title' => $menu_item->title,
                    'url' => $menu_item->url
                ];
            }
        }
    }

    echo json_encode($menu_structure, true);

    // Now loop through the menu structure to create the custom HTML
    echo '<ul class="custom-menu">';

    foreach ($menu_structure as $menu_id => $item) {
        // Top level menu items
        echo '<li>';
        echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['title']) . '</a>';

        // Check if there are children (submenu)
        if (!empty($item['children'])) {
            echo '<ul class="sub-menu">'; // Start submenu
            foreach ($item['children'] as $child) {
                // Submenu items
                echo '<li>';
                echo '<a href="' . esc_url($child['url']) . '">' . esc_html($child['title']) . '</a>';
                echo '</li>';
            }
            echo '</ul>'; // End submenu
        }

        echo '</li>';
    }

    echo '</ul>';
?>

<header class="bsc bsc__header bsc__header--desktop">
    <div class="header__container">
        <div class="header__image">
            <img src="<?php echo get_template_directory_uri();?>/images/bsc_logo_header.png">
        </div>
        <div class="header__nav">
            <?php
                
			?>
        </div>
        <div class="header__menu">
           
        </div>
    </div>
</header>

<header class="bsc bsc__header bsc__header--mobile">
    
</header>