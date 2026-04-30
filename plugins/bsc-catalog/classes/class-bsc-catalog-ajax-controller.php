<?php

class BSC_Catalog_Ajax_Controller {
    public static function register_hooks(): void {
        add_action('wp_ajax_bsc_filter_products', 'bsc_filter_products');
        add_action('wp_ajax_nopriv_bsc_filter_products', 'bsc_filter_products');
    }

    public static function handle_request(): void {
        check_ajax_referer('bsc_ajax_action', 'nonce');

        require_once get_template_directory() . '/components/products/card.php';

        $config = new BSC_Catalog_Filter_Config();
        $context = BSC_Catalog_Request_Context::from_request($_GET, $config);

        if (!$config->is_valid_group($context->get_group())) {
            wp_send_json_error([
                'message' => 'Grupo no válido',
                'payload' => $context->get_group(),
            ]);
            return;
        }

        try {
            $query = (new BSC_Catalog_Product_Query($config))->get_query($context);
            $html = (new BSC_Catalog_Product_Renderer())->render_query_results($query);

            wp_send_json_success([
                '#bscProductsContainer' => $html,
            ]);
        } catch (Throwable $exception) {
            error_log('Error en bsc_filter_products: ' . $exception->getMessage());

            wp_send_json_error([
                'message' => 'Ocurrió un error al cargar los productos.',
            ]);
        }
    }
}
