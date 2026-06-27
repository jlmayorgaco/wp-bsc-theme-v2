<?php

class BSC_Products_Card {
	private $id;
	private $title;
	private $price;
	private $raw_price = 0.0;
	private $regular_price;
	private $sale_price;
	private $stock_status;
	private $sku = '';
	private $image;
	private $image_id   = 0;
	private $categories = array();
	private $link;
	private $rating              = 0;
	private $brand               = '';
	private $type                = 'simple';
	private $is_purchasable      = false;
	private $is_in_stock         = false;
	private $image_loading       = 'lazy';
	private $image_fetchpriority = '';

	public function setProduct( WC_Product $product, ?array $terms = null ): void {
		$this->id            = $product->get_id();
		$this->title         = get_the_title( $product->get_id() );
		$this->price         = $product->get_price_html();
		$this->raw_price     = (float) wc_get_price_to_display( $product );
		$this->sku           = (string) $product->get_sku();
		$this->regular_price = wc_price( $product->get_regular_price() );
		$this->sale_price    = wc_price( $product->get_sale_price() );
		$this->stock_status  = $product->get_stock_status();

		$this->image_id  = (int) $product->get_image_id();
		$image_data      = wp_get_attachment_image_src( $this->image_id, 'woocommerce_thumbnail' );
		$img_placeholder = esc_url( get_stylesheet_directory_uri() ) . '/images/bsc__placeholder_product.jpg';
		$this->image     = is_array( $image_data ) ? $image_data[0] : $img_placeholder . '?query_photo_index=0';

		$this->link           = get_permalink( $product->get_id() );
		$this->rating         = (float) $product->get_average_rating();
		$this->type           = $product->get_type();
		$this->is_purchasable = $product->is_purchasable();
		$this->is_in_stock    = $product->is_in_stock();

		if ( null === $terms ) {
			$terms = get_the_terms( $product->get_id(), 'product_cat' );
		}

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			$terms = array();
		}

