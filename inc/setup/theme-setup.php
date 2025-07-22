<?php
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     *
     * Note that this function is hooked into the after_setup_theme hook, which
     * runs before the init hook. The init hook is too late for some features, such
     * as indicating support for post thumbnails.
     */
    function bsc_2_0_setup() {
        /*
            * Make theme available for translation.
            * Translations can be filed in the /languages/ directory.
            * If you're building a theme based on BSC2, use a find and replace
            * to change 'bsc-2-0' to the name of your theme in all the template files.
            */
        load_theme_textdomain( 'bsc-2-0', get_template_directory() . '/languages' );

        // Add default posts and comments RSS feed links to head.
        add_theme_support( 'automatic-feed-links' );

        /*
            * Let WordPress manage the document title.
            * By adding theme support, we declare that this theme does not use a
            * hard-coded <title> tag in the document head, and expect WordPress to
            * provide it for us.
            */
        add_theme_support( 'title-tag' );

        /*
            * Enable support for Post Thumbnails on posts and pages.
            *
            * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
            */
        add_theme_support( 'post-thumbnails' );

        // This theme uses wp_nav_menu() in one location.
        register_nav_menus(
            array(
                'menu-1' => esc_html__( 'Primary', 'bsc-2-0' ),
            )
        );

        /*
            * Switch default core markup for search form, comment form, and comments
            * to output valid HTML5.
            */
        add_theme_support(
            'html5',
            array(
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
            )
        );

        // Set up the WordPress core custom background feature.
        add_theme_support(
            'custom-background',
            apply_filters(
                'bsc_2_0_custom_background_args',
                array(
                    'default-color' => 'ffffff',
                    'default-image' => '',
                )
            )
        );

        // Add theme support for selective refresh for widgets.
        add_theme_support( 'customize-selective-refresh-widgets' );

        /**
         * Add support for core custom logo.
         *
         * @link https://codex.wordpress.org/Theme_Logo
         */
        add_theme_support(
            'custom-logo',
            array(
                'height'      => 250,
                'width'       => 250,
                'flex-width'  => true,
                'flex-height' => true,
            )
        );
    }
    add_action( 'after_setup_theme', 'bsc_2_0_setup' );


    /**
     * Set the content width in pixels, based on the theme's design and stylesheet.
     *
     * Priority 0 to make it available to lower priority callbacks.
     *
     * @global int $content_width
     */
    function bsc_2_0_content_width() {
        $GLOBALS['content_width'] = apply_filters( 'bsc_2_0_content_width', 640 );
    }
    add_action( 'after_setup_theme', 'bsc_2_0_content_width', 0 );



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
        'slug'     => 'mi-cuenta',
        'title'    => 'Mi Cuenta',
        'shortcode' => '[woocommerce_my_account]',
        ]
    ];

    foreach ($pages as $page) {
        $existing = get_page_by_path($page['slug']);
        if (!$existing) {
        $post_id = wp_insert_post([
            'post_title'   => $page['title'],
            'post_name'    => $page['slug'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => isset($page['shortcode']) ? $page['shortcode'] : '',
        ]);

        if (!is_wp_error($post_id)) {
            if (!empty($page['template'])) {
            update_post_meta($post_id, '_wp_page_template', $page['template']);
            }
        }
        }
    }
}

function bsc_create_default_pages_once() {
  if (!get_option('bsc_default_pages_created')) {
    bsc_create_default_pages();
    update_option('bsc_default_pages_created', true);
  }
}
add_action('after_setup_theme', 'bsc_create_default_pages_once');
?>