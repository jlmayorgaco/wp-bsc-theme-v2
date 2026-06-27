<?php
/**
 * BSC-062: BSC Products table with explicit inline regular price and stock editing.
 * BSC-066: Includes AJAX handlers for stock adjustment and history log.
 */
defined( 'ABSPATH' ) || exit;

if (!function_exists( 'bsc_sanitize_product_price_value' )) {
	function bsc_sanitize_product_price_value( $raw_value ): ?string {
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
}

if (!function_exists( 'bsc_update_product_price_values' )) {
	function bsc_update_product_price_values( WC_Product $product, string $regular_price, string $sale_price ): ?string {
		if ($regular_price === '' && $sale_price !== '') {
			return 'El precio regular es obligatorio cuando hay precio de oferta.';
		}

		if ($sale_price !== '' && $regular_price !== '' && (float) $sale_price > (float) $regular_price) {
			return 'El precio de oferta no puede superar el precio regular.';
		}

		if (
			!is_callable( array( $product, 'set_regular_price' ) )
			|| !is_callable( array( $product, 'set_sale_price' ) )
			|| !is_callable( array( $product, 'set_price' ) )
		) {
			return 'Este tipo de producto no permite editar precio desde BSC Products.';
		}

		try {
			$product->set_regular_price( $regular_price );
			$product->set_sale_price( $sale_price );
			$product->set_price( $sale_price !== '' ? $sale_price : $regular_price );
			$product->save();
			wc_delete_product_transients( $product->get_id() );
		} catch (Exception) {
			return 'No se pudo guardar el precio.';
		}

		return null;
	}
}

add_action( 'wp_ajax_bsc_update_product_stock', 'bsc_ajax_update_product_stock' );
function bsc_ajax_update_product_stock(): void {
	check_ajax_referer( 'bsc_products_nonce', 'nonce' );
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'edit_products' )) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ), 403 );
	}

	$product_id = absint( wp_unslash( $_POST['product_id'] ?? 0 ) );
	$type_raw   = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$type       = in_array( $type_raw, array( 'bodega', 'tienda' ), true )
		? $type_raw
		: 'bodega';
	$value      = max( 0, intval( wp_unslash( $_POST['value'] ?? 0 ) ) );
	$meta_key   = $type === 'tienda' ? '_stock_tienda' : '_stock_bodega';

	if (!$product_id || get_post_type( $product_id ) !== 'product') {
		wp_send_json_error( array( 'message' => 'Producto inválido' ), 400 );
	}

	$old_value = (int) get_post_meta( $product_id, $meta_key, true );
	$new_value = $value;

	if (class_exists( 'BSC_Stock' ) && $old_value !== $value) {
		$new_value = BSC_Stock::adjust( $product_id, $type, $value - $old_value, 'Edicion inline BSC Products' );
	} else {
		update_post_meta( $product_id, $meta_key, $value );
	}

	wp_send_json_success( array( 'new_value' => $new_value ) );
}

