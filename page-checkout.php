<?php
/**
 * Template Name: Página Checkout Personalizada
 */

get_header();

require_once get_template_directory() . '/components/checkout/class-bsc-checkout-view-router.php';

$checkout_view_router = new BSC_Checkout_View_Router();
$checkout_view_router->render();

get_footer();
