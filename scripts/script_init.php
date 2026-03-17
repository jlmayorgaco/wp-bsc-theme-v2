<?php
/**
 * Third-party asset enqueue — Font Awesome & Swiper (served locally).
 * CDN removed for performance and privacy: files copied to vendor/ directory.
 */

/**
 * Font Awesome 6.5.0 — local, served from theme vendor/ directory.
 * Webfonts live at vendor/webfonts/ (CSS uses relative ../webfonts/ paths).
 */
function bsc_enqueue_fontawesome() {
	wp_enqueue_style(
		'fontawesome',
		get_template_directory_uri() . '/vendor/fontawesome/all.min.css',
		[],
		'6.5.0'
	);
}
add_action( 'wp_enqueue_scripts', 'bsc_enqueue_fontawesome' );

/**
 * Swiper 11 — local, served from theme vendor/swiper/ directory.
 * Only loaded on the front page (hero slider).
 */
function bsc_enqueue_swiper_assets() {
	if ( ! is_front_page() ) {
		return;
	}
	wp_enqueue_style(
		'swiper-css',
		get_template_directory_uri() . '/vendor/swiper/swiper-bundle.min.css',
		[],
		'11.0.0'
	);
	wp_enqueue_script(
		'swiper-js',
		get_template_directory_uri() . '/vendor/swiper/swiper-bundle.min.js',
		[],
		'11.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bsc_enqueue_swiper_assets' );
