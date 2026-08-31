<?php
/**
 * Theme asset revision helpers.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build a cache-safe version for one file in the theme.
 *
 * The release version forces a global refresh when the theme is released. The
 * file modification time and size then invalidate only assets that change in a
 * later deploy, without disabling long-lived browser caching.
 *
 * @param string $relative_path Theme-relative asset path.
 * @return string
 */
function bsc_get_asset_version( string $relative_path ): string {
	static $versions = array();

	$relative_path = ltrim( str_replace( '\\', '/', $relative_path ), '/' );
	$release       = defined( 'BSC_THEME_VERSION' ) ? (string) BSC_THEME_VERSION : '1';

	if ( isset( $versions[ $relative_path ] ) ) {
		return $versions[ $relative_path ];
	}

	if ( '' === $relative_path || false !== strpos( $relative_path, '..' ) ) {
		return $release;
	}

	$file = get_template_directory() . '/' . $relative_path;
	if ( ! is_file( $file ) ) {
		$versions[ $relative_path ] = $release;
		return $release;
	}

	$modified = filemtime( $file );
	$size     = filesize( $file );
	$version  = $release . '-' . ( false === $modified ? '0' : (string) $modified ) . '-' . ( false === $size ? '0' : (string) $size );

	$versions[ $relative_path ] = $version;
	return $version;
}
