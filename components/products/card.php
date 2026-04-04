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
    private $type  = 'simple';

    public function setProduct(WC_Product $product): void {
        $this->id            = $product->get_id();
        $this->title         = get_the_title($product->get_id());
        $this->price         = $product->get_price_html();
        $this->regular_price = wc_price($product->get_regular_price());
        $this->sale_price    = wc_price($product->get_sale_price());
        $this->stock_status  = $product->get_stock_status();
        
        $image_data = wp_get_attachment_image_src($product->get_image_id(), 'woocommerce_single');
        $img_placeholder = esc_url(get_stylesheet_directory_uri()) . '/images/bsc__placeholder_product.jpg';
        $this->image = is_array($image_data) ? $image_data[0] : $img_placeholder . '?query_photo_index=0';

        $this->link          = get_permalink($product->get_id());
        $this->rating        = (float) $product->get_average_rating();
        $this->type          = $product->get_type();

        // Set categories — fetch ONCE and reuse for brand lookup
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            $this->categories = array_map(fn($term) => $term->name, $terms);
        }

        // Brand — pass already-fetched terms to avoid a second DB query
        $this->brand = $this->getProductBrand($terms ?: []);

    }

    private function getProductBrand(array $terms): string {
        foreach ($terms as $term) {
            if ($term instanceof WP_Term && strpos($term->slug, '-marca') !== false) {
                return $term->name;
            }
        }
        return 'Sin marca';
    }

    public function render_images(): void {
        echo '<img'
            . ' class="card__image"'
            . ' src="' . esc_url($this->image) . '"'
            . ' alt="' . esc_attr($this->title) . '"'
            . ' loading="lazy"'
            . ' decoding="async"'
            . ' width="300"'
            . ' height="300"'
            . ' />';
    }

    public function render_rating(): void {
        // Use real rating clamped 0–5; fallback to 5 when no ratings yet
        $rating = ($this->rating > 0) ? min(5, (float) $this->rating) : 5;
        $full   = (int) floor($rating);
        $empty  = 5 - $full;

        // Use Font Awesome icons (already loaded globally) — avoids external HTTP requests
        for ($i = 0; $i < $full; $i++) {
            echo '<i class="star full-star fas fa-heart bsc__heart-icon-rating" aria-hidden="true"></i>';
        }
        for ($i = 0; $i < $empty; $i++) {
            echo '<i class="star empty-star far fa-heart bsc__heart-icon-rating" aria-hidden="true"></i>';
        }
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

    public function render_button(string $label = '¡Lo quiero!'): void {
        $product_id = $this->id;

        // Variable products cannot be added to cart without selecting options — redirect to product page
        if ( $this->type === 'variable' ) {
            echo '<a href="' . esc_url($this->link) . '" class="bsc__button-add-to-cart bsc__button-add-to-cart--variable" aria-label="Ver opciones del producto">';
            echo '<span>Ver opciones</span>';
            echo '</a>';
            return;
        }

        $in_cart  = false;
        $quantity = 0;

        // Check if product is in the cart
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ((int)$cart_item['product_id'] === (int)$product_id) {
                $in_cart  = true;
                $quantity = $cart_item['quantity'];
                break;
            }
        }

        if ($in_cart) {
            // BSC-003: render the add-to-cart button hidden so JS can show it when qty reaches 0
            echo '<a
                href="?add-to-cart=' . esc_attr($product_id) . '"
                class="bsc__button-add-to-cart"
                style="display:none"
                data-quantity="1"
                data-product_id="' . esc_attr($product_id) . '"
                data-product_sku=""
                aria-label="' . esc_attr($label) . '"
                rel="nofollow"
            ><span>' . esc_html($label) . '</span></a>';

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
                class="bsc__button-add-to-cart"
                data-quantity="1"
                data-product_id="' . esc_attr($product_id) . '"
                data-product_sku=""
                aria-label="' . esc_attr($label) . '"
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