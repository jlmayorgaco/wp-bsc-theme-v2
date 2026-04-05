<?php
/**
 * BSC-036: Dual stock management — _stock_bodega (web/dispatch) and _stock_tienda (showroom).
 * Web orders deduct bodega; showroom sales deduct tienda.
 * Result is always clamped to 0 (no negative stock).
 */
defined('ABSPATH') || exit;

class BSC_Stock {

    /**
     * Deduct from _stock_bodega — called when a web order is placed.
     */
    public static function deduct_bodega( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $qty        = (int) $item->get_quantity();
            $current    = (int) get_post_meta( $product_id, '_stock_bodega', true );
            update_post_meta( $product_id, '_stock_bodega', max( 0, $current - $qty ) );
        }
    }

    /**
     * Deduct from _stock_tienda — called when a showroom sale is registered.
     */
    public static function deduct_tienda( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $qty        = (int) $item->get_quantity();
            $current    = (int) get_post_meta( $product_id, '_stock_tienda', true );
            update_post_meta( $product_id, '_stock_tienda', max( 0, $current - $qty ) );
        }
    }

    /**
     * Get formatted stock info for a product (used in admin UI).
     *
     * @return array{bodega: int, tienda: int, envio_tipo: string}
     */
    public static function get_stock( int $product_id ): array {
        return [
            'bodega'     => (int) get_post_meta( $product_id, '_stock_bodega', true ),
            'tienda'     => (int) get_post_meta( $product_id, '_stock_tienda', true ),
            'envio_tipo' => get_post_meta( $product_id, '_envio_tipo', true ) ?: 'bodega',
        ];
    }

    /**
     * BSC-066: Adjust stock manually (reposition or correction).
     *
     * @param int    $product_id
     * @param string $type    'bodega' | 'tienda'
     * @param int    $delta   positive (add) or negative (subtract)
     * @param string $reason  reason for the adjustment (logged)
     * @return int  new stock value after adjustment
     */
    public static function adjust( int $product_id, string $type, int $delta, string $reason = '' ): int {
        $meta_key = ( $type === 'tienda' ) ? '_stock_tienda' : '_stock_bodega';
        $current  = (int) get_post_meta( $product_id, $meta_key, true );
        $new      = max( 0, $current + $delta );
        update_post_meta( $product_id, $meta_key, $new );

        // Log the movement
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
        // Keep only last 50 entries
        if ( count( $log ) > 50 ) {
            $log = array_slice( $log, -50 );
        }
        update_post_meta( $product_id, '_bsc_stock_log', $log );

        return $new;
    }

    /**
     * BSC-066: Get stock movement log for a product.
     *
     * @return array Latest 50 log entries, newest first.
     */
    public static function get_log( int $product_id ): array {
        $log = get_post_meta( $product_id, '_bsc_stock_log', true );
        if ( ! is_array( $log ) ) return [];
        return array_reverse( $log );
    }

    /**
     * BSC-061/066: Get products with low stock bodega (below threshold).
     *
     * @param int $threshold  Alert threshold (default from option, fallback 3)
     * @return array WP_Post objects
     */
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
}