		$this->setCategoryTerms( $terms );
	}

	private function setCategoryTerms( array $terms ): void {
		$this->categories = array();

		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$this->categories[] = $term->name;
			}
		}

		$this->brand = $this->getProductBrand( $terms );
	}

	private function getProductBrand( array $terms ): string {
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term && strpos( $term->slug, '-marca' ) !== false ) {
				return $term->name;
			}
		}

		return 'Sin marca';
	}

	public function setImagePriority( bool $is_priority ): void {
		$this->image_loading       = $is_priority ? 'eager' : 'lazy';
		$this->image_fetchpriority = $is_priority ? 'high' : '';
	}

	public function render_images(): void {
		$image_attrs = array(
			'class'    => 'card__image',
			'alt'      => $this->title,
			'loading'  => $this->image_loading,
			'decoding' => $this->image_loading === 'eager' ? 'sync' : 'async',
			'sizes'    => '(max-width: 767px) calc(100vw - 76px), 200px',
		);

		if ( $this->image_fetchpriority !== '' ) {
			$image_attrs['fetchpriority'] = $this->image_fetchpriority;
		}

		if ( $this->image_id > 0 ) {
			echo wp_get_attachment_image(
				$this->image_id,
				'woocommerce_thumbnail',
				false,
				$image_attrs
			);
			return;
		}

		echo '<img class="card__image" src="' . esc_url( $this->image ) . '" alt="' . esc_attr( $this->title ) . '" width="300" height="300" loading="' . esc_attr( $this->image_loading ) . '" decoding="' . esc_attr( $this->image_loading === 'eager' ? 'sync' : 'async' ) . '"' . ( $this->image_fetchpriority !== '' ? ' fetchpriority="' . esc_attr( $this->image_fetchpriority ) . '"' : '' ) . ' />';
	}

	public function render_rating(): void {
		$rating = ( $this->rating > 0 ) ? min( 5, (float) $this->rating ) : 5;
		$full   = (int) floor( $rating );
		$empty  = 5 - $full;

		for ( $i = 0; $i < $full; $i++ ) {
			echo '<i class="star full-star fas fa-heart bsc__heart-icon-rating" aria-hidden="true"></i>';
		}

		for ( $i = 0; $i < $empty; $i++ ) {
			echo '<i class="star empty-star far fa-heart bsc__heart-icon-rating" aria-hidden="true"></i>';
		}
	}

	public function render_title(): void {
		echo '<a href="' . esc_url( $this->link ) . '">' . esc_html( $this->title ) . '</a>';
	}

	public function render_brand(): void {
		if ( ! empty( $this->brand ) ) {
			echo '<span class="card__brand-name">' . esc_html( $this->brand ) . '</span>';
			return;
		}

		if ( ! empty( $this->categories ) ) {
			echo '<span class="card__brand-name">' . esc_html( $this->categories[0] ) . '</span>';
		}
	}

	public function render_price(): void {
		echo wp_kses_post( $this->price );
	}

	public function render_button( string $label = '', bool $force_direct_add = false ): void {
		if ( $label === '' ) {
			$label = html_entity_decode( '&#161;Lo quiero!', ENT_QUOTES, 'UTF-8' );
		}

		$product_id = $this->id;
		$has_bsc_options = function_exists( 'bsc_product_has_public_variant_options' )
			&& bsc_product_has_public_variant_options( (int) $product_id );

		if ( $this->type === 'variable' || ( $has_bsc_options && ! $force_direct_add ) ) {
			echo '<a href="' . esc_url( $this->link ) . '" class="bsc__button bsc__button--product-card bsc__button-add-to-cart--variable" aria-label="Ver opciones del producto">';
			echo '<span>Ver opciones</span>';
			echo '</a>';
			return;
		}

		$formatted_price = wc_format_decimal( $this->raw_price, wc_get_price_decimals() );
		$quantity = self::getCartQuantityForProduct( (int) $product_id );
		$in_cart  = $quantity > 0 && ! $has_bsc_options;

		if ( $in_cart ) {
			echo '<button
                type="button"
                class="bsc__button bsc__button--product-card bsc__button-add-to-cart bsc__button-add-to-cart--hidden"
                data-quantity="1"
                data-product_id="' . esc_attr( $product_id ) . '"
                data-product_sku="' . esc_attr( $this->sku ) . '"
                data-product_name="' . esc_attr( $this->title ) . '"
                data-product_price="' . esc_attr( $formatted_price ) . '"
                data-product_brand="' . esc_attr( $this->brand ) . '"
                aria-label="' . esc_attr( $label ) . '"
            ><span>' . esc_html( $label ) . '</span></button>';

			echo '<div class="bsc__quantity-controls" data-min="-1" data-product_id="' . esc_attr( $product_id ) . '">';
			echo '<button class="bsc__qty-minus">&minus;</button>';
			echo '<span class="bsc__qty-value">' . esc_html( $quantity ) . '</span>';
			echo '<button class="bsc__qty-plus">+</button>';
			echo '</div>';
			return;
		}

		if ( ! $this->is_purchasable || ! $this->is_in_stock ) {
			echo '<button
                type="button"
                class="bsc__button bsc__button--product-card bsc__button-add-to-cart"
                disabled
                aria-disabled="true"
                aria-label="Producto agotado"
            ><span>Agotado</span></button>';
			return;
		}

		echo '<button
            type="button"
            class="bsc__button bsc__button--product-card bsc__button-add-to-cart"
            data-quantity="1"
            data-product_id="' . esc_attr( $product_id ) . '"
            data-product_sku="' . esc_attr( $this->sku ) . '"
            data-product_name="' . esc_attr( $this->title ) . '"
            data-product_price="' . esc_attr( $formatted_price ) . '"
            data-product_brand="' . esc_attr( $this->brand ) . '"
            aria-label="' . esc_attr( $label ) . '"
        ><span>' . esc_html( $label ) . '</span></button>';
	}

	private static function getCartQuantityForProduct( int $product_id ): int {
		static $cart_quantities = null;

		if ( null === $cart_quantities ) {
			$cart_quantities = array();

			if ( function_exists( 'WC' ) && WC()->cart ) {
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					$cart_product_id = (int) ( $cart_item['product_id'] ?? 0 );
					if ( $cart_product_id <= 0 ) {
						continue;
					}

					$cart_quantities[ $cart_product_id ] = (int) ( $cart_quantities[ $cart_product_id ] ?? 0 ) + (int) ( $cart_item['quantity'] ?? 0 );
				}
			}
		}

		return (int) ( $cart_quantities[ $product_id ] ?? 0 );
	}

	public function render(): void {
		echo '<div class="bsc__product-card">';

		echo '<a class="card__images" href="' . esc_url( $this->link ) . '">';
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
