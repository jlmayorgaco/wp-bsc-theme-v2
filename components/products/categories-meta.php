<?php

class BSC_Product_Category_Meta {
    private WC_Product $product;
    private array $categories = [];

    public function __construct(WC_Product $product) {
        $this->product = $product;
        $this->load_categories();
    }

    private function load_categories(): void {
        $terms = get_the_terms($this->product->get_id(), 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            $this->categories = $terms;
        }
    }

    public function render(): void {
        if (empty($this->categories)) return;

        // Step 1: Define desired render order
        $render_order = [
            'skin_type',
            'how_to_use',
            'rutine_steps',
        ];

        // Step 2: Prepare content blocks grouped by key
        $blocks_by_key = [
            'skin_type'    => '',
            'how_to_use'   => [],
            'rutine_steps' => [],
        ];

        $categories_reversed = array_reverse($this->categories);

        foreach ($categories_reversed as $category) {
            $meta = get_term_meta($category->term_id);

            $how_to_use   = $meta['bsc__how_to_use'][0]     ?? '';
            $rutine_steps = $meta['bsc__rutine_steps'][0]   ?? '';
            $skin_type    = $meta['bsc__skin_type_root'][0] ?? '';

            if (!empty($skin_type)) {
                $blocks_by_key['skin_type'] .= esc_html($skin_type) . ', ';
            }

            if (!empty($how_to_use)) {
                $blocks_by_key['how_to_use'][] = esc_html($how_to_use);
            }

            if (!empty($rutine_steps)) {
                $blocks_by_key['rutine_steps'][] = esc_html($rutine_steps);
            }
        }

        // Step 3: Start container
        echo '<div class="bsc__product-details-categories">';

        // Step 4: Render each block in custom order
        foreach ($render_order as $key) {
            switch ($key) {
                case 'skin_type':
                    if (!empty($blocks_by_key['skin_type'])) {
                        $skin_html = rtrim($blocks_by_key['skin_type'], ', ') . '.';
                        echo '<details class="bsc__product-detail-category">';
                        echo '<summary>Tipo de Piel</summary>';
                        echo '<p>Apto para ' . $skin_html . '</p>';
                        echo '</details>';
                    }
                    break;

                case 'how_to_use':
                    foreach ($blocks_by_key['how_to_use'] as $html) {
                        echo '<details class="bsc__product-detail-category">';
                        echo '<summary>¿Cómo usar?</summary>';
                        echo '<p>' . $html . '</p>';
                        echo '</details>';
                    }
                    break;

                case 'rutine_steps':
                    foreach ($blocks_by_key['rutine_steps'] as $html) {
                        echo '<details class="bsc__product-detail-category">';
                        echo '<summary>Pasos de la Rutina Coreana</summary>';
                        echo '<p>' . $html . '</p>';
                        echo '</details>';
                    }
                    break;
            }
        }

        // Step 5: End container
        echo '<i class="icon-chevron-up"></i>';
        echo '</div>';
    }
}

?>
