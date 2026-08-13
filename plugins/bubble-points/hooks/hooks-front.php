<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'bsc_bp_enqueue_scripts' );

function bsc_bp_enqueue_scripts() {
	if ( is_admin() || ! is_page_template( 'page-bubble-points.php' ) ) {
		return;
	}

	wp_enqueue_script(
		'bsc-bubble-points-scroll',
		get_stylesheet_directory_uri() . '/plugins/bubble-points/scripts/bubble-points-scroll.js',
		array(),
		(string) filemtime( get_stylesheet_directory() . '/plugins/bubble-points/scripts/bubble-points-scroll.js' ),
		true
	);

	wp_enqueue_script(
		'bsc-bubble-points-modal',
		get_stylesheet_directory_uri() . '/plugins/bubble-points/scripts/bubble-points-modal.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);

	wp_localize_script(
		'bsc-bubble-points-modal',
		'bsc_points',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'bsc_redeem_points' ),
		)
	);

	wp_enqueue_script(
		'bsc-bubble-points-copy',
		get_stylesheet_directory_uri() . '/plugins/bubble-points/scripts/bubble-points-copy.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);
}
