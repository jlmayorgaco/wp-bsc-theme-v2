<?php

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/components/checkout/class-bsc-checkout-form-view.php';

$checkout_form_view = new BSC_Checkout_Form_View();
$checkout_form_view->render();