add_action( 'wp_ajax_bsc_update_product_stocks', 'bsc_ajax_update_product_stocks' );
function bsc_ajax_update_product_stocks(): void {
	check_ajax_referer( 'bsc_products_nonce', 'nonce' );
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'edit_products' )) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ), 403 );
	}

	$product_id = absint( wp_unslash( $_POST['product_id'] ?? 0 ) );
	$bodega     = max( 0, intval( wp_unslash( $_POST['bodega'] ?? 0 ) ) );
	$tienda     = max( 0, intval( wp_unslash( $_POST['tienda'] ?? 0 ) ) );

	if (!$product_id || get_post_type( $product_id ) !== 'product') {
		wp_send_json_error( array( 'message' => 'Producto inválido' ), 400 );
	}

	$product = wc_get_product( $product_id );
	if (!$product instanceof WC_Product) {
		wp_send_json_error( array( 'message' => 'Producto invalido' ), 400 );
	}

	$current_bodega = (int) get_post_meta( $product_id, '_stock_bodega', true );
	$current_tienda = (int) get_post_meta( $product_id, '_stock_tienda', true );

	if (class_exists( 'BSC_Stock' )) {
		if ($current_bodega !== $bodega) {
			$bodega = BSC_Stock::adjust( $product_id, 'bodega', $bodega - $current_bodega, 'Edicion inline BSC Products' );
		}

		if ($current_tienda !== $tienda) {
			$tienda = BSC_Stock::adjust( $product_id, 'tienda', $tienda - $current_tienda, 'Edicion inline BSC Products' );
		}
	} else {
		update_post_meta( $product_id, '_stock_bodega', $bodega );
		update_post_meta( $product_id, '_stock_tienda', $tienda );
	}

	$has_regular_price = isset( $_POST['regular_price'] );
	$has_sale_price    = isset( $_POST['sale_price'] );

	if ($has_regular_price || $has_sale_price) {
		$regular_price_raw = $has_regular_price
			? wp_unslash( $_POST['regular_price'] )
			: $product->get_regular_price();
		$sale_price_raw    = $has_sale_price
			? wp_unslash( $_POST['sale_price'] )
			: $product->get_sale_price();
		$regular_price     = bsc_sanitize_product_price_value( $regular_price_raw );
		$sale_price        = bsc_sanitize_product_price_value( $sale_price_raw );

		if ($regular_price === null || $sale_price === null) {
			wp_send_json_error( array( 'message' => 'Precio invalido.' ), 400 );
		}

		$price_error = bsc_update_product_price_values( $product, $regular_price, $sale_price );
		if ($price_error !== null) {
			wp_send_json_error( array( 'message' => $price_error ), 400 );
		}

		$product = wc_get_product( $product_id );
	}

	wp_send_json_success(
		array(
			'bodega'        => $bodega,
			'tienda'        => $tienda,
			'regular_price' => $product instanceof WC_Product ? $product->get_regular_price() : '',
			'sale_price'    => $product instanceof WC_Product ? $product->get_sale_price() : '',
		)
	);
}

add_action( 'wp_ajax_bsc_apply_product_discount', 'bsc_ajax_apply_product_discount' );
function bsc_ajax_apply_product_discount(): void {
	check_ajax_referer( 'bsc_products_nonce', 'nonce' );
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'edit_products' )) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ), 403 );
	}

	$product_ids_raw = isset( $_POST['product_ids'] ) ? wp_unslash( $_POST['product_ids'] ) : array();
	if (is_string( $product_ids_raw )) {
		$product_ids_raw = explode( ',', $product_ids_raw );
	}

	$product_ids = array_values(
		array_unique(
			array_filter(
				array_map( 'absint', (array) $product_ids_raw )
			)
		)
	);

	$discount_percent_raw = isset( $_POST['discount_percent'] )
		? str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['discount_percent'] ) ) )
		: '';

	if (empty( $product_ids )) {
		wp_send_json_error( array( 'message' => 'Selecciona al menos un producto.' ), 400 );
	}

	if (!is_numeric( $discount_percent_raw )) {
		wp_send_json_error( array( 'message' => 'Ingresa un porcentaje valido.' ), 400 );
	}

	$discount_percent = (float) $discount_percent_raw;
	if ($discount_percent < 0 || $discount_percent >= 100) {
		wp_send_json_error( array( 'message' => 'El descuento debe estar entre 0% y 99%.' ), 400 );
	}

	$updated = 0;
	$skipped = 0;
	$prices  = array();

	foreach ($product_ids as $product_id) {
		if (!$product_id || get_post_type( $product_id ) !== 'product') {
			$skipped++;
			continue;
		}

		$product = wc_get_product( $product_id );
		if (!$product instanceof WC_Product) {
			$skipped++;
			continue;
		}

		$regular_price = bsc_sanitize_product_price_value( $product->get_regular_price() );
		if ($regular_price === null || $regular_price === '' || (float) $regular_price <= 0) {
			$skipped++;
			continue;
		}

		$sale_price = $discount_percent > 0
			? wc_format_decimal(
				(float) $regular_price * ( ( 100 - $discount_percent ) / 100 ),
				wc_get_price_decimals()
			)
			: '';

		$price_error = bsc_update_product_price_values( $product, $regular_price, $sale_price );
		if ($price_error !== null) {
			$skipped++;
			continue;
		}

		$updated++;
		$prices[ $product_id ] = array(
			'sale_price'       => $sale_price,
			'sale_price_label' => $sale_price === '' ? '' : wp_strip_all_tags( wc_price( (float) $sale_price ) ),
		);
	}

	if (!$updated) {
		wp_send_json_error( array( 'message' => 'No se pudo aplicar el descuento a los productos seleccionados.' ), 400 );
	}

	wp_send_json_success(
		array(
			'updated' => $updated,
			'skipped' => $skipped,
			'prices'  => $prices,
		)
	);
}

