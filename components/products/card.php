<?php

class BSC_Products_Card {
    private $id;
    private $title;
    private $price;
    private $regular_price;
    private $sale_price;
    private $stock_status;
    private $image;
    private $categories = [];
    private $link;
    private $rating = 0;
    private $brand = '';

    public function setProduct(WC_Product $product): void {
        $this->id            = $product->get_id();
        $this->title         = get_the_title($product->get_id());
        $this->price         = $product->get_price_html();
        $this->regular_price = wc_price($product->get_regular_price());
        $this->sale_price    = wc_price($product->get_sale_price());
        $this->stock_status  = $product->get_stock_status();
        $this->image = wp_get_attachment_image_src($product->get_image_id(), 'woocommerce_single')[0];
        $this->link          = get_permalink($product->get_id());
        $this->rating        = (float) $product->get_average_rating();

        // Set categories
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            $this->categories = array_map(fn($term) => $term->name, $terms);
        }

        // Brand
        // Get if product belongs to any "group" category
        $this->brand = $this->getProductBrand($product);

    }

    private function getProductBrand($product) {
        $brand = get_the_terms($product->get_id(), 'product_brand');
        return ($brand && !is_wp_error($brand)) ? $brand[0]->name : '_';
    }

    public function render_images(): void {
       echo '<img class="card__image" src="' . esc_url($this->image) . '" alt="' . esc_attr($this->title) . '" />';
    }

    public function render_rating(): void {
        
        $rating = 3;
        //$rating = $this->rating;
        
        $full = floor($rating);
        $empty = 5 - $full;
        $img_heart_full = 'http://bsc.local/wp-content/plugins/wp-bsc-plugin-v1/assets/images/2.png';
        $img_heart_empty = 'http://bsc.local/wp-content/plugins/wp-bsc-plugin-v1/assets/images/1.png';

        echo '';
        for ($i = 0; $i < $full; $i++) {
            echo '<i class="star full-star"><img decoding="async" class="bsc__heart-icon-rating" src="' . esc_url($img_heart_full) . '"></i>';
        }
        for ($i = 0; $i < $empty; $i++) {
            echo '<i class="star empty-star"><img decoding="async" class="bsc__heart-icon-rating" src="' . esc_url($img_heart_empty) . '"></i>';
        }
        echo '';
    }

    public function render_title(): void {
        echo '<a href="' . esc_url($this->link) . '">' . esc_html($this->title) . '</a>';
    }

    public function render_brand(): void {
        if (!empty($this->brand)) {
            echo '<span class="card__brand-name">' . esc_html($this->brand) . '</span>';
        } elseif (!empty($this->categories)) {
            // Fallback: show first category
            echo '<span class="card__brand-name">' . esc_html($this->categories[0]) . '</span>';
        }
    }

    public function render_price(): void {
        echo $this->price;
    }

    public function render_button(): void {
        echo '<a 
            href="?add-to-cart=' . esc_attr($this->id) . '" 
            class="bsc__button-add-to-cart ajax_add_to_cart" 
            data-quantity="1" 
            data-product_id="' . esc_attr($this->id) . '" 
            data-product_sku="" 
            aria-label="Agregar este producto al carrito"
            rel="nofollow"
        >';
        echo '<span>Agregar</span>';
        echo '</a>';
    }

    public function render(): void {
        echo '<div class="bsc__product-card">';

            echo '<div class="card__images">';
                $this->render_images();
            echo '</div>';

            echo '<div class="card__rating">';
                $this->render_rating();
            echo '</div>';

            echo '<div class="card__title">';
                $this->render_title();
            echo '</div>';

            echo '<div class="card__brand">';
                $this->render_brand();
            echo '</div>';

            echo '<div class="card__price">';
                $this->render_price();
            echo '</div>';

            echo '<div class="card__button button--add-to-cart">';
                $this->render_button();
            echo '</div>';

        echo '</div>';
    }
}

?>