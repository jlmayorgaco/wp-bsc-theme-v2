<?php
/**
 * Template: Custom View Order Page for WooCommerce
 *
 * Reviewed against WooCommerce view-order.php 10.6.0.
 *
 * @package WooCommerce\Templates
 * @version 10.6.0
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id );
if ( ! $order ) {
    return;
}

do_action( 'woocommerce_before_view_order', $order );

require_once get_template_directory() . '/components/orders/class-bsc-order-view.php';

$order_view = new BSC_Order_View( $order );
$order_view->set_actions(
    array(
        array(
            'label' => '¡ Ir a la tienda !',
            'url'   => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
        ),
    )
);
$order_view->render();

$bsc_restore_order_details_callback = has_action( 'woocommerce_view_order', 'woocommerce_order_details_table' );
if ( false !== $bsc_restore_order_details_callback ) {
    remove_action( 'woocommerce_view_order', 'woocommerce_order_details_table', 10 );
}

do_action( 'woocommerce_view_order', $order_id );

if ( false !== $bsc_restore_order_details_callback ) {
    add_action( 'woocommerce_view_order', 'woocommerce_order_details_table', 10 );
}

do_action( 'woocommerce_after_view_order', $order );