add_action( 'wp_ajax_bsc_trash_product', 'bsc_ajax_trash_product' );
function bsc_ajax_trash_product(): void {
	check_ajax_referer( 'bsc_products_nonce', 'nonce' );
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'edit_products' )) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ), 403 );
	}

	$product_id = absint( wp_unslash( $_POST['product_id'] ?? 0 ) );
	if (!$product_id || get_post_type( $product_id ) !== 'product') {
		wp_send_json_error( array( 'message' => 'Producto invalido' ), 400 );
	}

	$result = wp_trash_post( $product_id );
	if (!$result) {
		wp_send_json_error( array( 'message' => 'No se pudo borrar el producto.' ), 400 );
	}

	wc_delete_product_transients( $product_id );

	wp_send_json_success(
		array(
			'product_id' => $product_id,
			'message'    => 'Producto enviado a la papelera.',
		)
	);
}

add_action( 'wp_ajax_bsc_adjust_stock', 'bsc_ajax_adjust_stock' );
function bsc_ajax_adjust_stock(): void {
	check_ajax_referer( 'bsc_products_nonce', 'nonce' );
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'edit_products' )) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ), 403 );
	}

	$product_id = absint( wp_unslash( $_POST['product_id'] ?? 0 ) );
	$type_raw   = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$type       = in_array( $type_raw, array( 'bodega', 'tienda' ), true )
		? $type_raw
		: 'bodega';
	$delta      = intval( wp_unslash( $_POST['delta'] ?? 0 ) );
	$reason     = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

	if (!$product_id || get_post_type( $product_id ) !== 'product') {
		wp_send_json_error( array( 'message' => 'Producto inválido' ), 400 );
	}

	$new_stock = BSC_Stock::adjust( $product_id, $type, $delta, $reason );
	wp_send_json_success( array( 'new_stock' => $new_stock ) );
}

