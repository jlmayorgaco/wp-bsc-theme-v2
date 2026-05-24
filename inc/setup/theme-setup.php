<?php
/**
 * Theme Setup for BSC 2.0
 */

if (!function_exists( 'bsc_2_0_setup' )) {
	function bsc_2_0_setup() {
		load_theme_textdomain( 'bsc-2-0', get_template_directory() . '/languages' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );

		// BSC-042: register responsive image sizes for srcset generation
		add_image_size( 'bsc-card', 400, 400, true );   // product card (square crop)
		add_image_size( 'bsc-hero', 1440, 600, true );  // hero slider
		add_image_size( 'bsc-thumb', 120, 120, true );  // thumbnails

		// BSC-050: convert new uploads to WebP when the server supports it
		add_filter(
			'image_editor_output_format',
			function ( array $formats ): array {
				if ( function_exists( 'imagewebp' ) ) { // GD WebP support check
					$formats['image/jpeg'] = 'image/webp';
					$formats['image/png']  = 'image/webp';
				}
				return $formats;
			}
		);

		register_nav_menus(
			array(
				'menu-1' => esc_html__( 'Primary', 'bsc-2-0' ),
			)
		);

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

		add_theme_support(
			'custom-background',
			array(
				'default-color' => 'ffffff',
			)
		);

		add_theme_support( 'customize-selective-refresh-widgets' );

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
}

if (!function_exists( 'bsc_2_0_content_width' )) {
	function bsc_2_0_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'bsc_2_0_content_width', 640 );
	}
	add_action( 'after_setup_theme', 'bsc_2_0_content_width', 0 );
}

/**
 * Create default pages (login/register/mi cuenta/bubble points + K-Beauty landing)
 */
if (!function_exists( 'bsc_create_default_pages' )) {
	function bsc_create_default_pages() {
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

		$pages = array(
			array(
				'slug'     => 'login',
				'title'    => 'Iniciar Sesión',
				'template' => 'page-login.php',
			),
			array(
				'slug'     => 'register',
				'title'    => 'Registrarse',
				'template' => 'page-register.php',
			),
			array(
				'slug'      => 'mi-cuenta',
				'title'     => 'Mi Cuenta',
				'shortcode' => '[woocommerce_my_account]',
			),
			array(
				'slug'     => 'bubble-points',
				'title'    => 'Bubble Points',
				'template' => 'page-bubble-points.php',
				'parent'   => 'mi-cuenta',
			),
			// K-Beauty /product-category/ landing page
			array(
				'slug'     => 'product-category',
				'title'    => 'K-Beauty',
				'template' => 'template-bsc-shop-landing.php',
			),
			// BSC required pages — static content pages
			array(
				'slug'     => 'bubble-creators',
				'title'    => 'Bubble Creators',
				'template' => 'page-bubble-creators.php',
			),
			array(
				'slug'     => 'shipping-returns',
				'title'    => 'Envíos y Devoluciones',
				'template' => 'page-shipping-returns.php',
			),
			array(
				'slug'     => 'faq',
				'title'    => 'Preguntas Frecuentes',
				'template' => 'page-faq.php',
			),
			array(
				'slug'     => 'contact-us',
				'title'    => 'Contacto',
				'template' => 'page-contact-us.php',
			),
			array(
				'slug'    => 'registro-familia-bubbles',
				'title'   => '¡Bienvenida a la familia Bubbles!',
				'content' => sprintf( '<p>Gracias por registrarte. Ya eres parte de la familia Bubbles Skin Care.</p><p><a href="%s">Explorar la tienda</a></p>', esc_url( $shop_url ) ),
			),
		);

		foreach ($pages as $page) {
			$existing = get_page_by_path( $page['slug'] );

			if (!$existing) {
				// Resolve parent ID if needed
				$parent_id = 0;
				if (!empty( $page['parent'] )) {
					$parent = get_page_by_path( $page['parent'] );
					if ($parent) {
						$parent_id = $parent->ID;
					}
				}

				$content = $page['shortcode'] ?? $page['content'] ?? '';
				$post_id = wp_insert_post(
					array(
						'post_title'   => $page['title'],
						'post_name'    => $page['slug'],
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'post_content' => $content,
						'post_parent'  => $parent_id,
					)
				);

				if (!is_wp_error( $post_id ) && !empty( $page['template'] )) {
					update_post_meta( $post_id, '_wp_page_template', $page['template'] );
				}
			} else {
				// If the page already exists and we specified a template, ensure it's set
				if (!empty( $page['template'] )) {
					$current_template = get_page_template_slug( $existing->ID );
					if ($current_template !== $page['template']) {
						update_post_meta( $existing->ID, '_wp_page_template', $page['template'] );
					}
				}
			}
		}
	}

	function bsc_create_default_pages_once() {
		if (!get_option( 'bsc_default_pages_created' )) {
			// First run: create all required pages
			bsc_create_default_pages();
			update_option( 'bsc_default_pages_created', true );
			delete_transient( 'bsc_pages_checked' );
		} else {
			// Subsequent runs: check templates at most once per day to avoid
			// running N get_page_by_path() queries on every single request
			if (!get_transient( 'bsc_pages_checked' )) {
				bsc_create_default_pages();
				set_transient( 'bsc_pages_checked', true, DAY_IN_SECONDS );
			}
		}
	}

	add_action( 'after_setup_theme', 'bsc_create_default_pages_once' );
	// after_switch_theme: clear transient first (priority 5), then run bootstrap (priority 10)
	add_action( 'after_switch_theme', 'bsc_create_default_pages_once', 10 );
}

