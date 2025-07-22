<?php

/**
 * Enqueue scripts and styles.
 */
function bsc_2_0_scripts() {
	wp_enqueue_style( 'bsc-2-0-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'bsc-2-0-style', 'rtl', 'replace' );

	wp_enqueue_script( 'bsc-2-0-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );
	wp_enqueue_script( 'bsc-2-0-search', get_template_directory_uri() . '/js/search.js', array(), _S_VERSION, true );
	wp_enqueue_script( 'bsc-2-0-tabs', get_template_directory_uri() . '/js/tabs.js', ['jquery'], _S_VERSION, true );
	wp_enqueue_script( 'bsc-2-0-add-to-cart', get_template_directory_uri() . '/js/cart.js', ['jquery'], _S_VERSION, true );
	wp_enqueue_script( 'bsc-2-0-products-filters', get_template_directory_uri() . '/js/filters.js', ['jquery'], _S_VERSION, true );
	wp_enqueue_script( 'bsc-2-0-checkout', get_template_directory_uri() . '/js/checkout.js', ['jquery'], _S_VERSION, true );
	wp_enqueue_script( 'bsc-2-0-coupons', get_template_directory_uri() . '/js/coupons.js', ['jquery'], _S_VERSION, true );
 	
	wp_localize_script('bsc-2-0-add-to-cart', 'bsc_ajax', [
		'ajax_url' => admin_url('admin-ajax.php'),
	]);
	wp_localize_script('bsc-2-0-products-filter', 'bsc_ajax', [
		'ajax_url' => admin_url('admin-ajax.php'),
	]);
	wp_localize_script('bsc-2-0-checkout', 'bsc_ajax', [
		'ajax_url' => admin_url('admin-ajax.php'),
	]);
	wp_localize_script('bsc-2-0-coupons', 'bsc_ajax', [
		'ajax_url' => admin_url('admin-ajax.php'),
	]);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'bsc_2_0_scripts' );

?>