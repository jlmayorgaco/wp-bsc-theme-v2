<?php

defined( 'ABSPATH' ) || exit;

function bsc_theme_importer_product_exists_by_sku( $sku ) {
	if (function_exists( 'wc_get_product_id_by_sku' )) {
		$product_id = wc_get_product_id_by_sku( $sku );
		return $product_id ? (int) $product_id : false;
	}

	global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Importer fallback lookup when WooCommerce SKU helper is unavailable.
	$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1", $sku ) );
	return $product_id ? (int) $product_id : false;
}
function bsc_theme_importer_product_exists_by_name( $name ) {
	global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Importer fallback lookup by product title.
	$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'product' AND post_status NOT IN ('trash', 'auto-draft') LIMIT 1", $name ) );
	return $product_id ? (int) $product_id : false;
}

function bsc_theme_importer_upload_products( $json ) {
	$categories_keys = array( 'sk', 'hc', 'mk' );
	$category_fields = array(
		'sk' => array( 'BSC__CAT__SK_MARCAS', 'BSC__CAT__SK_RUTINA', 'BSC__CAT__SK_INGREDIENTES', 'BSC__CAT__SK_NECESIDADES', 'BSC__CAT__SK_TIPO_PIEL' ),
		'hc' => array( 'BSC__CAT__HC_RUTINA', 'BSC__CAT__HC_MARCA', 'BSC__CAT__HC_NECESIDADES' ),
		'mk' => array( 'BSC__CAT__MK_PRODUCTOS', 'BSC__CAT__MK_MARCAS' ),
	);
	$processed       = 0;
	$skipped         = 0;

	echo '<h3>' . esc_html__( 'UPLOADING JSON FILE ....', 'bubblesskincare' ) . '</h3><br>';

	foreach ($categories_keys as $key) {
		$nodes = isset( $json[ $key ] ) && is_array( $json[ $key ] ) ? $json[ $key ] : array();

		foreach ($nodes as $node) {
			$source_id = preg_replace( '/[^0-9A-Za-z_-]/', '', (string) $node['ID'] );

			if ($source_id === '') {
				++$skipped;
				echo esc_html( 'Product skipped: invalid source ID' ) . '<br>';
				continue;
			}

			$sku                   = 'BSC:' . strtoupper( $key ) . ':' . $source_id;
			$name                  = sanitize_text_field( (string) $node['NAME'] );
			$description           = isset( $node['DESCRIPTION'] ) ? wp_kses_post( (string) $node['DESCRIPTION'] ) : '';
			$regular_price_numeric = (int) preg_replace( '/[^0-9]/', '', (string) $node['PRICE'] );

			$categories = array();
			foreach ($category_fields[ $key ] as $field) {
				if (!empty( $node[ $field ] )) {
					$categories = array_merge( $categories, array_map( 'trim', explode( ',', (string) $node[ $field ] ) ) );
				}
			}

			$category_ids = array();
			foreach (array_filter( array_unique( $categories ) ) as $slug) {
				$term = get_term_by( 'slug', sanitize_title( $slug ), 'product_cat' );
				if ($term && isset( $term->term_id )) {
					$category_ids[] = (int) $term->term_id;
				}
			}

			$product_id = bsc_theme_importer_product_exists_by_sku( $sku );
			if (!$product_id) {
				$product_id = bsc_theme_importer_product_exists_by_name( $name );
			}

			if ($product_id) {
				$product = wc_get_product( $product_id );
				echo esc_html( 'Product updated: ' . $name ) . '<br>';
			} else {
				$product = new WC_Product_Simple();
				echo esc_html( 'Product created: ' . $name ) . '<br>';
			}

			if (!$product) {
				++$skipped;
				echo esc_html( 'Product skipped: ' . $name ) . '<br>';
				continue;
			}

			try {
				$product->set_name( $name );
				$product->set_regular_price( (string) $regular_price_numeric );
				$product->set_short_description( $description );
				$product->set_sku( $sku );
				$product->set_category_ids( $category_ids );

				$product->update_meta_data( '_BSC_BRAND', isset( $node['BRAND'] ) ? sanitize_text_field( (string) $node['BRAND'] ) : '' );
				$product->update_meta_data( '_BSC_CATEGORIES', isset( $node['CATEGORIES'] ) ? sanitize_text_field( (string) $node['CATEGORIES'] ) : '' );
				$product->update_meta_data( '_BSC_INGREDIENTS', isset( $node['INGREDIENTS'] ) ? wp_kses_post( (string) $node['INGREDIENTS'] ) : '' );

				$product->save();
				++$processed;
			} catch (Throwable $throwable) {
				++$skipped;
				echo esc_html( 'Product skipped: ' . $name . ' - ' . $throwable->getMessage() ) . '<br>';
			}
		}
	}

	if ($skipped > 0) {
		echo esc_html( 'Products skipped: ' . $skipped ) . '<br>';
	}

	return $processed;
}
