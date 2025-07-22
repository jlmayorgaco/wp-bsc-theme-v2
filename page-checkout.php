<?php
/**
 * Template Name: Página Checkout Personalizada
 */

get_header(); 

/**
 * Router: Detect WooCommerce checkout state
 */
function bsc_get_checkout_view_state() {
    if ( is_wc_endpoint_url('order-received') ) return 'order_received';
    if ( is_wc_endpoint_url('order-pay') ) return 'order_pay';
    if ( is_wc_endpoint_url('cancelled') ) return 'order_cancelled';
    if ( WC()->cart && WC()->cart->is_empty() ) return 'empty_cart';
    return 'checkout';
}

$current_view = bsc_get_checkout_view_state();

switch ( $current_view ) {

    case 'order_received':
        require_once get_template_directory() . '/components/checkout/views/checkout-view-thankyou.php';
        break;

    case 'order_pay':
        require_once get_template_directory() . '/components/checkout/views/checkout-view-pay.php';
        break;

    case 'order_cancelled':
        require_once get_template_directory() . '/components/checkout/views/checkout-view-cancelled.php';
        break;

    case 'empty_cart':
        require_once get_template_directory() . '/components/checkout/views/checkout-view-empty.php';
        break;

    case 'checkout':
    default:
        require_once get_template_directory() . '/components/checkout/views/checkout-view-main.php';
        break;
}
?> 


<?php get_footer(); ?>
