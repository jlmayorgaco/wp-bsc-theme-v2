<?php

defined('ABSPATH') || exit;

require_once get_template_directory() . '/components/checkout/class-bsc-checkout-main-view.php';

$checkout_main_view = new BSC_Checkout_Main_View();
$checkout_main_view->render();
