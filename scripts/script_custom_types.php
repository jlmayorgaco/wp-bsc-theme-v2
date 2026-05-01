<?php
/**
 * Register Custom Post Type: Home Slides
 * Add Meta Box and Fields (No Plugin)
 */

add_action('init', 'bsc_register_home_slide_post_type');
function bsc_register_home_slide_post_type() {
    register_post_type('home_slide', [
        'labels' => [
            'name'          => __('BSC Home Slides', 'bsc'),
            'singular_name' => __('Home Slide', 'bsc'),
        ],
        'public'        => true,
        'has_archive'   => false,
        'menu_icon'     => 'dashicons-slides',
        'supports'      => ['title', 'thumbnail'],
        'show_in_rest'  => true,
        'menu_position' => 20,
        // BSC-021: hide CPT auto-menu; Hero Slides submenu lives under BSC in bsc-admin-menu.php
        'show_in_menu'  => false,
    ]);
}

// -----------------------------------------------------------------------------
// Meta Box Setup
// -----------------------------------------------------------------------------

add_action('add_meta_boxes', 'bsc_add_home_slide_meta_box');
function bsc_add_home_slide_meta_box() {
    add_meta_box(
        'bsc_home_slide_fields',
        __('Slide Options', 'bsc'),
        'bsc_render_home_slide_fields',
        'home_slide',
        'normal',
        'default'
    );
}

function bsc_render_home_slide_fields($post) {
    // Define all fields once for scalability
    $fields = [
        'slide_subtitle'    => ['label' => 'Subtitle', 'type' => 'text'],
        'slide_button_text' => ['label' => 'Button Text', 'type' => 'text'],
        'slide_button_link' => ['label' => 'Button Link', 'type' => 'url'],
    ];

    wp_nonce_field('bsc_save_slide_meta', 'bsc_slide_nonce');

    foreach ($fields as $name => $config) {
        $value = get_post_meta($post->ID, "_{$name}", true);
        printf(
            '<p><label for="%1$s">%2$s</label><br>
            <input class="regular-text" type="%3$s" name="%1$s" id="%1$s" value="%4$s" /></p>',
            esc_attr($name),
            esc_html($config['label']),
            esc_attr($config['type']),
            esc_attr($value)
        );
    }
}

// -----------------------------------------------------------------------------
// Save Meta Fields
// -----------------------------------------------------------------------------

add_action('save_post_home_slide', 'bsc_save_home_slide_meta');
function bsc_save_home_slide_meta($post_id) {
    // Verify nonce
    if (
        !isset($_POST['bsc_slide_nonce']) ||
        !wp_verify_nonce($_POST['bsc_slide_nonce'], 'bsc_save_slide_meta')
    ) {
        return;
    }

    // Auto-save guard
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Permissions check
    if (!current_user_can('edit_post', $post_id)) return;

    // Save expected fields
    $fields = ['slide_subtitle', 'slide_button_text', 'slide_button_link'];

    foreach ($fields as $field) {
        $value = $_POST[$field] ?? '';
        $clean = $field === 'slide_button_link'
            ? esc_url_raw($value)
            : sanitize_text_field($value);

        update_post_meta($post_id, "_$field", $clean);
    }
}

// BSC-021: bsc-home-favorites is registered as a submenu of BSC in bsc-admin-menu.php.
// The standalone add_menu_page() has been removed to avoid a duplicate top-level entry.

function bsc_home_favorites_settings_page() {
    ?>
    <div class="wrap">
        <h1>BSC Favorites</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('bsc_home_favorites_group');
            do_settings_sections('bsc-home-favorites');
            submit_button('Save Favorites');
            ?>
        </form>
    </div>
    <?php
}

function bsc_home_favorites_settings_init() {
    register_setting('bsc_home_favorites_group', 'bsc_home_favorites');

    add_settings_section(
        'bsc_fav_section',
        'Select Favorite Product SKUs (comma-separated)',
        null,
        'bsc-home-favorites'
    );

    $categories = [
        'ultimos_lanzamientos' => 'Últimos Lanzamientos',
        'piel_seca' => 'Piel Seca',
        'piel_normal' => 'Piel Normal',
        'piel_mixta' => 'Piel Mixta',
        'piel_grasa' => 'Piel Grasa',
        'hair_care' => 'Hair Care',
        'maquillaje' => 'Maquillaje',
    ];

    foreach ($categories as $key => $label) {
        add_settings_field(
            $key,
            $label,
            function () use ($key) {
                $options = get_option('bsc_home_favorites');
                $value = isset($options[$key]) ? esc_attr($options[$key]) : '';
                echo "<input class='regular-text' type='text' name='bsc_home_favorites[$key]' value='$value' placeholder='e.g. BSC:SK:1,BSC:HC:99' />";
            },
            'bsc-home-favorites',
            'bsc_fav_section'
        );
    }
}
add_action('admin_init', 'bsc_home_favorites_settings_init');
