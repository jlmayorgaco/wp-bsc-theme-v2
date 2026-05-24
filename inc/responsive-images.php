<?php
/**
 * Responsive theme image helpers.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize a theme-relative image path.
 *
 * @param string $relative_path Theme-relative image path.
 * @return string
 */
function bsc_normalize_theme_image_path( string $relative_path ): string {
	return ltrim( str_replace( '\\', '/', $relative_path ), '/' );
}

/**
 * Build a public URL for a theme-relative image.
 *
 * @param string $relative_path Theme-relative image path.
 * @return string
 */
function bsc_get_theme_image_url( string $relative_path ): string {
	return trailingslashit( get_template_directory_uri() ) . bsc_normalize_theme_image_path( $relative_path );
}

/**
 * Build an absolute filesystem path for a theme-relative image.
 *
 * @param string $relative_path Theme-relative image path.
 * @return string
 */
function bsc_get_theme_image_file( string $relative_path ): string {
	return trailingslashit( get_template_directory() ) . bsc_normalize_theme_image_path( $relative_path );
}

/**
 * Get generated responsive variants for a theme image.
 *
 * Expected filenames: image-480w.webp, image-480w.avif, etc.
 *
 * @param string $relative_path Theme-relative source image path.
 * @param string $extension Variant extension.
 * @return array<int, string>
 */
function bsc_get_theme_image_srcset_items( string $relative_path, string $extension ): array {
	$relative_path = bsc_normalize_theme_image_path( $relative_path );
	$path_info     = pathinfo( $relative_path );
	$directory     = $path_info['dirname'] ?? '';
	$filename      = $path_info['filename'] ?? '';
	$source_dir    = dirname( bsc_get_theme_image_file( $relative_path ) );
	$pattern       = $source_dir . DIRECTORY_SEPARATOR . $filename . '-*w.' . $extension;
	$files         = glob( $pattern );

	if ( ! is_array( $files ) || empty( $files ) ) {
		return array();
	}

	$items = array();

	foreach ( $files as $file ) {
		$basename = basename( $file );

		if ( ! preg_match( '/-(\d+)w\.' . preg_quote( $extension, '/' ) . '$/', $basename, $matches ) ) {
			continue;
		}

		$width           = (int) $matches[1];
		$variant_path    = ( '.' === $directory ? '' : trailingslashit( $directory ) ) . $basename;
		$items[ $width ] = esc_url( bsc_get_theme_image_url( $variant_path ) ) . ' ' . $width . 'w';
	}

	ksort( $items, SORT_NUMERIC );

	return array_values( $items );
}

/**
 * Render a responsive theme image with AVIF/WebP sources and an original fallback.
 *
 * @param string               $relative_path Theme-relative source image path.
 * @param string               $alt           Image alt text.
 * @param array<string,string> $attrs         Image attributes.
 * @param string               $sizes         Responsive sizes attribute.
 * @return string
 */
function bsc_get_responsive_theme_image_html( string $relative_path, string $alt = '', array $attrs = array(), string $sizes = '100vw' ): string {
	$relative_path = bsc_normalize_theme_image_path( $relative_path );
	$file          = bsc_get_theme_image_file( $relative_path );
	$attrs         = array_merge(
		array(
			'decoding' => 'async',
		),
		$attrs
	);

	if ( file_exists( $file ) && ( empty( $attrs['width'] ) || empty( $attrs['height'] ) ) ) {
		$dimensions = getimagesize( $file );

		if ( is_array( $dimensions ) ) {
			$attrs['width']  = $attrs['width'] ?? (string) $dimensions[0];
			$attrs['height'] = $attrs['height'] ?? (string) $dimensions[1];
		}
	}

	$attrs['src'] = bsc_get_theme_image_url( $relative_path );
	$attrs['alt'] = $alt;

	$image_attrs = '';

	foreach ( $attrs as $name => $value ) {
		if ( null === $value || false === $value || '' === $name ) {
			continue;
		}

		$image_attrs .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
	}

	$avif_srcset = bsc_get_theme_image_srcset_items( $relative_path, 'avif' );
	$webp_srcset = bsc_get_theme_image_srcset_items( $relative_path, 'webp' );

	if ( empty( $avif_srcset ) && empty( $webp_srcset ) ) {
		return '<img' . $image_attrs . '>';
	}

	$html = '<picture>';

	if ( ! empty( $avif_srcset ) ) {
		$html .= sprintf(
			'<source type="image/avif" srcset="%s" sizes="%s">',
			esc_attr( implode( ', ', $avif_srcset ) ),
			esc_attr( $sizes )
		);
	}

	if ( ! empty( $webp_srcset ) ) {
		$html .= sprintf(
			'<source type="image/webp" srcset="%s" sizes="%s">',
			esc_attr( implode( ', ', $webp_srcset ) ),
			esc_attr( $sizes )
		);
	}

	$html .= '<img' . $image_attrs . '>';
	$html .= '</picture>';

	return $html;
}

/**
 * Echo a responsive theme image.
 *
 * @param string               $relative_path Theme-relative source image path.
 * @param string               $alt           Image alt text.
 * @param array<string,string> $attrs         Image attributes.
 * @param string               $sizes         Responsive sizes attribute.
 * @return void
 */
function bsc_responsive_theme_image( string $relative_path, string $alt = '', array $attrs = array(), string $sizes = '100vw' ): void {
	echo bsc_get_responsive_theme_image_html( $relative_path, $alt, $attrs, $sizes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
