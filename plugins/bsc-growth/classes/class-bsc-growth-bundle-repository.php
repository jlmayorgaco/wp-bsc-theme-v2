<?php
/**
 * Data access for routine bundles.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Bundle_Repository {
	public const POST_TYPE        = 'bsc_routine_bundle';
	public const META_PRODUCT_IDS = '_bsc_bundle_product_ids';
	public const META_NEEDS       = '_bsc_bundle_needs';
	public const META_SKIN_TYPES  = '_bsc_bundle_skin_types';
	public const META_BADGE       = '_bsc_bundle_badge';
	public const META_DISCOUNT    = '_bsc_bundle_discount_label';
	public const META_SEARCH      = '_bsc_bundle_search_terms';

	public function get_bundles( int $limit = 6 ): array {
		$bundles = $this->get_custom_bundles( $limit );

		if ( ! empty( $bundles ) ) {
			return $bundles;
		}

		return array_slice( $this->get_default_bundles(), 0, $limit );
	}

	public function find_bundle( string $bundle_id ): ?array {
		if ( str_starts_with( $bundle_id, 'post-' ) ) {
			$post_id = (int) substr( $bundle_id, 5 );

			return $this->get_custom_bundle_by_id( $post_id );
		}

		foreach ( $this->get_default_bundles() as $bundle ) {
			if ( $bundle['id'] === $bundle_id ) {
				return $bundle;
			}
		}

		return null;
	}

	public function recommend_bundles( array $answers, int $limit = 3 ): array {
		$skin_type = sanitize_key( (string) ( $answers['skin_type'] ?? '' ) );
		$needs     = array_map( 'sanitize_key', (array) ( $answers['needs'] ?? array() ) );
		$bundles   = $this->get_bundles( 12 );

		foreach ( $bundles as $index => $bundle ) {
			$score = 0;

			if ( $skin_type && in_array( $skin_type, $bundle['skin_types'], true ) ) {
				$score += 4;
			}

			$shared_needs = array_intersect( $needs, $bundle['needs'] );
			$score       += count( $shared_needs ) * 3;

			if ( empty( $shared_needs ) && ! empty( $bundle['needs'] ) ) {
				$score += 1;
			}

			$bundles[ $index ]['score'] = $score;
		}

		usort(
			$bundles,
			static fn( array $a, array $b ): int => ( $b['score'] <=> $a['score'] ) ?: strnatcasecmp( $a['title'], $b['title'] )
		);

		return array_slice( $bundles, 0, $limit );
	}

	public function get_bundle_cards( array $bundle ): array {
		$cards = array();

		foreach ( $bundle['product_ids'] as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product || 'publish' !== $product->get_status() ) {
				continue;
			}

			$cards[] = $this->product_to_card( $product );
		}

		return $cards;
	}

	public function product_to_card( WC_Product $product ): array {
		$product_id = $product->get_id();
		$image_url  = $this->product_image_url( $product );
		$brand      = '';
		$terms      = get_the_terms( $product_id, 'product_cat' );
		$price_raw  = (float) $product->get_price();

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( str_contains( $term->slug, '-marca' ) ) {
					$brand = $term->name;
					break;
				}
			}
		}

		return array(
			'id'          => $product_id,
			'name'        => wp_strip_all_tags( $product->get_name() ),
			'permalink'   => esc_url_raw( get_permalink( $product_id ) ),
			'image'       => esc_url_raw( $image_url ? $image_url : BSC_Growth_Plugin::placeholder_image() ),
			'brand'       => wp_strip_all_tags( $brand ),
			'price'       => wp_strip_all_tags( $product->get_price_html() ),
			'price_html'  => wp_kses_post( $product->get_price_html() ),
			'price_raw'   => $price_raw,
			'stock_status' => $product->is_in_stock() ? 'instock' : 'outofstock',
			'stock_label' => $product->is_in_stock() ? 'Disponible' : 'Agotado',
			'is_addable'  => $product->is_purchasable() && $product->is_in_stock(),
			'add_to_cart' => esc_url_raw( $product->add_to_cart_url() ),
		);
	}

	public function format_bundle_for_response( array $bundle ): array {
		$products    = $this->get_bundle_cards( $bundle );
		$addable_ids = array_values(
			array_map(
				'absint',
				array_column(
					array_filter(
						$products,
						static fn( array $product ): bool => ! empty( $product['is_addable'] )
					),
					'id'
				)
			)
		);

		return array(
			'id'             => $bundle['id'],
			'title'          => $bundle['title'],
			'summary'        => $bundle['summary'],
			'badge'          => $bundle['badge'],
			'discount_label' => $bundle['discount_label'],
			'url'            => $bundle['url'],
			'product_count'  => count( $addable_ids ),
			'product_ids'    => $addable_ids,
			'products'       => $products,
			'total_raw'      => $this->products_total( $products ),
			'total_html'     => BSC_Growth_Plugin::price_html( $this->products_total( $products ) ),
		);
	}

	public function format_dynamic_bundle_for_response( array $bundle ): array {
		$product_ids = array_values( array_map( 'absint', (array) ( $bundle['product_ids'] ?? array() ) ) );
		$products    = array();
		$addable_ids = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product || 'publish' !== $product->get_status() ) {
				continue;
			}

			if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				$replacement = $this->find_replacement_product( $product );

				if ( $replacement instanceof WC_Product ) {
					$card                            = $this->product_to_card( $replacement );
					$card['replaces_product_id']     = $product->get_id();
					$card['replacement_label']       = 'Reemplazo disponible';
					$products[]                      = $card;
					$addable_ids[]                   = $replacement->get_id();
					continue;
				}
			}

			$card       = $this->product_to_card( $product );
			$products[] = $card;

			if ( ! empty( $card['is_addable'] ) ) {
				$addable_ids[] = (int) $card['id'];
			}
		}

		return array(
			'id'             => (string) ( $bundle['id'] ?? 'dynamic-routine' ),
			'title'          => (string) ( $bundle['title'] ?? 'Rutina recomendada' ),
			'summary'        => (string) ( $bundle['summary'] ?? '' ),
			'badge'          => (string) ( $bundle['badge'] ?? 'Recomendacion' ),
			'discount_label' => (string) ( $bundle['discount_label'] ?? 'Carrito listo' ),
			'url'            => home_url( '/skin-quiz/' ),
			'product_count'  => count( $addable_ids ),
			'product_ids'    => $addable_ids,
			'products'       => $products,
			'total_raw'      => $this->products_total( $products ),
			'total_html'     => BSC_Growth_Plugin::price_html( $this->products_total( $products ) ),
			'steps'          => array_values( (array) ( $bundle['steps'] ?? array() ) ),
		);
	}

	private function product_image_url( WC_Product $product ): string {
		$image_id = $product->get_image_id();

		if ( ! $image_id && $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			if ( $parent instanceof WC_Product ) {
				$image_id = $parent->get_image_id();
				if ( ! $image_id ) {
					$parent_gallery = $parent->get_gallery_image_ids();
					$image_id       = ! empty( $parent_gallery ) ? (int) $parent_gallery[0] : 0;
				}
			}
		}

		if ( ! $image_id ) {
			$gallery = $product->get_gallery_image_ids();
			$image_id = ! empty( $gallery ) ? (int) $gallery[0] : 0;
		}

		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : '';

		return is_string( $image_url ) ? $image_url : '';
	}

	private function find_replacement_product( WC_Product $product ): ?WC_Product {
		$terms = get_the_terms( $product->get_id(), 'product_cat' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}

		$category_slugs = array_values(
			array_filter(
				array_map(
					static fn( WP_Term $term ): string => $term->slug,
					$terms
				)
			)
		);

		if ( empty( $category_slugs ) ) {
			return null;
		}

		$candidates = BSC_Growth_Plugin::products(
			array(
				'limit'        => 1,
				'status'       => 'publish',
				'stock_status' => 'instock',
				'exclude'      => array( $product->get_id() ),
				'category'     => $category_slugs,
				'orderby'      => 'popularity',
				'return'       => 'objects',
			)
		);

		$replacement = $candidates[0] ?? null;

		return $replacement instanceof WC_Product && $replacement->is_purchasable() ? $replacement : null;
	}

	private function products_total( array $products ): float {
		return array_reduce(
			$products,
			static fn( float $carry, array $product ): float => $carry + (float) ( $product['price_raw'] ?? 0 ),
			0.0
		);
	}

	public function get_catalog_context( int $limit = 80 ): array {
		$products = BSC_Growth_Plugin::products(
			array(
				'limit'        => $limit,
				'status'       => 'publish',
				'stock_status' => 'instock',
				'orderby'      => 'popularity',
				'return'       => 'objects',
			)
		);
		$catalog = array();

		foreach ( $products as $product ) {
			if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
				continue;
			}

			$product_id = $product->get_id();
			$terms      = get_the_terms( $product_id, 'product_cat' );
			$categories = array();

			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$categories[] = $term->name;
				}
			}

			$catalog[] = array(
				'id'         => $product_id,
				'name'       => wp_strip_all_tags( $product->get_name() ),
				'price'      => wp_strip_all_tags( $product->get_price_html() ),
				'categories' => array_slice( $categories, 0, 8 ),
			);
		}

		return $catalog;
	}

	public function get_need_catalog(): array {
		return array(
			'acne'            => array(
				'label' => 'Acne',
				'url'   => home_url( '/product-category/group-skin-care/' ),
			),
			'manchas'         => array(
				'label' => 'Manchas',
				'url'   => home_url( '/product-category/group-skin-care/' ),
			),
			'hidratacion'     => array(
				'label' => 'Hidratacion',
				'url'   => home_url( '/product-category/group-skin-care/' ),
			),
			'protector-solar' => array(
				'label' => 'Protector solar',
				'url'   => home_url( '/product-category/group-sun-care/' ),
			),
			'barrera'         => array(
				'label' => 'Barrera',
				'url'   => home_url( '/product-category/group-skin-care/' ),
			),
			'glow'            => array(
				'label' => 'Glow coreano',
				'url'   => home_url( '/skin-quiz/' ),
			),
		);
	}

	private function get_custom_bundles( int $limit ): array {
		$posts = get_posts(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'orderby'                => array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				),
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		return array_values(
			array_filter(
				array_map(
					fn( WP_Post $post ): array => $this->post_to_bundle( $post ),
					$posts
				)
			)
		);
	}

	private function get_custom_bundle_by_id( int $post_id ): ?array {
		if ( $post_id <= 0 ) {
			return null;
		}

		$post = get_post( $post_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		return $this->post_to_bundle( $post );
	}

	private function post_to_bundle( WP_Post $post ): array {
		$product_ids = wp_parse_id_list( (string) get_post_meta( $post->ID, self::META_PRODUCT_IDS, true ) );
		$search      = $this->split_list_meta( (string) get_post_meta( $post->ID, self::META_SEARCH, true ) );
		$product_ids = $this->resolve_product_ids( $product_ids, $search );

		return array(
			'id'             => 'post-' . $post->ID,
			'post_id'        => $post->ID,
			'source'         => 'custom',
			'title'          => get_the_title( $post ),
			'summary'        => wp_trim_words( wp_strip_all_tags( $post->post_content ), 24, '...' ),
			'badge'          => (string) get_post_meta( $post->ID, self::META_BADGE, true ),
			'discount_label' => (string) get_post_meta( $post->ID, self::META_DISCOUNT, true ),
			'needs'          => $this->normalize_keys( $this->split_list_meta( (string) get_post_meta( $post->ID, self::META_NEEDS, true ) ) ),
			'skin_types'     => $this->normalize_keys( $this->split_list_meta( (string) get_post_meta( $post->ID, self::META_SKIN_TYPES, true ) ) ),
			'search_terms'   => $search,
			'product_ids'    => $product_ids,
			'url'            => add_query_arg( 'routine', 'post-' . $post->ID, home_url( '/skin-quiz/' ) ),
		);
	}

	private function get_default_bundles(): array {
		$defaults = array(
			array(
				'id'             => 'rutina-piel-grasa',
				'title'          => 'Rutina piel grasa',
				'summary'        => 'Limpieza, balance y protector solar con foco en brillo, poros y brotes.',
				'badge'          => 'Control brillo',
				'discount_label' => 'Kit sugerido',
				'needs'          => array( 'acne', 'poros', 'sebo' ),
				'skin_types'     => array( 'grasa', 'mixta' ),
				'search_terms'   => array( 'limpiador', 'toner', 'protector solar' ),
			),
			array(
				'id'             => 'kit-manchas',
				'title'          => 'Kit manchas',
				'summary'        => 'Rutina enfocada en tono uniforme, luminosidad y constancia diaria.',
				'badge'          => 'Tono uniforme',
				'discount_label' => 'Rutina guiada',
				'needs'          => array( 'manchas', 'glow' ),
				'skin_types'     => array( 'normal', 'seca', 'mixta', 'grasa' ),
				'search_terms'   => array( 'vitamina', 'rice', 'protector solar' ),
			),
			array(
				'id'             => 'glow-coreano',
				'title'          => 'Glow coreano',
				'summary'        => 'Hidratacion por capas para piel jugosa, suave y luminosa.',
				'badge'          => 'Glow diario',
				'discount_label' => 'Core BSC',
				'needs'          => array( 'hidratacion', 'glow', 'barrera' ),
				'skin_types'     => array( 'seca', 'normal', 'mixta', 'sensible' ),
				'search_terms'   => array( 'essence', 'serum', 'cream' ),
			),
		);

		return array_map(
			function ( array $bundle ): array {
				$bundle['source']      = 'default';
				$bundle['post_id']     = 0;
				$bundle['product_ids'] = $this->resolve_product_ids( array(), $bundle['search_terms'] );
				$bundle['url']         = add_query_arg( 'routine', $bundle['id'], home_url( '/skin-quiz/' ) );

				return $bundle;
			},
			$defaults
		);
	}

	private function resolve_product_ids( array $product_ids, array $search_terms ): array {
		$product_ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );

		if ( count( $product_ids ) >= 3 ) {
			return array_slice( $product_ids, 0, 6 );
		}

		$cache_key = 'bsc_growth_bundle_products_' . md5( wp_json_encode( array( $product_ids, $search_terms ) ) );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		foreach ( $search_terms as $term ) {
			if ( count( $product_ids ) >= 4 ) {
				break;
			}

			$query = new WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					's'                      => sanitize_text_field( $term ),
					'fields'                 => 'ids',
					'posts_per_page'         => 2,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			foreach ( $query->posts as $found_id ) {
				$product = wc_get_product( (int) $found_id );

				if ( $product && $product->is_purchasable() && $product->is_in_stock() ) {
					$product_ids[] = (int) $found_id;
				}
			}

			$product_ids = array_values( array_unique( $product_ids ) );
		}

		$product_ids = array_slice( $product_ids, 0, 6 );
		set_transient( $cache_key, $product_ids, 30 * MINUTE_IN_SECONDS );

		return $product_ids;
	}

	private function split_list_meta( string $value ): array {
		if ( '' === trim( $value ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					static fn( string $item ): string => trim( $item ),
					preg_split( '/[\n,]+/', $value ) ?: array()
				)
			)
		);
	}

	private function normalize_keys( array $values ): array {
		return array_values(
			array_unique(
				array_map(
					static fn( string $value ): string => sanitize_key( remove_accents( strtolower( $value ) ) ),
					$values
				)
			)
		);
	}
}
