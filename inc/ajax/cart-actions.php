<?php
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/checkout-review-summary-helpers.php';

/**
 * Return the stock limit that applies to a cart line.
 *
 * BSC products manage inventory in the warehouse/store meta fields, which are
 * separate from WooCommerce's native stock value. Returning null for products
 * without a managed quantity tells the client not to impose a false limit.
 *
 * @param array $cart_item WooCommerce cart item data.
 * @return int|null
 */
function bsc_cart_item_stock_total( array $cart_item ): ?int {
	$product_id  = (int) ( $cart_item['product_id'] ?? 0 );
	$variant_key = sanitize_key( (string) ( $cart_item['bsc_product_variant_key'] ?? '' ) );

	if ( $product_id <= 0 ) {
		return null;
	}

	if ( '' !== $variant_key && function_exists( 'bsc_find_product_variant_matrix_row' ) ) {
		$variant = bsc_find_product_variant_matrix_row( $product_id, $variant_key, '', '', '', false );
		if ( is_array( $variant ) ) {
			return bsc_product_variant_stock_total( $variant );
		}
	}

	if ( class_exists( 'BSC_Stock' ) && BSC_Stock::has_dual_stock( $product_id ) ) {
		return BSC_Stock::get_total_stock( $product_id );
	}

	$product = $cart_item['data'] ?? null;
	if ( ! $product instanceof WC_Product || ! $product->managing_stock() ) {
		return null;
	}

	$stock_quantity = $product->get_stock_quantity();
	return is_numeric( $stock_quantity ) ? max( 0, (int) $stock_quantity ) : null;
}

/**
 * Identify cart lines that draw from the same stock pool.
 *
 * @param array $cart_item WooCommerce cart item data.
 * @return string
 */
function bsc_cart_item_stock_key( array $cart_item ): string {
	$product_id   = (int) ( $cart_item['product_id'] ?? 0 );
	$variation_id = (int) ( $cart_item['variation_id'] ?? 0 );
	$variant_key  = sanitize_key( (string) ( $cart_item['bsc_product_variant_key'] ?? '' ) );

	if ( '' !== $variant_key ) {
		return 'bsc-variant:' . $product_id . ':' . $variant_key;
	}

	$product = $cart_item['data'] ?? null;
	if ( $product instanceof WC_Product ) {
		$stock_managed_by_id = (int) $product->get_stock_managed_by_id();
		if ( $stock_managed_by_id > 0 ) {
			return 'product:' . $stock_managed_by_id;
		}
	}

	return 'product:' . ( 0 !== $variation_id ? $variation_id : $product_id );
}

/**
 * Return the total quantity that would consume the same stock pool.
 *
 * @param array  $cart_item              Cart item or prospective cart item.
 * @param int    $line_quantity          Quantity requested for this line.
 * @param string $excluded_cart_item_key Existing line to replace rather than add.
 */
function bsc_cart_item_requested_stock_quantity( array $cart_item, int $line_quantity, string $excluded_cart_item_key = '' ): int {
	$requested_quantity = max( 0, $line_quantity );

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $requested_quantity;
	}

	$stock_key = bsc_cart_item_stock_key( $cart_item );
	foreach ( WC()->cart->get_cart() as $candidate_key => $candidate ) {
		if ( $candidate_key === $excluded_cart_item_key || bsc_cart_item_stock_key( $candidate ) !== $stock_key ) {
			continue;
		}

		$requested_quantity += max( 0, (int) ( $candidate['quantity'] ?? 0 ) );
	}

	return $requested_quantity;
}

/**
 * Determine whether a requested cart-line quantity fits the shared stock pool.
 *
 * @param array  $cart_item              Cart item or prospective cart item.
 * @param int    $line_quantity          Quantity requested for this line.
 * @param string $excluded_cart_item_key Existing line to replace rather than add.
 * @return bool
 */
function bsc_cart_item_has_enough_stock( array $cart_item, int $line_quantity, string $excluded_cart_item_key = '' ): bool {
	$stock_total = bsc_cart_item_stock_total( $cart_item );

	return null === $stock_total
		|| bsc_cart_item_requested_stock_quantity( $cart_item, $line_quantity, $excluded_cart_item_key ) <= $stock_total;
}


// ── Refresh public AJAX nonce ──────────────────────────────────────────────
add_action( 'wp_ajax_bsc_refresh_ajax_nonce', 'bsc_refresh_ajax_nonce' );
add_action( 'wp_ajax_nopriv_bsc_refresh_ajax_nonce', 'bsc_refresh_ajax_nonce' );

