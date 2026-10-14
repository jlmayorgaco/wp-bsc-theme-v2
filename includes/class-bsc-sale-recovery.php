<?php
/**
 * Restore historical sales through WooCommerce CRUD, without replaying a checkout.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The ledger has no CRUD API for relinking surviving events; table names are internal and values are prepared.

/** Transactional, administrator-only historical sale recovery. */
final class BSC_Sale_Recovery {

	/**
	 * Find a sale by stable identity before creating an order.
	 *
	 * @param array $data data.
	 * @return int
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function existing( array $data ): int {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Administrator-only single-sale identity lookup.
		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'return'     => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Administrator-only stable sale identity lookup.
				'meta_query' => array(
					array(
						'key'   => '_bsc_sale_uuid',
						'value' => $data['order_uuid'],
					),
				),
			)
		);
		if ( $orders ) {
			return (int) $orders[0]; }
		$key = (string) ( $data['order']['order_key'] ?? '' );
		if ( $key ) {
			$id = wc_get_order_id_by_order_key( $key );
			if ( $id ) {
				return (int) $id; }
		}
		$transaction = (string) ( $data['order']['transaction_id'] ?? '' );
		if ( $transaction ) {
			$ids = wc_get_orders(
				array(
					'limit'          => 1,
					'return'         => 'ids',
					'transaction_id' => $transaction,
					'payment_method' => $data['order']['payment_method'] ?? '',
				)
			);
			if ( $ids ) {
				return (int) $ids[0]; }
		}
		return 0;
	}

	/**
	 * Match catalog identity without trusting a reused numeric ID.
	 *
	 * @param array $item item.
	 * @return mixed
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function product( array $item ) {
		if ( 'line_item' !== $item['type'] || empty( $item['product'] ) ) {
			return false; }
		$identity = $item['product'];
		if ( ! empty( $identity['sku'] ) ) {
			$id = wc_get_product_id_by_sku( $identity['sku'] );
			if ( $id ) {
				return wc_get_product( $id ); }
		}
		$id      = (int) ( ! empty( $item['data']['variation_id'] ) ? $item['data']['variation_id'] : $item['data']['product_id'] );
		$product = wc_get_product( $id );
		// Numeric IDs can be reused after a rollback. Require a matching identity.
		return $product && $product->get_slug() === $identity['slug'] && $product->get_name() === $identity['name'] && $product->get_sku() === $identity['sku'] ? $product : false;
	}

	/**
	 * Describe the proposed account, points and catalog changes.
	 *
	 * @param array $data data.
	 * @return array
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function preview( array $data ): array {
		$user    = get_user_by( 'email', $data['customer']['email'] );
		$missing = array();
		foreach ( $data['items'] as $item ) {
			if ( 'line_item' === $item['type'] && ! self::product( $item ) ) {
				$missing[] = $item['data']['name']; }
		}
		return array(
			'existing'         => self::existing( $data ),
			'user_id'          => $user ? $user->ID : 0,
			'missing_products' => $missing,
			'points'           => array_sum( array_column( $data['points'], 'delta' ) ),
		);
	}

	/** Ensure a failed restore can roll back every authoritative write. */
	private static function transactional_tables() {
		global $wpdb;
		$names = array( $wpdb->posts, $wpdb->postmeta, $wpdb->users, $wpdb->usermeta, $wpdb->comments, $wpdb->commentmeta, $wpdb->prefix . 'woocommerce_order_items', $wpdb->prefix . 'woocommerce_order_itemmeta', bsc_bp_table() );
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			foreach ( array( 'wc_orders', 'wc_order_addresses', 'wc_order_operational_data', 'wc_orders_meta' ) as $suffix ) {
				$names[] = $wpdb->prefix . $suffix; }
		}
		foreach ( $names as $name ) {
			$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $name ) );
			if ( 'InnoDB' !== $engine ) {
				return new WP_Error( 'storage_engine', 'La recuperación requiere tablas InnoDB para poder revertir una importación fallida.' ); }
		}
		return true;
	}

	/**
	 * Temporarily suppress checkout side effects while restoring historical data.
	 *
	 * @param callable $callback callback.
	 * @return mixed
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	private static function isolated( callable $callback ) {
		global $wp_filter;
		$hooks = array();
		foreach ( array_keys( $wp_filter ) as $name ) {
			if ( preg_match( '/^woocommerce_(order_status_|payment_complete|new_order$|update_order$|new_order_item$|update_order_item$|order_refunded|refund_created|after_order_object_save|before_order_object_save|order_object_updated_props|created_customer|new_customer|order_note_added)/', $name ) ) {
				$hooks[ $name ] = $wp_filter[ $name ];
				unset( $wp_filter[ $name ] );
			}
		}
		$mail = static function () {
			return true;
		};
		$http = static function () {
			return new WP_Error( 'bsc_recovery_no_external_effects', 'Recuperación histórica en curso.' );
		};
		add_filter( 'pre_wp_mail', $mail, PHP_INT_MAX );
		add_filter( 'pre_http_request', $http, PHP_INT_MAX );
		BSC_Sale_Backup::$restoring = true;
		try {
			return $callback(); } finally {
			BSC_Sale_Backup::$restoring = false;
			remove_filter( 'pre_wp_mail', $mail, PHP_INT_MAX );
			remove_filter( 'pre_http_request', $http, PHP_INT_MAX );
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact hook objects saved above, even when import throws.
			foreach ( $hooks as $name => $hook ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the original hook objects in finally, including error paths.
				$wp_filter[ $name ] = $hook; }
			}
	}

	/**
	 * Propagate a failed domain write to the transaction boundary.
	 *
	 * @param mixed $result result.
	 * @return void
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	private static function check( $result ): void {
		if ( is_wp_error( $result ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Converted to WP_Error; the admin template escapes the message at output.
			throw new RuntimeException( $result->get_error_message() ); }
		if ( false === $result ) {
			throw new RuntimeException( 'No se pudo guardar la venta completa. No se aplicaron cambios.' ); }
	}

	/**
	 * Atomically add a missing historical sale to its customer account.
	 *
	 * @param array $data data.
	 * @param bool  $restore_stock restore stock.
	 * @return mixed
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function restore( array $data, bool $restore_stock = false ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'permission', 'Solo un administrador puede recuperar ventas.' ); }
		$valid = BSC_Sale_Backup::validate( $data );
		if ( is_wp_error( $valid ) ) {
			return $valid; }
		$engine = self::transactional_tables();
		if ( is_wp_error( $engine ) ) {
			return $engine; }
		return BSC_DB_Lock::run(
			'sale-recovery',
			static function () use ( $data, $restore_stock ) {
				$existing = self::existing( $data );
				if ( $existing ) {
					return array(
						'id'             => $existing,
						'already_exists' => true,
					); }
				$resources = array( 'recovery-customer:' . strtolower( $data['customer']['email'] ) );
				$user      = get_user_by( 'email', $data['customer']['email'] );
				if ( $user ) {
					$resources[] = 'points-user:' . $user->ID; }
				$product_ids = array();
				foreach ( $data['items'] as $item ) {
					$product = self::product( $item );
					if ( $product ) {
						$pid           = $product->is_type( 'variation' ) && ! BSC_Stock::has_dual_stock( $product->get_id() ) ? $product->get_parent_id() : $product->get_id();
						$product_ids[] = $pid;
					}
				}
				sort( $product_ids, SORT_NUMERIC );
				foreach ( array_unique( $product_ids ) as $id ) {
					$resources[] = 'stock-product:' . $id; }
				$held    = array();
				$new_id  = 0;
				$user_id = $user ? $user->ID : 0;
				try {
					foreach ( $resources as $resource ) {
						if ( ! BSC_DB_Lock::acquire( $resource ) ) {
							throw new RuntimeException( 'Hay otra operación en curso. Intenta nuevamente.' ); }
						$held[] = $resource;
					}
					return self::isolated(
						static function () use ( $data, $restore_stock, &$new_id, &$user_id ) {
							return BSC_DB_Lock::transaction(
								static function () use ( $data, $restore_stock, &$new_id, &$user_id ) {
									if ( $restore_stock && self::preview( $data )['missing_products'] ) {
										throw new RuntimeException( 'Faltan productos. Recupera solo el historial y revisa el inventario por separado.' ); }
									$user = get_user_by( 'email', $data['customer']['email'] );
									if ( ! $user ) {
										$user_id = wp_insert_user(
											array(
												'user_login' => 'recuperado_' . str_replace( '-', '', wp_generate_uuid4() ),
												'user_pass' => wp_generate_password( 40, true, true ),
												'user_email' => $data['customer']['email'],
												'first_name' => sanitize_text_field( $data['customer']['first_name'] ),
												'last_name' => sanitize_text_field( $data['customer']['last_name'] ),
												'role' => 'customer',
											)
										);
										self::check( $user_id );
									} else {
										$user_id = $user->ID; }
									$order = new WC_Order();
									$props = $data['order'];
									unset( $props['id'] );
									$props['customer_id'] = $user_id;
									$props['created_via'] = 'bsc-recovery';
									self::check( $order->set_props( $props ) );
									foreach ( $data['meta'] as $key => $value ) {
										$order->update_meta_data( $key, $value ); }
									$order->delete_meta_data( '_bsc_bp_points_ledger_id' );
									$order->delete_meta_data( '_bsc_stock_reservation_expires' );
									$order->update_meta_data( '_bsc_sale_uuid', $data['order_uuid'] );
									$order->update_meta_data(
										'_bsc_recovery_source',
										array(
											'site_id'      => $data['site_id'],
											'order_id'     => $data['order']['id'],
											'order_number' => $data['order_number'],
											'captured_at'  => $data['captured_at'],
											'imported_at'  => gmdate( 'c' ),
											'admin_id'     => get_current_user_id(),
										)
									);
									$new_id = $order->save();
									if ( ! $new_id ) {
										throw new RuntimeException( 'No se pudo crear el pedido.' ); }
									$map = self::restore_items( $order, $data['items'], $restore_stock );
									foreach ( $data['refunds'] as $entry ) {
										$refund = new WC_Order_Refund();
										self::check(
											$refund->set_props(
												array(
													'parent_id' => $new_id,
													'amount'   => $entry['amount'],
													'reason'   => $entry['reason'],
													'date_created' => $entry['date_created'],
													'currency' => $order->get_currency(),
													'refunded_payment' => false,
												)
											)
										);
										$refund->save();
										self::restore_items( $refund, $entry['items'], false, $map );
										$refund->set_total( -abs( (float) $entry['amount'] ) );
										$refund->save();
									}
									self::restore_points( $order, $data );
									foreach ( $data['notes'] ?? array() as $note ) {
										$order->add_order_note( 'Nota original (' . gmdate( 'Y-m-d H:i', (int) $note['date'] ) . ' UTC): ' . wp_strip_all_tags( $note['content'] ), false, false );
									}
									$order->get_data_store()->set_stock_reduced( $new_id, true );
									$order->get_data_store()->set_recorded_coupon_usage_counts( $order, true );
									$order->add_order_note( 'Venta recuperada del respaldo #' . $data['order_number'] . '. ' . ( $restore_stock ? 'Inventario descontado según el archivo.' : 'Inventario conservado: revisar si la copia de la base de datos ya incluía estos movimientos.' ) );
									$order->save();
									return array(
										'id'             => $new_id,
										'already_exists' => false,
									);
								}
							);
						}
					);
				} catch ( Throwable $error ) {
					return new WP_Error( 'restore_failed', $error->getMessage() );
				} finally {
					if ( is_int( $user_id ) && $user_id ) {
						clean_user_cache( $user_id );
						wp_cache_delete( $user_id, 'user_meta' ); }
					foreach ( $product_ids as $id ) {
						clean_post_cache( $id );
						wc_delete_product_transients( $id ); }
					if ( $new_id ) {
						clean_post_cache( $new_id );
						wc_delete_shop_order_transients( $new_id );
						if ( class_exists( '\Automattic\WooCommerce\Caches\OrderCache' ) ) {
							wc_get_container()->get( \Automattic\WooCommerce\Caches\OrderCache::class )->remove( $new_id ); }
					}
					foreach ( array_reverse( $held ) as $resource ) {
						BSC_DB_Lock::release( $resource ); }
				}
			}
		);
	}

	/**
	 * Recreate typed order lines and map original item IDs.
	 *
	 * @param WC_Abstract_Order $order order.
	 * @param array             $entries entries.
	 * @param bool              $stock stock.
	 * @param array             $original_items original items.
	 * @return array
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	private static function restore_items( WC_Abstract_Order $order, array $entries, bool $stock, array $original_items = array() ): array {
		$classes = array(
			'line_item' => 'WC_Order_Item_Product',
			'shipping'  => 'WC_Order_Item_Shipping',
			'fee'       => 'WC_Order_Item_Fee',
			'tax'       => 'WC_Order_Item_Tax',
			'coupon'    => 'WC_Order_Item_Coupon',
		);
		$map     = array();
		foreach ( $entries as $entry ) {
			if ( ! isset( $classes[ $entry['type'] ] ) ) {
				throw new RuntimeException( 'El respaldo contiene una línea no compatible.' ); }
			$item        = new $classes[ $entry['type'] ]();
			$props       = $entry['data'];
			$original_id = $props['id'];
			unset( $props['id'], $props['order_id'] );
			$product = self::product( $entry );
			if ( 'line_item' === $entry['type'] ) {
				$props['product_id']   = $product ? ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) : 0;
				$props['variation_id'] = $product && $product->is_type( 'variation' ) ? $product->get_id() : 0;
			}
			self::check( $item->set_props( $props ) );
			foreach ( $entry['meta'] as $key => $value ) {
				if ( '_refunded_item_id' === $key ) {
					$value = $original_items[ $value ] ?? 0; }
				$item->update_meta_data( $key, $value );
			}
			if ( $product && $item instanceof WC_Order_Item_Product ) {
				$pid = $product->is_type( 'variation' ) && ! BSC_Stock::has_dual_stock( $product->get_id() ) ? $product->get_parent_id() : $product->get_id();
				$item->update_meta_data( '_bsc_stock_product_id', $pid );
				if ( $stock ) {
					self::deduct_stock( $item, $product, $pid ); }
			} elseif ( $item instanceof WC_Order_Item_Product ) {
				$item->delete_meta_data( '_bsc_stock_product_id' );
				$item->delete_meta_data( '_bsc_stock_allocated_at' );
				$item->delete_meta_data( '_reduced_stock' );
			}
			$order->add_item( $item );
			$order->save();
			$map[ $original_id ] = $item->get_id();
		}
		return $map;
	}

	/**
	 * Apply the outstanding stock movement when explicitly selected.
	 *
	 * @param WC_Order_Item_Product $item item.
	 * @param WC_Product            $product product.
	 * @param int                   $pid pid.
	 * @return void
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	private static function deduct_stock( WC_Order_Item_Product $item, WC_Product $product, int $pid ): void {
		if ( BSC_Stock::has_dual_stock( $pid ) ) {
			if ( $item->get_meta( '_bsc_stock_released' ) ) {
				return; }
			if ( ! $item->get_meta( '_bsc_stock_allocated_at' ) ) {
				throw new RuntimeException( 'El archivo no contiene la asignación de inventario. Recupera solo el historial.' ); }
			foreach ( array( 'bodega', 'tienda' ) as $location ) {
				$quantity = max( 0, (int) $item->get_meta( '_bsc_stock_' . $location . '_qty' ) - (int) $item->get_meta( '_bsc_stock_restored_' . $location ) );
				if ( ! $quantity ) {
					continue; }
				$variant = (string) $item->get_meta( '_bsc_product_variant_key' );
				$result  = $variant ? BSC_Stock::adjust_variant_stock_strict( $pid, $variant, $location, -$quantity, 'Recuperación de venta' ) : BSC_Stock::adjust_strict( $pid, $location, -$quantity, 'Recuperación de venta' );
				self::check( $result );
			}
		} elseif ( $product->managing_stock() ) {
			$qty = (float) $item->get_meta( '_reduced_stock' );
			if ( $qty > 0 ) {
				if ( ! $product->has_enough_stock( $qty ) ) {
					throw new RuntimeException( 'Inventario insuficiente para recuperar esta venta.' ); }
				self::check( wc_update_product_stock( $product, $qty, 'decrease' ) );
			}
		}
	}

	/**
	 * Link surviving ledger entries and insert missing movements once.
	 *
	 * @param WC_Order $order order.
	 * @param array    $data data.
	 * @return void
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	private static function restore_points( WC_Order $order, array $data ): void {
		global $wpdb;
		$table   = bsc_bp_table();
		$user_id = $order->get_customer_id();
		foreach ( $data['points'] as $row ) {
			$extra    = (array) $row['meta'];
			$reason   = sanitize_key( $row['reason'] );
			$order_id = 'redeem' === $reason ? null : $order->get_id();
			$key      = 'recovery:' . hash( 'sha256', $data['site_id'] . ':' . $row['id'] . ':' . $row['created_at'] . ':' . $row['delta'] );
			// A recovered order may be absent while its original points ledger still survives.
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d AND (event_key = %s OR (id = %d AND delta = %d AND reason = %s AND created_at = %s)) LIMIT 1", $user_id, $key, $row['id'], $row['delta'], $reason, $row['created_at'] ), ARRAY_A );
			if ( $existing ) {
				if ( null !== $order_id ) {
					$other = (int) $existing['order_id'];
					if ( $other && $other !== $order_id && wc_get_order( $other ) ) {
						throw new RuntimeException( 'Los puntos están vinculados a otro pedido existente. Revisa el conflicto antes de importar.' ); }
					self::check(
						$wpdb->update(
							$table,
							array(
								'order_id'  => $order_id,
								'event_key' => 'order_complete' === $reason ? 'earn:order:' . $order_id : $key,
							),
							array( 'id' => $existing['id'] )
						)
					);
				}
				continue;
			}
			$extra['event_key']             = $key;
			$extra['recovery_source_event'] = $row['id'];
			$result                         = bsc_bp_add_ledger_entry( $user_id, (int) $row['delta'], $reason, $order_id, $extra, true );
			if ( empty( $result['ok'] ) ) {
				throw new RuntimeException( 'No se pudieron recuperar los Bubble Points. No se aplicaron cambios.' ); }
			self::check( $wpdb->update( $table, array( 'created_at' => $row['created_at'] ), array( 'id' => $result['insert_id'] ) ) );
			if ( 'order_complete' === $reason ) {
				$order->update_meta_data( '_bsc_bp_points_ledger_id', $result['insert_id'] ); }
		}
	}
}