/**
 * On theme (re)activation: clear daily transient so bootstrap always runs fully,
 * then flush rewrite rules so new page slugs resolve immediately.
 * Priority 5 ensures this runs before bsc_create_default_pages_once (priority 10).
 */
add_action(
	'after_switch_theme',
	function () {
		delete_transient( 'bsc_pages_checked' );
		flush_rewrite_rules();
	},
	5
);

// BSC-029: create operational roles on theme (re)activation
add_action(
	'after_switch_theme',
	function () {
		if ( class_exists( 'BSC_Roles' ) ) {
			BSC_Roles::create();
		}
	},
	15
);

// BSC-066: also create roles at init in case theme was already active
// (safe: BSC_Roles::create() is guarded by get_role checks)
add_action(
	'init',
	function () {
		if ( class_exists( 'BSC_Roles' ) ) {
			BSC_Roles::create();
			BSC_Roles::grant_admin_wc_caps(); // ensure admin has all WC caps (WC 7.x+ fix)
		}
	},
	1
);

/**
 * Load bundled plugins
 */
if (!function_exists( 'bsc_load_theme_plugins' )) {
	function bsc_load_theme_plugins() {
		$plugins_file = get_template_directory() . '/plugins/index.php';
		if (file_exists( $plugins_file )) {
			require_once $plugins_file;
		}
	}
	add_action( 'after_setup_theme', 'bsc_load_theme_plugins' );
	add_action( 'after_switch_theme', 'bsc_load_theme_plugins' );
}


// ── BSC-039: Invalidate slider and menu category transients on content change ──
add_action( 'save_post_product', 'bsc_clear_slider_cache' );
add_action( 'edited_term', 'bsc_clear_category_cache', 10, 3 );
add_action( 'created_term', 'bsc_clear_category_cache', 10, 3 );

function bsc_clear_slider_cache(): void {
	global $wpdb;
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk invalidation of slider transients by prefix.
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bsc_slider_%' OR option_name LIKE '_transient_timeout_bsc_slider_%'"
	);
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

function bsc_clear_category_cache( int $term_id, int $tt_id, string $taxonomy ): void {
	if ( $taxonomy === 'product_cat' ) {
		delete_transient( 'bsc_menu_categories' );
	}
}

add_action(
	'woocommerce_save_account_details',
	function ( int $user_id ): void {
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce validates the save-account-details nonce before this hook runs.
		// BSC-057: needs fields — plain text only, no HTML
		foreach ( array( 'bsc_needs1', 'bsc_needs2', 'bsc_needs3', 'bsc_needs4' ) as $k ) {
			if ( isset( $_POST[ $k ] ) ) {
				update_user_meta( $user_id, $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) );
			}
		}

		// BSC-057: birthday — validate format YYYY-MM-DD
		if ( isset( $_POST['account_birthday'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_POST['account_birthday'] ) );
			if ( $raw === '' || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ) {
				update_user_meta( $user_id, 'bsc_birthday', $raw );
			}
		}

		// BSC-057: skin_type — whitelist
		$allowed_skin_types = array( 'Grasa', 'Mixta', 'Seca', 'Normal', 'Normal a seca', 'Normal a grasa' );
		if ( isset( $_POST['account_skin_type'] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST['account_skin_type'] ) );
			if ( $val === '' || in_array( $val, $allowed_skin_types, true ) ) {
				update_user_meta( $user_id, 'bsc_skin_type', $val );
			}
		}

		// BSC-057: sensitivity — whitelist
		$allowed_sensitivities = array( 'Sensible normal', 'Muy sensible', 'No sensible' );
		if ( isset( $_POST['account_sensitivity'] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST['account_sensitivity'] ) );
			if ( $val === '' || in_array( $val, $allowed_sensitivities, true ) ) {
				update_user_meta( $user_id, 'bsc_sensitivity', $val );
			}
		}
    // phpcs:enable WordPress.Security.NonceVerification.Missing
	},
	10,
	1
);
