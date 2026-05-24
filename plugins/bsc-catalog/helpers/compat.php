<?php

if (!function_exists( 'bsc_render_custom_filters_sidebar' )) {
	function bsc_render_custom_filters_sidebar( ?BSC_Catalog_Request_Context $context = null ): void {
		( new BSC_Catalog_Filter_Sidebar_Renderer() )->render( $context );
	}
}

if (!function_exists( 'bsc_filter_products' )) {
	function bsc_filter_products(): void {
		BSC_Catalog_Ajax_Controller::handle_request();
	}
}
