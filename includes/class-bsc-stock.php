<?php
/**
 * BSC-036: Dual stock management.
 */
defined('ABSPATH') || exit;

class BSC_Stock {

    public static function deduct_bodega( int $order_id ): void {
        self::deduct_web_order( $order_id );
    }

    public static function deduct_web_order( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        if ( $order->get_meta( '_bsc_is_showroom_sale', true ) ) return;

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
            $allocation = self::allocate_and_deduct( $product_id, $qty, $reason );

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
        if ( ! $order ) return;

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
        return [
            'bodega'     => (int) get_post_meta( $product_id, '_stock_bodega', true ),
            'tienda'     => (int) get_post_meta( $product_id, '_stock_tienda', true ),
            'envio_tipo' => get_post_meta( $product_id, '_envio_tipo', true ) ?: 'bodega',
        ];
    }

    public static function has_dual_stock( int $product_id ): bool {
        return metadata_exists( 'post', $product_id, '_stock_bodega' )
            || metadata_exists( 'post', $product_id, '_stock_tienda' );
    }

    public static function get_total_stock( int $product_id ): int {
        $stock = self::get_stock( $product_id );
        return max( 0, (int) $stock['bodega'] ) + max( 0, (int) $stock['tienda'] );
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

        return [
            'bodega' => $from_bodega,
            'tienda' => $from_tienda,
        ];
    }

    /**
     * Deduct bodega first and showroom only for the remaining quantity.
     *
     * @return array{bodega: int, tienda: int}|WP_Error
     */
    public static function allocate_and_deduct( int $product_id, int $quantity, string $reason = '' ) {
        $qty = max( 0, $quantity );
        if ( $qty <= 0 ) {
            return [ 'bodega' => 0, 'tienda' => 0 ];
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
                [
                    'bodega' => $bodega_qty,
                    'tienda' => $tienda_qty,
                ]
            );
        }

        $product_id = $product_id > 0 ? $product_id : (int) $item->get_product_id();
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
        $where  = 'post_id = %d AND meta_key = %s';
        $params = [ $delta, $product_id, $meta_key ];

        if ( $delta < 0 ) {
            $where .= ' AND CAST(meta_value AS SIGNED) >= %d';
            $params[] = abs( $delta );
        }

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->postmeta}
                 SET meta_value = CAST(meta_value AS SIGNED) + %d
                 WHERE {$where}",
                $params
            )
        );

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
        if ( ! is_array( $log ) ) return [];
        return array_reverse( $log );
    }

    public static function get_low_stock_products( int $threshold = -1 ): array {
        if ( $threshold < 0 ) {
            $threshold = (int) get_option( 'bsc_low_stock_threshold', 3 );
        }
        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_stock_bodega'
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
               AND CAST(IFNULL(pm.meta_value, 0) AS UNSIGNED) < %d
             ORDER BY CAST(IFNULL(pm.meta_value, 0) AS UNSIGNED) ASC
             LIMIT 20",
            max( 1, $threshold )
        ) );
        return $ids ? array_map( 'intval', $ids ) : [];
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
        $log   = is_array( $log ) ? $log : [];
        $log[] = [
            'date'    => current_time( 'mysql' ),
            'type'    => $type,
            'delta'   => $delta,
            'before'  => $current,
            'after'   => $new,
            'reason'  => sanitize_text_field( $reason ),
            'user_id' => get_current_user_id(),
        ];
        if ( count( $log ) > 50 ) {
            $log = array_slice( $log, -50 );
        }
        update_post_meta( $product_id, '_bsc_stock_log', $log );
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
