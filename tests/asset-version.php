<?php
/**
 * Focused regression test for per-file cache revisions.
 *
 * Run with: php tests/asset-version.php
 */

define( 'ABSPATH', __DIR__ );
define( 'BSC_THEME_VERSION', '2.1.3' );

function get_template_directory() {
	return dirname( __DIR__ );
}

require_once dirname( __DIR__ ) . '/inc/asset-version.php';

function bsc_asset_version_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$style_file    = dirname( __DIR__ ) . '/style.css';
$style_version = '2.1.3-' . filemtime( $style_file ) . '-' . filesize( $style_file );

bsc_asset_version_assert_same(
	$style_version,
	bsc_get_asset_version( 'style.css' ),
	'The style revision must include the release, modification time and byte size.'
);
bsc_asset_version_assert_same(
	'2.1.3',
	bsc_get_asset_version( 'missing.css' ),
	'Missing assets must fall back to the release version.'
);
bsc_asset_version_assert_same(
	'2.1.3',
	bsc_get_asset_version( '../style.css' ),
	'Paths outside the theme must not be inspected.'
);

fwrite( STDOUT, "Asset version test passed.\n" );
