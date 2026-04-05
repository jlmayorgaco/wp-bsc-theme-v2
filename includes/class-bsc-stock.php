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
}
