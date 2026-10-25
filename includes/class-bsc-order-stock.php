<?php
/** Reserve and restore BSC stock together with each order-item movement marker. */
defined( 'ABSPATH' ) || exit;

final class BSC_Order_Stock {
	/** @var array<int, bool> Prevent recursive saves while repairing an order. */
	private static array $syncing_showroom = array();

	public static function init(): void {
		add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'checkout' ), 20 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'checkout' ), 20 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'status_changed' ), 5, 4 );
		add_action( 'woocommerce_refund_created', array( __CLASS__, 'refund' ), 20, 2 );
		add_action( 'bsc_expire_stock_reservation', array( __CLASS__, 'expire' ) );
		add_action( 'bsc_retry_stock_refund', array( __CLASS__, 'refund' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_exception', array( __CLASS__, 'checkout_exception' ) );
		add_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'sync_showroom_customer' ), 40 );
		add_action( 'bsc_sync_showroom_customer', array( __CLASS__, 'sync_showroom_customer' ) );
		add_action( 'bsc_sync_showroom_customer_orders', array( __CLASS__, 'sync_showroom_customer_orders' ), 10, 2 );
		add_action( 'wp', array( __CLASS__, 'sync_showroom_account' ), 15 );
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
	public static function showroom( array $items, string $payment, string $payment_title, array $customer, string $request_id, string $coupon_code = '' ) {
		return BSC_DB_Lock::run( 'showroom-request:' . get_current_user_id() . ':' . $request_id, static function () use ( $items, $payment, $payment_title, $customer, $request_id, $coupon_code ) {
			$existing = wc_get_orders( array( 'limit' => 1, 'meta_key' => '_bsc_showroom_request_id', 'meta_value' => get_current_user_id() . ':' . $request_id ) );
			if ( $existing ) {
				// Resume after a crash between the stock commit and status transition.
				return self::finish_showroom( $existing[0] );
			}
			if ( ! $items || count( $items ) > 50 ) { return new WP_Error( 'invalid_items', 'Selecciona entre 1 y 50 productos.' ); }
			$product_ids = wp_parse_id_list( wp_list_pluck( $items, 'id' ) );
			sort( $product_ids, SORT_NUMERIC );
			$held = array();
			$coupon_locked = false;
			try {
				foreach ( $product_ids as $id ) {
					if ( ! BSC_DB_Lock::acquire( 'stock-product:' . $id ) ) { return new WP_Error( 'stock_busy', 'El inventario está en uso. Intenta nuevamente.' ); }
					$held[] = $id; wp_cache_delete( $id, 'post_meta' );
				}
				if ( '' !== $coupon_code ) {
					$coupon_locked = BSC_DB_Lock::acquire( 'showroom-coupon:' . $coupon_code );
					if ( ! $coupon_locked ) { return new WP_Error( 'coupon_busy', 'El cupón está en uso. Intenta nuevamente.' ); }
				}
				$result = BSC_DB_Lock::transaction( static function () use ( $items, $payment, $payment_title, $customer, $request_id, $coupon_code ) {
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
							$allocated = min( $qty, max( 0, (int) $variant['stock_tienda'] ) );
							$adjusted = $allocated ? BSC_Stock::adjust_variant_stock_strict( $id, $key, 'tienda', -$allocated, 'Venta presencial #' . $order->get_id() ) : true;
						} else {
							$key = '';
							$allocated = min( $qty, max( 0, (int) get_post_meta( $id, '_stock_tienda', true ) ) );
							$adjusted = BSC_Stock::adjust_strict( $id, 'tienda', -$allocated, 'Venta presencial #' . $order->get_id() );
						}
						if ( is_wp_error( $adjusted ) ) { return $adjusted; }
						$item_id = $order->add_product( $product, $qty, array( 'subtotal' => $price * $qty, 'total' => $price * $qty ) );
						$item = $order->get_item( $item_id );
						if ( ! $item ) { return new WP_Error( 'item_failed', 'No se pudo crear la línea de pedido.' ); }
						foreach ( array( '_bsc_stock_product_id' => $id, '_bsc_stock_tienda_qty' => $allocated, '_bsc_stock_bodega_qty' => 0, '_bsc_stock_source' => $allocated ? 'tienda' : 'encargo', '_bsc_stock_allocated_at' => current_time( 'mysql' ), '_bsc_stock_allocation_cycle' => wp_generate_uuid4(), '_bsc_product_variant_key' => $key ) as $meta_key => $value ) { $item->update_meta_data( $meta_key, $value ); }
						if ( $allocated < $qty ) {
							$item->update_meta_data( 'Encargo', $qty - $allocated );
							$order->update_meta_data( '_bsc_showroom_has_backorder', 'yes' );
						}
						if ( $label ) { $item->update_meta_data( 'Variante', $label ); }
						$item->save();
					}
					$name = explode( ' ', $customer['name'], 2 );
					$order->set_billing_first_name( $name[0] ); $order->set_billing_last_name( $name[1] ?? '' );
					$order->set_billing_email( $customer['email'] ); $order->set_billing_phone( $customer['phone'] );
					$order->set_customer_id( $customer['email'] ? (int) email_exists( $customer['email'] ) : 0 );
					$order->set_payment_method( $payment ); $order->set_payment_method_title( $payment_title );
					if ( '' !== $coupon_code ) {
						$order->calculate_totals();
						$valid = self::showroom_discounts( $order, $coupon_code );
						if ( is_wp_error( $valid ) ) { return $valid; }
						$applied = $order->apply_coupon( $coupon_code );
						if ( is_wp_error( $applied ) ) { return $applied; }
					}
					$order->calculate_totals(); $order->save();
					return $order;
				} );
				if ( $result instanceof WC_Order ) { return self::finish_showroom( $result ); }
				return $result;
			} finally {
				if ( $coupon_locked ) { BSC_DB_Lock::release( 'showroom-coupon:' . $coupon_code ); }
				foreach ( $held as $id ) { wp_cache_delete( $id, 'post_meta' ); BSC_DB_Lock::release( 'stock-product:' . $id ); }
			}
		} );
	}

	/**
	 * Attach the customer before the paid status awards points. Safe on retries.
	 *
	 * @param WC_Order $order Saved Showroom order.
	 * @return WC_Order|WP_Error
	 */
	private static function finish_showroom( WC_Order $order ) {
		$email = $order->get_billing_email();
		if ( $email && ! $order->get_customer_id() ) {
			$user_id = BSC_DB_Lock::run(
				'showroom-customer:' . strtolower( $email ),
				static function () use ( $email, $order ) {
					$existing = email_exists( $email );
					if ( $existing ) {
						return (int) $existing;
					}
					WC()->mailer();
					return wc_create_new_customer(
						$email,
						'',
						'',
						array(
							'first_name' => $order->get_billing_first_name(),
							'last_name'  => $order->get_billing_last_name(),
							'meta_input' => array(
								'_bsc_checkout_account' => 'yes',
								'_bsc_showroom_account' => 'yes',
								'billing_email'         => $email,
								'billing_phone'         => $order->get_billing_phone(),
								'billing_first_name'    => $order->get_billing_first_name(),
								'billing_last_name'     => $order->get_billing_last_name(),
							),
						)
					);
				}
			);
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}
			$order->set_customer_id( $user_id );
			$order->save();
		}
		if ( $order->has_status( 'pending' ) ) {
			$order->set_date_paid( time() );
			$order->update_status( 'yes' === $order->get_meta( '_bsc_showroom_has_backorder' ) ? 'processing' : 'completed', 'Venta presencial registrada desde BSC Admin.' );
		}
		if ( $order->get_customer_id() && function_exists( 'bsc_bp_order_is_awardable' ) && bsc_bp_order_is_awardable( $order ) && bsc_bp_calculate_order_points( $order ) > 0 ) {
			$result = BSC_DB_Lock::run( 'points-order:' . $order->get_id(), static function () use ( $order ) {
				return bsc_bp_with_user_lock( $order->get_customer_id(), static function () use ( $order ) {
					return BSC_DB_Lock::transaction( static function () use ( $order ) {
						$award = bsc_bp_award_order_points( $order, 'showroom_customer' );
						if ( empty( $award['ok'] ) ) {
							return new WP_Error( 'showroom_points_failed', 'La venta está guardada, pero no se pudieron acreditar sus puntos. Reintenta para completar el vínculo.' );
						}
						$net = bsc_bp_reconcile_order_points( $order->get_id(), false );
						if ( is_wp_error( $net ) || ( is_array( $net ) && empty( $net['ok'] ) ) ) {
							return new WP_Error( 'showroom_points_failed', 'No se pudo conciliar el saldo de puntos de esta venta.' );
						}
						return true;
					} );
				} );
			} );
			if ( is_wp_error( $result ) || ( is_array( $result ) && empty( $result['ok'] ) ) ) {
				// Award metadata written inside a rolled-back transaction must not mask a retry.
				$store = $order->get_data_store();
				if ( $store->has_callable( 'clear_cached_data' ) ) { $store->clear_cached_data( array( $order->get_id() ) ); }
				wp_cache_delete( $order->get_meta_cache_key(), 'orders' );
				wp_cache_delete( $order->get_id(), 'post_meta' );
				$order->read_meta_data( true );
				if ( class_exists( \Automattic\WooCommerce\Caches\OrderCache::class ) ) {
					wc_get_container()->get( \Automattic\WooCommerce\Caches\OrderCache::class )->remove( $order->get_id() );
				}
				return is_wp_error( $result ) ? $result : new WP_Error( 'showroom_points_busy', 'La acreditación de puntos está en curso. Reintenta en un momento.' );
			}
		}
		return $order;
	}

	/** Repair paid Showroom orders saved without their customer or points. */
	public static function sync_showroom_customer( $order_or_id ) {
		if ( class_exists( 'BSC_Sale_Backup' ) && BSC_Sale_Backup::$restoring ) { return null; }
		$order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order( absint( $order_or_id ) );
		if ( ! $order instanceof WC_Order || ! $order->get_meta( '_bsc_is_showroom_sale' ) || ! is_email( $order->get_billing_email() ) ) {
			return null;
		}
		$paid_statuses = function_exists( 'bsc_bp_awardable_order_statuses' ) ? bsc_bp_awardable_order_statuses() : wc_get_is_paid_statuses();
		if ( ! $order->has_status( $paid_statuses ) ) { return null; }
		$id = $order->get_id();
		if ( isset( self::$syncing_showroom[ $id ] ) ) { return null; }
		self::$syncing_showroom[ $id ] = true;
		try {
			$result = BSC_DB_Lock::run( 'showroom-customer-order:' . $id, static function () use ( $order ) {
				return self::finish_showroom( $order );
			} );
			if ( is_wp_error( $result ) && ! wp_next_scheduled( 'bsc_sync_showroom_customer', array( $id ) ) ) {
				wp_schedule_single_event( time() + 300, 'bsc_sync_showroom_customer', array( $id ) );
			}
			return $result;
		} finally {
			unset( self::$syncing_showroom[ $id ] );
		}
	}

	/** Run before WooCommerce queries the customer's order history. */
	public static function sync_showroom_account(): void {
		if ( is_user_logged_in() && function_exists( 'is_account_page' ) && is_account_page() ) {
			self::sync_showroom_customer_orders( get_current_user_id() );
		}
	}

	/** Backfill only staff-created Showroom sales; web guest orders keep email verification. */
	public static function sync_showroom_customer_orders( int $user_id, int $page = 1 ): void {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) { return; }
		$paid_statuses = function_exists( 'bsc_bp_awardable_order_statuses' ) ? bsc_bp_awardable_order_statuses() : wc_get_is_paid_statuses();
		$args = array( 'limit' => 50, 'orderby' => 'ID', 'order' => 'DESC', 'meta_key' => '_bsc_is_showroom_sale', 'meta_value' => '1', 'status' => $paid_statuses );
		$unlinked = wc_get_orders( array_merge( $args, array( 'customer_id' => 0, 'billing_email' => $user->user_email ) ) );
		$linked = wc_get_orders( array_merge( $args, array( 'customer_id' => $user_id, 'page' => max( 1, $page ) ) ) );
		foreach ( array_merge( $unlinked, $linked ) as $order ) {
			if ( ! $order->get_customer_id() || ! $order->get_meta( '_bsc_bp_points_awarded' ) ) {
				self::sync_showroom_customer( $order );
			}
		}
		if ( count( $unlinked ) === 50 || count( $linked ) === 50 ) {
			$next = array( $user_id, max( 1, $page ) + 1 );
			if ( ! wp_next_scheduled( 'bsc_sync_showroom_customer_orders', $next ) ) {
				wp_schedule_single_event( time() + 30, 'bsc_sync_showroom_customer_orders', $next );
			}
		}
	}

	/**
	 * Validate against the buyer, including email restrictions and guest usage.
	 *
	 * @param WC_Order $order Buyer and order lines.
	 * @param string   $code Coupon code.
	 * @return WC_Discounts|WP_Error
	 */
	private static function showroom_discounts( WC_Order $order, string $code ) {
		$coupon       = new WC_Coupon( $code );
		$restrictions = $coupon->get_email_restrictions();
		if ( $restrictions && ! \Automattic\WooCommerce\Utilities\DiscountsUtil::is_coupon_emails_allowed( array( strtolower( $order->get_billing_email() ) ), $restrictions ) ) {
			return new WP_Error( 'coupon_email', 'Este cupón no es válido para el correo del cliente.' );
		}
		if ( $coupon->get_usage_limit_per_user() && $coupon->get_data_store()->get_usage_by_email( $coupon, $order->get_billing_email() ) >= $coupon->get_usage_limit_per_user() ) {
			return new WP_Error( 'coupon_usage', 'El cliente ya alcanzó el límite de uso de este cupón.' );
		}
		$discounts = new WC_Discounts( $order );
		$result    = $discounts->apply_coupon( $coupon );
		return is_wp_error( $result ) ? $result : $discounts;
	}

	/**
	 * Preview a coupon without saving orders, stock movements or coupon usages.
	 *
	 * @param array  $items Product and variant quantities.
	 * @param string $email Customer email.
	 * @param string $code Coupon code.
	 * @return array|WP_Error
	 */
	public static function showroom_quote( array $items, string $email, string $code ) {
		if ( ! $items || count( $items ) > 50 || '' === $code ) {
			return new WP_Error( 'invalid_quote', 'Agrega productos e ingresa un cupón.' );
		}
		// WooCommerce totals/tax calculations call save(); previews must stay in memory.
		$order = new class() extends WC_Order {
			/** {@inheritDoc} */
			public function save() {
				return 0;
			}
			/**
			 * Apply WooCommerce's line discounts in memory.
			 *
			 * @param WC_Discounts $discounts Validated discounts.
			 */
			public function preview_discounts( WC_Discounts $discounts ): void {
				$this->set_item_discount_amounts( $discounts );
			}
		};
		$order->set_currency( get_woocommerce_currency() );
		$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
		$order->set_billing_email( $email );
		$order->set_customer_id( $email ? (int) email_exists( $email ) : 0 );
		foreach ( $items as $line ) {
			$product = wc_get_product( absint( $line['id'] ?? 0 ) );
			$qty     = absint( $line['qty'] ?? 0 );
			if ( ! $product || ! $qty || $qty > 999 ) {
				return new WP_Error( 'invalid_product', 'Producto o cantidad inválidos.' );
			}
			$price  = (float) $product->get_price();
			$matrix = bsc_get_product_saved_variant_matrix( $product->get_id(), false );
			if ( $matrix ) {
				$variant = null;
				foreach ( $matrix as $row ) {
					if ( $row['key'] === sanitize_key( (string) ( $line['variant_key'] ?? '' ) ) && ! empty( $row['enabled'] ) ) {
						$variant = $row;
						break;
					}
				}
				if ( ! $variant ) {
					return new WP_Error( 'variant_required', 'Selecciona una variante disponible.' );
				}
				$price = (float) ( '' !== (string) ( $variant['sale_price'] ?? '' ) ? $variant['sale_price'] : $variant['regular_price'] );
			}
			$item = new WC_Order_Item_Product();
			// WC_Discounts indexes order lines by ID, even for an unsaved preview.
			$item->set_id( count( $order->get_items() ) + 1 );
			$item->set_product( $product );
			$item->set_quantity( $qty );
			$item->set_subtotal( $price * $qty );
			$item->set_total( $price * $qty );
			$order->add_item( $item );
		}
		$order->calculate_totals();
		$discounts = self::showroom_discounts( $order, $code );
		if ( is_wp_error( $discounts ) ) {
			return $discounts;
		}
		$order->preview_discounts( $discounts );
		$order->calculate_totals();
		return array(
			'code'     => $code,
			'discount' => (float) $order->get_discount_total() + (float) $order->get_discount_tax(),
			'total'    => (float) $order->get_total(),
		);
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
