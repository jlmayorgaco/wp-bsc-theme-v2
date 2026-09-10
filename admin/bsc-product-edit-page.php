<?php
/**
 * BSC-065: Simplified BSC product editor for Shop Manager.
 * Allows editing: name, short description, main image, gallery, categories,
 * tags, SKU, price, discount, dual stock, and review toggle without the full WC editor.
 */
defined( 'ABSPATH' ) || exit;

function bsc_product_edit_page_is_active(): bool {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
	return sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ) === 'bsc-product-edit';
}

function bsc_get_product_edit_category_tree_data(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
		)
	);

	if (!is_array( $terms )) {
		return array();
	}

	$build_tree = function ( array $items, int $parent_id ) use ( &$build_tree ): array {
		$nodes = array();

		foreach ($items as $term) {
			if ( (int) $term->parent !== $parent_id) {
				continue;
			}

			$nodes[] = array(
				'id'       => (int) $term->term_id,
				'name'     => (string) $term->name,
				'slug'     => (string) $term->slug,
				'parent'   => (int) $term->parent,
				'children' => $build_tree( $items, (int) $term->term_id ),
			);
		}

		return $nodes;
	};

	return $build_tree( $terms, 0 );
}

function bsc_get_product_edit_current_category_ids( int $product_id ): array {
	$term_ids = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

	if (!is_array( $term_ids )) {
		return array();
	}

	return array_values( array_map( 'intval', $term_ids ) );
}

function bsc_product_edit_root_slugs(): array {
	return array( 'group-skin-care', 'group-hair-care', 'group-make-up' );
}

function bsc_product_edit_user_can_manage_categories(): bool {
	return current_user_can( 'manage_options' )
		|| current_user_can( 'manage_woocommerce' )
		|| current_user_can( 'manage_product_terms' )
		|| current_user_can( 'edit_products' );
}

function bsc_product_edit_parent_is_descendant( int $term_id, int $parent_id ): bool {
	if ( $term_id <= 0 || $parent_id <= 0 ) {
		return false;
	}

	$ancestor_ids = get_ancestors( $parent_id, 'product_cat' );
	$ancestor_ids = array_map( 'intval', is_array( $ancestor_ids ) ? $ancestor_ids : array() );

	return in_array( $term_id, $ancestor_ids, true );
}

function bsc_product_edit_validate_category_parent( int $parent_id ) {
	if ( $parent_id <= 0 ) {
		return 0;
	}

	$parent = get_term( $parent_id, 'product_cat' );
	if ( ! $parent instanceof WP_Term ) {
		return new WP_Error( 'bsc_invalid_category_parent', 'El padre seleccionado no existe.' );
	}

	return $parent_id;
}

function bsc_product_edit_category_ajax_payload( int $term_id = 0 ): array {
	$payload = array(
		'tree' => bsc_get_product_edit_category_tree_data(),
	);

	if ( $term_id > 0 ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( $term instanceof WP_Term ) {
			$payload['term'] = array(
				'id'     => (int) $term->term_id,
				'name'   => (string) $term->name,
				'slug'   => (string) $term->slug,
				'parent' => (int) $term->parent,
			);
		}
	}

	return $payload;
}

function bsc_product_edit_ajax_guard(): void {
	check_ajax_referer( 'bsc_product_edit_categories', 'nonce' );

	if ( ! bsc_product_edit_user_can_manage_categories() ) {
		wp_send_json_error( array( 'message' => 'Sin permisos para editar categorias.' ), 403 );
	}
}

add_action( 'wp_ajax_bsc_product_category_create', 'bsc_ajax_product_category_create' );
function bsc_ajax_product_category_create(): void {
	bsc_product_edit_ajax_guard();

	$name      = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$slug      = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );
	$parent_id = absint( wp_unslash( $_POST['parent'] ?? 0 ) );
	$parent_id = bsc_product_edit_validate_category_parent( $parent_id );

	if ( is_wp_error( $parent_id ) ) {
		wp_send_json_error( array( 'message' => $parent_id->get_error_message() ), 400 );
	}

	if ( $name === '' ) {
		wp_send_json_error( array( 'message' => 'Escribe el nombre de la categoria.' ), 400 );
	}

	$args = array(
		'parent' => $parent_id,
	);

	if ( $slug !== '' ) {
		$args['slug'] = $slug;
	}

	$result = wp_insert_term( $name, 'product_cat', $args );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	wp_send_json_success( bsc_product_edit_category_ajax_payload( (int) $result['term_id'] ) );
}

add_action( 'wp_ajax_bsc_product_category_update', 'bsc_ajax_product_category_update' );
function bsc_ajax_product_category_update(): void {
	bsc_product_edit_ajax_guard();

	$term_id   = absint( wp_unslash( $_POST['term_id'] ?? 0 ) );
	$name      = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$slug      = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );
	$parent_id = absint( wp_unslash( $_POST['parent'] ?? 0 ) );
	$term      = get_term( $term_id, 'product_cat' );

	if ( ! $term instanceof WP_Term ) {
		wp_send_json_error( array( 'message' => 'Categoria no encontrada.' ), 404 );
	}

	if ( $name === '' ) {
		wp_send_json_error( array( 'message' => 'Escribe el nombre de la categoria.' ), 400 );
	}

	$parent_id = bsc_product_edit_validate_category_parent( $parent_id );
	if ( is_wp_error( $parent_id ) ) {
		wp_send_json_error( array( 'message' => $parent_id->get_error_message() ), 400 );
	}

	if ( $parent_id === $term_id || bsc_product_edit_parent_is_descendant( $term_id, $parent_id ) ) {
		wp_send_json_error( array( 'message' => 'La categoria no puede ser padre de si misma ni de sus hijas.' ), 400 );
	}

	$args = array(
		'name'   => $name,
		'parent' => $parent_id,
	);

	if ( $slug !== '' ) {
		$args['slug'] = $slug;
	}

	$result = wp_update_term( $term_id, 'product_cat', $args );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	wp_send_json_success( bsc_product_edit_category_ajax_payload( $term_id ) );
}

add_action( 'wp_ajax_bsc_product_category_delete', 'bsc_ajax_product_category_delete' );
function bsc_ajax_product_category_delete(): void {
	bsc_product_edit_ajax_guard();

	$term_id = absint( wp_unslash( $_POST['term_id'] ?? 0 ) );
	$term    = get_term( $term_id, 'product_cat' );

	if ( ! $term instanceof WP_Term ) {
		wp_send_json_error( array( 'message' => 'Categoria no encontrada.' ), 404 );
	}

	if ( in_array( (string) $term->slug, bsc_product_edit_root_slugs(), true ) ) {
		wp_send_json_error( array( 'message' => 'No se pueden eliminar las categorias raiz del menu BSC.' ), 400 );
	}

	$result = wp_delete_term( $term_id, 'product_cat' );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	if ( ! $result ) {
		wp_send_json_error( array( 'message' => 'No se pudo eliminar la categoria.' ), 400 );
	}

	wp_send_json_success(
		array(
			'tree'          => bsc_get_product_edit_category_tree_data(),
			'deletedTermId' => $term_id,
		)
	);
}