/**
 * Return a fresh nonce from the uncached admin-ajax endpoint.
 *
 * Public storefront HTML can remain open in a browser after its embedded nonce
 * expires. The client uses this endpoint only after WordPress answers 403/-1,
 * then retries the original cart request once.
 */
function bsc_refresh_ajax_nonce(): void {
	nocache_headers();

	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'ajax_nonce_refresh', 30, MINUTE_IN_SECONDS );
	}

	wp_send_json_success(
		array(
			'nonce' => wp_create_nonce( 'bsc_ajax_action' ),
		)
	);
}


// ── Add to cart ────────────────────────────────────────────────────────────
add_action( 'wp_ajax_bsc_add_to_cart', 'bsc_ajax_add_to_cart_handler' );
add_action( 'wp_ajax_nopriv_bsc_add_to_cart', 'bsc_ajax_add_to_cart_handler' );

function bsc_ajax_add_to_cart_handler() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );
	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'cart_add', 30, MINUTE_IN_SECONDS );
	}

	$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( wp_unslash( $_POST['product_id'] ?? 0 ) ) );
	$quantity   = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) );

	if ($product_id < 1 || $quantity < 1) {
		wp_send_json_error( array( 'error' => 'Producto o cantidad inválida.' ), 400 );
	}

	$cart_item_data = function_exists( 'bsc_build_product_variant_cart_item_data' )
		? bsc_build_product_variant_cart_item_data( (int) $product_id, $_POST )
		: array();

	if ( is_wp_error( $cart_item_data ) ) {
		wp_send_json_error( array( 'error' => $cart_item_data->get_error_message() ), 400 );
	}

	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product ) {
		wp_send_json_error( array( 'error' => 'Producto no disponible.' ), 404 );
	}

	$prospective_cart_item = array_merge(
		array(
			'product_id'   => $product_id,
			'variation_id' => 0,
			'data'         => $product,
		),
		$cart_item_data
	);
	$stock_total           = bsc_cart_item_stock_total( $prospective_cart_item );

	if ( ! bsc_cart_item_has_enough_stock( $prospective_cart_item, $quantity ) ) {
		wp_send_json_error(
			array(
				'error'       => 'No hay stock suficiente para agregar esa cantidad.',
				'message'     => 'No hay stock suficiente para agregar esa cantidad.',
				'stock_total' => $stock_total,
			),
			409
		);
	}

	$added = WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $cart_item_data );

	if ($added) {
		$cart_item = WC()->cart->get_cart_item( $added );

		ob_start();
		woocommerce_mini_cart();
		$mini_cart = ob_get_clean();

		wp_send_json(
			array(
				'fragments'     => apply_filters(
					'woocommerce_add_to_cart_fragments',
					array(
						'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
					)
				),
				'cart_hash'     => WC()->cart->get_cart_hash(),
				'cart_count'    => (int) WC()->cart->get_cart_contents_count(),
				'bsc_cart_item' => array(
					'key'         => $added,
					'quantity'    => max( 1, (int) ( $cart_item['quantity'] ?? $quantity ) ),
					'product_id'  => (int) ( $cart_item['product_id'] ?? $product_id ),
					'variant_key' => sanitize_key( (string) ( $cart_item['bsc_product_variant_key'] ?? '' ) ),
					'stock_total' => bsc_cart_item_stock_total( $cart_item ),
				),
			)
		);
	} else {
		wp_send_json_error( array( 'error' => 'No se pudo agregar el producto al carrito.' ), 409 );
	}
}


// ── Update quantity ────────────────────────────────────────────────────────
add_action( 'wp_ajax_update_cart_quantity', 'bsc_update_cart_quantity' );
add_action( 'wp_ajax_nopriv_update_cart_quantity', 'bsc_update_cart_quantity' );

