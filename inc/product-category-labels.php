<?php
/**
 * Public-facing labels for product category groups.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the public display name for a product category term.
 */
function bsc_get_product_category_display_name( WP_Term $term ): string {
	$labels = array(
		'group-skin-care' => 'Skin Care',
		'group-hair-care' => 'Hair Care',
		'group-make-up'   => 'Maquillaje',
	);

	return $labels[ $term->slug ] ?? $term->name;
}
