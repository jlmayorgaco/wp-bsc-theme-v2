<?php
defined('ABSPATH') || exit;


add_action('wp_ajax_bsc_filter_products', 'bsc_filter_products');
add_action('wp_ajax_nopriv_bsc_filter_products', 'bsc_filter_products');

function bsc_filter_products() {
	check_ajax_referer('bsc_ajax_action', 'nonce');

	require_once get_template_directory() . '/components/products/card.php';

    $categories = [];
    $group = isset($_GET['group']) ? sanitize_text_field($_GET['group']) : '';
    $subgroup = isset($_GET['subgroup']) ? sanitize_text_field($_GET['subgroup']) : '';
    $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

	$categories[] = $category;

    // 👇 Campos por grupo
    if ($group === 'group-skin-care') {
        $fields = ['piel', 'necesidad', 'ingredientes', 'marca'];
    } elseif ($group === 'group-hair-care') {
        $fields = ['necesidad', 'rutina', 'marca'];
    } elseif ($group === 'group-make-up') {
        $fields = ['producto', 'marca'];
    } else {
        wp_send_json_error(['message' => 'Grupo no válido', 'payload' => $group]);
        return;
    }


    // ✅ Recoge las categorías no vacías
    foreach ($fields as $field) {
        if (!empty($_GET[$field])) {
            $values = (array) $_GET[$field]; // Soporta múltiples filtros
            foreach ($values as $val) {
                $val = sanitize_text_field($val);
                if (!empty($val)) {
                    $categories[] = $val;
                }
            }
        }
    }



    // Rango de precios
    $min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
    $max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 999999;

    $args = [
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => 48,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,  // BSC-040: pre-load meta in batch
        'update_post_term_cache' => true,  // BSC-040: pre-load terms in batch
        'meta_query'             => [
            [
                'key'     => '_price',
                'value'   => [$min_price, $max_price],
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            ]
        ],
    ];

    // 🧠 Solo agregar tax_query si hay categorías
    if (!empty($categories)) {
        $args['tax_query'][] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $categories,
            'operator' => 'AND', // Requiere que cumpla todos los filtros
        ];
    }

try {
    $query = new WP_Query($args);

    ob_start();

    if (!class_exists('BSC_Products_Card')) {
        throw new Exception('La clase BSC_Products_Card no está disponible.');
    }

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());

            if (!$product instanceof WC_Product) {
                throw new Exception('No se pudo obtener una instancia válida de WC_Product.');
            }

            $card = new BSC_Products_Card();
            $card->setProduct($product);
            $card->render();
        }
    } else {
        echo '<p>No se encontraron productos.</p>';
    }

    wp_reset_postdata();

    $html = ob_get_clean();

    wp_send_json_success(['#bscProductsContainer' => $html]);

} catch (Exception $e) {
    // Opcional: guardar en log
    error_log('Error en bsc_filter_products: ' . $e->getMessage());

    wp_send_json_error([
        'message' => 'Ocurrió un error al cargar los productos.',
    ]);
}

}
?>