<?php
/**
 * BSC-037: Showroom sale page - create WC orders from physical store sales,
 * deduct _stock_tienda, mark with _bsc_is_showroom_sale meta.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_showroom_admin_assets' );
function bsc_enqueue_showroom_admin_assets(): void {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-showroom' !== $page ) {
		return;
	}

	bsc_enqueue_admin_ui_assets();
	wp_enqueue_script( 'jquery-ui-autocomplete' );

	$css_path = get_template_directory() . '/admin/bsc-showroom.css';
	$js_path  = get_template_directory() . '/js/admin/bsc-showroom.js';

	wp_enqueue_style(
		'bsc-showroom-admin',
		get_template_directory_uri() . '/admin/bsc-showroom.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);

	wp_enqueue_script(
		'bsc-showroom-admin',
		get_template_directory_uri() . '/js/admin/bsc-showroom.js',
		array( 'jquery', 'jquery-ui-autocomplete' ),
		file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
		true
	);

	wp_localize_script(
		'bsc-showroom-admin',
		'bscShowroomAdmin',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'bsc_admin_orders' ),
			'strings' => array(
				'emptyResults'      => 'Sin resultados.',
				'selectProduct'     => 'Selecciona un producto primero.',
				'addProductFirst'   => 'Agrega al menos un producto.',
				'registering'       => 'Registrando...',
				'registerSale'      => 'Registrar venta',
				'connectionError'   => 'Error de conexión.',
				'unknownError'      => 'desconocido',
				'anonymousCustomer' => 'Anónimo',
				'skuLabel'          => 'SKU: ',
				'storeStockLabel'   => 'Stock tienda: ',
			),
		)
	);
}

function bsc_render_showroom_page(): void {
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'edit_orders' )) {
		wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
	}

	$recent_orders = wc_get_orders(
		array(
			'meta_key'   => '_bsc_is_showroom_sale',
			'meta_value' => '1',
			'limit'      => 10,
			'orderby'    => 'date',
			'order'      => 'DESC',
		)
	);
	?>
	<div class="wrap bsc-showroom">
		<div class="bsc-admin-page-header bsc-admin-page-header--compact">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Showroom</span>
				<h1>Venta Presencial</h1>
				<p class="bsc-admin-page-header__description">Registra ventas presenciales, descuenta stock de tienda y consulta los pedidos recientes.</p>
			</div>
			<div class="bsc-admin-page-header__actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-orders' ) ); ?>" class="button">Pedidos</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-products' ) ); ?>" class="button button-primary">Stock</a>
			</div>
		</div>

		<div class="bsc-showroom__layout">
			<div>
				<div class="bsc-card bsc-showroom__card bsc-admin-panel">
					<h3 class="bsc-showroom__section-title">Productos</h3>
					<div class="bsc-showroom__search-bar">
						<input type="text" id="bsc-product-search" placeholder="Buscar por nombre o SKU..." class="bsc-showroom__text-input bsc-showroom__product-search">
						<input type="number" id="bsc-product-qty" value="1" min="1" class="bsc-showroom__qty-input">
						<button type="button" id="bsc-add-product" class="button button-primary">Agregar</button>
					</div>
					<div id="bsc-search-results" class="bsc-showroom__search-results bsc-showroom__search-results--hidden"></div>

					<table id="bsc-cart-table" class="bsc-showroom__cart-table bsc-showroom__cart-table--hidden">
						<thead>
							<tr class="bsc-showroom__cart-head-row">
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--left">Producto</th>
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--center bsc-showroom__cart-head-cell--qty">Qty</th>
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--right bsc-showroom__cart-head-cell--price">Precio</th>
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--right bsc-showroom__cart-head-cell--subtotal">Subtotal</th>
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--actions"></th>
							</tr>
						</thead>
						<tbody id="bsc-cart-body"></tbody>
						<tfoot>
							<tr>
								<td colspan="3" class="bsc-showroom__cart-total-label">Total:</td>
								<td id="bsc-cart-total" class="bsc-showroom__cart-total-value">$0</td>
								<td></td>
							</tr>
						</tfoot>
					</table>
					<p id="bsc-cart-empty" class="bsc-showroom__cart-empty">Agrega productos usando el buscador arriba.</p>

					<hr class="bsc-showroom__divider">

					<h3 class="bsc-showroom__section-title">Datos del cliente</h3>
					<div class="bsc-showroom__customer-grid">
						<div class="bsc-showroom__customer-field">
							<label class="bsc-showroom__field-label">Nombre</label>
							<input type="text" id="bsc-customer-name" placeholder="Cliente anónimo" class="bsc-showroom__text-input bsc-showroom__customer-input">
						</div>
						<div class="bsc-showroom__customer-field">
							<label class="bsc-showroom__field-label">Teléfono</label>
							<input type="text" id="bsc-customer-phone" placeholder="Opcional" class="bsc-showroom__text-input bsc-showroom__customer-input">
						</div>
						<div class="bsc-showroom__customer-field bsc-showroom__customer-field--full">
							<label class="bsc-showroom__field-label">Email</label>
							<input type="email" id="bsc-customer-email" placeholder="Opcional" class="bsc-showroom__text-input bsc-showroom__customer-input">
						</div>
					</div>

					<h3 class="bsc-showroom__section-title">Método de pago</h3>
					<div class="bsc-showroom__payment-options">
						<?php
						$methods = array(
							'efectivo'      => 'Efectivo',
							'transferencia' => 'Transferencia',
							'tarjeta'       => 'Tarjeta',
						);
						foreach ($methods as $val => $label) :
							?>
						<label class="bsc-showroom__payment-option">
							<input type="radio" name="bsc-payment" value="<?php echo esc_attr( $val ); ?>"
									<?php checked( $val, 'efectivo' ); ?>>
							<?php echo esc_html( $label ); ?>
						</label>
						<?php endforeach; ?>
					</div>

					<div id="bsc-showroom-notice" class="bsc-showroom__notice bsc-showroom__notice--hidden"></div>

					<button type="button" id="bsc-register-sale" class="button button-primary bsc-showroom__submit">
						Registrar venta
					</button>
				</div>
			</div>

			<div>
				<div class="bsc-showroom__recent-sales bsc-admin-panel">
					<h3 class="bsc-showroom__recent-title">Últimas ventas presenciales</h3>
					<?php if ($recent_orders) : ?>
					<table class="bsc-showroom__recent-table">
						<thead>
							<tr class="bsc-showroom__recent-head-row">
								<th class="bsc-showroom__recent-head-cell">#</th>
								<th class="bsc-showroom__recent-head-cell">Cliente</th>
								<th class="bsc-showroom__recent-head-cell bsc-showroom__recent-head-cell--right">Total</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($recent_orders as $order) :
								$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ?: 'Anónimo';
								?>
							<tr>
								<td class="bsc-showroom__recent-cell">
									<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>" target="_blank">
										#<?php echo esc_html( $order->get_order_number() ); ?>
									</a>
								</td>
								<td class="bsc-showroom__recent-cell"><?php echo esc_html( $name ); ?></td>
								<td class="bsc-showroom__recent-cell bsc-showroom__recent-cell--right">
									<?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php else : ?>
					<p class="bsc-showroom__empty-state">Sin ventas presenciales registradas.</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<?php
}

add_action( 'wp_ajax_bsc_showroom_search', 'bsc_ajax_showroom_search' );
function bsc_ajax_showroom_search(): void {
	check_ajax_referer( 'bsc_admin_orders', 'nonce' );
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'edit_orders' )) {
		wp_send_json_error();
	}

	$q = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
	if (strlen( $q ) < 2) {
		wp_send_json_success( array() );
	}

	$products = wc_get_products(
		array(
			's'      => $q,
			'limit'  => 12,
			'status' => 'publish',
			'return' => 'objects',
		)
	);

	$results = array();
	foreach ($products as $product) {
		$results[] = array(
			'id'           => $product->get_id(),
			'name'         => $product->get_name(),
			'sku'          => $product->get_sku(),
			'price'        => (float) $product->get_price(),
			'stock_tienda' => (int) get_post_meta( $product->get_id(), '_stock_tienda', true ),
		);
	}

	wp_send_json_success( $results );
}

add_action( 'wp_ajax_bsc_register_showroom_sale', 'bsc_ajax_register_showroom_sale' );
function bsc_ajax_register_showroom_sale(): void {
	check_ajax_referer( 'bsc_admin_orders', 'nonce' );
	if (!current_user_can( 'manage_options' ) && !current_user_can( 'manage_woocommerce' ) && !current_user_can( 'edit_orders' )) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ) );
	}

	if ( ! class_exists( 'BSC_Stock' ) ) {
		wp_send_json_error( array( 'message' => 'Modulo de stock no disponible' ) );
	}

	$items_raw       = isset( $_POST['items'] ) ? sanitize_textarea_field( wp_unslash( $_POST['items'] ) ) : '[]';
	$items           = json_decode( (string) $items_raw, true );
	$payment_methods = array(
		'efectivo'      => 'Efectivo',
		'transferencia' => 'Transferencia',
		'tarjeta'       => 'Tarjeta',
	);
	$payment         = sanitize_key( wp_unslash( $_POST['payment_method'] ?? 'efectivo' ) );

	if (!isset( $payment_methods[ $payment ] )) {
		wp_send_json_error( array( 'message' => 'Método de pago inválido' ) );
	}
	$customer_name  = sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) );
	$customer_phone = sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) );
	$customer_email = sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) );

	if (empty( $items ) || !is_array( $items )) {
		wp_send_json_error( array( 'message' => 'Sin productos' ) );
	}

	$validated_items = array();
	foreach ($items as $item) {
		$product_id = absint( $item['id'] ?? 0 );
		$qty        = max( 1, absint( $item['qty'] ?? 1 ) );
		$product    = wc_get_product( $product_id );

		if (!$product) {
			wp_send_json_error( array( 'message' => 'Producto invalido' ) );
		}

		if (!isset( $validated_items[ $product_id ] )) {
			$validated_items[ $product_id ] = array(
				'product' => $product,
				'qty'     => 0,
			);
		}
		$validated_items[ $product_id ]['qty'] += $qty;
	}

	foreach ($validated_items as $product_id => $item) {
		$stock = BSC_Stock::get_stock( (int) $product_id );
		if ( (int) $stock['tienda'] < (int) $item['qty']) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						'Stock tienda insuficiente para %1$s. Disponible: %2$d.',
						$item['product']->get_name(),
						(int) $stock['tienda']
					),
				)
			);
		}
	}

	$deducted_items = array();
	foreach ($validated_items as $product_id => $item) {
		$result = BSC_Stock::adjust_strict( (int) $product_id, 'tienda', - (int) $item['qty'], 'Venta presencial pendiente' );
		if (is_wp_error( $result )) {
			foreach ($deducted_items as $deducted) {
				BSC_Stock::adjust( (int) $deducted['product_id'], 'tienda', (int) $deducted['qty'], 'Rollback venta presencial' );
			}
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$deducted_items[] = array(
			'product_id' => (int) $product_id,
			'qty'        => (int) $item['qty'],
		);
	}

	$order = wc_create_order( array( 'customer_id' => 0 ) );
	if (is_wp_error( $order ) || !$order) {
		foreach ($deducted_items as $deducted) {
			BSC_Stock::adjust( (int) $deducted['product_id'], 'tienda', (int) $deducted['qty'], 'Rollback venta presencial' );
		}
		wp_send_json_error( array( 'message' => 'No se pudo crear el pedido' ) );
	}

	foreach ($validated_items as $item) {
		$order->add_product( $item['product'], (int) $item['qty'] );
	}

	if ($customer_name) {
		$parts = explode( ' ', $customer_name, 2 );
		$order->set_billing_first_name( $parts[0] );
		$order->set_billing_last_name( $parts[1] ?? '' );
	}
	if ($customer_phone) {
		$order->set_billing_phone( $customer_phone );
	}
	if ($customer_email) {
		$order->set_billing_email( $customer_email );
	}

	$order->set_payment_method( $payment );
	$order->set_payment_method_title( $payment_methods[ $payment ] );
	$order->update_meta_data( '_bsc_is_showroom_sale', '1' );
	$order->save();

	$order->calculate_totals();
	$order->update_status( 'completed', 'Venta presencial registrada desde BSC Admin.' );
	$order->save();

	$order_id = $order->get_id();

	$order->add_order_note( 'Stock de tienda descontado para venta presencial #' . $order_id . '.' );

	wp_send_json_success(
		array(
			'order_id' => $order_id,
			'edit_url' => admin_url( 'post.php?post=' . $order_id . '&action=edit' ),
		)
	);
}
