<?php
/**
 * Editable header menu cover images and links.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BSC_HEADER_MENU_COVERS_OPTION' ) ) {
	define( 'BSC_HEADER_MENU_COVERS_OPTION', 'bsc_header_menu_covers' );
}

/**
 * Return editable header menu cover defaults.
 */
function bsc_get_header_menu_cover_definitions(): array {
	return array(
		'BSC_MENU_NAV_SKIN_CARE' => array(
			'label'         => 'Skin Care',
			'default_image' => get_theme_file_uri( 'images/header_menus/Menu-01-F-100.jpg' ),
			'default_link'  => '/product-category/group-skin-care/',
		),
		'BSC_MENU_NAV_HAIR_CARE' => array(
			'label'         => 'Hair Care',
			'default_image' => get_theme_file_uri( 'images/header_menus/Menu-02-F-100.jpg' ),
			'default_link'  => '/product-category/group-hair-care/',
		),
		'BSC_MENU_NAV_MAKE_UP'   => array(
			'label'         => 'Make Up',
			'default_image' => get_theme_file_uri( 'images/header_menus/Menu-05-F-100.jpg' ),
			'default_link'  => '/product-category/group-make-up/',
		),
	);
}

/**
 * Sanitize saved header menu cover options.
 *
 * @param mixed $input Raw option payload.
 */
function bsc_sanitize_header_menu_cover_options( $input ): array {
	$definitions = bsc_get_header_menu_cover_definitions();
	$input       = is_array( $input ) ? $input : array();
	$clean       = array();

	foreach ( array_keys( $definitions ) as $slug ) {
		$row      = isset( $input[ $slug ] ) && is_array( $input[ $slug ] ) ? $input[ $slug ] : array();
		$image_id = absint( $row['image_id'] ?? 0 );
		$raw_link = isset( $row['link'] ) && is_scalar( $row['link'] ) ? (string) $row['link'] : '';
		$link     = trim( sanitize_text_field( $raw_link ) );

		if ( 0 < $image_id && ! wp_attachment_is_image( $image_id ) ) {
			$image_id = 0;
		}

		$clean[ $slug ] = array(
			'image_id' => $image_id,
			'link'     => '' !== $link ? esc_url_raw( $link ) : '',
		);
	}

	return $clean;
}

/**
 * Read saved header menu cover options.
 */
function bsc_get_header_menu_cover_options(): array {
	return bsc_sanitize_header_menu_cover_options(
		get_option( BSC_HEADER_MENU_COVERS_OPTION, array() )
	);
}

/**
 * Get saved values for one header menu cover.
 *
 * @param string $slug Header menu slug.
 */
function bsc_get_header_menu_cover_saved_value( string $slug ): array {
	$options = bsc_get_header_menu_cover_options();

	return $options[ $slug ] ?? array(
		'image_id' => 0,
		'link'     => '',
	);
}

/**
 * Resolve the effective image URL for one header menu cover.
 *
 * @param string $slug Header menu slug.
 * @param string $size WordPress image size.
 */
function bsc_get_header_menu_cover_image_url( string $slug, string $size = 'full' ): string {
	$definitions = bsc_get_header_menu_cover_definitions();

	if ( empty( $definitions[ $slug ] ) ) {
		return '';
	}

	$saved    = bsc_get_header_menu_cover_saved_value( $slug );
	$image_id = absint( $saved['image_id'] ?? 0 );

	if ( 0 < $image_id ) {
		$image_url = wp_get_attachment_image_url( $image_id, $size );

		if ( is_string( $image_url ) && '' !== $image_url ) {
			return $image_url;
		}
	}

	return (string) $definitions[ $slug ]['default_image'];
}

/**
 * Resolve the effective link for one header menu cover.
 *
 * @param string $slug Header menu slug.
 */
function bsc_get_header_menu_cover_link( string $slug ): string {
	$definitions = bsc_get_header_menu_cover_definitions();

	if ( empty( $definitions[ $slug ] ) ) {
		return '';
	}

	$saved_link = (string) ( bsc_get_header_menu_cover_saved_value( $slug )['link'] ?? '' );

	return '' !== $saved_link
		? $saved_link
		: (string) $definitions[ $slug ]['default_link'];
}

/**
 * Apply saved cover overrides to the static header menu config.
 *
 * @param array $configs Header menu config array.
 */
function bsc_apply_header_menu_cover_overrides( array $configs ): array {
	$definitions = bsc_get_header_menu_cover_definitions();

	foreach ( $configs as &$config ) {
		$slug = (string) ( $config['slug'] ?? '' );

		if ( '' === $slug || empty( $definitions[ $slug ] ) ) {
			continue;
		}

		if ( empty( $config['cover'] ) || ! is_array( $config['cover'] ) ) {
			$config['cover'] = array();
		}

		$config['cover']['image'] = bsc_get_header_menu_cover_image_url( $slug );
		$config['cover']['link']  = bsc_get_header_menu_cover_link( $slug );
	}
	unset( $config );

	return $configs;
}
