<?php
/**
 * Sample implementation of the Custom Header feature
 *
 * You can add an optional custom header image to header.php like so ...
 *
	<?php the_header_image_tag(); ?>
 *
 * @link https://developer.wordpress.org/themes/functionality/custom-headers/
 *
 * @package BSC2
 */

/**
 * Set up the WordPress core custom header feature.
 *
 * @uses bsc_2_0_enqueue_custom_header_style()
 */
function bsc_2_0_custom_header_setup() {
	add_theme_support(
		'custom-header',
		apply_filters(
			'bsc_2_0_custom_header_args',
			array(
				'default-image'      => '',
				'default-text-color' => '000000',
				'width'              => 1000,
				'height'             => 250,
				'flex-height'        => true,
			)
		)
	);
}
add_action( 'after_setup_theme', 'bsc_2_0_custom_header_setup' );

/**
 * Enqueue custom header text styles on the main theme stylesheet.
 */
function bsc_2_0_enqueue_custom_header_style() {
	$header_text_color = get_header_textcolor();

	if ( get_theme_support( 'custom-header', 'default-text-color' ) === $header_text_color ) {
		return;
	}

	$css = ! display_header_text()
		? '.site-title,.site-description{position:absolute;clip:rect(1px,1px,1px,1px);}'
		: '.site-title a,.site-description{color:#' . sanitize_hex_color_no_hash( $header_text_color ) . ';}';

	wp_add_inline_style( 'bsc-2-0-style', $css );
}
add_action( 'wp_enqueue_scripts', 'bsc_2_0_enqueue_custom_header_style', 20 );
