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

					<div class="bsc-showroom__cart-scroll">
					<table id="bsc-cart-table" class="bsc-showroom__cart-table bsc-showroom__cart-table--hidden">
						<thead>
							<tr class="bsc-showroom__cart-head-row">
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--left">Producto</th>
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--center">Stock</th>
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--center bsc-showroom__cart-head-cell--qty">Qty</th>
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--right bsc-showroom__cart-head-cell--price">Precio</th>
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--right bsc-showroom__cart-head-cell--subtotal">Subtotal</th>
								<th class="bsc-showroom__cart-head-cell bsc-showroom__cart-head-cell--actions"></th>
							</tr>
						</thead>
						<tbody id="bsc-cart-body"></tbody>
						<tfoot>
							<tr>
								<td colspan="4" class="bsc-showroom__cart-total-label">Total:</td>
								<td id="bsc-cart-total" class="bsc-showroom__cart-total-value">$0</td>
								<td></td>
							</tr>
						</tfoot>
					</table>
					</div>
					<p id="bsc-cart-empty" class="bsc-showroom__cart-empty">Agrega productos usando el buscador arriba.</p>
					<label for="bsc-coupon-code" class="bsc-showroom__field-label">Cupón de descuento</label>
					<div class="bsc-showroom__search-bar">
						<input type="text" id="bsc-coupon-code" class="bsc-showroom__text-input" placeholder="Código del cupón">
						<button type="button" id="bsc-apply-coupon" class="button">Aplicar</button>
						<button type="button" id="bsc-remove-coupon" class="button" hidden>Quitar</button>
					</div>
					<p id="bsc-coupon-notice" role="status" aria-live="polite"></p>

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
							<p>Con este correo, la compra y sus puntos se asociarán al perfil del cliente. Si no tiene cuenta, se creará una y recibirá un enlace para establecer su contraseña.</p>
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

/** Find published Showroom products by name or SKU, including out-of-stock items. */
function bsc_showroom_find_products( string $query ): array {
	$args = array( 'limit' => 12, 'status' => 'publish', 'return' => 'objects' );
	$matches = array_merge(
		wc_get_products( array_merge( $args, array( 'sku' => $query ) ) ),
		wc_get_products( array_merge( $args, array( 's' => $query ) ) )
	);
	$products = array();
	foreach ( $matches as $product ) {
		$products[ $product->get_id() ] = $product;
	}
	return array_slice( array_values( $products ), 0, 12 );
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

	$products = bsc_showroom_find_products( $q );

	$results = array();
	foreach ($products as $product) {
		$matrix = bsc_get_product_saved_variant_matrix( $product->get_id(), false );
		if ( $matrix ) {
			foreach ( $matrix as $variant ) {
				if ( empty( $variant['enabled'] ) ) { continue; }
				$results[] = array( 'id' => $product->get_id(), 'variant_key' => $variant['key'], 'name' => $product->get_name() . ' — ' . trim( $variant['color_name'] . ' ' . $variant['size_name'] ), 'sku' => $product->get_sku(), 'price' => (float) ( '' !== (string) ( $variant['sale_price'] ?? '' ) ? $variant['sale_price'] : $variant['regular_price'] ), 'stock_tienda' => (int) $variant['stock_tienda'] );
			}
			continue;
		}
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
	$customer_email = trim( sanitize_text_field( wp_unslash( $_POST['customer_email'] ?? '' ) ) );
	if ( '' !== $customer_email && ! is_email( $customer_email ) ) {
		wp_send_json_error( array( 'message' => 'Ingresa un correo válido para asociar la compra al cliente.' ), 400 );
	}
	$customer_email = sanitize_email( $customer_email );
	$coupon_code = wc_format_coupon_code( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) ) );

	if (empty( $items ) || !is_array( $items )) {
		wp_send_json_error( array( 'message' => 'Sin productos' ) );
	}

	$request_id = isset( $_POST['request_id'] ) && is_string( $_POST['request_id'] ) ? sanitize_key( wp_unslash( $_POST['request_id'] ) ) : '';
	if ( ! preg_match( '/^[a-f0-9-]{36}$/D', $request_id ) ) { wp_send_json_error( array( 'message' => 'Recarga la página antes de registrar la venta.' ), 400 ); }
	$order = BSC_Order_Stock::showroom( $items, $payment, $payment_methods[ $payment ], array( 'name' => $customer_name, 'phone' => $customer_phone, 'email' => $customer_email ), $request_id, $coupon_code );
	if ( is_wp_error( $order ) ) { wp_send_json_error( array( 'message' => $order->get_error_message() ), 409 ); }
	$order_id = $order->get_id();

	wp_send_json_success(
		array(
			'order_id' => $order_id,
			'edit_url' => $order->get_edit_order_url(),
		)
	);
}

add_action( 'wp_ajax_bsc_showroom_coupon', 'bsc_ajax_showroom_coupon' );
/** Validate and preview a coupon without saving a sale. */
function bsc_ajax_showroom_coupon(): void {
	check_ajax_referer( 'bsc_admin_orders', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_orders' ) ) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ), 403 );
	}
	$items = json_decode( sanitize_textarea_field( wp_unslash( $_POST['items'] ?? '[]' ) ), true );
	$email = trim( sanitize_text_field( wp_unslash( $_POST['customer_email'] ?? '' ) ) );
	if ( ! is_array( $items ) || ( '' !== $email && ! is_email( $email ) ) ) {
		wp_send_json_error( array( 'message' => 'Revisa los productos y el correo del cliente.' ), 400 );
	}
	$code = wc_format_coupon_code( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) ) );
	$result = BSC_Order_Stock::showroom_quote( $items, sanitize_email( $email ), $code );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => wp_strip_all_tags( $result->get_error_message() ) ), 400 );
	}
	wp_send_json_success( $result );
}
