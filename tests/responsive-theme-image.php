<?php
/**
 * Focused regression test for responsive theme-image markup.
 *
 * Run with: php tests/responsive-theme-image.php
 */

define( 'ABSPATH', __DIR__ );

function trailingslashit( $value ) {
	return rtrim( (string) $value, '/\\' ) . '/';
}

function get_template_directory_uri() {
	return 'https://example.test/theme';
}

function get_template_directory() {
	return dirname( __DIR__ );
}

function esc_url( $value ) {
	return (string) $value;
}

function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

require_once dirname( __DIR__ ) . '/inc/responsive-images.php';

function bsc_responsive_image_assert_contains( string $expected, string $actual, string $message ): void {
	if ( false === strpos( $actual, $expected ) ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$sizes = '(max-width: 768px) 100vw, 33vw';
$html  = bsc_get_responsive_theme_image_html(
	'images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
	'Skin care',
	array( 'loading' => 'lazy' ),
	$sizes
);

bsc_responsive_image_assert_contains( '<picture>', $html, 'Generated AVIF/WebP variants must render through picture.' );
bsc_responsive_image_assert_contains( 'type="image/avif"', $html, 'The AVIF source must be present.' );
bsc_responsive_image_assert_contains( 'type="image/webp"', $html, 'The WebP source must be present.' );
bsc_responsive_image_assert_contains(
	'<img decoding="async" loading="lazy"',
	$html,
	'The fallback image must retain its loading and decoding attributes.'
);
bsc_responsive_image_assert_contains(
	'sizes="' . $sizes . '"',
	$html,
	'The fallback img must describe the same rendered slot as its picture sources.'
);

fwrite( STDOUT, "Responsive theme image test passed.\n" );
