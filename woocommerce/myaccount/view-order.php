<?php
/**
 * Template: Custom View Order Page for WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id );
if ( ! $order ) {
    return;
}

do_action( 'woocommerce_before_view_order', $order );

require_once get_template_directory() . '/components/orders/class-bsc-order-view.php';

$order_view = new BSC_Order_View( $order );
$order_view->render();

do_action( 'woocommerce_after_view_order', $order );
