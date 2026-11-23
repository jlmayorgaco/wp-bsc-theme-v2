<?php
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/orders.php';

/** Each workshop has one WooCommerce product. The slot is a cart-line attribute. */
function bsc_workshops_product( int $id ) {
	return BSC_DB_Lock::run( 'workshop:' . $id, static function () use ( $id ) {
		$post = bsc_workshops_get( $id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return new WP_Error( 'closed' );
		}

		$settings = bsc_workshops_settings( $id );
		$sku      = 'bsc-taller-' . $id;
		$existing = wc_get_product_id_by_sku( $sku );
		$product  = $existing ? wc_get_product( $existing ) : new WC_Product_Simple();
		if ( ! $product || ( $existing && $id !== (int) $product->get_meta( '_bsc_workshop_id' ) ) ) {
			return new WP_Error( 'failed' );
		}

		try {
			$product->set_name( $post->post_title );
			$product->set_sku( $sku );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'hidden' );
			$product->set_virtual( true );
			$product->set_manage_stock( false );
			$product->set_sold_individually( false );
			$product->set_stock_status( 'instock' );
			$product->set_regular_price( (string) $settings['price'] );
			$product->set_sale_price( '' );
			$product->set_image_id( $settings['image'] );
			$product->update_meta_data( '_bsc_workshop_id', $id );
			return $product->save() ?: new WP_Error( 'failed' );
		} catch ( Exception $error ) {
			return new WP_Error( 'failed' );
		}
	} );
}

function bsc_workshops_add_to_cart( int $id, string $slot, int $seats ): string {
	if ( ! function_exists( 'wc_load_cart' ) ) {
		return 'failed';
	}
	$settings = bsc_workshops_settings( $id );
	if ( empty( $settings['slots'][ $slot ] ) || $settings['price'] <= 0 ) {
		return 'closed';
	}
	wc_load_cart();
	$product = bsc_workshops_product( $id );
	if ( is_wp_error( $product ) ) {
		return 'closed' === $product->get_error_code() ? 'closed' : 'failed';
	}
	$item = array( 'product_id' => $product, 'bsc_workshop_slot' => $slot );
	if ( ! bsc_cart_item_has_enough_stock( $item, $seats ) ) {
		return 'full';
	}
	if ( ! WC()->cart->add_to_cart( $product, $seats, 0, array(), array( 'bsc_workshop_slot' => $slot ) ) ) {
		return 'failed';
	}
	return 'cart';
}

function bsc_workshops_cart_id( array $item ): int {
	return (int) get_post_meta( (int) ( $item['product_id'] ?? 0 ), '_bsc_workshop_id', true );
}

function bsc_workshops_cart_has_tickets(): bool {
	foreach ( WC()->cart ? WC()->cart->get_cart() : array() as $item ) {
		if ( bsc_workshops_cart_id( $item ) ) {
			return true;
		}
	}
	return false;
}

function bsc_workshops_cart_available( array $item ): ?int {
	$id = bsc_workshops_cart_id( $item );
	if ( ! $id ) {
		return null;
	}
	$post     = bsc_workshops_get( $id );
	$settings = bsc_workshops_settings( $id );
	$key      = $item['bsc_workshop_slot'] ?? '';
	$slot     = $settings['slots'][ $key ] ?? null;
	if ( ! $slot || ! $post || 'publish' !== $post->post_status || $settings['price'] <= 0 ) {
		return 0;
	}
	return bsc_workshops_available( $slot, bsc_workshops_counts( $id )[ $key ] ?? 0 );
}

add_filter( 'woocommerce_add_cart_item_data', static function ( array $data, int $product_id ): array {
	if ( bsc_workshops_cart_id( array( 'product_id' => $product_id ) ) && empty( $data['bsc_workshop_slot'] ) ) {
		throw new Exception( 'Elige el horario de tu taller en la página de Talleres.' );
	}
	return $data;
}, 10, 2 );

add_action( 'woocommerce_check_cart_items', static function (): void {
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( bsc_workshops_cart_id( $item ) && empty( $item['bsc_workshop_slot'] ) ) {
			wc_add_notice( 'Quita el taller del carrito y vuelve a elegir su horario en la página de Talleres.', 'error' );
			break;
		}
	}
} );

