<?php
/**
 * Ecommerce SEO helpers and structured data.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the most useful product brand value.
 *
 * @param WC_Product $product Product instance.
 */
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

	return '' !== $brand ? $brand : get_bloginfo( 'name' );
}

/**
 * Return product image URLs for schema.
 *
 * @param WC_Product $product Product instance.
 */
function bsc_seo_get_product_image_urls( WC_Product $product ): array {
	$image_ids = array_filter(
		array_merge(
			array( (int) $product->get_image_id() ),
			array_map( 'absint', $product->get_gallery_image_ids() )
		)
	);
	$urls      = array();

	foreach ( array_slice( $image_ids, 0, 6 ) as $image_id ) {
		$url = wp_get_attachment_image_url( $image_id, 'full' );
		if ( $url ) {
			$urls[] = esc_url_raw( $url );
		}
	}

	return array_values( array_unique( $urls ) );
}

/**
 * Return Schema.org availability URL.
 *
 * @param WC_Product $product Product instance.
 */
function bsc_seo_get_availability_url( WC_Product $product ): string {
	if ( $product->is_in_stock() ) {
		return 'https://schema.org/InStock';
	}

	if ( 'onbackorder' === $product->get_stock_status() ) {
		return 'https://schema.org/BackOrder';
	}

	return 'https://schema.org/OutOfStock';
}

/**
 * Build Product schema for a WooCommerce product.
 *
 * @param WC_Product $product Product instance.
 */
function bsc_seo_get_product_schema( WC_Product $product ): array {
	$current_product = function_exists( 'bsc_seo_get_current_product' ) ? bsc_seo_get_current_product() : null;
	if ( $current_product instanceof WC_Product && $product->get_id() === $current_product->get_id() ) {
		$description = bsc_seo_get_current_description();
	} else {
		$description_source = bsc_seo_get_post_meta_value( $product->get_id(), '_bsc_seo_description' );
		if ( '' === $description_source ) {
			$description_source = $product->get_short_description();
		}
		if ( '' === $description_source ) {
			$description_source = $product->get_description();
		}
		$description = bsc_seo_trim_text( $description_source );
	}

	$identifiers = array(
		'gtin' => '',
		'mpn'  => '',
	);
	if ( function_exists( 'bsc_seo_get_product_identifier' ) ) {
		$identifiers = bsc_seo_get_product_identifier( $product );
	}

	$sku = $product->get_sku();
	if ( '' === $sku ) {
		$sku = (string) $product->get_id();
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'@id'         => get_permalink( $product->get_id() ) . '#product',
		'name'        => wp_strip_all_tags( $product->get_name() ),
		'description' => $description,
		'url'         => get_permalink( $product->get_id() ),
		'sku'         => $sku,
		'image'       => bsc_seo_get_product_image_urls( $product ),
		'brand'       => array(
			'@type' => 'Brand',
			'name'  => bsc_seo_get_product_brand( $product ),
		),
		'offers'      => array(
			'@type'           => 'Offer',
			'url'             => get_permalink( $product->get_id() ),
			'priceCurrency'   => get_woocommerce_currency(),
			'price'           => wc_format_decimal( wc_get_price_to_display( $product ), wc_get_price_decimals() ),
			'availability'    => bsc_seo_get_availability_url( $product ),
			'itemCondition'   => 'https://schema.org/NewCondition',
			'priceValidUntil' => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
			'seller'          => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			),
		),
	);

	if ( ! empty( $identifiers['gtin'] ) ) {
		$schema['gtin'] = $identifiers['gtin'];
	}

	if ( ! empty( $identifiers['mpn'] ) ) {
		$schema['mpn'] = $identifiers['mpn'];
	}

	$rating_count = (int) $product->get_rating_count();
	if ( $rating_count > 0 ) {
		$schema['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => (string) $product->get_average_rating(),
			'reviewCount' => $rating_count,
		);
	}

	return $schema;
}

/**
 * Build breadcrumb schema for products and categories.
 */
function bsc_seo_get_breadcrumb_schema(): array {
	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'Inicio',
			'item'     => home_url( '/' ),
		),
	);

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
						$items[] = array(
							'@type'    => 'ListItem',
							'position' => $position++,
							'name'     => $ancestor->name,
							'item'     => get_term_link( $ancestor ),
						);
					}
				}

				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => $term->name,
					'item'     => get_term_link( $term ),
				);
			}
		}

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position ?? 2,
			'name'     => get_the_title(),
			'item'     => get_permalink( $product_id ),
		);
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term     = get_queried_object();
		$position = 2;

		if ( $term instanceof WP_Term ) {
			$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );
				if ( $ancestor instanceof WP_Term ) {
					$items[] = array(
						'@type'    => 'ListItem',
						'position' => $position++,
						'name'     => $ancestor->name,
						'item'     => get_term_link( $ancestor ),
					);
				}
			}

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => $term->name,
				'item'     => get_term_link( $term ),
			);
		}
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

