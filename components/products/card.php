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
        
        $image_data = wp_get_attachment_image_src($product->get_image_id(), 'woocommerce_single');
        $this->image = is_array($image_data) ? $image_data[0] : 'http://bsc.local/wp-content/plugins/wp-bsc-plugin-v1/assets/images/bsc__product_placeholder.jpeg?query_photo_index=0';

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
        $categories = get_the_terms($product->get_id(), 'product_cat');
        $brand_term = null;

        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                if (strpos($category->slug, '-marca') !== false) {
                    $brand_term = $category;
                    break;
                }
            }
        }

        return $brand_term ? $brand_term->name : 'Sin marca';
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

    public function render_button(string $label = 'Agregar'): void {
        $product_id = $this->id;
        $in_cart = false;
        $quantity = 0;

        // Check if product is in the cart
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ((int)$cart_item['product_id'] === (int)$product_id) {
                $in_cart = true;
                $quantity = $cart_item['quantity'];
                break;
            }
        }

        if ($in_cart) {
            // Render quantity controls
            echo '<div class="bsc__quantity-controls" data-min="-1" data-product_id="' . esc_attr($product_id) . '">';
            echo '<button class="bsc__qty-minus">−</button>';
            echo '<span class="bsc__qty-value">' . esc_html($quantity) . '</span>';
            echo '<button class="bsc__qty-plus">+</button>';
            echo '</div>';
        } else {
            // Render add-to-cart button
            echo '<a 
                href="?add-to-cart=' . esc_attr($product_id) . '" 
                class="bsc__button-add-to-cart ajax_add_to_cart" 
                data-quantity="1" 
                data-product_id="' . esc_attr($product_id) . '" 
                data-product_sku="" 
                aria-label="Agregar este producto al carrito"
                rel="nofollow"
            >';
            echo '<span>' . esc_html($label) . '</span>';
            echo '</a>';
        }
    }



    public function render(): void {
        echo '<div class="bsc__product-card">';

            echo '<a class="card__images" href="'.$this->link.'">';
                $this->render_images();
            echo '</a>';

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