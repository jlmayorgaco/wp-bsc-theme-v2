<?php
/**
 * Editable home brand items.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BSC_HOME_BRANDS_OPTION' ) ) {
	define( 'BSC_HOME_BRANDS_OPTION', 'bsc_home_brands' );
}

/**
 * Default brand items used before the admin option is saved.
 */
function bsc_home_brands_default_items(): array {
	return array(
		array(
			'name'          => 'COSRX',
			'slug'          => 'sk-marca-cosrx',
			'default_image' => 'images/home_brands/brand_01.png',
		),
		array(
			'name'          => 'SOME BY MI',
			'slug'          => 'sk-marca-some-by-mi',
			'default_image' => 'images/home_brands/brand_02.png',
		),
		array(
			'name'          => 'Heimish',
			'slug'          => 'sk-marca-heimish',
			'default_image' => 'images/home_brands/brand_03.png',
		),
		array(
			'name'          => 'Pyunkang Yul',
			'slug'          => 'sk-marca-pyunkang-yul',
			'default_image' => 'images/home_brands/brand_04.png',
		),
		array(
			'name'          => 'Im From',
			'slug'          => 'sk-marca-im-from',
			'default_image' => 'images/home_brands/brand_05.png',
		),
		array(
			'name'          => 'Beauty of Joseon',
			'slug'          => 'sk-marca-beauty-of-joseon',
			'default_image' => 'images/home_brands/brand_06.png',
		),
		array(
			'name'          => 'TOCOBO',
			'slug'          => 'sk-marca-tocobo',
			'default_image' => 'images/home_brands/brand_07.png',
		),
		array(
			'name'          => 'BANILA CO',
			'slug'          => 'sk-marca-banila-co',
			'default_image' => 'images/home_brands/brand_08.png',
		),
		array(
			'name'          => 'Benton',
			'slug'          => 'sk-marca-benton',
			'default_image' => 'images/home_brands/brand_09.png',
		),
	);
}

/**
 * Resolve a product category term for a submitted/default brand row.
 *
 * @param array $row     Submitted or saved brand row.
 * @param array $default Default brand row for this slot.
 * @return WP_Term|false
 */
function bsc_home_brand_resolve_term( array $row, array $default ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return false;
	}

	$term_id = absint( $row['term_id'] ?? 0 );

	if ( 0 < $term_id ) {
		$term = get_term( $term_id, 'product_cat' );

		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
	}

	$raw_slug = isset( $row['slug'] ) && is_scalar( $row['slug'] ) ? (string) $row['slug'] : '';
	$slug     = sanitize_title( $raw_slug );

	if ( '' === $slug ) {
		$slug = sanitize_title( (string) ( $default['slug'] ?? '' ) );
	}

	if ( '' === $slug ) {
		return false;
	}

	$term = get_term_by( 'slug', $slug, 'product_cat' );

	return ( $term && ! is_wp_error( $term ) ) ? $term : false;
}

/**
 * Sanitize home brand option payload.
 *
 * @param mixed $input Submitted or saved option payload.
 */
function bsc_sanitize_home_brand_items( $input ): array {
	$defaults = bsc_home_brands_default_items();
	$input    = is_array( $input ) ? wp_unslash( $input ) : array();
	$clean    = array();

	foreach ( $defaults as $index => $default ) {
		$row      = isset( $input[ $index ] ) && is_array( $input[ $index ] ) ? $input[ $index ] : array();
		$term     = bsc_home_brand_resolve_term( $row, $default );
		$image_id = absint( $row['image_id'] ?? 0 );

		if ( 0 < $image_id && ! wp_attachment_is_image( $image_id ) ) {
			$image_id = 0;
		}

		$clean[] = array(
			'term_id'       => $term ? (int) $term->term_id : 0,
			'name'          => $term ? (string) $term->name : (string) ( $default['name'] ?? '' ),
			'slug'          => $term ? (string) $term->slug : sanitize_title( (string) ( $default['slug'] ?? '' ) ),
			'image_id'      => $image_id,
			'default_image' => (string) ( $default['default_image'] ?? '' ),
		);
	}

	return $clean;
}

/**
 * Get effective home brand items.
 */
function bsc_get_home_brand_items(): array {
	return bsc_sanitize_home_brand_items(
		get_option( BSC_HOME_BRANDS_OPTION, array() )
	);
}

/**
 * Get the default theme image URL for one brand item.
 *
 * @param array $brand Brand row.
 */
function bsc_home_brand_get_default_image_url( array $brand ): string {
	$relative_path = (string) ( $brand['default_image'] ?? '' );

	return '' !== $relative_path ? get_theme_file_uri( $relative_path ) : '';
}

/**
 * Get the effective image URL for one brand item.
 *
 * @param array  $brand Brand row.
 * @param string $size  WordPress image size.
 */
function bsc_home_brand_get_image_url( array $brand, string $size = 'full' ): string {
	$image_id = absint( $brand['image_id'] ?? 0 );

	if ( 0 < $image_id ) {
		$image_url = wp_get_attachment_image_url( $image_id, $size );

		if ( is_string( $image_url ) && '' !== $image_url ) {
			return $image_url;
		}
	}

	return bsc_home_brand_get_default_image_url( $brand );
}

/**
 * Get the product category term for one brand item.
 *
 * @param array $brand Brand row.
 * @return WP_Term|false
 */
function bsc_home_brand_get_term( array $brand ) {
	return bsc_home_brand_resolve_term( $brand, $brand );
}

/**
 * Get the effective product category URL for one brand item.
 *
 * @param array $brand Brand row.
 */
function bsc_home_brand_get_link( array $brand ): string {
	$term = bsc_home_brand_get_term( $brand );

	if ( ! $term ) {
		return '#';
	}

	$term_link = get_term_link( $term );

	return ( is_string( $term_link ) && ! is_wp_error( $term_link ) ) ? $term_link : '#';
}

/**
 * Get product category terms available for the home brand selector.
 */
function bsc_home_brands_get_available_terms(): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	return ( is_array( $terms ) && ! is_wp_error( $terms ) ) ? $terms : array();
}

/**
 * Build a readable select label for a category term.
 *
 * @param WP_Term $term Product category term.
 */
function bsc_home_brands_get_term_label( WP_Term $term ): string {
	$names     = array();
	$ancestors = array_reverse( get_ancestors( (int) $term->term_id, 'product_cat' ) );

	foreach ( $ancestors as $ancestor_id ) {
		$ancestor = get_term( (int) $ancestor_id, 'product_cat' );

		if ( $ancestor && ! is_wp_error( $ancestor ) ) {
			$names[] = (string) $ancestor->name;
		}
	}

	$names[] = (string) $term->name;

	return implode( ' / ', array_filter( $names ) );
}
