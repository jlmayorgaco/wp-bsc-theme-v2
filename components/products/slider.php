<?php

    $categories = [
        'ultimos_lanzamientos' => 'Últimos Lanzamientos',
        'piel_seca' => 'Piel Seca',
        'piel_normal' => 'Piel Normal',
        'piel_mixta' => 'Piel Mixta',
        'piel_grasa' => 'Piel Grasa',
        'hair_care' => 'Hair Care',
        'maquillaje' => 'Maquillaje',
    ];
class BSC_Products_Sliders {

    private $skus = [];
    private $label = '';
    private $slug = '';
    private $max_products = 5;

    public function setMax($max_products){
        $this->max_products = $max_products;
    }

    public function setSkus(array $skus): void {
        // Clean and normalize SKUs
        $this->skus = array_filter(array_map('trim', $skus));
    }

    public function setLabel(string $label): void {
        $this->label = esc_html($label);
    }

    public function setSlug(string $slug): void {
        $this->slug = sanitize_title($slug);
    }

    public function render(): void {

        // Convert SKUs to IDs
        $product_ids = array_filter(array_map('wc_get_product_id_by_sku', $this->skus));
        $needed = $this->max_products - count($product_ids);


        if ($needed > 0) {
            $fallback_ids = $this->getFallbackProductIds($needed, $product_ids);
            $product_ids = array_unique(array_merge($product_ids, $fallback_ids));
        }

       

        if (empty($product_ids)) return;

        $query = new WP_Query([
            'post_type'      => 'product',
            'post__in'       => $product_ids,
            'orderby'        => 'post__in',
            'posts_per_page' => $this->max_products,
        ]);

        if (!$query->have_posts()) return;

        $slug_class = $this->slug ? "bsc__slider--{$this->slug}" : '';

        if (!empty($this->label)) {
            echo "<h2 class='bsc__slider-title'>{$this->label}</h2>";
        }
        echo "<div class='bsc__slider {$slug_class}'>";

        while ($query->have_posts()) {
            $query->the_post();
            global $product;

            if ($product instanceof WC_Product) {
                $card = new BSC_Products_Card();
                $card->setProduct($product);
                $card->render();
            }
        }

        echo "</div>";

        wp_reset_postdata();
    }

    private function getFallbackProductIds(int $limit, array $exclude_ids = []): array {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'fields'         => 'ids',
            'orderby'        => 'rand',
            'post__not_in'   => $exclude_ids,
        ];

        switch ($this->slug) {
            case 'ultimos_lanzamientos':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;

            case 'piel_seca':
            case 'piel_normal':
            case 'piel_mixta':
            case 'piel_grasa':
                $args['tax_query'][] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => ['sk-tipo-' . str_replace('_', '-', $this->slug)],
                    'operator' => 'IN',
                ];
                break;

            case 'hair_care':
                $args['tax_query'][] = [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'slug',
                    'terms'            => ['group-hair-care'],
                    'include_children' => true,
                ];
                break;

            case 'maquillaje':
                $args['tax_query'][] = [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'slug',
                    'terms'            => ['group-make-up'],
                    'include_children' => true,
                ];
                break;

            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }

        return get_posts($args);
    }
}

?>

