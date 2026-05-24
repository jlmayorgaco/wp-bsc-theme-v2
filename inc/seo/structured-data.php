<?php
/**
 * Ecommerce SEO helpers and structured data.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

function bsc_seo_get_product_brand( WC_Product $product ): string {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term && false !== strpos( $term->slug, '-marca' ) ) {
				return $term->name;
			}
		}
	}

	$brand = $product->get_attribute( 'brand' );

	return $brand !== '' ? $brand : get_bloginfo( 'name' );
}

function bsc_seo_get_product_image_urls( WC_Product $product ): array {
	$image_ids = array_filter(
		array_merge(
			[ (int) $product->get_image_id() ],
			array_map( 'absint', $product->get_gallery_image_ids() )
		)
	);
	$urls = [];

	foreach ( array_slice( $image_ids, 0, 6 ) as $image_id ) {
		$url = wp_get_attachment_image_url( $image_id, 'full' );
		if ( $url ) {
			$urls[] = esc_url_raw( $url );
		}
	}

	return array_values( array_unique( $urls ) );
}

function bsc_seo_get_availability_url( WC_Product $product ): string {
	if ( $product->is_in_stock() ) {
		return 'https://schema.org/InStock';
	}

	if ( 'onbackorder' === $product->get_stock_status() ) {
		return 'https://schema.org/BackOrder';
	}

	return 'https://schema.org/OutOfStock';
}

function bsc_seo_get_product_schema( WC_Product $product ): array {
	$description = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
	$schema      = [
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'@id'         => get_permalink( $product->get_id() ) . '#product',
		'name'        => wp_strip_all_tags( $product->get_name() ),
		'description' => $description,
		'url'         => get_permalink( $product->get_id() ),
		'sku'         => $product->get_sku() ?: (string) $product->get_id(),
		'image'       => bsc_seo_get_product_image_urls( $product ),
		'brand'       => [
			'@type' => 'Brand',
			'name'  => bsc_seo_get_product_brand( $product ),
		],
		'offers'      => [
			'@type'           => 'Offer',
			'url'             => get_permalink( $product->get_id() ),
			'priceCurrency'   => get_woocommerce_currency(),
			'price'           => wc_format_decimal( wc_get_price_to_display( $product ), wc_get_price_decimals() ),
			'availability'    => bsc_seo_get_availability_url( $product ),
			'itemCondition'   => 'https://schema.org/NewCondition',
			'priceValidUntil' => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
			'seller'          => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			],
		],
	];

	$rating_count = (int) $product->get_rating_count();
	if ( $rating_count > 0 ) {
		$schema['aggregateRating'] = [
			'@type'       => 'AggregateRating',
			'ratingValue' => (string) $product->get_average_rating(),
			'reviewCount' => $rating_count,
		];
	}

	return $schema;
}

function bsc_seo_get_breadcrumb_schema(): array {
	$items = [
		[
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'Inicio',
			'item'     => home_url( '/' ),
		],
	];

	if ( function_exists( 'is_product' ) && is_product() ) {
		$product_id = get_the_ID();
		$terms      = get_the_terms( $product_id, 'product_cat' );
		$position   = 2;

		if ( $terms && ! is_wp_error( $terms ) ) {
			$term = reset( $terms );
			if ( $term instanceof WP_Term ) {
				$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
				foreach ( $ancestors as $ancestor_id ) {
					$ancestor = get_term( $ancestor_id, 'product_cat' );
					if ( $ancestor instanceof WP_Term ) {
						$items[] = [
							'@type'    => 'ListItem',
							'position' => $position++,
							'name'     => $ancestor->name,
							'item'     => get_term_link( $ancestor ),
						];
					}
				}

				$items[] = [
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => $term->name,
					'item'     => get_term_link( $term ),
				];
			}
		}

		$items[] = [
			'@type'    => 'ListItem',
			'position' => $position ?? 2,
			'name'     => get_the_title(),
			'item'     => get_permalink( $product_id ),
		];
	}

	return [
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	];
}

function bsc_seo_get_item_list_schema(): array {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return [];
	}

	$product_ids = [];

	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$product_ids = wc_get_products(
				[
					'limit'    => 12,
					'status'   => 'publish',
					'return'   => 'ids',
					'category' => [ $term->slug ],
				]
			);
		}
	} elseif ( is_shop() ) {
		$product_ids = wc_get_products(
			[
				'limit'  => 12,
				'status' => 'publish',
				'return' => 'ids',
			]
		);
	} elseif ( is_search() ) {
		$query = get_search_query( false );
		if ( $query !== '' ) {
			$product_ids = get_posts(
				[
					'post_type'      => 'product',
					'post_status'    => 'publish',
					's'              => $query,
					'fields'         => 'ids',
					'posts_per_page' => 12,
					'no_found_rows'  => true,
				]
			);
		}
	}

	$item_list = [];
	foreach ( array_values( array_unique( array_map( 'absint', $product_ids ) ) ) as $index => $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$item_list[] = [
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'url'      => get_permalink( $product_id ),
			'name'     => wp_strip_all_tags( $product->get_name() ),
		];
	}

	if ( empty( $item_list ) ) {
		return [];
	}

	return [
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'itemListElement' => $item_list,
	];
}

function bsc_seo_print_json_ld( array $schema ): void {
	if ( empty( $schema ) ) {
		return;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

function bsc_seo_output_structured_data(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( is_product() ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product instanceof WC_Product ) {
			bsc_seo_print_json_ld( bsc_seo_get_product_schema( $product ) );
			bsc_seo_print_json_ld( bsc_seo_get_breadcrumb_schema() );
		}
		return;
	}

	if ( is_shop() || is_product_category() || is_search() ) {
		bsc_seo_print_json_ld( bsc_seo_get_item_list_schema() );
	}
}
add_action( 'wp_head', 'bsc_seo_output_structured_data', 30 );

function bsc_seo_output_product_meta_tags(): void {
	if ( ! is_product() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$product = wc_get_product( get_the_ID() );
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$image_urls  = bsc_seo_get_product_image_urls( $product );
	$description = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );

	printf( '<meta property="og:type" content="product">' . "\n" );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $product->get_name() ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( wp_trim_words( $description, 28 ) ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( get_permalink( $product->get_id() ) ) );
	if ( ! empty( $image_urls[0] ) ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image_urls[0] ) );
	}
}
add_action( 'wp_head', 'bsc_seo_output_product_meta_tags', 20 );
