<?php

class BSC_Catalog_Ajax_Controller {
	public static function register_hooks(): void {
		add_action( 'wp_ajax_bsc_filter_products', 'bsc_filter_products' );
		add_action( 'wp_ajax_nopriv_bsc_filter_products', 'bsc_filter_products' );
	}

	public static function handle_request(): void {
		check_ajax_referer( 'bsc_ajax_action', 'nonce' );

		require_once get_template_directory() . '/components/products/card.php';

		$config       = new BSC_Catalog_Filter_Config();
		$query_params = BSC_Catalog_Request_Context::sanitize_request_array( $_GET );
		$context      = BSC_Catalog_Request_Context::from_request( $query_params, $config );

		if (!$config->is_valid_group( $context->get_group() )) {
			wp_send_json_error(
				array(
					'message' => 'Grupo no válido',
					'payload' => $context->get_group(),
				)
			);
			return;
		}

		try {
			$is_group_page = '' === $context->get_subgroup() && '' !== $context->get_category();
			$query_args = array(
				'posts_per_page' => $is_group_page ? 120 : 24,
				'paged'          => 1,
				'no_found_rows'  => $is_group_page,
			);
			$query    = ( new BSC_Catalog_Product_Query( $config ) )->get_query( $context, $query_args );
			$renderer = new BSC_Catalog_Product_Renderer();
			$html     = $renderer->render_query_results( $query, $is_group_page );

			wp_send_json_success(
				array(
					'#bscProductsContainer'   => $html,
					'#bscPaginationContainer' => $is_group_page ? '' : $renderer->render_pagination( $query, $context, 1 ),
				)
			);
		} catch (Throwable $exception) {
			error_log( 'Error en bsc_filter_products: ' . $exception->getMessage() );

			wp_send_json_error(
				array(
					'message' => 'Ocurrió un error al cargar los productos.',
				)
			);
		}
	}
}
