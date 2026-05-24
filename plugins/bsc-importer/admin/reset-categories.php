<?php

defined( 'ABSPATH' ) || exit;

function bsc_theme_importer_uncategorized_term_id() {
	$isValidId = get_option( 'default_product_cat' );
	return $isValidId;
}
function bsc_theme_importer_product_cat_taxonomy_exists() {
	$isValid = taxonomy_exists( 'product_cat' );
	return $isValid;
}

function bsc_theme_importer_delete_categories() {

	$is_valid_taxonomy = bsc_theme_importer_product_cat_taxonomy_exists();
	if (!$is_valid_taxonomy) {
		throw new Exception( 'There is not Taxonomy product_cat, install woocommerce first.' );
	}

	$uncategorized_term_id = (int) bsc_theme_importer_uncategorized_term_id();
	if (!$uncategorized_term_id) {
		throw new Exception( 'There is nor Uncategorized category' );
	}

	// Get all product categories
	$product_categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);

	if (is_wp_error( $product_categories )) {
		throw new Exception( 'There is a WordPress error' );
	}

	if (empty( $product_categories )) {
		throw new Exception( 'There is none category in list' );
	}

	$n_size_of_categories_all     = sizeof( $product_categories );
	$n_size_of_categories_removed = 0;

	foreach ($product_categories as $category) {
		if ( (int) $category->term_id !== $uncategorized_term_id) {
			if (
				$category->slug == 'uncategorized' ||
				$category->slug == 'sin-categoria'
			) {
			} else {
				wp_delete_term( $category->term_id, 'product_cat' );
				++$n_size_of_categories_removed;
			}
		}
	}
	return $n_size_of_categories_removed;
}
