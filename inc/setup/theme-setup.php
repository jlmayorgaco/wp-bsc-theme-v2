<?php
/**
 * Theme Setup for BSC 2.0
 */

if (!function_exists('bsc_2_0_setup')) {
    function bsc_2_0_setup() {
        load_theme_textdomain('bsc-2-0', get_template_directory() . '/languages');
        add_theme_support('automatic-feed-links');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');

        register_nav_menus([
            'menu-1' => esc_html__('Primary', 'bsc-2-0'),
        ]);

        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ]);

        add_theme_support('custom-background', [
            'default-color' => 'ffffff',
        ]);

        add_theme_support('customize-selective-refresh-widgets');

        add_theme_support('custom-logo', [
            'height'      => 250,
            'width'       => 250,
            'flex-width'  => true,
            'flex-height' => true,
        ]);
    }
    add_action('after_setup_theme', 'bsc_2_0_setup');
}

if (!function_exists('bsc_2_0_content_width')) {
    function bsc_2_0_content_width() {
        $GLOBALS['content_width'] = apply_filters('bsc_2_0_content_width', 640);
    }
    add_action('after_setup_theme', 'bsc_2_0_content_width', 0);
}

/**
 * Create default pages (login/register/mi cuenta/bubble points + K-Beauty landing)
 */
if (!function_exists('bsc_create_default_pages')) {
    function bsc_create_default_pages() {
        $pages = [
            [
                'slug'     => 'login',
                'title'    => 'Iniciar Sesión',
                'template' => 'page-login.php',
            ],
            [
                'slug'     => 'register',
                'title'    => 'Registrarse',
                'template' => 'page-register.php',
            ],
            [
                'slug'      => 'mi-cuenta',
                'title'     => 'Mi Cuenta',
                'shortcode' => '[woocommerce_my_account]',
            ],
            [
                'slug'     => 'bubble-points',
                'title'    => 'Bubble Points',
                'template' => 'page-bubble-points.php',
                'parent'   => 'mi-cuenta',
            ],
                // K-Beauty /product-category/ landing page
            [
                'slug'     => 'product-category',
                'title'    => 'K-Beauty',
                'template' => 'template-bsc-shop-landing.php',
            ],
            // BSC required pages — static content pages
            [
                'slug'     => 'bubble-creators',
                'title'    => 'Bubble Creators',
                'template' => 'page-bubble-creators.php',
            ],
            [
                'slug'     => 'shipping-returns',
                'title'    => 'Envíos y Devoluciones',
                'template' => 'page-shipping-returns.php',
            ],
            [
                'slug'     => 'faq',
                'title'    => 'Preguntas Frecuentes',
                'template' => 'page-faq.php',
            ],
            [
                'slug'     => 'contact-us',
                'title'    => 'Contacto',
                'template' => 'page-contact-us.php',
            ],
            [
                'slug'    => 'registro-familia-bubbles',
                'title'   => '¡Bienvenida a la familia Bubbles!',
                'content' => '<p>Gracias por registrarte. Ya eres parte de la familia Bubbles Skin Care. 🌸</p><p><a href="/shop/">Explorar la tienda</a></p>',
            ],
        ];

        foreach ($pages as $page) {
            $existing = get_page_by_path($page['slug']);

            if (!$existing) {
                // Resolve parent ID if needed
                $parent_id = 0;
                if (!empty($page['parent'])) {
                    $parent = get_page_by_path($page['parent']);
                    if ($parent) {
                        $parent_id = $parent->ID;
                    }
                }

                $content = $page['shortcode'] ?? $page['content'] ?? '';
                $post_id = wp_insert_post([
                    'post_title'   => $page['title'],
                    'post_name'    => $page['slug'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => $content,
                    'post_parent'  => $parent_id,
                ]);

                if (!is_wp_error($post_id) && !empty($page['template'])) {
                    update_post_meta($post_id, '_wp_page_template', $page['template']);
                }
            } else {
                // If the page already exists and we specified a template, ensure it's set
                if (!empty($page['template'])) {
                    $current_template = get_page_template_slug($existing->ID);
                    if ($current_template !== $page['template']) {
                        update_post_meta($existing->ID, '_wp_page_template', $page['template']);
                    }
                }
            }
        }
    }

    function bsc_create_default_pages_once() {
        if (!get_option('bsc_default_pages_created')) {
            // First run: create all required pages
            bsc_create_default_pages();
            update_option('bsc_default_pages_created', true);
            delete_transient('bsc_pages_checked');
        } else {
            // Subsequent runs: check templates at most once per day to avoid
            // running N get_page_by_path() queries on every single request
            if (!get_transient('bsc_pages_checked')) {
                bsc_create_default_pages();
                set_transient('bsc_pages_checked', true, DAY_IN_SECONDS);
            }
        }
    }

    add_action('after_setup_theme', 'bsc_create_default_pages_once');
    // after_switch_theme: clear transient first (priority 5), then run bootstrap (priority 10)
    add_action('after_switch_theme', 'bsc_create_default_pages_once', 10);
}

/**
 * On theme (re)activation: clear daily transient so bootstrap always runs fully,
 * then flush rewrite rules so new page slugs resolve immediately.
 * Priority 5 ensures this runs before bsc_create_default_pages_once (priority 10).
 */
add_action('after_switch_theme', function () {
    delete_transient('bsc_pages_checked');
    flush_rewrite_rules();
}, 5);

// BSC-029: create operational roles on theme (re)activation
add_action('after_switch_theme', function () {
    if ( class_exists('BSC_Roles') ) {
        BSC_Roles::create();
    }
}, 15);

/**
 * Load bundled plugins
 */
if (!function_exists('bsc_load_theme_plugins')) {
    function bsc_load_theme_plugins() {
        $plugins_file = get_template_directory() . '/plugins/index.php';
        if (file_exists($plugins_file)) {
            require_once $plugins_file;
        }
    }
    add_action('after_setup_theme', 'bsc_load_theme_plugins');
    add_action('after_switch_theme', 'bsc_load_theme_plugins');
}


add_action('woocommerce_save_account_details', function($user_id) {
  foreach (['bsc_needs1','bsc_needs2','bsc_needs3','bsc_needs4'] as $k) {
    if (isset($_POST[$k])) update_user_meta($user_id, $k, sanitize_text_field(wp_unslash($_POST[$k])));
  }
  if (isset($_POST['account_birthday'])) update_user_meta($user_id, 'bsc_birthday', sanitize_text_field(wp_unslash($_POST['account_birthday'])));
  if (isset($_POST['account_skin_type'])) update_user_meta($user_id, 'bsc_skin_type', sanitize_text_field(wp_unslash($_POST['account_skin_type'])));
  if (isset($_POST['account_sensitivity'])) update_user_meta($user_id, 'bsc_sensitivity', sanitize_text_field(wp_unslash($_POST['account_sensitivity'])));
}, 10, 1);
