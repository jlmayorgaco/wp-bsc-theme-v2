<?php
/**
 * Product extra images rendering.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_after_single_product_summary', 'bsc_render_extra_images', 25 );

function bsc_render_extra_images(): void {
	$post_id = get_the_ID();
	$images  = array();

	for ( $i = 1; $i <= 3; $i++ ) {
		$attachment_id = (int) get_post_meta( $post_id, "_bsc_extra_image_{$i}", true );

		if ( $attachment_id > 0 ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'large' );

			if ( $url ) {
				$images[] = array(
					'url' => $url,
					'alt' => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				);
			}
		}
	}

	if ( empty( $images ) ) {
		return;
	}

	echo '<div class="bsc-product-extra-images">';

	foreach ( $images as $img ) {
		echo '<img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $img['alt'] ) . '" loading="lazy">';
	}

	echo '</div>';
}