/**
 * Build ItemList schema for catalog/search pages.
 */
function bsc_seo_get_item_list_schema(): array {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array();
	}

	$product_ids = array();

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$product_ids = wc_get_products(
				array(
					'limit'    => 12,
					'status'   => 'publish',
					'return'   => 'ids',
					'category' => array( $term->slug ),
				)
			);
		}
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$product_ids = wc_get_products(
			array(
				'limit'  => 12,
				'status' => 'publish',
				'return' => 'ids',
			)
		);
	} elseif ( is_search() ) {
		$query = get_search_query( false );
		if ( '' !== $query ) {
			$product_ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					's'              => $query,
					'fields'         => 'ids',
					'posts_per_page' => 12,
					'no_found_rows'  => true,
				)
			);
		}
	}

	$item_list = array();
	foreach ( array_values( array_unique( array_map( 'absint', $product_ids ) ) ) as $index => $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$item_list[] = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'url'      => get_permalink( $product_id ),
			'name'     => wp_strip_all_tags( $product->get_name() ),
		);
	}

	if ( empty( $item_list ) ) {
		return array();
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'itemListElement' => $item_list,
	);
}

/**
 * Build Organization schema for the storefront.
 */
function bsc_seo_get_organization_schema(): array {
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	$same_as = array_filter(
		array(
			esc_url_raw( (string) get_option( 'bsc_seo_instagram_url', '' ) ),
			esc_url_raw( (string) get_option( 'bsc_seo_tiktok_url', '' ) ),
		)
	);

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'@id'      => home_url( '/#organization' ),
		'name'     => (string) get_option( 'bsc_seo_organization_name', get_bloginfo( 'name' ) ),
		'url'      => home_url( '/' ),
	);

	if ( $logo ) {
		$schema['logo'] = esc_url_raw( $logo );
	}

	if ( ! empty( $same_as ) ) {
		$schema['sameAs'] = array_values( $same_as );
	}

	return $schema;
}

/**
 * Build WebSite schema with SearchAction.
 */
function bsc_seo_get_website_schema(): array {
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'@id'             => home_url( '/#website' ),
		'name'            => get_bloginfo( 'name' ),
		'url'             => home_url( '/' ),
		'publisher'       => array(
			'@id' => home_url( '/#organization' ),
		),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => add_query_arg( 's', '{search_term_string}', home_url( '/' ) ),
			'query-input' => 'required name=search_term_string',
		),
	);
}

/**
 * Build FAQ schema for configured product categories.
 */
function bsc_seo_get_faq_schema(): array {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() || ! function_exists( 'bsc_seo_get_term_faqs' ) ) {
		return array();
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return array();
	}

	$faqs = bsc_seo_get_term_faqs( $term->term_id );
	if ( empty( $faqs ) ) {
		return array();
	}

	$entities = array();
	foreach ( $faqs as $faq ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( (string) $faq['question'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( (string) $faq['answer'] ),
			),
		);
	}

	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);
}

/**
 * Output a JSON-LD script tag.
 *
 * @param array $schema Schema payload.
 */
function bsc_seo_print_json_ld( array $schema ): void {
	if ( empty( $schema ) ) {
		return;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/**
 * Output structured data for public storefront pages.
 */
function bsc_seo_output_structured_data(): void {
	if ( is_front_page() || is_home() ) {
		bsc_seo_print_json_ld( bsc_seo_get_organization_schema() );
		bsc_seo_print_json_ld( bsc_seo_get_website_schema() );
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product instanceof WC_Product ) {
			bsc_seo_print_json_ld( bsc_seo_get_product_schema( $product ) );
			bsc_seo_print_json_ld( bsc_seo_get_breadcrumb_schema() );
		}
		return;
	}

	$is_product_category = function_exists( 'is_product_category' ) && is_product_category();

	if ( ( function_exists( 'is_shop' ) && is_shop() ) || $is_product_category || is_search() ) {
		if ( $is_product_category ) {
			bsc_seo_print_json_ld( bsc_seo_get_breadcrumb_schema() );
			bsc_seo_print_json_ld( bsc_seo_get_faq_schema() );
		}
		bsc_seo_print_json_ld( bsc_seo_get_item_list_schema() );
	}
}
add_action( 'wp_head', 'bsc_seo_output_structured_data', 30 );