add_action( 'wp_ajax_bsc_get_stock_log', 'bsc_ajax_get_stock_log' );
function bsc_ajax_get_stock_log(): void {
	check_ajax_referer( 'bsc_products_nonce', 'nonce' );
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'edit_products' )) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ), 403 );
	}

	$product_id = absint( wp_unslash( $_GET['product_id'] ?? 0 ) );
	if (!$product_id) {
		wp_send_json_error( array( 'message' => 'Producto inválido' ), 400 );
	}

	$log      = BSC_Stock::get_log( $product_id );
	$enriched = array_map(
		static function ( array $entry ): array {
			$entry['username'] = $entry['user_id']
			? ( get_userdata( $entry['user_id'] )->display_name ?? '—' )
			: '—';
			return $entry;
		},
		$log
	);

	wp_send_json_success( array( 'log' => $enriched ) );
}

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_products_page_assets' );
function bsc_enqueue_products_page_assets( string $hook ): void {
	if (strpos( $hook, 'bsc-products' ) === false) {
		return;
	}

	bsc_enqueue_admin_ui_assets();

	$css_path = get_template_directory() . '/admin/bsc-products.css';
	wp_enqueue_style(
		'bsc-products-admin',
		get_template_directory_uri() . '/admin/bsc-products.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);

	$js_path = get_template_directory() . '/js/admin/bsc-products.js';
	wp_enqueue_script(
		'bsc-products-admin',
		get_template_directory_uri() . '/js/admin/bsc-products.js',
		array( 'jquery' ),
		file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
		true
	);

	wp_localize_script(
		'bsc-products-admin',
		'bscProductsAdmin',
		array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'bsc_products_nonce' ),
			'lowStockThreshold' => (int) get_option( 'bsc_low_stock_threshold', 3 ),
			'strings'           => array(
				'historyTitlePrefix'     => 'Historial: ',
				'loading'                => 'Cargando...',
				'emptyLog'               => 'Sin movimientos registrados.',
				'loadError'              => 'No se pudo cargar el historial.',
				'saved'                  => 'Cambios guardados.',
				'saveError'              => 'No se pudieron guardar los cambios.',
				'priceError'             => 'Revisa los precios antes de guardar.',
				'salePriceError'         => 'El precio de oferta no puede superar el precio regular.',
				'discountNoSelection'    => 'Selecciona al menos un producto.',
				'discountInvalidPercent' => 'Ingresa un descuento entre 0% y 99%.',
				'discountApplied'        => 'Descuento actualizado.',
				'deleteConfirm'          => 'Borrar "%s"? El producto se enviara a la papelera.',
				'deleting'               => 'Borrando...',
				'deleted'                => 'Producto enviado a la papelera.',
				'deleteError'            => 'No se pudo borrar el producto.',
				'connectionError'        => 'Error de conexión. Intenta de nuevo.',
			),
		)
	);
}

