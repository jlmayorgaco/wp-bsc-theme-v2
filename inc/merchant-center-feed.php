<?php
/**
 * Google Merchant Center product feed.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

function bsc_merchant_center_feed_url(): string {
	return add_query_arg( 'feed', 'bsc-google-merchant', home_url( '/' ) );
}

function bsc_merchant_center_is_enabled(): bool {
	return (bool) get_option( 'bsc_merchant_feed_enabled', 1 );
}

function bsc_merchant_center_xml( $value ): string {
	return htmlspecialchars( wp_strip_all_tags( (string) $value ), ENT_XML1 | ENT_COMPAT, 'UTF-8' );
}

function bsc_merchant_center_price( WC_Product $product, ?float $override_price = null ): string {
	$price = null === $override_price
		? (float) wc_get_price_to_display( $product )
		: (float) wc_get_price_to_display( $product, array( 'price' => $override_price ) );

	return number_format( max( 0, $price ), 2, '.', '' ) . ' ' . get_woocommerce_currency();
}

function bsc_merchant_center_get_brand( WC_Product $product, ?WC_Product $parent = null ): string {
	if ( function_exists( 'bsc_seo_get_product_brand' ) ) {
		$brand_product = $parent instanceof WC_Product ? $parent : $product;
		$brand         = bsc_seo_get_product_brand( $brand_product );
		if ( '' !== $brand ) {
			return $brand;
		}
	}

	return (string) get_option( 'bsc_merchant_feed_default_brand', get_bloginfo( 'name' ) );
}

function bsc_merchant_center_get_identifier( WC_Product $product ): array {
	$gtin_meta_keys = array( '_global_unique_id', '_gtin', '_wc_gpf_gtin', '_bsc_gtin', 'gtin' );
	$mpn_meta_keys  = array( '_mpn', '_wc_gpf_mpn', '_bsc_mpn', 'mpn' );
	$gtin           = '';
	$mpn            = '';

	if ( method_exists( $product, 'get_global_unique_id' ) ) {
		$gtin = (string) $product->get_global_unique_id();
	}

	foreach ( $gtin_meta_keys as $meta_key ) {
		if ( '' !== $gtin ) {
			break;
		}
		$gtin = (string) get_post_meta( $product->get_id(), $meta_key, true );
	}

	foreach ( $mpn_meta_keys as $meta_key ) {
		if ( '' !== $mpn ) {
			break;
		}
		$mpn = (string) get_post_meta( $product->get_id(), $meta_key, true );
	}

	return array(
		'gtin' => preg_replace( '/[^0-9]/', '', $gtin ),
		'mpn'  => sanitize_text_field( $mpn ),
	);
}

function bsc_merchant_center_get_item_id( WC_Product $product ): string {
	$item_id = $product->get_sku() ?: 'BSC-' . $product->get_id();
	$item_id = preg_replace( '/\s+/', '-', trim( (string) $item_id ) );

	return substr( $item_id, 0, 50 );
}

function bsc_merchant_center_get_description( WC_Product $product, ?WC_Product $parent = null ): string {
	$description = $product->get_short_description() ?: $product->get_description();

	if ( '' === trim( wp_strip_all_tags( $description ) ) && $parent instanceof WC_Product ) {
		$description = $parent->get_short_description() ?: $parent->get_description();
	}

	$description = preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $description ) );

	return wp_trim_words( $description, 120, '' );
}

function bsc_merchant_center_get_title( WC_Product $product, ?WC_Product $parent = null ): string {
	$title = wp_strip_all_tags( $product->get_name() );

	if ( $product instanceof WC_Product_Variation && $parent instanceof WC_Product ) {
		$variation = wp_strip_all_tags( wc_get_formatted_variation( $product, true, false, true ) );
		$title     = wp_strip_all_tags( $parent->get_name() );

		if ( '' !== $variation ) {
			$title .= ' - ' . $variation;
		}
	}

	return wp_trim_words( preg_replace( '/\s+/', ' ', $title ), 22, '' );
}

function bsc_merchant_center_get_image_urls( WC_Product $product, ?WC_Product $parent = null ): array {
	$image_ids = array();

	if ( $product->get_image_id() ) {
		$image_ids[] = (int) $product->get_image_id();
	}

	if ( $parent instanceof WC_Product ) {
		if ( empty( $image_ids ) && $parent->get_image_id() ) {
			$image_ids[] = (int) $parent->get_image_id();
		}

		$image_ids = array_merge( $image_ids, array_map( 'absint', $parent->get_gallery_image_ids() ) );
	} else {
		$image_ids = array_merge( $image_ids, array_map( 'absint', $product->get_gallery_image_ids() ) );
	}

	$urls = array();
	foreach ( array_slice( array_unique( array_filter( $image_ids ) ), 0, 10 ) as $image_id ) {
		$url = wp_get_attachment_image_url( $image_id, 'full' );
		if ( $url ) {
			$urls[] = esc_url_raw( $url );
		}
	}

	return $urls;
}

function bsc_merchant_center_get_availability( WC_Product $product ): string {
	$stock_status = $product->get_stock_status();

	if ( 'instock' === $stock_status ) {
		return 'in_stock';
	}

	if ( 'onbackorder' === $stock_status ) {
		return 'backorder';
	}

	return 'out_of_stock';
}

function bsc_merchant_center_get_product_type( WC_Product $product, ?WC_Product $parent = null ): string {
	$product_id = $parent instanceof WC_Product ? $parent->get_id() : $product->get_id();
	$terms      = get_the_terms( $product_id, 'product_cat' );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}

	$names = array();
	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term && false === strpos( $term->slug, '-marca' ) ) {
			$names[] = $term->name;
		}
	}

	return implode( ' > ', array_slice( array_unique( $names ), 0, 3 ) );
}

function bsc_merchant_center_product_is_feedable( WC_Product $product, ?WC_Product $parent = null ): bool {
	$catalog_product = $parent instanceof WC_Product ? $parent : $product;

	if ( 'hidden' === $catalog_product->get_catalog_visibility() ) {
		return false;
	}

	if ( '' === (string) $product->get_price() ) {
		return false;
	}

	if ( ! $product->is_purchasable() ) {
		return false;
	}

	return ! empty( bsc_merchant_center_get_image_urls( $product, $parent ) );
}

function bsc_merchant_center_write_tag( string $tag, $value ): void {
	$tag   = preg_replace( '/[^a-z0-9:_-]/i', '', $tag );
	$value = trim( (string) $value );
	if ( '' === $tag || '' === $value ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Value is escaped for XML by bsc_merchant_center_xml(); tag name is allowlisted above.
	echo "\t\t\t<" . $tag . '>' . bsc_merchant_center_xml( $value ) . '</' . $tag . ">\n";
}

function bsc_merchant_center_write_url_tag( string $tag, string $url ): void {
	$tag = preg_replace( '/[^a-z0-9:_-]/i', '', $tag );
	$url = esc_url_raw( $url );
	if ( '' === $tag || '' === $url ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL is escaped for XML by bsc_merchant_center_xml(); tag name is allowlisted above.
	echo "\t\t\t<" . $tag . '>' . bsc_merchant_center_xml( $url ) . '</' . $tag . ">\n";
}

function bsc_merchant_center_write_item( WC_Product $product, ?WC_Product $parent = null ): bool {
	if ( ! bsc_merchant_center_product_is_feedable( $product, $parent ) ) {
		return false;
	}

	$images       = bsc_merchant_center_get_image_urls( $product, $parent );
	$identifiers  = bsc_merchant_center_get_identifier( $product );
	$regular      = (float) $product->get_regular_price();
	$sale         = (float) $product->get_sale_price();
	$is_on_sale   = $product->is_on_sale() && $regular > 0 && $sale > 0 && $sale < $regular;
	$link         = $product->get_permalink();
	$brand        = bsc_merchant_center_get_brand( $product, $parent );
	$product_type = bsc_merchant_center_get_product_type( $product, $parent );

	echo "\t\t<item>\n";
	bsc_merchant_center_write_tag( 'title', bsc_merchant_center_get_title( $product, $parent ) );
	bsc_merchant_center_write_url_tag( 'link', $link );
	bsc_merchant_center_write_tag( 'description', bsc_merchant_center_get_description( $product, $parent ) );
	bsc_merchant_center_write_tag( 'g:id', bsc_merchant_center_get_item_id( $product ) );
	bsc_merchant_center_write_tag( 'g:title', bsc_merchant_center_get_title( $product, $parent ) );
	bsc_merchant_center_write_tag( 'g:description', bsc_merchant_center_get_description( $product, $parent ) );
	bsc_merchant_center_write_url_tag( 'g:link', $link );
	bsc_merchant_center_write_url_tag( 'g:image_link', $images[0] ?? '' );

	foreach ( array_slice( $images, 1, 9 ) as $image_url ) {
		bsc_merchant_center_write_url_tag( 'g:additional_image_link', $image_url );
	}

	bsc_merchant_center_write_tag( 'g:availability', bsc_merchant_center_get_availability( $product ) );
	bsc_merchant_center_write_tag( 'g:condition', 'new' );
	bsc_merchant_center_write_tag( 'g:price', bsc_merchant_center_price( $product, $is_on_sale ? $regular : null ) );

	if ( $is_on_sale ) {
		bsc_merchant_center_write_tag( 'g:sale_price', bsc_merchant_center_price( $product, $sale ) );
	}

	if ( $parent instanceof WC_Product ) {
		bsc_merchant_center_write_tag( 'g:item_group_id', bsc_merchant_center_get_item_id( $parent ) );
	}

	bsc_merchant_center_write_tag( 'g:brand', $brand );
	bsc_merchant_center_write_tag( 'g:product_type', $product_type );

	if ( '' !== $identifiers['gtin'] ) {
		bsc_merchant_center_write_tag( 'g:gtin', $identifiers['gtin'] );
	}

	if ( '' !== $identifiers['mpn'] ) {
		bsc_merchant_center_write_tag( 'g:mpn', $identifiers['mpn'] );
	}

	if ( '' === $identifiers['gtin'] && '' === $identifiers['mpn'] ) {
		bsc_merchant_center_write_tag( 'g:identifier_exists', 'no' );
	}

	echo "\t\t</item>\n";

	return true;
}

function bsc_merchant_center_output_feed(): void {
	if ( ! bsc_merchant_center_is_enabled() || ! class_exists( 'WooCommerce' ) ) {
		status_header( 404 );
		exit;
	}

	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	status_header( 200 );
	header( 'Content-Type: application/rss+xml; charset=' . get_option( 'blog_charset' ), true );
	nocache_headers();

	echo '<?xml version="1.0" encoding="' . esc_attr( get_option( 'blog_charset' ) ) . "\"?>\n";
	echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
	echo "\t<channel>\n";
	bsc_merchant_center_write_tag( 'title', get_bloginfo( 'name' ) . ' - Google Merchant Center' );
	bsc_merchant_center_write_url_tag( 'link', home_url( '/' ) );
	bsc_merchant_center_write_tag( 'description', 'Catalogo de productos BSC para Google Merchant Center.' );

	$page     = 1;
	$per_page = 100;
	$has_more = true;

	while ( $has_more ) {
		$result = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => $per_page,
				'paged'    => $page,
				'paginate' => true,
			)
		);

		$products = is_object( $result ) && isset( $result->products ) ? $result->products : array();
		$max_page = is_object( $result ) && isset( $result->max_num_pages ) ? (int) $result->max_num_pages : 1;

		foreach ( $products as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			if ( $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $child_id ) {
					$variation = wc_get_product( $child_id );
					if ( $variation instanceof WC_Product ) {
						bsc_merchant_center_write_item( $variation, $product );
					}
				}
				continue;
			}

			bsc_merchant_center_write_item( $product );
		}

		$has_more = $page < $max_page;
		++$page;
	}

	echo "\t</channel>\n";
	echo "</rss>\n";
	exit;
}

function bsc_merchant_center_register_feed(): void {
	add_feed( 'bsc-google-merchant', 'bsc_merchant_center_output_feed' );
}
add_action( 'init', 'bsc_merchant_center_register_feed' );
