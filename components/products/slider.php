<?php

	$categories = array(
		'ultimos_lanzamientos' => 'Últimos Lanzamientos',
		'piel_seca'            => 'Piel Seca',
		'piel_normal'          => 'Piel Normal',
		'piel_mixta'           => 'Piel Mixta',
		'piel_grasa'           => 'Piel Grasa',
		'hair_care'            => 'Hair Care',
		'maquillaje'           => 'Maquillaje',
	);
	class BSC_Products_Sliders {

		private $skus         = array();
		private $label        = '';
		private $slug         = '';
		private $max_products = 5;

		public function setMax( $max_products ) {
			$this->max_products = $max_products;
		}

		public function setSkus( array $skus ): void {
			// Clean and normalize saved product tokens.
			$this->skus = array_filter(
				array_map(
					function ( $token ) {
						return trim( (string) $token );
					},
					$skus
				)
			);
		}

		public function setLabel( string $label ): void {
			$this->label = esc_html( $label );
		}

		public function setSlug( string $slug ): void {
			$this->slug = sanitize_title( $slug );
		}

		public function render(): void {
			static $slider_instance = 0;

			// Convert saved product tokens (legacy SKUs or product IDs) to eligible IDs.
			$product_ids = array_values(
				array_filter(
					array_map( array( $this, 'resolveProductTokenToId' ), $this->skus ),
					fn( $product_id ) => $this->isEligibleProductId( (int) $product_id )
				)
			);
			$needed      = $this->max_products - count( $product_ids );

			if ($needed > 0) {
				$fallback_ids = $this->getFallbackProductIds( $needed, $product_ids );
				$product_ids  = array_unique( array_merge( $product_ids, $fallback_ids ) );
			}

			if (empty( $product_ids )) {
				return;
			}

			$query = new WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'post__in'               => $product_ids,
					'orderby'                => 'post__in',
					'posts_per_page'         => $this->max_products,
					'meta_query'             => array(
						array(
							'key'   => '_stock_status',
							'value' => 'instock',
						),
					),
					'no_found_rows'          => true,     // skip COUNT(*) — no pagination needed in sliders
					'cache_results'          => true,
					'update_post_meta_cache' => true,     // BSC-040: pre-load meta in batch
					'update_post_term_cache' => true,     // BSC-040: pre-load terms in batch
				)
			);

			if (!$query->have_posts()) {
				return;
			}

			$slug_class = $this->slug ? "bsc__slider--{$this->slug}" : '';
			$slider_instance++;
			$slider_id = 'bsc-product-slider-' . $slider_instance;

			if (!empty( $this->label )) {
				echo "<h2 class='bsc__slider-title'>" . esc_html( $this->label ) . '</h2>';
			}
			echo '<div id="' . esc_attr( $slider_id ) . '" class="bsc__slider ' . esc_attr( $slug_class ) . '">';

			while ($query->have_posts()) {
				$query->the_post();
				global $product;

				if ($product instanceof WC_Product && $this->isEligibleProductId( $product->get_id() )) {
					$card = new BSC_Products_Card();
					$card->setProduct( $product );
					$card->render();
				}
			}

			echo '</div>';
			echo '<div class="bsc__slider-progress" role="progressbar" aria-label="Progreso del carrusel de productos" aria-controls="' . esc_attr( $slider_id ) . '" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">';
			echo '<span class="bsc__slider-progress-track"><span class="bsc__slider-progress-thumb"></span></span>';
			echo '</div>';

			wp_reset_postdata();
		}

		private function getFallbackProductIds( int $limit, array $exclude_ids = array() ): array {
			// BSC-039: check transient cache first (1 hour TTL, invalidated on product save)
			$cache_key = 'bsc_slider_v2_' . md5( $this->slug . '_' . $limit . '_' . implode( ',', $exclude_ids ) );
			$cached    = get_transient( $cache_key );
			if ( $cached !== false ) {
				return $cached;
			}

			$args = array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'fields'                 => 'ids',
				'orderby'                => 'date',  // deterministic — avoids MySQL RAND() full-table scan
				'order'                  => 'DESC',
				'post__not_in'           => $exclude_ids,
				'meta_query'             => array(
					array(
						'key'   => '_stock_status',
						'value' => 'instock',
					),
				),
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);

			switch ($this->slug) {
				case 'ultimos_lanzamientos':
					$args['orderby'] = 'date';
					$args['order']   = 'DESC';
					break;

				case 'piel_seca':
				case 'piel_normal':
				case 'piel_mixta':
				case 'piel_grasa':
					$args['tax_query'][] = array(
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => array( 'sk-tipo-' . str_replace( '_', '-', $this->slug ) ),
						'operator' => 'IN',
					);
					break;

				case 'hair_care':
					$args['tax_query'][] = array(
						'taxonomy'         => 'product_cat',
						'field'            => 'slug',
						'terms'            => array( 'group-hair-care' ),
						'include_children' => true,
					);
					break;

				case 'maquillaje':
					$args['tax_query'][] = array(
						'taxonomy'         => 'product_cat',
						'field'            => 'slug',
						'terms'            => array( 'group-make-up' ),
						'include_children' => true,
					);
					break;

				default:
					$args['orderby'] = 'date';
					$args['order']   = 'DESC';
			}

			$ids = $this->queryFallbackProductIds( $args );

			if (empty( $ids ) && $this->slug !== 'ultimos_lanzamientos') {
				unset( $args['tax_query'] );
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				$ids             = $this->queryFallbackProductIds( $args );
			}

			set_transient( $cache_key, $ids, HOUR_IN_SECONDS );

			return $ids;
		}

		private function queryFallbackProductIds( array $args ): array {
			return array_values(
				array_filter(
					array_map( 'absint', get_posts( $args ) ),
					fn( $product_id ) => $this->isEligibleProductId( (int) $product_id )
				)
			);
		}

		private function resolveProductTokenToId( string $token ): int {
			$token = trim( $token );
			if ($token === '') {
				return 0;
			}

			if (ctype_digit( $token )) {
				return absint( $token );
			}

			return (int) wc_get_product_id_by_sku( $token );
		}

		private function isEligibleProductId( int $product_id ): bool {
			if ($product_id <= 0) {
				return false;
			}

			$product = wc_get_product( $product_id );

			if (function_exists( 'bsc_recommendation_product_is_eligible' )) {
				return bsc_recommendation_product_is_eligible( $product );
			}

			return $product instanceof WC_Product
			&& $product->get_status() === 'publish'
			&& $product->is_purchasable()
			&& $product->is_in_stock();
		}
	}
