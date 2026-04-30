<?php

if (!function_exists('bsc_render_custom_filters_sidebar')) {
    function bsc_render_custom_filters_sidebar(): void {
        (new BSC_Catalog_Filter_Sidebar_Renderer())->render();
    }
}

if (!function_exists('bsc_filter_products')) {
    function bsc_filter_products(): void {
        BSC_Catalog_Ajax_Controller::handle_request();
    }
}