function bsc_get_product_edit_status_value( WP_Post $post, WC_Product $product ): string {
	if ('1' === (string) get_post_meta( $post->ID, '_bsc_product_archived', true )) {
		return 'archive';
	}

	if ('draft' === $post->post_status) {
		return 'draft';
	}

	if ('publish' === $post->post_status && 'hidden' === $product->get_catalog_visibility()) {
		return 'hidden';
	}

	return 'publish';
}

function bsc_apply_product_edit_status( int $product_id, WC_Product $product, string $status ): void {
	if ('archive' === $status) {
		update_post_meta( $product_id, '_bsc_product_archived', '1' );
		$product->set_catalog_visibility( 'hidden' );
		$product->save();
		return;
	}

	delete_post_meta( $product_id, '_bsc_product_archived' );

	if ('hidden' === $status) {
		$product->set_catalog_visibility( 'hidden' );
		$product->save();
		return;
	}

	$product->set_catalog_visibility( 'visible' );
	$product->save();
}

function bsc_product_edit_normalize_color_hex( string $raw_color ): string {
	$raw_color = trim( $raw_color );

	if ($raw_color === '') {
		return '';
	}

	if (!str_starts_with( $raw_color, '#' )) {
		$raw_color = '#' . $raw_color;
	}

	$color = sanitize_hex_color( $raw_color );
	if (!$color) {
		return '';
	}

	if (strlen( $color ) === 4) {
		$color = sprintf(
			'#%1$s%1$s%2$s%2$s%3$s%3$s',
			$color[1],
			$color[2],
			$color[3]
		);
	}

	return strtoupper( $color );
}

function bsc_product_edit_sanitize_variant_price_value( $raw_value ): ?string {
	$raw_value = trim( (string) sanitize_text_field( $raw_value ) );

	if ($raw_value === '') {
		return '';
	}

	$normalized_value = str_replace( ',', '.', $raw_value );

	if (!is_numeric( $normalized_value ) || (float) $normalized_value < 0) {
		return null;
	}

	return wc_format_decimal( $normalized_value, wc_get_price_decimals() );
}

function bsc_product_edit_variant_enabled( $raw_value ): bool {
	if ($raw_value === null || $raw_value === '') {
		return true;
	}

	return in_array( strtolower( (string) $raw_value ), array( '1', 'true', 'yes', 'on', 'enabled' ), true );
}

function bsc_product_edit_sanitize_variant_row_values( array $raw_variant, string $label, &$errors = null ): ?array {
	$collect_errors = is_array( $errors );
	$regular_price  = bsc_product_edit_sanitize_variant_price_value(
		$raw_variant['regular_price'] ?? ( $raw_variant['price'] ?? '' )
	);
	$sale_price     = bsc_product_edit_sanitize_variant_price_value( $raw_variant['sale_price'] ?? '' );

	if ($regular_price === null) {
		if ($collect_errors) {
			$errors[] = sprintf( 'Precio regular invalido para la variante "%s".', $label );
		}
		return null;
	}

	if ($sale_price === null) {
		if ($collect_errors) {
			$errors[] = sprintf( 'Precio de oferta invalido para la variante "%s".', $label );
		}
		return null;
	}

	if ($sale_price !== '' && $regular_price === '') {
		if ($collect_errors) {
			$errors[] = sprintf( 'La variante "%s" necesita precio regular para usar oferta.', $label );
		}
		return null;
	}

	if ($sale_price !== '' && $regular_price !== '' && (float) $sale_price > (float) $regular_price) {
		if ($collect_errors) {
			$errors[] = sprintf( 'La oferta supera el precio regular en la variante "%s".', $label );
		}
		return null;
	}

	return array(
		'regular_price' => $regular_price,
		'sale_price'    => $sale_price,
		'price'         => $sale_price !== '' ? $sale_price : $regular_price,
		'stock_bodega'  => max( 0, intval( $raw_variant['stock_bodega'] ?? 0 ) ),
		'stock_tienda'  => max( 0, intval( $raw_variant['stock_tienda'] ?? 0 ) ),
		'enabled'       => bsc_product_edit_variant_enabled( $raw_variant['enabled'] ?? true ),
	);
}

function bsc_product_edit_sanitize_color_variants( $raw_variants, &$errors = null ): array {
	if (!is_array( $raw_variants )) {
		return array();
	}

	$variants = array();
	$seen     = array();

	foreach ($raw_variants as $raw_variant) {
		if (!is_array( $raw_variant )) {
			continue;
		}

		$name = sanitize_text_field( (string) ( $raw_variant['name'] ?? '' ) );
		$hex  = bsc_product_edit_normalize_color_hex( (string) ( $raw_variant['hex'] ?? '' ) );

		if ($name === '' || $hex === '') {
			continue;
		}

		$dedupe_key = strtolower( $name . '|' . $hex );
		if (isset( $seen[ $dedupe_key ] )) {
			continue;
		}

		$row_values = bsc_product_edit_sanitize_variant_row_values( $raw_variant, $name, $errors );
		if ($row_values === null) {
			continue;
		}

		$seen[ $dedupe_key ] = true;
		$variants[]          = array_merge(
			array(
				'name' => $name,
				'hex'  => $hex,
			),
			$row_values
		);

		if (count( $variants ) >= 50) {
			break;
		}
	}

	return $variants;
}

function bsc_product_edit_get_color_variants( int $product_id ): array {
	$raw_variants = get_post_meta( $product_id, '_bsc_color_variants', true );

	if (is_string( $raw_variants ) && $raw_variants !== '') {
		$decoded = json_decode( $raw_variants, true );
		if (is_array( $decoded )) {
			$raw_variants = $decoded;
		}
	}

	return bsc_product_edit_sanitize_color_variants( is_array( $raw_variants ) ? $raw_variants : array() );
}

function bsc_product_edit_sanitize_size_variants( $raw_variants, &$errors = null ): array {
	if (!is_array( $raw_variants )) {
		return array();
	}

	$variants = array();
	$seen     = array();

	foreach ($raw_variants as $raw_variant) {
		if (!is_array( $raw_variant )) {
			continue;
		}

		$name = sanitize_text_field( (string) ( $raw_variant['name'] ?? '' ) );

		if ($name === '') {
			continue;
		}

		$dedupe_key = strtolower( $name );
		if (isset( $seen[ $dedupe_key ] )) {
			continue;
		}

		$row_values = bsc_product_edit_sanitize_variant_row_values( $raw_variant, $name, $errors );
		if ($row_values === null) {
			continue;
		}

		$seen[ $dedupe_key ] = true;
		$variants[]          = array_merge(
			array(
				'name' => $name,
			),
			$row_values
		);

		if (count( $variants ) >= 50) {
			break;
		}
	}

	return $variants;
}

function bsc_product_edit_get_size_variants( int $product_id ): array {
	$raw_variants = get_post_meta( $product_id, '_bsc_size_variants', true );

	if (is_string( $raw_variants ) && $raw_variants !== '') {
		$decoded = json_decode( $raw_variants, true );
		if (is_array( $decoded )) {
			$raw_variants = $decoded;
		}
	}

	return bsc_product_edit_sanitize_size_variants( is_array( $raw_variants ) ? $raw_variants : array() );
}