function bsc_update_cart_quantity() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );
	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'cart_quantity', 45, MINUTE_IN_SECONDS );
	}

	if (!isset( $_POST['product_id'], $_POST['quantity'] )) {
		wp_send_json_error( array( 'message' => 'Missing required fields' ), 400 );
	}

	$product_id              = intval( wp_unslash( $_POST['product_id'] ) );
	$requested_cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
	$delta                   = intval( wp_unslash( $_POST['quantity'] ) ); // This is the change (+1 or -1)

	if ($delta === 0) {
		wp_send_json_error( array( 'message' => 'Invalid quantity delta' ), 400 );
	}

	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		$is_target_key     = $requested_cart_item_key !== '' && $cart_item_key === $requested_cart_item_key;
		$is_target_product = $requested_cart_item_key === '' && $product_id > 0 && (int) $cart_item['product_id'] === $product_id;

		if ($is_target_key || $is_target_product) {
			$current_qty = $cart_item['quantity'];
			$new_qty     = $current_qty + $delta;

			if ($new_qty < 1) {
				WC()->cart->remove_cart_item( $cart_item_key );
				bsc_recalculate_checkout_totals( true );
				wc_clear_notices();
				// BSC-004: incluir cart_count para que el JS actualice el badge sin depender del fragmento
				wp_send_json_success(
					array(
						'message'       => 'Product removed from cart',
						'cart_count'    => WC()->cart->get_cart_contents_count(),
						'removed'       => true,
						'cart_item_key' => $cart_item_key,
						'product_id'    => (int) $cart_item['product_id'],
					)
				);
			}

			$stock_total = bsc_cart_item_stock_total( $cart_item );
			if ( $delta > 0 && null !== $stock_total ) {
				if ( ! bsc_cart_item_has_enough_stock( $cart_item, $new_qty, $cart_item_key ) ) {
					wp_send_json_error(
						array(
							'message'     => 'No hay stock suficiente para agregar esa cantidad.',
							'stock_total' => $stock_total,
						),
						409
					);
				}
			}

			$updated = WC()->cart->set_quantity( $cart_item_key, $new_qty, true );
			if (!$updated) {
				wp_send_json_error( array( 'message' => 'No se pudo actualizar la cantidad' ), 409 );
			}

			bsc_recalculate_checkout_totals( true );
			wc_clear_notices();
			wp_send_json_success(
				array(
					'message'       => 'Quantity updated',
					'new_qty'       => $new_qty,
					'cart_count'    => WC()->cart->get_cart_contents_count(),
					'item_total'    => wc_price( $new_qty * $cart_item['data']->get_price() ),
					'cart_item_key' => $cart_item_key,
					'product_id'    => (int) $cart_item['product_id'],
					'variant_key'   => sanitize_key( (string) ( $cart_item['bsc_product_variant_key'] ?? '' ) ),
					'stock_total'   => $stock_total,
				)
			);
		}
	}

	wp_send_json_error( array( 'message' => 'Product not found in cart' ), 404 );
}


add_action( 'wp_ajax_bsc_get_cart_quantities', 'bsc_get_cart_quantities' );
add_action( 'wp_ajax_nopriv_bsc_get_cart_quantities', 'bsc_get_cart_quantities' );

function bsc_get_cart_quantities() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );
	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'cart_quantities', 60, MINUTE_IN_SECONDS );
	}

	if ( ! WC()->cart ) {
		wp_send_json_error();
	}

	$items = array();

	foreach ( WC()->cart->get_cart() as $key => $item ) {
		$product_id  = (int) ( $item['product_id'] ?? 0 );
		$variant_key = sanitize_key( (string) ( $item['bsc_product_variant_key'] ?? '' ) );
		$stock_total = bsc_cart_item_stock_total( $item );

		$items[] = array(
			'key'         => $key,
			'quantity'    => (int) $item['quantity'],
			'product_id'  => $product_id,
			'variant_key' => $variant_key,
			'stock_total' => $stock_total,
		);
	}

	wp_send_json_success( $items );
}



add_action( 'wp_ajax_bsc_remove_cart_item', 'bsc_remove_cart_item' );
add_action( 'wp_ajax_nopriv_bsc_remove_cart_item', 'bsc_remove_cart_item' );

function bsc_remove_cart_item() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );
	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'cart_remove', 30, MINUTE_IN_SECONDS );
	}

	if ( ! isset( $_POST['cart_item_key'] ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'Datos incompletos o carrito no disponible.' ) );
		wp_die();
	}

	$cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) );
	$removed       = WC()->cart->remove_cart_item( $cart_item_key );

	if ($removed) {
		// Recalcular totales
		bsc_recalculate_checkout_totals( true );

		if ( WC()->cart->is_empty() ) {
			wc_clear_notices();
			wp_send_json_success(
				array(
					'cart_count'    => 0,
					'cart_item_key' => $cart_item_key,
					'fragments'     => array(),
					'removed'       => true,
				)
			);
		}

		// Obtener fragmentos actualizados
		WC_AJAX::get_refreshed_fragments();
	} else {
		wp_send_json_error( array( 'message' => 'No se pudo eliminar el producto.' ) );
	}
}