function bsc_render_products_page(): void {
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'edit_products' )) {
		wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
	}

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only product table filters.
	$search     = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
	$status_raw = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
	$status     = in_array( $status_raw, array( 'publish', 'draft' ), true )
		? $status_raw
		: '';
	$paged      = max( 1, intval( wp_unslash( $_GET['paged'] ?? 1 ) ) );
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
	$per_page = 20;

	$args = array(
		'post_type'              => 'product',
		'post_status'            => $status ?: array( 'publish', 'draft' ),
		'posts_per_page'         => $per_page,
		'paged'                  => $paged,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	);

	if ($search) {
		$args['s'] = $search;
	}

	$query    = new WP_Query( $args );
	$products = $query->posts;
	$total    = (int) $query->found_posts;
	$pages    = (int) ceil( $total / $per_page );
	$counts   = wp_count_posts( 'product' );
	$low_ids  = class_exists( 'BSC_Stock' ) ? BSC_Stock::get_low_stock_products( (int) get_option( 'bsc_low_stock_threshold', 3 ) ) : array();
	?>
	<div class="wrap bsc-admin-products">
		<div class="bsc-admin-page-header">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Catalogo y stock</span>
				<h1 class="wp-heading-inline">Productos BSC</h1>
				<p class="bsc-admin-page-header__description">Edita precio, inventario y visibilidad sin salir del flujo operativo.</p>
			</div>
			<div class="bsc-admin-page-header__actions">
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-dashboard' ) ); ?>">Dashboard</a>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>">Nuevo producto</a>
			</div>
		</div>

		<div class="bsc-admin-stat-grid bsc-admin-products__summary">
			<div class="bsc-admin-stat-card">
				<span class="bsc-admin-stat-card__label">Resultados visibles</span>
				<span class="bsc-admin-stat-card__value"><?php echo esc_html( number_format_i18n( $total ) ); ?></span>
				<span class="bsc-admin-stat-card__help">Pagina <?php echo esc_html( number_format_i18n( $paged ) ); ?> de <?php echo esc_html( number_format_i18n( max( 1, $pages ) ) ); ?></span>
			</div>
			<div class="bsc-admin-stat-card">
				<span class="bsc-admin-stat-card__label">Publicados</span>
				<span class="bsc-admin-stat-card__value"><?php echo esc_html( number_format_i18n( (int) ( $counts->publish ?? 0 ) ) ); ?></span>
				<span class="bsc-admin-stat-card__help">Disponibles en tienda.</span>
			</div>
			<div class="bsc-admin-stat-card">
				<span class="bsc-admin-stat-card__label">Borradores</span>
				<span class="bsc-admin-stat-card__value"><?php echo esc_html( number_format_i18n( (int) ( $counts->draft ?? 0 ) ) ); ?></span>
				<span class="bsc-admin-stat-card__help">Pendientes de publicar.</span>
			</div>
			<div class="bsc-admin-stat-card<?php echo esc_attr( ! empty( $low_ids ) ? ' bsc-admin-stat-card--warning' : '' ); ?>">
				<span class="bsc-admin-stat-card__label">Stock bajo</span>
				<span class="bsc-admin-stat-card__value"><?php echo esc_html( number_format_i18n( count( (array) $low_ids ) ) ); ?></span>
				<span class="bsc-admin-stat-card__help">Bodega bajo umbral.</span>
			</div>
		</div>

		<form method="get" class="bsc-admin-products__filters bsc-admin-toolbar bsc-admin-filter-panel">
			<input type="hidden" name="page" value="bsc-products">
			<div class="bsc-admin-field bsc-admin-field--grow">
				<label for="bsc-products-search">Buscar</label>
				<input id="bsc-products-search" type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Nombre o SKU" class="regular-text">
			</div>
			<div class="bsc-admin-field">
				<label for="bsc-products-status">Estado</label>
				<select id="bsc-products-status" name="status">
					<option value="">Todos</option>
					<option value="publish" <?php selected( $status, 'publish' ); ?>>Publicados</option>
					<option value="draft" <?php selected( $status, 'draft' ); ?>>Borradores</option>
				</select>
			</div>
			<div class="bsc-admin-filter-panel__actions">
				<button type="submit" class="button button-primary">Filtrar</button>
				<?php if ($search || $status) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-products' ) ); ?>" class="button">Limpiar</a>
				<?php endif; ?>
			</div>
		</form>

		<div class="bsc-admin-products__discount-toolbar bsc-admin-panel bsc-admin-panel--compact">
			<div class="bsc-admin-products__discount-copy">
				<strong>Descuentos por lote</strong>
				<span>Selecciona productos visibles y aplica una oferta temporal.</span>
			</div>
			<button type="button" class="button" id="bsc-discount-mode-toggle">Modificar descuentos</button>
			<div class="bsc-admin-products__discount-controls" id="bsc-discount-controls" hidden>
				<span class="bsc-admin-products__selection-count" id="bsc-discount-selection-count">0 seleccionados</span>
				<label class="bsc-admin-products__discount-field" for="bsc-discount-percent">
					<span>Descuento</span>
					<input
						type="number"
						min="0"
						max="99"
						step="1"
						inputmode="numeric"
						id="bsc-discount-percent"
						class="small-text"
						disabled
					>
					<span>%</span>
				</label>
				<button type="button" class="button button-primary" id="bsc-apply-discount" disabled>Guardar descuento</button>
				<button type="button" class="button" id="bsc-discount-mode-cancel">Cancelar</button>
			</div>
		</div>

		<p class="description bsc-admin-products__result-summary"><?php echo esc_html( $total ); ?> productos encontrados.</p>

		<div class="bsc-admin-table-wrap">
		<table class="wp-list-table widefat fixed striped bsc-admin-products__table">
			<thead>
				<tr>
					<th class="bsc-admin-products__col-discount bsc-admin-products__discount-cell">
						<input type="checkbox" id="bsc-discount-select-all" disabled aria-label="Seleccionar todos los productos visibles">
					</th>
					<th class="bsc-admin-products__col-image">Imagen</th>
					<th>Nombre / SKU</th>
					<th class="bsc-admin-products__col-price">Precio (COP)</th>
					<th class="bsc-admin-products__col-stock">Stock Bodega</th>
					<th class="bsc-admin-products__col-stock">Stock Tienda</th>
					<th class="bsc-admin-products__col-status">Estado</th>
					<th class="bsc-admin-products__col-actions">Acciones</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($products as $post) : ?>
					<?php
					$product = wc_get_product( $post->ID );
					if (!$product) {
						continue;
					}

					$sku                  = $product->get_sku();
					$price                = $product->get_regular_price();
					$sale_price           = $product->get_sale_price();
					$stock                = BSC_Stock::get_stock( $post->ID );
					$image_id             = $product->get_image_id();
					$image_src            = $image_id
						? ( wp_get_attachment_image_url( $image_id, array( 60, 60 ) ) ?: wc_placeholder_img_src() )
						: wc_placeholder_img_src();
					$edit_url             = admin_url( 'admin.php?page=bsc-product-edit&id=' . $post->ID );
					$view_url             = get_permalink( $post->ID );
					$low_threshold        = (int) get_option( 'bsc_low_stock_threshold', 3 );
					$is_archived          = '1' === (string) get_post_meta( $post->ID, '_bsc_product_archived', true );
					$is_hidden            = 'hidden' === $product->get_catalog_visibility();
					$price_input_classes  = 'bsc-product-inline-input bsc-price-input bsc-admin-products__price-input bsc-admin-inline-editor__field';
					$bodega_input_classes = 'bsc-product-inline-input bsc-stock-input bsc-admin-products__stock-input bsc-admin-inline-editor__field';
					$tienda_input_classes = 'bsc-product-inline-input bsc-stock-input bsc-admin-products__stock-input bsc-admin-inline-editor__field';

					if ( (int) $stock['bodega'] < $low_threshold) {
						$bodega_input_classes .= ' is-low';
					}

					if ( (int) $stock['tienda'] < $low_threshold) {
						$tienda_input_classes .= ' is-low';
					}
					?>
					<tr class="bsc-admin-products__row" data-product-id="<?php echo esc_attr( $post->ID ); ?>">
						<td class="bsc-admin-products__discount-cell">
							<input
								type="checkbox"
								class="bsc-product-discount-checkbox"
								value="<?php echo esc_attr( $post->ID ); ?>"
								disabled
								aria-label="<?php echo esc_attr( sprintf( 'Seleccionar %s para descuento', $post->post_title ) ); ?>"
							>
						</td>
						<td>
							<img src="<?php echo esc_url( $image_src ); ?>" alt="" loading="lazy" class="bsc-admin-products__image">
						</td>
						<td>
							<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $post->post_title ); ?></a></strong>
							<?php if ($sku) : ?>
								<br><code class="bsc-admin-products__sku"><?php echo esc_html( $sku ); ?></code>
							<?php endif; ?>
						</td>
						<td>
							<div class="bsc-admin-products__price-fields">
								<label class="bsc-admin-products__price-field">
									<span class="bsc-admin-products__price-label">Regular</span>
									<input
										type="number"
										min="0"
										step="1"
										inputmode="numeric"
										class="<?php echo esc_attr( $price_input_classes ); ?>"
										data-field="regular_price"
										data-original="<?php echo esc_attr( $price ); ?>"
										value="<?php echo esc_attr( $price ); ?>"
									>
								</label>
								<span
									class="bsc-admin-products__sale-price<?php echo esc_attr( $sale_price === '' ? ' is-hidden' : '' ); ?>"
									data-role="sale-price-note"
								><?php echo esc_html( $sale_price === '' ? '' : 'Oferta: ' . wp_strip_all_tags( wc_price( (float) $sale_price ) ) ); ?></span>
							</div>
						</td>
						<td>
							<input
								type="number"
								min="0"
								class="<?php echo esc_attr( $bodega_input_classes ); ?>"
								data-type="bodega"
								data-original="<?php echo esc_attr( $stock['bodega'] ); ?>"
								value="<?php echo esc_attr( $stock['bodega'] ); ?>"
							>
						</td>
						<td>
							<input
								type="number"
								min="0"
								class="<?php echo esc_attr( $tienda_input_classes ); ?>"
								data-type="tienda"
								data-original="<?php echo esc_attr( $stock['tienda'] ); ?>"
								value="<?php echo esc_attr( $stock['tienda'] ); ?>"
							>
						</td>
						<td>
							<?php if ($is_archived) : ?>
								<span class="bsc-admin-badge bsc-admin-badge--locked">Archivado</span>
							<?php elseif ($is_hidden) : ?>
								<span class="bsc-admin-badge bsc-admin-badge--warning">Oculto</span>
							<?php elseif ($post->post_status === 'publish') : ?>
								<span class="bsc-admin-badge bsc-admin-badge--success">Publicado</span>
							<?php else : ?>
								<span class="bsc-admin-badge bsc-admin-badge--muted">Borrador</span>
							<?php endif; ?>
						</td>
						<td>
							<div class="bsc-admin-actions bsc-admin-products__row-actions">
								<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">Editar</a>
								<a href="<?php echo esc_url( $view_url ); ?>" class="button button-small" target="_blank" rel="noopener noreferrer">Ver tienda</a>
								<button
									type="button"
									class="button button-small bsc-stock-history-btn"
									data-product-id="<?php echo esc_attr( $post->ID ); ?>"
									data-product-name="<?php echo esc_attr( $post->post_title ); ?>"
								>Historial</button>
								<button
									type="button"
									class="button button-small bsc-admin-products__delete bsc-product-delete-btn"
									data-product-id="<?php echo esc_attr( $post->ID ); ?>"
									data-product-name="<?php echo esc_attr( $post->post_title ); ?>"
									aria-label="<?php echo esc_attr( sprintf( 'Borrar %s', $post->post_title ) ); ?>"
								>Borrar</button>
							</div>
							<div class="bsc-admin-inline-editor bsc-admin-products__save-controls">
								<span class="bsc-admin-inline-editor__pending" data-role="pending">Guardar cambios</span>
								<button
									type="button"
									class="button button-primary button-small bsc-admin-inline-editor__save bsc-product-row-save"
									data-role="save"
									disabled
								>Guardar</button>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>

				<?php if (empty( $products )) : ?>
					<tr><td colspan="8" class="bsc-admin-products__empty">No hay productos.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		</div>

		<?php if ($pages > 1) : ?>
			<div class="bsc-admin-products__pagination">
				<?php if ($paged > 1) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'paged' => $paged - 1 ) ) ); ?>" class="button">Anterior</a>
				<?php endif; ?>
				<span class="bsc-admin-products__pagination-label">Pagina <?php echo esc_html( $paged ); ?> de <?php echo esc_html( $pages ); ?></span>
				<?php if ($paged < $pages) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'paged' => $paged + 1 ) ) ); ?>" class="button">Siguiente</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div id="bsc-stock-modal" class="bsc-admin-products__modal">
		<div id="bsc-stock-modal-overlay" class="bsc-admin-products__modal-overlay"></div>
		<div class="bsc-admin-products__modal-dialog">
			<div class="bsc-admin-products__modal-header">
				<h2 id="bsc-stock-modal-title" class="bsc-admin-products__modal-title">Historial de stock</h2>
				<button id="bsc-stock-modal-close" type="button" class="button bsc-admin-products__modal-close">&times;</button>
			</div>
			<div id="bsc-stock-modal-body"></div>
		</div>
	</div>

	<div id="bsc-admin-products-toast" class="bsc-admin-toast" aria-live="polite"></div>

	<?php wp_nonce_field( 'bsc_products_nonce', 'bsc_products_nonce_field' ); ?>
	<?php
}