function bsc_product_edit_build_variant_matrix_from_rows( array $variants, string $mode, ?array &$errors = null ): array {
	if (!function_exists( 'bsc_sanitize_product_variant_matrix' )) {
		return array();
	}

	$raw_matrix = array();

	foreach ($variants as $variant) {
		$raw_matrix[] = array(
			'color_name'    => 'color' === $mode ? (string) ( $variant['name'] ?? '' ) : '',
			'color_hex'     => 'color' === $mode ? (string) ( $variant['hex'] ?? '' ) : '',
			'size_name'     => 'size' === $mode ? (string) ( $variant['name'] ?? '' ) : '',
			'regular_price' => (string) ( $variant['regular_price'] ?? '' ),
			'sale_price'    => (string) ( $variant['sale_price'] ?? '' ),
			'stock_bodega'  => max( 0, intval( $variant['stock_bodega'] ?? 0 ) ),
			'stock_tienda'  => max( 0, intval( $variant['stock_tienda'] ?? 0 ) ),
			'enabled'       => !empty( $variant['enabled'] ) ? '1' : '0',
		);
	}

	return bsc_sanitize_product_variant_matrix( $raw_matrix, $errors );
}

function bsc_product_edit_variant_rows_from_matrix( array $matrix, string $mode ): array {
	$rows = array();
	$seen = array();

	foreach ($matrix as $variant) {
		$name = 'color' === $mode
			? (string) ( $variant['color_name'] ?? '' )
			: (string) ( $variant['size_name'] ?? '' );

		if ($name === '') {
			continue;
		}

		$hex = 'color' === $mode ? bsc_product_edit_normalize_color_hex( (string) ( $variant['color_hex'] ?? '' ) ) : '';
		$key = strtolower( $name . '|' . $hex );

		if (isset( $seen[ $key ] )) {
			$rows[ $seen[ $key ] ]['stock_bodega'] += max( 0, intval( $variant['stock_bodega'] ?? 0 ) );
			$rows[ $seen[ $key ] ]['stock_tienda'] += max( 0, intval( $variant['stock_tienda'] ?? 0 ) );
			continue;
		}

		$seen[ $key ] = count( $rows );
		$row          = array(
			'name'          => $name,
			'regular_price' => (string) ( $variant['regular_price'] ?? '' ),
			'sale_price'    => (string) ( $variant['sale_price'] ?? '' ),
			'price'         => (string) ( $variant['price'] ?? '' ),
			'stock_bodega'  => max( 0, intval( $variant['stock_bodega'] ?? 0 ) ),
			'stock_tienda'  => max( 0, intval( $variant['stock_tienda'] ?? 0 ) ),
			'enabled'       => !empty( $variant['enabled'] ),
		);

		if ('color' === $mode) {
			$row['hex'] = $hex !== '' ? $hex : '#F7C0CD';
		}

		$rows[] = $row;
	}

	return $rows;
}

