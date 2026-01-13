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
            // 👇 NEW: K-Beauty /product-category/ landing page
            [
                'slug'     => 'product-category',
                'title'    => 'K-Beauty',
                'template' => 'template-bsc-shop-landing.php',
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

                $post_id = wp_insert_post([
                    'post_title'   => $page['title'],
                    'post_name'    => $page['slug'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => $page['shortcode'] ?? '',
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
        // We already use this flag, now it also covers the K-Beauty landing
        if (!get_option('bsc_default_pages_created')) {
            bsc_create_default_pages();
            update_option('bsc_default_pages_created', true);
        } else {
            // Optional: run again but non-destructively to ensure templates stay correct
            bsc_create_default_pages();
        }
    }

    add_action('after_setup_theme', 'bsc_create_default_pages_once');
    add_action('after_switch_theme', 'bsc_create_default_pages_once');
}

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