add_filter( 'woocommerce_update_cart_validation', static function ( bool $valid, string $key, array $item, int $quantity ): bool {
	if ( $valid && bsc_workshops_cart_id( $item ) && ! bsc_cart_item_has_enough_stock( $item, $quantity, $key ) ) {
		wc_add_notice( 'Ya no quedan suficientes cupos en ese horario del taller.', 'error' );
		return false;
	}
	return $valid;
}, 10, 4 );

add_action( 'woocommerce_before_calculate_totals', static function ( WC_Cart $cart ): void {
	foreach ( $cart->get_cart() as $item ) {
		$id = bsc_workshops_cart_id( $item );
		if ( $id ) {
			$price = bsc_workshops_settings( $id )['price'];
			$item['data']->set_price( (string) bsc_workshops_unit_price( $price, $item['quantity'] ) );
		}
	}
}, 30 );

add_action( 'woocommerce_cart_loaded_from_session', static function ( WC_Cart $cart ): void {
	if ( bsc_workshops_cart_has_tickets() ) {
		$cart->calculate_totals();
	}
} );

add_filter( 'woocommerce_get_item_data', static function ( array $data, array $item ): array {
	$id = bsc_workshops_cart_id( $item );
	if ( $id ) {
		$slot = bsc_workshops_settings( $id )['slots'][ $item['bsc_workshop_slot'] ?? '' ] ?? null;
		$data[] = array( 'key' => 'Horario', 'value' => $slot['label'] ?? 'No disponible' );
		if ( $item['quantity'] >= 2 ) {
			$data[] = array( 'key' => 'Descuento de grupo', 'value' => '10% por persona' );
		}
	}
	return $data;
}, 20, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', static function ( WC_Order_Item_Product $line, string $key, array $item ): void {
	$id = bsc_workshops_cart_id( $item );
	if ( $id ) {
		$slot = $item['bsc_workshop_slot'] ?? '';
		$line->add_meta_data( '_bsc_workshop_id', $id, true );
		$line->add_meta_data( '_bsc_workshop_slot', $slot, true );
		$line->add_meta_data( 'Horario', bsc_workshops_settings( $id )['slots'][ $slot ]['label'] ?? '', true );
		if ( $line->get_quantity() >= 2 ) {
			$line->add_meta_data( 'Descuento de grupo', '10% por persona', true );
		}
	}
}, 10, 3 );

// A store coupon can still apply to the skincare in a mixed cart.
add_filter( 'woocommerce_coupon_get_items_to_apply', static function ( array $items ): array {
	return array_filter( $items, static function ( object $item ): bool {
		return ! $item->product || ! $item->product->get_meta( '_bsc_workshop_id' );
	} );
} );

add_filter( 'woocommerce_product_get_image', static function ( string $image, WC_Product $product, $size, $attributes, $placeholder ): string {
	if ( ! $placeholder || $product->get_image_id() || ! $product->get_meta( '_bsc_workshop_id' ) ) {
		return $image;
	}
	return sprintf(
		'<img src="%s" width="1104" height="974" alt="" loading="lazy">',
		esc_url( get_template_directory_uri() . '/images/talleres/mesa.webp' )
	);
}, 10, 5 );

add_filter( 'woocommerce_available_payment_gateways', static function ( array $gateways ): array {
	$order = is_wc_endpoint_url( 'order-pay' ) ? wc_get_order( absint( get_query_var( 'order-pay' ) ) ) : false;
	if ( $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( $item->get_meta( '_bsc_workshop_id' ) ) {
				unset( $gateways['cod'] );
				break;
			}
		}
	} elseif ( bsc_workshops_cart_has_tickets() ) {
		unset( $gateways['cod'] );
	}
	return $gateways;
} );

add_action( 'template_redirect', static function (): void {
	if ( is_singular( 'product' ) && get_post_meta( get_queried_object_id(), '_bsc_workshop_id', true ) ) {
		wp_safe_redirect( bsc_workshops_public_url() . '#talleres-agenda' );
		exit;
	}
} );