function bsc_get_product_edit_script_data( int $product_id ): array {
	return array(
		'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
		'categoryNonce'       => wp_create_nonce( 'bsc_product_edit_categories' ),
		'canManageCategories' => bsc_product_edit_user_can_manage_categories(),
		'catTree'             => bsc_get_product_edit_category_tree_data(),
		'currentCats'         => bsc_get_product_edit_current_category_ids( $product_id ),
		'rootSlugs'           => bsc_product_edit_root_slugs(),
		'rootLabels'          => array(
			'group-skin-care' => 'Skin Care',
			'group-hair-care' => 'Hair Care',
			'group-make-up'   => 'Make Up',
		),
		'strings'             => array(
			'mainImageTitle'    => 'Imagen principal',
			'mainImageButton'   => 'Usar imagen',
			'galleryTitle'      => 'Galeria del producto',
			'galleryButton'     => 'Usar estas imagenes',
			'searchPlaceholder' => 'Buscar...',
			'noCategories'      => 'No hay categorias para este grupo.',
			'noSubcategories'   => 'Sin subcategorias',
			'categorySaved'     => 'Categoria guardada.',
			'categoryDeleted'   => 'Categoria eliminada.',
		),
	);
}

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_product_edit_page_assets' );
function bsc_enqueue_product_edit_page_assets( string $hook ): void {
	if (!bsc_product_edit_page_is_active()) {
		return;
	}

	bsc_enqueue_admin_ui_assets();

	$css_path = get_template_directory() . '/admin/bsc-product-edit.css';
	wp_enqueue_style(
		'bsc-product-edit-admin',
		get_template_directory_uri() . '/admin/bsc-product-edit.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);

	$js_path = get_template_directory() . '/js/admin/bsc-product-edit.js';
	wp_enqueue_script(
		'bsc-product-edit-admin',
		get_template_directory_uri() . '/js/admin/bsc-product-edit.js',
		array( 'jquery' ),
		file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
		true
	);

	wp_enqueue_media();

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only product id for admin asset data.
	$product_id = absint( wp_unslash( $_GET['id'] ?? 0 ) );
	wp_localize_script(
		'bsc-product-edit-admin',
		'bscProductEditData',
		bsc_get_product_edit_script_data( $product_id )
	);
}

function bsc_render_product_edit_page(): void {
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'edit_products' )) {
		wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
	}

	$product_id = absint( wp_unslash( $_GET['id'] ?? 0 ) );
	if (!$product_id || get_post_type( $product_id ) !== 'product') {
		wp_die( esc_html__( 'Producto no encontrado.', 'bsc-2-0' ) );
	}

	$product = wc_get_product( $product_id );
	$post    = get_post( $product_id );

	if (!$product || !$post instanceof WP_Post) {
		wp_die( esc_html__( 'Producto no encontrado.', 'bsc-2-0' ) );
	}

	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

	if ('POST' === $request_method && isset( $_POST['bsc_product_edit_nonce'] )) {
		if (!wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_product_edit_nonce'] ) ), 'bsc_product_edit_action' )) {
			wp_die( esc_html__( 'Solicitud no valida.', 'bsc-2-0' ) );
		}

		$new_title       = sanitize_text_field( wp_unslash( $_POST['post_title'] ?? '' ) );
		$new_excerpt     = wp_kses_post( wp_unslash( $_POST['post_excerpt'] ?? '' ) );
		$location_code   = bsc_normalize_product_location_code( wp_unslash( $_POST['_bsc_location_code'] ?? '' ) );
		$post_status_raw = isset( $_POST['post_status'] ) ? sanitize_key( wp_unslash( $_POST['post_status'] ) ) : '';
		$product_status  = in_array( $post_status_raw, array( 'publish', 'draft', 'hidden', 'archive', 'delete' ), true )
			? $post_status_raw
			: 'draft';
		$new_status      = in_array( $product_status, array( 'publish', 'hidden' ), true ) ? 'publish' : 'draft';

		if ( ! bsc_is_valid_product_location_code( $location_code ) ) {
			wp_die( esc_html__( 'Location code invalido. Usa el formato COD-M4-E1.', 'bsc-2-0' ) );
		}

		if ('delete' === $product_status) {
			wp_trash_post( $product_id );
			wp_safe_redirect( admin_url( 'admin.php?page=bsc-products&deleted=1' ) );
			exit;
		}

		wp_update_post(
			array(
				'ID'           => $product_id,
				'post_title'   => $new_title,
				'post_excerpt' => $new_excerpt,
				'post_status'  => $new_status,
			)
		);

		$product = wc_get_product( $product_id );
		if (!$product instanceof WC_Product) {
			wp_die( esc_html__( 'Producto no encontrado.', 'bsc-2-0' ) );
		}

		bsc_apply_product_edit_status( $product_id, $product, $product_status );

		$sku = sanitize_text_field( wp_unslash( $_POST['_sku'] ?? '' ) );
		update_post_meta( $product_id, '_sku', $sku );
		bsc_update_product_location_code( $product_id, $location_code );

		$regular_price        = bsc_sanitize_product_price_value( wp_unslash( $_POST['_regular_price'] ?? '' ) );
		$discount_percent_raw = isset( $_POST['_discount_percent'] )
			? trim( str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['_discount_percent'] ) ) ) )
			: '';

		if ($regular_price === null) {
			wp_die( esc_html__( 'Precio invalido.', 'bsc-2-0' ) );
		}

		$sale_price = '';
		if ($discount_percent_raw !== '') {
			if (!is_numeric( $discount_percent_raw )) {
				wp_die( esc_html__( 'Descuento invalido.', 'bsc-2-0' ) );
			}

			$discount_percent = (float) $discount_percent_raw;
			if ($discount_percent < 0 || $discount_percent >= 100) {
				wp_die( esc_html__( 'El descuento debe estar entre 0% y 99.99%.', 'bsc-2-0' ) );
			}

			if ($discount_percent > 0) {
				if ($regular_price === '') {
					wp_die( esc_html__( 'Descuento invalido.', 'bsc-2-0' ) );
				}

				$sale_price = wc_format_decimal(
					(float) $regular_price * ( ( 100 - $discount_percent ) / 100 ),
					wc_get_price_decimals()
				);
			}
		}

		$price_error = bsc_update_product_price_values( $product, $regular_price, $sale_price );
		if ($price_error !== null) {
			wp_die( esc_html( $price_error ) );
		}

		$thumbnail_id = absint( $_POST['_thumbnail_id'] ?? 0 );
		if ($thumbnail_id) {
			set_post_thumbnail( $product_id, $thumbnail_id );
		} elseif (isset( $_POST['_remove_thumbnail'] )) {
			delete_post_thumbnail( $product_id );
		}

		$gallery_ids = sanitize_text_field( wp_unslash( $_POST['_product_image_gallery'] ?? '' ) );
		update_post_meta( $product_id, '_product_image_gallery', $gallery_ids );

		$cat_ids = array_map( 'absint', (array) ( $_POST['product_cat'] ?? array() ) );
		wp_set_object_terms( $product_id, $cat_ids, 'product_cat' );

		$tag_input = sanitize_text_field( wp_unslash( $_POST['product_tag'] ?? '' ) );
		$tags      = array_filter( array_map( 'trim', explode( ',', $tag_input ) ) );
		wp_set_object_terms( $product_id, $tags, 'product_tag' );

		$color_variants_enabled = isset( $_POST['_bsc_color_variants_enabled'] );
		$color_variants_raw     = isset( $_POST['_bsc_color_variants'] )
			? wp_unslash( (array) $_POST['_bsc_color_variants'] )
			: array();
		$variant_errors         = array();
		$color_variants         = bsc_product_edit_sanitize_color_variants( $color_variants_raw, $variant_errors );
		$size_variants_enabled  = isset( $_POST['_bsc_size_variants_enabled'] );
		$size_variants_raw      = isset( $_POST['_bsc_size_variants'] )
			? wp_unslash( (array) $_POST['_bsc_size_variants'] )
			: array();
		$size_variants          = bsc_product_edit_sanitize_size_variants( $size_variants_raw, $variant_errors );

		if ($color_variants_enabled && $size_variants_enabled) {
			wp_die( esc_html__( 'Elige variantes por color o por Tamaño, no ambas.', 'bsc-2-0' ) );
		}

		if (!$color_variants_enabled) {
			$color_variants = array();
		}

		if (!$size_variants_enabled) {
			$size_variants = array();
		}

		$variant_matrix = $color_variants_enabled
			? bsc_product_edit_build_variant_matrix_from_rows( $color_variants, 'color', $variant_errors )
			: bsc_product_edit_build_variant_matrix_from_rows( $size_variants, 'size', $variant_errors );

		if (!empty( $variant_errors )) {
			wp_die( esc_html( implode( ' ', $variant_errors ) ) );
		}

		if ($color_variants_enabled && !empty( $color_variants )) {
			update_post_meta( $product_id, '_bsc_color_variants_enabled', '1' );
			update_post_meta( $product_id, '_bsc_color_variants', $color_variants );
		} else {
			delete_post_meta( $product_id, '_bsc_color_variants_enabled' );
			delete_post_meta( $product_id, '_bsc_color_variants' );
		}

		if ($size_variants_enabled && !empty( $size_variants )) {
			update_post_meta( $product_id, '_bsc_size_variants_enabled', '1' );
			update_post_meta( $product_id, '_bsc_size_variants', $size_variants );
		} else {
			delete_post_meta( $product_id, '_bsc_size_variants_enabled' );
			delete_post_meta( $product_id, '_bsc_size_variants' );
		}

		if (!empty( $variant_matrix )) {
			update_post_meta( $product_id, '_bsc_variant_matrix', $variant_matrix );
			if (function_exists( 'bsc_sync_product_variant_parent_stock' )) {
				bsc_sync_product_variant_parent_stock( $product_id, $variant_matrix );
			}
		} else {
			delete_post_meta( $product_id, '_bsc_variant_matrix' );
		}

		$comment_status = isset( $_POST['comment_status'] ) ? 'open' : 'closed';
		wp_update_post(
			array(
				'ID'             => $product_id,
				'comment_status' => $comment_status,
			)
		);

		$stock_bodega   = max( 0, intval( $_POST['_stock_bodega'] ?? 0 ) );
		$stock_tienda   = max( 0, intval( $_POST['_stock_tienda'] ?? 0 ) );
		$envio_tipo_raw = isset( $_POST['_envio_tipo'] ) ? sanitize_key( wp_unslash( $_POST['_envio_tipo'] ) ) : '';
		$envio_tipo     = in_array( $envio_tipo_raw, array( 'bodega', 'tienda', 'ambos' ), true )
			? $envio_tipo_raw
			: 'bodega';

		if (empty( $variant_matrix )) {
			$current_stock = BSC_Stock::get_stock( $product_id );
			if ( (int) $current_stock['bodega'] !== $stock_bodega) {
				BSC_Stock::adjust( $product_id, 'bodega', $stock_bodega - (int) $current_stock['bodega'], 'Edicion BSC Product Edit' );
			}

			if ( (int) $current_stock['tienda'] !== $stock_tienda) {
				BSC_Stock::adjust( $product_id, 'tienda', $stock_tienda - (int) $current_stock['tienda'], 'Edicion BSC Product Edit' );
			}
		}

		update_post_meta( $product_id, '_envio_tipo', $envio_tipo );

		wp_safe_redirect( admin_url( 'admin.php?page=bsc-products&saved=1' ) );
		exit;
	}

	$stock                   = BSC_Stock::get_stock( $product_id );
	$sku                     = $product->get_sku();
	$location_code           = bsc_get_product_location_code( $product_id );
	$product_status          = bsc_get_product_edit_status_value( $post, $product );
	$regular_price           = $product->get_regular_price();
	$sale_price              = $product->get_sale_price();
	$discount_percent        = '';
	if ($regular_price !== '' && $sale_price !== '' && (float) $regular_price > 0) {
		$discount_percent = wc_format_decimal(
			( 1 - ( (float) $sale_price / (float) $regular_price ) ) * 100,
			2
		);
	}
	$gallery                 = $product->get_gallery_image_ids();
	$thumbnail_id            = get_post_thumbnail_id( $product_id );
	$thumbnail_src           = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : '';
	$color_variants_enabled  = '1' === (string) get_post_meta( $product_id, '_bsc_color_variants_enabled', true );
	$color_variants          = bsc_product_edit_get_color_variants( $product_id );
	$size_variants_enabled   = '1' === (string) get_post_meta( $product_id, '_bsc_size_variants_enabled', true );
	$size_variants           = bsc_product_edit_get_size_variants( $product_id );
	$variant_matrix_rows     = function_exists( 'bsc_get_product_variant_matrix' )
		? bsc_get_product_variant_matrix( $product_id, true )
		: array();
	$matrix_color_rows       = bsc_product_edit_variant_rows_from_matrix( $variant_matrix_rows, 'color' );
	$matrix_size_rows        = bsc_product_edit_variant_rows_from_matrix( $variant_matrix_rows, 'size' );

	if ($color_variants_enabled && $size_variants_enabled) {
		$size_variants_enabled = false;
	} elseif (!$color_variants_enabled && !$size_variants_enabled) {
		if (!empty( $matrix_color_rows ) && empty( $matrix_size_rows )) {
			$color_variants_enabled = true;
		} elseif (!empty( $matrix_size_rows ) && empty( $matrix_color_rows )) {
			$size_variants_enabled = true;
		}
	}

	$has_variant_matrix      = !empty( $variant_matrix_rows ) && ( $color_variants_enabled || $size_variants_enabled );
	$color_variant_rows      = $color_variants_enabled && !empty( $matrix_color_rows )
		? $matrix_color_rows
		: ( !empty( $color_variants )
		? $color_variants
		: array(
			array(
				'name'          => '',
				'hex'           => '#F7C0CD',
				'regular_price' => $regular_price,
				'sale_price'    => $sale_price,
				'price'         => $sale_price !== '' ? $sale_price : $regular_price,
				'stock_bodega'  => 0,
				'stock_tienda'  => 0,
				'enabled'       => true,
			),
		) );
	$size_variant_rows       = $size_variants_enabled && !empty( $matrix_size_rows )
		? $matrix_size_rows
		: ( !empty( $size_variants )
		? $size_variants
		: array(
			array(
				'name'          => '',
				'regular_price' => $regular_price,
				'sale_price'    => $sale_price,
				'price'         => $sale_price !== '' ? $sale_price : $regular_price,
				'stock_bodega'  => 0,
				'stock_tienda'  => 0,
				'enabled'       => true,
			),
		) );
	$all_tags                = wp_get_object_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );

	$cover_fields = array(
		'bsc_cover_desktop'  => 'Cover Desktop',
		'bsc_cover_mobile'   => 'Cover Mobile',
		'_bsc_extra_image_1' => 'Imagen extra 1',
		'_bsc_extra_image_2' => 'Imagen extra 2',
		'_bsc_extra_image_3' => 'Imagen extra 3',
	);
	$cover_values = array();
	foreach ($cover_fields as $key => $label) {
		$cover_values[ $key ] = (int) get_post_meta( $product_id, $key, true );
	}

	?>
	<div class="wrap bsc-admin-product-edit">
		<div class="bsc-admin-page-header bsc-admin-page-header--compact bsc-admin-product-edit__header">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Catalogo</span>
				<h1>
					Editar Producto
					<span class="bsc-admin-product-edit__title-meta">#<?php echo esc_html( $product_id ); ?></span>
				</h1>
				<p class="bsc-admin-page-header__description bsc-admin-product-edit__header-meta"><?php echo esc_html( $post->post_title ); ?></p>
			</div>
			<div class="bsc-admin-page-header__actions bsc-admin-product-edit__header-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-products' ) ); ?>" class="button">&larr; Productos</a>
				<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="button" target="_blank" rel="noopener noreferrer">Ver en tienda &#8599;</a>
			</div>
		</div>
		<?php if (isset( $_GET['saved'] )) : ?>
			<div class="notice notice-success is-dismissible"><p>&#10003; Producto actualizado correctamente.</p></div>
		<?php endif; ?>

		<form method="post" class="bsc-admin-product-edit__form">
			<?php wp_nonce_field( 'bsc_product_edit_action', 'bsc_product_edit_nonce' ); ?>
			<?php wp_nonce_field( 'bsc_product_covers_save', 'bsc_product_covers_nonce' ); ?>

			<div class="bsc-product-edit-grid bsc-admin-product-edit__grid">
				<div>
					<div class="postbox bsc-admin-product-edit__card">
						<h2 class="bsc-admin-product-edit__section-title">Basico</h2>

						<label class="bsc-admin-product-edit__field">
							<span class="bsc-admin-product-edit__field-label">Nombre del producto</span>
							<input type="text" name="post_title" value="<?php echo esc_attr( $post->post_title ); ?>" class="large-text" required>
						</label>

						<label class="bsc-admin-product-edit__field">
							<span class="bsc-admin-product-edit__field-label">Descripcion corta</span>
							<textarea name="post_excerpt" rows="4" class="large-text"><?php echo esc_textarea( $post->post_excerpt ); ?></textarea>
						</label>

						<div class="bsc-admin-product-edit__field-grid">
							<label class="bsc-admin-product-edit__field">
								<span class="bsc-admin-product-edit__field-label">SKU</span>
								<input type="text" name="_sku" value="<?php echo esc_attr( $sku ); ?>" class="regular-text">
							</label>

							<label class="bsc-admin-product-edit__field">
								<span class="bsc-admin-product-edit__field-label">Location code</span>
								<input
									type="text"
									name="_bsc_location_code"
									value="<?php echo esc_attr( $location_code ); ?>"
									class="regular-text"
									pattern="COD-M[0-9]+-E[0-9]+"
									maxlength="30"
									placeholder="COD-M4-E1"
									title="Usa el formato COD-M4-E1"
									autocomplete="off"
									spellcheck="false"
									data-bsc-location-code
								>
								<span class="bsc-admin-product-edit__field-note">Formato: COD-M4-E1 (mueble 4, espacio 1).</span>
							</label>

							<label class="bsc-admin-product-edit__field">
								<span class="bsc-admin-product-edit__field-label">Precio regular (COP)</span>
								<input
									type="number"
									min="0"
									step="1"
									inputmode="numeric"
									name="_regular_price"
									value="<?php echo esc_attr( $regular_price ); ?>"
									class="bsc-admin-product-edit__number-input bsc-admin-product-edit__number-input--wide"
								>
							</label>

							<label class="bsc-admin-product-edit__field bsc-admin-product-edit__field--wide">
								<span class="bsc-admin-product-edit__field-label">Descuento (%)</span>
								<input
									type="number"
									min="0"
									max="99.99"
									step="0.01"
									inputmode="decimal"
									name="_discount_percent"
									value="<?php echo esc_attr( $discount_percent ); ?>"
									placeholder="0"
									class="bsc-admin-product-edit__number-input bsc-admin-product-edit__number-input--wide"
								>
								<span class="bsc-admin-product-edit__field-note">
									Dejalo vacio o en 0 para quitar el descuento.
									<?php if ($sale_price !== '') : ?>
										Precio con descuento actual: <?php echo wp_kses_post( wc_price( (float) $sale_price ) ); ?>.
									<?php endif; ?>
								</span>
							</label>

							<label class="bsc-admin-product-edit__field bsc-admin-product-edit__field--wide">
								<span class="bsc-admin-product-edit__field-label">Estado</span>
								<select name="post_status">
									<option value="publish" <?php selected( $product_status, 'publish' ); ?>>Publicado</option>
									<option value="draft" <?php selected( $product_status, 'draft' ); ?>>Borrador</option>
									<option value="hidden" <?php selected( $product_status, 'hidden' ); ?>>Oculto</option>
									<option value="archive" <?php selected( $product_status, 'archive' ); ?>>Archivado</option>
									<option value="delete">Borrar</option>
								</select>
								<span class="bsc-admin-product-edit__field-note">Borrar envia el producto a la papelera.</span>
							</label>
						</div>

						<label class="bsc-admin-product-edit__field bsc-admin-product-edit__field--checkbox">
							<input type="checkbox" name="comment_status" value="open" <?php checked( $post->comment_status, 'open' ); ?>>
							Habilitar reseñas de clientes
						</label>
					</div>

					<div class="postbox bsc-admin-product-edit__card">
						<h2 class="bsc-admin-product-edit__section-title">Tags</h2>
						<label class="bsc-admin-product-edit__field">
							<span class="bsc-admin-product-edit__field-help">Separados por coma</span>
							<input type="text" name="product_tag" value="<?php echo esc_attr( implode( ', ', is_array( $all_tags ) ? $all_tags : array() ) ); ?>" class="large-text">
						</label>
					</div>

					<div class="postbox bsc-admin-product-edit__card" data-bsc-color-variants>
						<h2 class="bsc-admin-product-edit__section-title">Variantes de color</h2>

						<label class="bsc-admin-product-edit__field bsc-admin-product-edit__field--checkbox bsc-admin-product-edit__color-toggle">
							<input
								type="checkbox"
								name="_bsc_color_variants_enabled"
								value="1"
								data-bsc-color-variants-toggle
								<?php checked( $color_variants_enabled ); ?>
							>
							Habilitar variantes de color
						</label>
						<span class="bsc-admin-product-edit__field-note">Agrega cada tono con su precio y stock. Solo puedes usar variantes por color o por Tamaño.</span>

						<div class="bsc-admin-product-edit__color-panel<?php echo esc_attr( $color_variants_enabled ? '' : ' is-hidden' ); ?>" data-bsc-color-variants-panel>
							<div class="bsc-admin-product-edit__color-list" data-bsc-color-variants-list>
								<?php foreach ($color_variant_rows as $index => $variant) : ?>
									<?php
									$variant_hex           = bsc_product_edit_normalize_color_hex( (string) ( $variant['hex'] ?? '' ) );
									$variant_hex           = $variant_hex !== '' ? $variant_hex : '#F7C0CD';
									$variant_name          = (string) ( $variant['name'] ?? '' );
									$variant_regular_price = (string) ( $variant['regular_price'] ?? ( $variant['price'] ?? '' ) );
									$variant_sale_price    = (string) ( $variant['sale_price'] ?? '' );
									$variant_stock_bodega  = (string) ( $variant['stock_bodega'] ?? 0 );
									$variant_stock_tienda  = (string) ( $variant['stock_tienda'] ?? 0 );
									$variant_enabled       = !array_key_exists( 'enabled', $variant ) || !empty( $variant['enabled'] );
									?>
									<div class="bsc-admin-product-edit__color-row" data-bsc-color-variant-row>
										<label class="bsc-admin-product-edit__field bsc-admin-product-edit__color-field">
											<span class="bsc-admin-product-edit__field-label">Color</span>
											<span class="bsc-admin-product-edit__color-picker">
												<input
													type="color"
													name="_bsc_color_variants[<?php echo esc_attr( (string) $index ); ?>][hex]"
													value="<?php echo esc_attr( $variant_hex ); ?>"
													data-bsc-color-variant-hex
												>
												<span
													class="bsc-admin-product-edit__color-preview"
													data-bsc-color-variant-preview
												></span>
											</span>
										</label>
										<label class="bsc-admin-product-edit__field bsc-admin-product-edit__color-name">
											<span class="bsc-admin-product-edit__field-label">Nombre del color</span>
											<input
												type="text"
												name="_bsc_color_variants[<?php echo esc_attr( (string) $index ); ?>][name]"
												value="<?php echo esc_attr( $variant_name ); ?>"
												class="regular-text"
												placeholder="Ej: Rosado claro"
												data-bsc-color-variant-name
											>
										</label>
										<label class="bsc-admin-product-edit__field bsc-admin-product-edit__variant-price">
											<span class="bsc-admin-product-edit__field-label">Precio regular</span>
											<input
												type="number"
												min="0"
												step="1"
												inputmode="numeric"
												name="_bsc_color_variants[<?php echo esc_attr( (string) $index ); ?>][regular_price]"
												value="<?php echo esc_attr( $variant_regular_price ); ?>"
												class="bsc-admin-product-edit__number-input"
												data-bsc-color-variant-regular-price
											>
										</label>
										<label class="bsc-admin-product-edit__field bsc-admin-product-edit__variant-price">
											<span class="bsc-admin-product-edit__field-label">Oferta</span>
											<input
												type="number"
												min="0"
												step="1"
												inputmode="numeric"
												name="_bsc_color_variants[<?php echo esc_attr( (string) $index ); ?>][sale_price]"
												value="<?php echo esc_attr( $variant_sale_price ); ?>"
												class="bsc-admin-product-edit__number-input"
												data-bsc-color-variant-sale-price
											>
										</label>
										<label class="bsc-admin-product-edit__field">
											<span class="bsc-admin-product-edit__field-label">Stock Bodega</span>
											<input
												type="number"
												min="0"
												step="1"
												name="_bsc_color_variants[<?php echo esc_attr( (string) $index ); ?>][stock_bodega]"
												value="<?php echo esc_attr( $variant_stock_bodega ); ?>"
												class="bsc-admin-product-edit__number-input"
												data-bsc-color-variant-stock-bodega
											>
										</label>
										<label class="bsc-admin-product-edit__field">
											<span class="bsc-admin-product-edit__field-label">Stock Tienda</span>
											<input
												type="number"
												min="0"
												step="1"
												name="_bsc_color_variants[<?php echo esc_attr( (string) $index ); ?>][stock_tienda]"
												value="<?php echo esc_attr( $variant_stock_tienda ); ?>"
												class="bsc-admin-product-edit__number-input"
												data-bsc-color-variant-stock-tienda
											>
										</label>
										<label class="bsc-admin-product-edit__field bsc-admin-product-edit__variant-inline-enabled">
											<span class="bsc-admin-product-edit__field-label">Activa</span>
											<input type="hidden" name="_bsc_color_variants[<?php echo esc_attr( (string) $index ); ?>][enabled]" value="0" data-bsc-color-variant-enabled-hidden>
											<input
												type="checkbox"
												name="_bsc_color_variants[<?php echo esc_attr( (string) $index ); ?>][enabled]"
												value="1"
												data-bsc-color-variant-enabled
												<?php checked( $variant_enabled ); ?>
											>
										</label>
										<button type="button" class="button bsc-admin-product-edit__color-remove" data-bsc-color-variant-remove>Quitar</button>
									</div>
								<?php endforeach; ?>
							</div>
							<button type="button" class="button" data-bsc-color-variant-add>Agregar color</button>
						</div>
					</div>

					<div class="postbox bsc-admin-product-edit__card" data-bsc-size-variants>
						<h2 class="bsc-admin-product-edit__section-title">Variantes de Tamaño</h2>

						<label class="bsc-admin-product-edit__field bsc-admin-product-edit__field--checkbox bsc-admin-product-edit__size-toggle">
							<input
								type="checkbox"
								name="_bsc_size_variants_enabled"
								value="1"
								data-bsc-size-variants-toggle
								<?php checked( $size_variants_enabled ); ?>
							>
							Habilitar variantes de Tamaño
						</label>
						<span class="bsc-admin-product-edit__field-note">Crea opciones como 50 ml, 150 ml, S, M o L. Cada Tamaño tiene su propio precio y stock.</span>

						<div class="bsc-admin-product-edit__size-panel<?php echo esc_attr( $size_variants_enabled ? '' : ' is-hidden' ); ?>" data-bsc-size-variants-panel>
							<div class="bsc-admin-product-edit__size-list" data-bsc-size-variants-list>
								<?php foreach ($size_variant_rows as $index => $variant) : ?>
									<?php
									$variant_name          = (string) ( $variant['name'] ?? '' );
									$variant_regular_price = (string) ( $variant['regular_price'] ?? ( $variant['price'] ?? '' ) );
									$variant_sale_price    = (string) ( $variant['sale_price'] ?? '' );
									$variant_stock_bodega  = (string) ( $variant['stock_bodega'] ?? 0 );
									$variant_stock_tienda  = (string) ( $variant['stock_tienda'] ?? 0 );
									$variant_enabled       = !array_key_exists( 'enabled', $variant ) || !empty( $variant['enabled'] );
									?>
									<div class="bsc-admin-product-edit__size-row" data-bsc-size-variant-row>
										<label class="bsc-admin-product-edit__field bsc-admin-product-edit__size-name">
											<span class="bsc-admin-product-edit__field-label">Tamaño</span>
											<input
												type="text"
												name="_bsc_size_variants[<?php echo esc_attr( (string) $index ); ?>][name]"
												value="<?php echo esc_attr( $variant_name ); ?>"
												class="regular-text"
												placeholder="Ej: 50 ml"
												data-bsc-size-variant-name
											>
										</label>
										<label class="bsc-admin-product-edit__field bsc-admin-product-edit__variant-price">
											<span class="bsc-admin-product-edit__field-label">Precio regular</span>
											<input
												type="number"
												min="0"
												step="1"
												inputmode="numeric"
												name="_bsc_size_variants[<?php echo esc_attr( (string) $index ); ?>][regular_price]"
												value="<?php echo esc_attr( $variant_regular_price ); ?>"
												class="bsc-admin-product-edit__number-input"
												data-bsc-size-variant-regular-price
											>
										</label>
										<label class="bsc-admin-product-edit__field bsc-admin-product-edit__variant-price">
											<span class="bsc-admin-product-edit__field-label">Oferta</span>
											<input
												type="number"
												min="0"
												step="1"
												inputmode="numeric"
												name="_bsc_size_variants[<?php echo esc_attr( (string) $index ); ?>][sale_price]"
												value="<?php echo esc_attr( $variant_sale_price ); ?>"
												class="bsc-admin-product-edit__number-input"
												data-bsc-size-variant-sale-price
											>
										</label>
										<label class="bsc-admin-product-edit__field">
											<span class="bsc-admin-product-edit__field-label">Stock Bodega</span>
											<input
												type="number"
												min="0"
												step="1"
												name="_bsc_size_variants[<?php echo esc_attr( (string) $index ); ?>][stock_bodega]"
												value="<?php echo esc_attr( $variant_stock_bodega ); ?>"
												class="bsc-admin-product-edit__number-input"
												data-bsc-size-variant-stock-bodega
											>
										</label>
										<label class="bsc-admin-product-edit__field">
											<span class="bsc-admin-product-edit__field-label">Stock Tienda</span>
											<input
												type="number"
												min="0"
												step="1"
												name="_bsc_size_variants[<?php echo esc_attr( (string) $index ); ?>][stock_tienda]"
												value="<?php echo esc_attr( $variant_stock_tienda ); ?>"
												class="bsc-admin-product-edit__number-input"
												data-bsc-size-variant-stock-tienda
											>
										</label>
										<label class="bsc-admin-product-edit__field bsc-admin-product-edit__variant-inline-enabled">
											<span class="bsc-admin-product-edit__field-label">Activa</span>
											<input type="hidden" name="_bsc_size_variants[<?php echo esc_attr( (string) $index ); ?>][enabled]" value="0" data-bsc-size-variant-enabled-hidden>
											<input
												type="checkbox"
												name="_bsc_size_variants[<?php echo esc_attr( (string) $index ); ?>][enabled]"
												value="1"
												data-bsc-size-variant-enabled
												<?php checked( $variant_enabled ); ?>
											>
										</label>
										<button type="button" class="button bsc-admin-product-edit__size-remove" data-bsc-size-variant-remove>Quitar</button>
									</div>
								<?php endforeach; ?>
							</div>
							<button type="button" class="button" data-bsc-size-variant-add>Agregar Tamaño</button>
						</div>
					</div>

				</div>

				<div>
					<div class="postbox bsc-admin-product-edit__card">
						<h2 class="bsc-admin-product-edit__section-title">Imagen principal</h2>
						<div id="bsc-main-image-preview">
							<?php if ($thumbnail_src) : ?>
								<img src="<?php echo esc_url( $thumbnail_src ); ?>" class="bsc-admin-product-edit__preview-image" alt="">
							<?php endif; ?>
						</div>
						<input type="hidden" name="_thumbnail_id" id="bsc-thumbnail-id" value="<?php echo esc_attr( $thumbnail_id ?: '' ); ?>">
						<input type="hidden" name="_remove_thumbnail" id="bsc-remove-thumbnail-flag" value="">
						<button type="button" class="button" id="bsc-select-main-image">Seleccionar imagen</button>
						<button type="button" class="button bsc-admin-product-edit__button-spaced<?php echo esc_attr( $thumbnail_id ? '' : ' is-hidden' ); ?>" id="bsc-remove-main-image">Quitar imagen</button>
					</div>

					<div class="postbox bsc-admin-product-edit__card">
						<h2 class="bsc-admin-product-edit__section-title">Galeria</h2>
						<div id="bsc-gallery-preview" class="bsc-admin-product-edit__gallery">
							<?php foreach ($gallery as $gallery_id) : ?>
								<?php $gallery_src = wp_get_attachment_image_url( $gallery_id, array( 80, 80 ) ); ?>
								<?php if ($gallery_src) : ?>
									<img src="<?php echo esc_url( $gallery_src ); ?>" class="bsc-admin-product-edit__gallery-thumb" alt="">
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
						<input type="hidden" name="_product_image_gallery" id="bsc-gallery-ids" value="<?php echo esc_attr( implode( ',', $gallery ) ); ?>">
						<button type="button" class="button" id="bsc-select-gallery">Editar galeria</button>
					</div>

					<div class="postbox bsc-admin-product-edit__card">
						<h2 class="bsc-admin-product-edit__section-title">Stock Dual (BSC)</h2>
						<?php if ($has_variant_matrix) : ?>
							<span class="bsc-admin-product-edit__field-note">Este total se calcula automaticamente desde las variantes activas.</span>
						<?php endif; ?>

						<div class="bsc-admin-product-edit__field-grid bsc-admin-product-edit__field-grid--stock">
							<label class="bsc-admin-product-edit__field">
								<span class="bsc-admin-product-edit__field-label">Stock Bodega (web)</span>
								<input type="number" min="0" name="_stock_bodega" value="<?php echo esc_attr( $stock['bodega'] ); ?>" class="bsc-admin-product-edit__number-input" <?php echo $has_variant_matrix ? 'readonly="readonly"' : ''; ?>>
							</label>

							<label class="bsc-admin-product-edit__field">
								<span class="bsc-admin-product-edit__field-label">Stock Tienda (showroom)</span>
								<input type="number" min="0" name="_stock_tienda" value="<?php echo esc_attr( $stock['tienda'] ); ?>" class="bsc-admin-product-edit__number-input" <?php echo $has_variant_matrix ? 'readonly="readonly"' : ''; ?>>
							</label>

							<label class="bsc-admin-product-edit__field bsc-admin-product-edit__field--wide">
								<span class="bsc-admin-product-edit__field-label">Tipo de envio</span>
								<select name="_envio_tipo">
									<option value="bodega" <?php selected( $stock['envio_tipo'], 'bodega' ); ?>>Bodega (web)</option>
									<option value="tienda" <?php selected( $stock['envio_tipo'], 'tienda' ); ?>>Tienda (showroom)</option>
									<option value="ambos" <?php selected( $stock['envio_tipo'], 'ambos' ); ?>>Ambos</option>
								</select>
							</label>
						</div>
					</div>

					<div class="postbox bsc-admin-product-edit__card">
						<h2 class="bsc-admin-product-edit__section-title">Covers de Producto</h2>
						<div class="bsc-admin-product-edit__cover-grid">
							<?php foreach ($cover_fields as $key => $label) : ?>
								<?php
								$attachment_id = $cover_values[ $key ];
								$image_src     = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
								?>
								<div class="bsc-cover-field bsc-admin-cover-field">
									<p class="bsc-admin-cover-label"><strong><?php echo esc_html( $label ); ?></strong></p>
									<div class="bsc-cover-preview bsc-admin-cover-preview">
										<?php if ($image_src) : ?>
											<img src="<?php echo esc_url( $image_src ); ?>" class="bsc-admin-cover-image" alt="">
										<?php endif; ?>
									</div>
									<div class="bsc-admin-cover-actions">
										<input type="hidden" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $attachment_id ?: '' ); ?>">
										<button type="button" class="button bsc-cover-select" data-field="<?php echo esc_attr( $key ); ?>">
											<?php echo esc_html( $attachment_id ? 'Cambiar' : 'Seleccionar' ); ?>
										</button>
										<?php if ($attachment_id) : ?>
											<button type="button" class="button bsc-cover-remove bsc-admin-cover-remove" data-field="<?php echo esc_attr( $key ); ?>">Eliminar</button>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="postbox bsc-admin-product-edit__card">
				<h2 class="bsc-admin-product-edit__section-title">Categorias</h2>
				<div class="bsc-admin-product-edit__category-layout">
					<?php if ( bsc_product_edit_user_can_manage_categories() ) : ?>
						<div class="bsc-admin-product-edit__category-manager" data-bsc-category-manager>
							<div class="bsc-admin-product-edit__category-manager-header">
								<strong data-bsc-cat-form-title>Agregar categoria</strong>
								<span class="bsc-admin-product-edit__field-note">Crea, edita o elimina categorias sin salir del producto.</span>
							</div>
							<div class="bsc-admin-product-edit__category-message is-hidden" data-bsc-cat-message></div>
							<input type="hidden" id="bsc-cat-term-id" value="" data-bsc-cat-term-id>
							<div class="bsc-admin-product-edit__category-form-grid">
								<label class="bsc-admin-product-edit__field">
									<span class="bsc-admin-product-edit__field-label">Nombre</span>
									<input type="text" id="bsc-cat-name" class="regular-text" data-bsc-cat-name>
								</label>
								<label class="bsc-admin-product-edit__field">
									<span class="bsc-admin-product-edit__field-label">Slug</span>
									<input type="text" id="bsc-cat-slug" class="regular-text" data-bsc-cat-slug placeholder="Opcional">
								</label>
								<label class="bsc-admin-product-edit__field">
									<span class="bsc-admin-product-edit__field-label">Padre</span>
									<select id="bsc-cat-parent" data-bsc-cat-parent></select>
								</label>
							</div>
							<div class="bsc-admin-product-edit__category-actions">
								<button type="button" class="button button-primary" data-bsc-cat-create>Agregar categoria</button>
								<button type="button" class="button button-primary is-hidden" data-bsc-cat-update>Guardar categoria</button>
								<button type="button" class="button is-hidden" data-bsc-cat-cancel>Cancelar</button>
								<button type="button" class="button bsc-admin-product-edit__category-delete is-hidden" data-bsc-cat-delete>Eliminar</button>
							</div>
						</div>
					<?php endif; ?>
					<div class="bsc-admin-product-edit__category-selector">
						<div class="bsc-admin-product-edit__category-selector-header">
							<div>
								<strong>Seleccionar categorias</strong>
								<span class="bsc-admin-product-edit__field-note">Usa las pestanas y buscadores por grupo para encontrar opciones rapido.</span>
							</div>
							<div class="bsc-admin-product-edit__selected-count" data-bsc-cat-selected-count>0 seleccionadas</div>
						</div>
						<div class="bsc-admin-product-edit__selected-cats" data-bsc-cat-selected-summary></div>
						<div id="bsc-root-tabs" class="bsc-admin-product-edit__tabs"></div>
						<div id="bsc-cat-branches"></div>
						<div id="bsc-cat-hidden-inputs" class="bsc-admin-product-edit__hidden"></div>
					</div>
				</div>
			</div>

			<div class="bsc-admin-product-edit__actions bsc-admin-panel">
				<button type="submit" class="button button-primary button-large">Guardar cambios</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-products' ) ); ?>" class="button button-large bsc-admin-product-edit__action-link">Cancelar</a>
			</div>
		</form>
	</div>
	<?php
}
