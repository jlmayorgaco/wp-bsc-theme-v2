<?php

/**
 * Enqueue scripts and styles.
 */

add_action('init', function () {
	switch_to_locale('es_ES');
});

function bsc_2_0_scripts() {
	wp_enqueue_style('bsc-2-0-style', get_stylesheet_uri(), array(), _S_VERSION);
	wp_style_add_data('bsc-2-0-style', 'rtl', 'replace');

	// Scripts globales — necesarios en todas las páginas
	wp_enqueue_script(
		'bsc-2-0-navigation',
		get_template_directory_uri() . '/js/navigation.js',
		array(),
		_S_VERSION,
		true
	);

	wp_enqueue_script(
		'bsc-2-0-search',
		get_template_directory_uri() . '/js/search.js',
		array(),
		_S_VERSION,
		true
	);

	wp_localize_script('bsc-2-0-search', 'bsc_search', [
		'ajax_url'        => admin_url('admin-ajax.php'),
		'placeholder_img' => get_template_directory_uri() . '/images/bsc__placeholder_product.jpg',
		'nonce'           => wp_create_nonce('bsc_ajax_action'),
	]);

	wp_enqueue_script(
		'bsc-2-0-add-to-cart',
		get_template_directory_uri() . '/js/cart.js',
		array('jquery'),
		_S_VERSION,
		true
	);

	wp_localize_script('bsc-2-0-add-to-cart', 'bsc_ajax', [
		'ajax_url'  => admin_url('admin-ajax.php'),
		'theme_uri' => get_template_directory_uri(),
		'nonce'     => wp_create_nonce('bsc_ajax_action'),
	]);

	// Slider mobile hint
	wp_enqueue_script(
		'bsc-2-0-slider-mobile-hint',
		get_template_directory_uri() . '/js/bsc-slider-mobile-hint.js',
		array(),
		_S_VERSION,
		true
	);

	// Tabs — solo en home (sección favoritos por tipo de piel)
	if (is_front_page()) {
		wp_enqueue_script(
			'bsc-2-0-tabs',
			get_template_directory_uri() . '/js/tabs.js',
			array('jquery'),
			_S_VERSION,
			true
		);
	}

	// Filtros de productos — solo en catálogo y páginas de categoría
	if (is_shop() || is_product_category() || is_product_tag() || is_archive()) {
		wp_enqueue_script(
			'bsc-2-0-products-filters',
			get_template_directory_uri() . '/js/filters.js',
			array('jquery'),
			_S_VERSION,
			true
		);

		wp_localize_script('bsc-2-0-products-filters', 'bsc_ajax', [
			'ajax_url'  => admin_url('admin-ajax.php'),
			'theme_uri' => get_template_directory_uri(),
			'nonce'     => wp_create_nonce('bsc_ajax_action'),
		]);
	}

	// Checkout — solo en la página de checkout
	if (is_checkout()) {
		wp_enqueue_script(
			'bsc-2-0-checkout',
			get_template_directory_uri() . '/js/checkout.js',
			array('jquery'),
			_S_VERSION,
			true
		);
	}

	// Cupones — solo en carrito y checkout
	if (is_cart() || is_checkout()) {
		wp_enqueue_script(
			'bsc-2-0-coupons',
			get_template_directory_uri() . '/js/coupons.js',
			array('jquery'),
			_S_VERSION,
			true
		);
	}

	if (is_singular() && comments_open() && get_option('thread_comments')) {
		wp_enqueue_script('comment-reply');
	}
}
add_action('wp_enqueue_scripts', 'bsc_2_0_scripts');
?>