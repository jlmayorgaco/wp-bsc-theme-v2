<?php
/**
 * BSC-036: Dual stock management.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Stock {

	public static function deduct_bodega( int $order_id ): void {
		self::deduct_web_order( $order_id );
	}

	public static function deduct_web_order( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		if ( $order->get_meta( '_bsc_is_showroom_sale', true ) ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			if ( $item->get_meta( '_bsc_stock_allocated_at', true ) ) {
				continue;
			}

			if ( ! self::acquire_order_item_lock( (int) $item->get_id() ) ) {
				continue;
			}

			$product_id = (int) $item->get_product_id();
			$qty        = max( 1, (int) $item->get_quantity() );
			$reason     = sprintf( 'Pedido web #%s item #%d', $order->get_order_number(), $item->get_id() );
			$variant_key = sanitize_key( (string) $item->get_meta( '_bsc_product_variant_key', true ) );
			$allocation  = $variant_key !== '' && function_exists( 'bsc_product_has_saved_variant_matrix' ) && bsc_product_has_saved_variant_matrix( $product_id )
				? self::allocate_variant_and_deduct( $product_id, $variant_key, $qty, $reason )
				: self::allocate_and_deduct( $product_id, $qty, $reason );

			if ( is_wp_error( $allocation ) ) {
				$item->update_meta_data( '_bsc_stock_allocation_error', $allocation->get_error_message() );
				$item->save();
				$order->add_order_note(
					sprintf(
						'No se pudo descontar stock para %1$s x %2$d: %3$s',
						$item->get_name(),
						$qty,
						$allocation->get_error_message()
					)
				);
				self::release_order_item_lock( (int) $item->get_id() );
				continue;
			}

			$item->update_meta_data( '_bsc_stock_source', self::source_from_allocation( $allocation ) );
			$item->update_meta_data( '_bsc_stock_bodega_qty', $allocation['bodega'] );
			$item->update_meta_data( '_bsc_stock_tienda_qty', $allocation['tienda'] );
			$item->update_meta_data( '_bsc_stock_allocated_at', current_time( 'mysql' ) );
			$item->save();
			self::release_order_item_lock( (int) $item->get_id() );
		}
	}

	public static function deduct_tienda( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();
			$qty        = max( 1, (int) $item->get_quantity() );
			self::adjust_strict( $product_id, 'tienda', -$qty, 'Venta presencial #' . $order_id );
		}
	}

	/**
	 * @return array{bodega: int, tienda: int, envio_tipo: string}
	 */
	public static function get_stock( int $product_id ): array {
		if ( function_exists( 'bsc_get_product_saved_variant_matrix' ) ) {
			$matrix = bsc_get_product_saved_variant_matrix( $product_id, false );
			if ( ! empty( $matrix ) ) {
				$bodega = 0;
				$tienda = 0;
				foreach ( $matrix as $variant ) {
					$bodega += max( 0, (int) ( $variant['stock_bodega'] ?? 0 ) );
					$tienda += max( 0, (int) ( $variant['stock_tienda'] ?? 0 ) );
				}

				return array(
					'bodega'     => $bodega,
					'tienda'     => $tienda,
					'envio_tipo' => get_post_meta( $product_id, '_envio_tipo', true ) ?: 'bodega',
				);
			}
		}

		return array(
			'bodega'     => (int) get_post_meta( $product_id, '_stock_bodega', true ),
			'tienda'     => (int) get_post_meta( $product_id, '_stock_tienda', true ),
			'envio_tipo' => get_post_meta( $product_id, '_envio_tipo', true ) ?: 'bodega',
		);
	}

	public static function has_dual_stock( int $product_id ): bool {
		if ( function_exists( 'bsc_product_has_saved_variant_matrix' ) && bsc_product_has_saved_variant_matrix( $product_id ) ) {
			return true;
		}

		return metadata_exists( 'post', $product_id, '_stock_bodega' )
			|| metadata_exists( 'post', $product_id, '_stock_tienda' );
	}

	public static function get_total_stock( int $product_id ): int {
		if ( function_exists( 'bsc_get_product_saved_variant_matrix' ) ) {
			$matrix = bsc_get_product_saved_variant_matrix( $product_id, false );
			if ( ! empty( $matrix ) ) {
				$total = 0;
				foreach ( $matrix as $variant ) {
					$total += max( 0, (int) ( $variant['stock_bodega'] ?? 0 ) ) + max( 0, (int) ( $variant['stock_tienda'] ?? 0 ) );
				}
				return $total;
			}
		}

		$stock = self::get_stock( $product_id );
		return max( 0, (int) $stock['bodega'] ) + max( 0, (int) $stock['tienda'] );
	}

	public static function get_available_stock_meta_query(): array {
		return array(
			'relation' => 'OR',
			array(
				'key'     => '_stock_bodega',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
			array(
				'key'     => '_stock_tienda',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
			array(
				'relation' => 'AND',
				array(
					'key'     => '_stock_bodega',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_stock_tienda',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				),
			),
		);
	}

	/**
	 * @return array{bodega: int, tienda: int}
	 */
	public static function allocate_for_quantity( int $product_id, int $quantity ): array {
		$stock = self::get_stock( $product_id );
		$qty   = max( 0, $quantity );

		$from_bodega = min( max( 0, (int) $stock['bodega'] ), $qty );
		$remaining   = max( 0, $qty - $from_bodega );
		$from_tienda = min( max( 0, (int) $stock['tienda'] ), $remaining );

		return array(
			'bodega' => $from_bodega,
			'tienda' => $from_tienda,
		);
	}

	/**
	 * Deduct bodega first and showroom only for the remaining quantity.
	 *
	 * @return array{bodega: int, tienda: int}|WP_Error
	 */
	public static function allocate_and_deduct( int $product_id, int $quantity, string $reason = '' ) {
		$qty = max( 0, $quantity );
		if ( $qty <= 0 ) {
			return array(
				'bodega' => 0,
				'tienda' => 0,
			);
		}

		$allocation = self::allocate_for_quantity( $product_id, $qty );
		if ( ( $allocation['bodega'] + $allocation['tienda'] ) < $qty ) {
			return new WP_Error( 'bsc_stock_shortage', 'Stock insuficiente.' );
		}

		if ( $allocation['bodega'] > 0 ) {
			$bodega_result = self::adjust_strict( $product_id, 'bodega', -$allocation['bodega'], $reason );
			if ( is_wp_error( $bodega_result ) ) {
				return $bodega_result;
			}
		}

		if ( $allocation['tienda'] > 0 ) {
			$tienda_result = self::adjust_strict( $product_id, 'tienda', -$allocation['tienda'], $reason );
			if ( is_wp_error( $tienda_result ) ) {
				if ( $allocation['bodega'] > 0 ) {
					self::adjust( $product_id, 'bodega', $allocation['bodega'], 'Rollback: ' . $reason );
				}
				return $tienda_result;
			}
		}

		return $allocation;
	}

	/**
	 * @return array{bodega: int, tienda: int}
	 */
	public static function get_variant_stock( int $product_id, string $variant_key ): array {
		$variant = self::get_saved_variant_row( $product_id, $variant_key );
		if ( ! is_array( $variant ) ) {
			return array(
				'bodega' => 0,
				'tienda' => 0,
			);
		}

		return array(
			'bodega' => max( 0, (int) ( $variant['stock_bodega'] ?? 0 ) ),
			'tienda' => max( 0, (int) ( $variant['stock_tienda'] ?? 0 ) ),
		);
	}

	/**
	 * @return array{bodega: int, tienda: int}
	 */
	public static function allocate_variant_for_quantity( int $product_id, string $variant_key, int $quantity ): array {
		$stock = self::get_variant_stock( $product_id, $variant_key );
		$qty   = max( 0, $quantity );

		$from_bodega = min( max( 0, (int) $stock['bodega'] ), $qty );
		$remaining   = max( 0, $qty - $from_bodega );
		$from_tienda = min( max( 0, (int) $stock['tienda'] ), $remaining );

		return array(
			'bodega' => $from_bodega,
			'tienda' => $from_tienda,
		);
	}

	/**
	 * @return array{bodega: int, tienda: int}|WP_Error
	 */
	public static function allocate_variant_and_deduct( int $product_id, string $variant_key, int $quantity, string $reason = '' ) {
		$qty = max( 0, $quantity );
		if ( $qty <= 0 ) {
			return array(
				'bodega' => 0,
				'tienda' => 0,
			);
		}

		$variant_key = sanitize_key( $variant_key );
		if ( $variant_key === '' || ! function_exists( 'bsc_get_product_saved_variant_matrix' ) ) {
			return new WP_Error( 'bsc_variant_stock_missing', 'Variante no encontrada.' );
		}

		if ( ! self::acquire_variant_stock_lock( $product_id, $variant_key ) ) {
			return new WP_Error( 'bsc_variant_stock_locked', 'El stock de esta variante se esta actualizando. Intenta de nuevo.' );
		}

		$matrix = bsc_get_product_saved_variant_matrix( $product_id, true );
		$index  = self::find_variant_index_by_key( $matrix, $variant_key );
		if ( $index < 0 || empty( $matrix[ $index ]['enabled'] ) ) {
			self::release_variant_stock_lock( $product_id, $variant_key );
			return new WP_Error( 'bsc_variant_stock_missing', 'Variante no encontrada.' );
		}

		$variant     = $matrix[ $index ];
		$bodega      = max( 0, (int) ( $variant['stock_bodega'] ?? 0 ) );
		$tienda      = max( 0, (int) ( $variant['stock_tienda'] ?? 0 ) );
		$from_bodega = min( $bodega, $qty );
		$remaining   = max( 0, $qty - $from_bodega );
		$from_tienda = min( $tienda, $remaining );

		if ( ( $from_bodega + $from_tienda ) < $qty ) {
			$label = self::variant_label( $variant );
			self::log_movement( $product_id, 'variante-bodega', -$qty, $bodega, $bodega, 'Rechazado: stock insuficiente. ' . $label . ' ' . $reason );
			self::release_variant_stock_lock( $product_id, $variant_key );
			return new WP_Error( 'bsc_stock_shortage', 'Stock insuficiente.' );
		}

		$matrix[ $index ]['stock_bodega'] = $bodega - $from_bodega;
		$matrix[ $index ]['stock_tienda'] = $tienda - $from_tienda;
		update_post_meta( $product_id, '_bsc_variant_matrix', array_values( $matrix ) );

		if ( function_exists( 'bsc_sync_product_variant_parent_stock' ) ) {
			bsc_sync_product_variant_parent_stock( $product_id, $matrix );
		}

		$label = self::variant_label( $variant );
		if ( $from_bodega > 0 ) {
			self::log_movement( $product_id, 'variante-bodega', -$from_bodega, $bodega, $bodega - $from_bodega, $label . ' ' . $reason );
		}
		if ( $from_tienda > 0 ) {
			self::log_movement( $product_id, 'variante-tienda', -$from_tienda, $tienda, $tienda - $from_tienda, $label . ' ' . $reason );
		}

		self::release_variant_stock_lock( $product_id, $variant_key );

		return array(
			'bodega' => $from_bodega,
			'tienda' => $from_tienda,
		);
	}

	/**
	 * @return int|WP_Error
	 */
	public static function adjust_variant_stock_strict( int $product_id, string $variant_key, string $type, int $delta, string $reason = '' ) {
		$variant_key = sanitize_key( $variant_key );
		$type        = self::normalize_type( $type );
		$field       = 'tienda' === $type ? 'stock_tienda' : 'stock_bodega';

		if ( $variant_key === '' || ! function_exists( 'bsc_get_product_saved_variant_matrix' ) ) {
			return new WP_Error( 'bsc_variant_stock_missing', 'Variante no encontrada.' );
		}

		if ( ! self::acquire_variant_stock_lock( $product_id, $variant_key ) ) {
			return new WP_Error( 'bsc_variant_stock_locked', 'El stock de esta variante se esta actualizando. Intenta de nuevo.' );
		}

		$matrix = bsc_get_product_saved_variant_matrix( $product_id, true );
		$index  = self::find_variant_index_by_key( $matrix, $variant_key );
		if ( $index < 0 ) {
			self::release_variant_stock_lock( $product_id, $variant_key );
			return new WP_Error( 'bsc_variant_stock_missing', 'Variante no encontrada.' );
		}

		$current = max( 0, (int) ( $matrix[ $index ][ $field ] ?? 0 ) );
		if ( $delta < 0 && $current < abs( $delta ) ) {
			self::log_movement( $product_id, 'variante-' . $type, $delta, $current, $current, 'Rechazado: stock insuficiente. ' . self::variant_label( $matrix[ $index ] ) . ' ' . $reason );
			self::release_variant_stock_lock( $product_id, $variant_key );
			return new WP_Error( 'bsc_stock_shortage', 'Stock insuficiente.' );
		}

		$new                         = max( 0, $current + $delta );
		$matrix[ $index ][ $field ] = $new;
		update_post_meta( $product_id, '_bsc_variant_matrix', array_values( $matrix ) );

		if ( function_exists( 'bsc_sync_product_variant_parent_stock' ) ) {
			bsc_sync_product_variant_parent_stock( $product_id, $matrix );
		}

		self::log_movement( $product_id, 'variante-' . $type, $delta, $current, $new, self::variant_label( $matrix[ $index ] ) . ' ' . $reason );
		self::release_variant_stock_lock( $product_id, $variant_key );

		return $new;
	}

	public static function adjust_variant_stock( int $product_id, string $variant_key, string $type, int $delta, string $reason = '' ): int {
		$result = self::adjust_variant_stock_strict( $product_id, $variant_key, $type, $delta, $reason );

		if ( is_wp_error( $result ) ) {
			$stock = self::get_variant_stock( $product_id, $variant_key );
			return (int) ( 'tienda' === self::normalize_type( $type ) ? $stock['tienda'] : $stock['bodega'] );
		}

		return (int) $result;
	}

	/**
	 * @param array{bodega: int, tienda: int} $allocation
	 */
	public static function source_from_allocation( array $allocation ): string {
		$bodega = max( 0, (int) ( $allocation['bodega'] ?? 0 ) );
		$tienda = max( 0, (int) ( $allocation['tienda'] ?? 0 ) );

		if ( $bodega > 0 && $tienda > 0 ) {
			return 'mixto';
		}

		return $tienda > 0 ? 'tienda' : 'bodega';
	}

	/**
	 * @param array{bodega: int, tienda: int} $allocation
	 */
	public static function allocation_label( array $allocation ): string {
		$bodega = max( 0, (int) ( $allocation['bodega'] ?? 0 ) );
		$tienda = max( 0, (int) ( $allocation['tienda'] ?? 0 ) );

		if ( $bodega > 0 && $tienda > 0 ) {
			return sprintf( 'Bodega x%d / Showroom x%d', $bodega, $tienda );
		}

		if ( $tienda > 0 ) {
			return $tienda > 1 ? sprintf( 'Showroom x%d', $tienda ) : 'Showroom';
		}

		return $bodega > 1 ? sprintf( 'Bodega x%d', $bodega ) : 'Bodega';
	}

	public static function get_order_item_source_label( WC_Order_Item_Product $item, int $product_id = 0 ): string {
		$bodega_qty = (int) $item->get_meta( '_bsc_stock_bodega_qty', true );
		$tienda_qty = (int) $item->get_meta( '_bsc_stock_tienda_qty', true );

		if ( $bodega_qty > 0 || $tienda_qty > 0 ) {
			return self::allocation_label(
				array(
					'bodega' => $bodega_qty,
					'tienda' => $tienda_qty,
				)
			);
		}

		$product_id  = 0 < $product_id ? $product_id : (int) $item->get_product_id();
		$variant_key = sanitize_key( (string) $item->get_meta( '_bsc_product_variant_key', true ) );
		if (
			'' !== $variant_key
			&& function_exists( 'bsc_product_has_saved_variant_matrix' )
			&& bsc_product_has_saved_variant_matrix( $product_id )
		) {
			return self::allocation_label(
				self::allocate_variant_for_quantity( $product_id, $variant_key, max( 1, (int) $item->get_quantity() ) )
			);
		}

		return self::allocation_label( self::allocate_for_quantity( $product_id, max( 1, (int) $item->get_quantity() ) ) );
	}

	public static function adjust( int $product_id, string $type, int $delta, string $reason = '' ): int {
		$result = self::adjust_strict( $product_id, $type, $delta, $reason );

		if ( is_wp_error( $result ) ) {
			return (int) get_post_meta( $product_id, self::meta_key_for_type( $type ), true );
		}

		return (int) $result;
	}

	/**
	 * @return int|WP_Error
	 */
	public static function adjust_strict( int $product_id, string $type, int $delta, string $reason = '' ) {
		$type     = self::normalize_type( $type );
		$meta_key = self::meta_key_for_type( $type );

		self::ensure_stock_meta_exists( $product_id, $meta_key );

		$current = (int) get_post_meta( $product_id, $meta_key, true );
		if ( $delta < 0 && $current < abs( $delta ) ) {
			self::log_movement( $product_id, $type, $delta, $current, $current, 'Rechazado: stock insuficiente. ' . $reason );
			return new WP_Error( 'bsc_stock_shortage', 'Stock insuficiente.' );
		}

		global $wpdb;

		if ( $delta < 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Atomic stock decrement requires one conditional update.
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta}
                     SET meta_value = CAST(meta_value AS SIGNED) + %d
                     WHERE post_id = %d AND meta_key = %s AND CAST(meta_value AS SIGNED) >= %d",
					$delta,
					$product_id,
					$meta_key,
					abs( $delta )
				)
			);
		} else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Atomic stock increment requires one update.
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta}
                     SET meta_value = CAST(meta_value AS SIGNED) + %d
                     WHERE post_id = %d AND meta_key = %s",
					$delta,
					$product_id,
					$meta_key
				)
			);
		}

		if ( false === $updated || 0 === (int) $updated ) {
			$latest = (int) get_post_meta( $product_id, $meta_key, true );
			self::log_movement( $product_id, $type, $delta, $latest, $latest, 'Rechazado: stock insuficiente. ' . $reason );
			return new WP_Error( 'bsc_stock_shortage', 'Stock insuficiente.' );
		}

		wp_cache_delete( $product_id, 'post_meta' );
		$new = (int) get_post_meta( $product_id, $meta_key, true );
		self::log_movement( $product_id, $type, $delta, $current, $new, $reason );

		return $new;
	}

	public static function get_log( int $product_id ): array {
		$log = get_post_meta( $product_id, '_bsc_stock_log', true );
		if ( ! is_array( $log ) ) {
			return array();
		}
		return array_reverse( $log );
	}

	public static function get_low_stock_products( int $threshold = -1 ): array {
		if ( $threshold < 0 ) {
			$threshold = (int) get_option( 'bsc_low_stock_threshold', 3 );
		}
		global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Low-stock report needs sorted numeric meta query.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_stock_bodega'
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
               AND CAST(IFNULL(pm.meta_value, 0) AS UNSIGNED) < %d
             ORDER BY CAST(IFNULL(pm.meta_value, 0) AS UNSIGNED) ASC
             LIMIT 20",
				max( 1, $threshold )
			)
		);
		return $ids ? array_map( 'intval', $ids ) : array();
	}

	private static function normalize_type( string $type ): string {
		return ( 'tienda' === $type ) ? 'tienda' : 'bodega';
	}

	private static function meta_key_for_type( string $type ): string {
		return ( 'tienda' === self::normalize_type( $type ) ) ? '_stock_tienda' : '_stock_bodega';
	}

	private static function ensure_stock_meta_exists( int $product_id, string $meta_key ): void {
		if ( ! metadata_exists( 'post', $product_id, $meta_key ) ) {
			add_post_meta( $product_id, $meta_key, 0, true );
		}
	}

	private static function log_movement( int $product_id, string $type, int $delta, int $current, int $new, string $reason = '' ): void {
		$log   = get_post_meta( $product_id, '_bsc_stock_log', true );
		$log   = is_array( $log ) ? $log : array();
		$log[] = array(
			'date'    => current_time( 'mysql' ),
			'type'    => $type,
			'delta'   => $delta,
			'before'  => $current,
			'after'   => $new,
			'reason'  => sanitize_text_field( $reason ),
			'user_id' => get_current_user_id(),
		);
		if ( count( $log ) > 50 ) {
			$log = array_slice( $log, -50 );
		}
		update_post_meta( $product_id, '_bsc_stock_log', $log );
	}

	private static function get_saved_variant_row( int $product_id, string $variant_key ): ?array {
		if ( ! function_exists( 'bsc_get_product_saved_variant_matrix' ) ) {
			return null;
		}

		$matrix = bsc_get_product_saved_variant_matrix( $product_id, true );
		$index  = self::find_variant_index_by_key( $matrix, sanitize_key( $variant_key ) );

		return $index >= 0 ? $matrix[ $index ] : null;
	}

	private static function find_variant_index_by_key( array $matrix, string $variant_key ): int {
		foreach ( $matrix as $index => $variant ) {
			if ( hash_equals( (string) ( $variant['key'] ?? '' ), $variant_key ) ) {
				return (int) $index;
			}
		}

		return -1;
	}

	private static function variant_label( array $variant ): string {
		$parts = array_filter(
			array(
				sanitize_text_field( (string) ( $variant['color_name'] ?? '' ) ),
				sanitize_text_field( (string) ( $variant['size_name'] ?? '' ) ),
			)
		);

		return empty( $parts ) ? 'Variante' : 'Variante ' . implode( ' / ', $parts );
	}

	private static function variant_stock_lock_key( string $variant_key ): string {
		return '_bsc_variant_stock_lock_' . sanitize_key( $variant_key );
	}

	private static function acquire_variant_stock_lock( int $product_id, string $variant_key ): bool {
		if ( $product_id <= 0 || ! function_exists( 'add_metadata' ) ) {
			return true;
		}

		return (bool) add_metadata( 'post', $product_id, self::variant_stock_lock_key( $variant_key ), current_time( 'mysql' ), true );
	}

	private static function release_variant_stock_lock( int $product_id, string $variant_key ): void {
		if ( $product_id <= 0 || ! function_exists( 'delete_metadata' ) ) {
			return;
		}

		delete_metadata( 'post', $product_id, self::variant_stock_lock_key( $variant_key ) );
	}

	private static function acquire_order_item_lock( int $item_id ): bool {
		if ( $item_id <= 0 || ! function_exists( 'add_metadata' ) ) {
			return true;
		}

		return (bool) add_metadata( 'order_item', $item_id, '_bsc_stock_allocation_lock', current_time( 'mysql' ), true );
	}

	private static function release_order_item_lock( int $item_id ): void {
		if ( $item_id <= 0 || ! function_exists( 'delete_metadata' ) ) {
			return;
		}

		delete_metadata( 'order_item', $item_id, '_bsc_stock_allocation_lock' );
	}
}
