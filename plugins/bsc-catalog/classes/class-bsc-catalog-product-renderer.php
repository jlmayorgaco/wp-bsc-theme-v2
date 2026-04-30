<?php

class BSC_Catalog_Product_Renderer {
    public function render_query_results(WP_Query $query): string {
        if (!class_exists('BSC_Products_Card')) {
            throw new RuntimeException('La clase BSC_Products_Card no está disponible.');
        }

        ob_start();

        try {
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $product = wc_get_product(get_the_ID());

                    if (!$product instanceof WC_Product) {
                        throw new RuntimeException('No se pudo obtener una instancia válida de WC_Product.');
                    }

                    $card = new BSC_Products_Card();
                    $card->setProduct($product);
                    $card->render();
                }
            } else {
                echo '<p>No se encontraron productos.</p>';
            }
        } finally {
            wp_reset_postdata();
        }

        return (string) ob_get_clean();
    }
}
