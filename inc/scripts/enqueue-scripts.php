<?php

/**
 * Enqueue scripts and styles.
 */

add_action('init', function () {
	switch_to_locale('es_ES');
});

function bsc_2_0_scripts() {
	wp_enqueue_style('bsc-2-0-style', get_stylesheet_uri(), array(), BSC_THEME_VERSION);
	wp_style_add_data('bsc-2-0-style', 'rtl', 'replace');

	// Scripts globales â€” necesarios en todas las pÃ¡ginas
	wp_enqueue_script(
		'bsc-2-0-navigation',
		get_template_directory_uri() . '/js/navigation.js',
		array(),
		BSC_THEME_VERSION,
		true
	);

	wp_localize_script('bsc-2-0-navigation', 'bsc_ajax', [
		'ajax_url'  => admin_url('admin-ajax.php'),
		'theme_uri' => get_template_directory_uri(),
		'cart_url'  => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
		'nonce'     => wp_create_nonce('bsc_ajax_action'),
	]);

	// Mobile menu â€” extracted from header.php inline script
	wp_enqueue_script(
		'bsc-2-0-mobile-menu',
		get_template_directory_uri() . '/js/mobile-menu.js',
		array(),
		BSC_THEME_VERSION,
		true
	);

	wp_enqueue_script(
		'bsc-2-0-search',
		get_template_directory_uri() . '/js/search.js',
		array(),
		BSC_THEME_VERSION,
		true
	);

	wp_localize_script('bsc-2-0-search', 'bsc_search', [
		'ajax_url'        => admin_url('admin-ajax.php'),
		'placeholder_img' => get_template_directory_uri() . '/images/bsc__placeholder_product.jpg',
		'nonce'           => wp_create_nonce('bsc_ajax_action'),
	]);

	$needs_cart_script = is_front_page()
		|| is_shop()
		|| is_product()
		|| is_product_category()
		|| is_product_tag()
		|| is_cart()
		|| is_checkout()
		|| is_post_type_archive('product');

	if ( $needs_cart_script ) {
		wp_enqueue_script(
			'bsc-2-0-add-to-cart',
			get_template_directory_uri() . '/js/cart.js',
			array('jquery'),
			BSC_THEME_VERSION,
			true
		);
	}

	// Slider mobile hint
	wp_enqueue_script(
		'bsc-2-0-slider-mobile-hint',
		get_template_directory_uri() . '/js/bsc-slider-mobile-hint.js',
		array(),
		BSC_THEME_VERSION,
		true
	);

	// Scripts solo en home
	if (is_front_page()) {
		// Tabs â€” secciÃ³n favoritos por tipo de piel
		wp_enqueue_script(
			'bsc-2-0-tabs',
			get_template_directory_uri() . '/js/tabs.js',
			array('jquery'),
			BSC_THEME_VERSION,
			true
		);

		// Newsletter form handler
		wp_enqueue_script(
			'bsc-2-0-newsletter',
			get_template_directory_uri() . '/js/newsletter.js',
			array('jquery'),
			BSC_THEME_VERSION,
			true
		);

		// Swiper init â€” depends on swiper-js (loaded via script_init.php)
		wp_enqueue_script(
			'bsc-2-0-swiper-init',
			get_template_directory_uri() . '/js/swiper-init.js',
			array('swiper-js'),
			BSC_THEME_VERSION,
			true
		);
	}

	// Filtros y category filter â€” solo en catÃ¡logo y pÃ¡ginas de categorÃ­a
	if (is_shop() || is_product_category() || is_product_tag() || is_archive()) {
		// AJAX product filters (sidebar)
		wp_enqueue_script(
			'bsc-2-0-products-filters',
			get_template_directory_uri() . '/js/filters.js',
			array('jquery', 'bsc-2-0-add-to-cart'),
			BSC_THEME_VERSION,
			true
		);

		// Client-side category filter (renderLevel2 subcategory tabs)
		wp_enqueue_script(
			'bsc-2-0-category-filter',
			get_template_directory_uri() . '/js/category-filter.js',
			array(),
			BSC_THEME_VERSION,
			true
		);
	}

	// Checkout â€” solo en la pÃ¡gina de checkout
	if (is_checkout()) {
		wp_enqueue_script(
			'bsc-2-0-checkout',
			get_template_directory_uri() . '/js/checkout.js',
			array('jquery'),
			BSC_THEME_VERSION,
			true
		);
	}

	// Contact form â€” solo en la landing de contacto
	if ( is_page_template( 'page-contact-us.php' ) ) {
		wp_enqueue_script(
			'bsc-2-0-contact',
			get_template_directory_uri() . '/js/contact.js',
			array( 'jquery', 'bsc-2-0-navigation' ),
			BSC_THEME_VERSION,
			true
		);
	}

	// Bubble Creators form â€” solo en su landing
	if ( is_page_template( 'page-bubble-creators.php' ) ) {
		wp_enqueue_script(
			'bsc-2-0-creator-apply',
			get_template_directory_uri() . '/js/creator-apply.js',
			array( 'jquery', 'bsc-2-0-navigation' ),
			BSC_THEME_VERSION,
			true
		);
	}

	if ( is_page_template( 'page-login.php' ) ) {
		wp_enqueue_script(
			'bsc-2-0-login',
			get_template_directory_uri() . '/js/login.js',
			array(),
			BSC_THEME_VERSION,
			true
		);
	}

	if ( is_page_template( 'page-register.php' ) ) {
		wp_enqueue_script(
			'bsc-2-0-register',
			get_template_directory_uri() . '/js/register.js',
			array(),
			BSC_THEME_VERSION,
			true
		);
	}

	if ( is_page_template( 'page-mi-cuenta.php' ) ) {
		wp_enqueue_script(
			'bsc-2-0-account-page',
			get_template_directory_uri() . '/js/account-page.js',
			array(),
			BSC_THEME_VERSION,
			true
		);
	}

	// Cupones â€” solo en carrito y checkout
	if (is_cart() || is_checkout()) {
		wp_enqueue_script(
			'bsc-2-0-coupons',
			get_template_directory_uri() . '/js/coupons.js',
			array('jquery'),
			BSC_THEME_VERSION,
			true
		);
	}

	if ( is_account_page() && is_wc_endpoint_url( 'edit-account' ) ) {
		wp_enqueue_script(
			'bsc-2-0-account-edit',
			get_template_directory_uri() . '/js/account-edit.js',
			array(),
			BSC_THEME_VERSION,
			true
		);
	}

	if ( is_account_page() && is_wc_endpoint_url( 'view-order' ) ) {
		wp_enqueue_script(
			'bsc-2-0-account-view-order',
			get_template_directory_uri() . '/js/account-view-order.js',
			array(),
			BSC_THEME_VERSION,
			true
		);
	}

	if (is_singular() && comments_open() && get_option('thread_comments')) {
		wp_enqueue_script('comment-reply');
	}
}
add_action('wp_enqueue_scripts', 'bsc_2_0_scripts');
?>
