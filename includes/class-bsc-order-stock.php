<?php
/** Reserve and restore BSC stock together with each order-item movement marker. */
defined( 'ABSPATH' ) || exit;

final class BSC_Order_Stock {

	public static function init(): void {
		add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'checkout' ), 20 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'checkout' ), 20 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'status_changed' ), 5, 4 );
		add_action( 'woocommerce_refund_created', array( __CLASS__, 'refund' ), 20, 2 );
		add_action( 'bsc_expire_stock_reservation', array( __CLASS__, 'expire' ) );
		add_action( 'bsc_retry_stock_refund', array( __CLASS__, 'refund' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_exception', array( __CLASS__, 'checkout_exception' ) );
		add_filter( 'woocommerce_product_get_manage_stock', array( __CLASS__, 'native_manage_stock' ), 30, 2 );
		add_filter( 'woocommerce_product_variation_get_manage_stock', array( __CLASS__, 'native_manage_stock' ), 30, 2 );
	}

	/** BSC owns dual inventory; WooCommerce must not reserve/decrement it a second time. */
	public static function native_manage_stock( $manage, $product ) {
		$id = (int) $product->get_id();
		$parent = (int) $product->get_parent_id();
		return BSC_Stock::has_dual_stock( $id ) || ( $parent && BSC_Stock::has_dual_stock( $parent ) ) ? false : $manage;
	}

	public static function checkout( $order ): void {
		$result = self::allocate( (int) $order->get_id(), false );
		if ( is_wp_error( $result ) ) {
			throw new Exception( $result->get_error_message() );
		}
	}

	public static function checkout_exception( $order ): void {
		if ( $order instanceof WC_Order ) {
			self::release( $order->get_id(), 'checkout-error', false );
		}
	}

	/** Commit a showroom order, all stock movements and their refund markers together. */
	public static function showroom( array $items, string $payment, string $payment_title, array $customer, string $request_id ) {
		return BSC_DB_Lock::run( 'showroom-request:' . get_current_user_id() . ':' . $request_id, static function () use ( $items, $payment, $payment_title, $customer, $request_id ) {
			$existing = wc_get_orders( array( 'limit' => 1, 'meta_key' => '_bsc_showroom_request_id', 'meta_value' => get_current_user_id() . ':' . $request_id ) );
			if ( $existing ) {
				// Resume after a crash between the stock commit and status transition.
				if ( $existing[0]->has_status( 'pending' ) ) { $existing[0]->update_status( 'completed', 'Venta presencial recuperada sin repetir el descuento de inventario.' ); }
				return $existing[0];
			}
			if ( ! $items || count( $items ) > 50 ) { return new WP_Error( 'invalid_items', 'Selecciona entre 1 y 50 productos.' ); }
			$product_ids = wp_parse_id_list( wp_list_pluck( $items, 'id' ) );
			sort( $product_ids, SORT_NUMERIC );
			$held = array();
			try {
				foreach ( $product_ids as $id ) {
					if ( ! BSC_DB_Lock::acquire( 'stock-product:' . $id ) ) { return new WP_Error( 'stock_busy', 'El inventario está en uso. Intenta nuevamente.' ); }
					$held[] = $id; wp_cache_delete( $id, 'post_meta' );
				}
				$result = BSC_DB_Lock::transaction( static function () use ( $items, $payment, $payment_title, $customer, $request_id ) {
					$order = wc_create_order();
					if ( is_wp_error( $order ) ) { return $order; }
					$order->update_meta_data( '_bsc_is_showroom_sale', '1' );
					$order->update_meta_data( '_bsc_showroom_request_id', get_current_user_id() . ':' . $request_id );
					foreach ( $items as $line ) {
						$id = absint( $line['id'] ?? 0 ); $qty = absint( $line['qty'] ?? 0 );
						$product = wc_get_product( $id );
						if ( ! $product || ! $qty || $qty > 999 ) { return new WP_Error( 'invalid_product', 'Producto o cantidad inválidos.' ); }
						$key = sanitize_key( (string) ( $line['variant_key'] ?? '' ) );
						$matrix = bsc_get_product_saved_variant_matrix( $id, false );
						$price = (float) $product->get_price(); $label = '';
						if ( $matrix ) {
							$variant = null;
							foreach ( $matrix as $row ) { if ( $row['key'] === $key && ! empty( $row['enabled'] ) ) { $variant = $row; break; } }
							if ( ! $variant ) { return new WP_Error( 'variant_required', 'Selecciona una variante disponible.' ); }
							$price = (float) ( '' !== (string) ( $variant['sale_price'] ?? '' ) ? $variant['sale_price'] : $variant['regular_price'] );
							$label = trim( $variant['color_name'] . ' ' . $variant['size_name'] );
							$adjusted = BSC_Stock::adjust_variant_stock_strict( $id, $key, 'tienda', -$qty, 'Venta presencial #' . $order->get_id() );
						} else {
							$key = '';
							$adjusted = BSC_Stock::adjust_strict( $id, 'tienda', -$qty, 'Venta presencial #' . $order->get_id() );
						}
						if ( is_wp_error( $adjusted ) ) { return $adjusted; }
						$item_id = $order->add_product( $product, $qty, array( 'subtotal' => $price * $qty, 'total' => $price * $qty ) );
						$item = $order->get_item( $item_id );
						if ( ! $item ) { return new WP_Error( 'item_failed', 'No se pudo crear la línea de pedido.' ); }
						foreach ( array( '_bsc_stock_product_id' => $id, '_bsc_stock_tienda_qty' => $qty, '_bsc_stock_bodega_qty' => 0, '_bsc_stock_source' => 'tienda', '_bsc_stock_allocated_at' => current_time( 'mysql' ), '_bsc_stock_allocation_cycle' => wp_generate_uuid4(), '_bsc_product_variant_key' => $key ) as $meta_key => $value ) { $item->update_meta_data( $meta_key, $value ); }
						if ( $label ) { $item->update_meta_data( 'Variante', $label ); }
						$item->save();
					}
					$name = explode( ' ', $customer['name'], 2 );
					$order->set_billing_first_name( $name[0] ); $order->set_billing_last_name( $name[1] ?? '' );
					$order->set_billing_email( $customer['email'] ); $order->set_billing_phone( $customer['phone'] );
					$order->set_payment_method( $payment ); $order->set_payment_method_title( $payment_title );
					$order->calculate_totals(); $order->save();
					return $order;
				} );
				if ( $result instanceof WC_Order ) { $result->update_status( 'completed', 'Venta presencial registrada desde BSC Admin.' ); }
				return $result;
			} finally {
				foreach ( $held as $id ) { wp_cache_delete( $id, 'post_meta' ); BSC_DB_Lock::release( 'stock-product:' . $id ); }
			}
		} );
	}

	public static function paid( int $order_id ): void {
		$result = self::allocate( $order_id, true );
		if ( is_wp_error( $result ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->update_meta_data( '_bsc_stock_review_required', $result->get_error_message() );
				$order->save();
				$order->update_status( 'on-hold', 'Pago recibido: detener preparación y revisar inventario. ' . $result->get_error_message() );
			}
		}
	}

	public static function allocate( int $order_id, bool $paid ) {
		return BSC_DB_Lock::run( 'stock-order:' . $order_id, static function () use ( $order_id, $paid ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || $order->get_meta( '_bsc_is_showroom_sale', true ) ) {
				return true;
			}
			if ( ! $paid ) {
				if ( $order->get_date_paid() || $order->is_paid() ) {
					return true;
				}
				$expires = time() + max( 15, min( 120, (int) get_option( 'woocommerce_hold_stock_minutes', 60 ) ) ) * MINUTE_IN_SECONDS;
				$order->update_meta_data( '_bsc_stock_reservation_expires', $expires );
				$order->save();
				$scheduled = wp_schedule_single_event( $expires, 'bsc_expire_stock_reservation', array( $order_id ), true );
				if ( is_wp_error( $scheduled ) && 'duplicate_event' !== $scheduled->get_error_code() ) {
					return new WP_Error( 'bsc_reservation_schedule', 'No pudimos reservar el inventario. Intenta nuevamente.' );
				}
			}
			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof WC_Order_Item_Product ) {
					continue;
				}
				$product_id = self::product_id( $item );
				if ( ! BSC_Stock::has_dual_stock( $product_id ) ) {
					continue;
				}
				$result = BSC_Stock::with_product_lock( $product_id, static function () use ( $item, $product_id, $order_id, $order ) {
					$item->read_meta_data( true );
					if ( $item->get_meta( '_bsc_stock_allocated_at', true ) && ! $item->get_meta( '_bsc_stock_released', true ) ) {
						return true;
					}
					$quantity = max( 0, (int) $item->get_quantity() + (int) $order->get_qty_refunded_for_item( $item->get_id() ) );
					if ( 0 === $quantity ) {
						return true;
					}
					$variant = sanitize_key( (string) $item->get_meta( '_bsc_product_variant_key', true ) );
					$reason = 'Reserva/asignación pedido #' . $order_id . ', item #' . $item->get_id();
					$allocation = '' !== $variant
						? BSC_Stock::allocate_variant_and_deduct( $product_id, $variant, $quantity, $reason )
						: BSC_Stock::allocate_and_deduct( $product_id, $quantity, $reason );
					if ( is_wp_error( $allocation ) ) {
						return $allocation;
					}
					$item->update_meta_data( '_bsc_stock_product_id', $product_id );
					$item->update_meta_data( '_bsc_stock_source', BSC_Stock::source_from_allocation( $allocation ) );
					$item->update_meta_data( '_bsc_stock_bodega_qty', $allocation['bodega'] );
					$item->update_meta_data( '_bsc_stock_tienda_qty', $allocation['tienda'] );
					$item->update_meta_data( '_bsc_stock_allocated_at', current_time( 'mysql' ) );
					$item->update_meta_data( '_bsc_stock_allocation_cycle', wp_generate_uuid4() );
					$item->delete_meta_data( '_bsc_stock_released' );
					$item->delete_meta_data( '_bsc_stock_restored_bodega' );
					$item->delete_meta_data( '_bsc_stock_restored_tienda' );
					$item->delete_meta_data( '_bsc_stock_allocation_error' );
					$item->save();
					return true;
				} );
				if ( is_wp_error( $result ) ) {
					if ( ! $paid ) {
						self::release( $order_id, 'reservation-failed', false );
					}
					return $result;
				}
			}
			if ( $paid ) {
				$order->delete_meta_data( '_bsc_stock_review_required' );
				$order->delete_meta_data( '_bsc_stock_reservation_expires' );
				$order->save();
			}
			return true;
		} );
	}

	private static function product_id( WC_Order_Item_Product $item ): int {
		$saved = (int) $item->get_meta( '_bsc_stock_product_id', true );
		$variation = (int) $item->get_variation_id();
		return $saved ?: ( $variation && BSC_Stock::has_dual_stock( $variation ) ? $variation : (int) $item->get_product_id() );
	}

	/** Each restock and its marker commit together; never restore more than allocated. */
	private static function restore_item( WC_Order_Item_Product $item, int $quantity, string $event, bool $release ) {
		$product_id = self::product_id( $item );
		return BSC_Stock::with_product_lock( $product_id, static function () use ( $item, $quantity, $event, $release, $product_id ) {
			$item->read_meta_data( true );
			$events = (array) $item->get_meta( '_bsc_stock_restore_events', true );
			if ( ! $item->get_meta( '_bsc_stock_allocated_at', true ) || $item->get_meta( '_bsc_stock_released', true ) || in_array( $event, $events, true ) ) {
				return true;
			}
			$variant = sanitize_key( (string) $item->get_meta( '_bsc_product_variant_key', true ) );
			$remaining = max( 0, $quantity );
			foreach ( array( 'bodega', 'tienda' ) as $location ) {
				$already = (int) $item->get_meta( '_bsc_stock_restored_' . $location, true );
				$allocated = (int) $item->get_meta( '_bsc_stock_' . $location . '_qty', true );
				$restore = min( $remaining, max( 0, $allocated - $already ) );
				if ( $restore > 0 ) {
					$reason = 'Reposición item #' . $item->get_id() . ': ' . $event;
					$result = '' !== $variant
						? BSC_Stock::adjust_variant_stock_strict( $product_id, $variant, $location, $restore, $reason )
						: BSC_Stock::adjust_strict( $product_id, $location, $restore, $reason );
					if ( is_wp_error( $result ) ) {
						return $result;
					}
					$item->update_meta_data( '_bsc_stock_restored_' . $location, $already + $restore );
					$remaining -= $restore;
				}
			}
			$events[] = $event;
			$item->update_meta_data( '_bsc_stock_restore_events', $events );
			if ( $release ) {
				$item->update_meta_data( '_bsc_stock_released', current_time( 'mysql' ) );
			}
			$item->save();
			return true;
		} );
	}

	public static function release( int $order_id, string $reason, bool $cancelled ) {
		return BSC_DB_Lock::run( 'stock-order:' . $order_id, static function () use ( $order_id, $reason, $cancelled ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || ( ! $cancelled && ( $order->get_date_paid() || $order->is_paid() ) ) ) {
				return true;
			}
			if ( 'expired' === $reason && (int) $order->get_meta( '_bsc_stock_reservation_expires', true ) > time() ) {
				return true;
			}
			$cycle = (string) $order->get_meta( '_bsc_stock_reservation_expires', true );
			foreach ( $order->get_items() as $item ) {
				if ( $item instanceof WC_Order_Item_Product ) {
					$result = self::restore_item( $item, (int) $item->get_quantity(), $reason . ':' . $cycle . ':' . $item->get_meta( '_bsc_stock_allocation_cycle', true ), true );
					if ( is_wp_error( $result ) ) {
						$order->add_order_note( 'Reposición de inventario pendiente: ' . $result->get_error_message() );
						return $result;
					}
				}
			}
			$order->delete_meta_data( '_bsc_stock_reservation_expires' );
			$order->save();
			return true;
		} );
	}

	public static function expire( int $order_id ): void {
		$order = wc_get_order( $order_id );
		$expires = $order ? (int) $order->get_meta( '_bsc_stock_reservation_expires', true ) : 0;
		if ( $expires && $expires <= time() ) {
			$result = self::release( $order_id, 'expired', false );
			if ( is_wp_error( $result ) ) {
				wp_schedule_single_event( time() + 300, 'bsc_expire_stock_reservation', array( $order_id ) );
			}
		}
	}

	public static function status_changed( $order_id, $old, $new, $order ): void {
		if ( 'cancelled' === $new && ! in_array( $old, array( 'shipped', 'completed' ), true ) ) {
			self::release( (int) $order_id, 'cancelled', true );
		} elseif ( 'failed' === $new ) {
			self::release( (int) $order_id, 'failed', false );
		}
	}

	public static function refund( $refund_id, $args ): void {
		if ( empty( $args['restock_items'] ) ) {
			return;
		}
		$order_id = (int) ( $args['order_id'] ?? 0 );
		$result = BSC_DB_Lock::run( 'stock-order:' . $order_id, static function () use ( $order_id, $refund_id, $args ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return;
			}
			foreach ( (array) ( $args['line_items'] ?? array() ) as $item_id => $line ) {
				$item = $order->get_item( $item_id );
				if ( $item instanceof WC_Order_Item_Product ) {
					$result = self::restore_item( $item, absint( $line['qty'] ?? 0 ), 'refund:' . (int) $refund_id, false );
					if ( is_wp_error( $result ) ) {
						$order->add_order_note( 'Reembolso registrado; inventario pendiente de revisión: ' . $result->get_error_message() );
						return $result;
					}
				}
			}
		} );
		if ( is_wp_error( $result ) ) {
			wp_schedule_single_event( time() + 300, 'bsc_retry_stock_refund', array( $refund_id, $args ) );
		}
	}
}
BSC_Order_Stock::init();
