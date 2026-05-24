<?php

defined( 'ABSPATH' ) || exit;

class BSC_Theme_Importer_PPU_ProductManager {

	public function clearImages( $product ) {
		$product->set_image_id( '' );
		$product->set_gallery_image_ids( array() );
		$product->save();
	}

	public function attachImages( $product, $images ) {
		if (empty( $images )) {
			return;
		}

		$featuredImage = $images[0];
		$galleryImages = array_slice( $images, 1 );

		$product->set_image_id( $featuredImage );
		$product->set_gallery_image_ids( $galleryImages );
		$product->save();
	}

	public function getProductBySku( $sku ) {
		$productId = wc_get_product_id_by_sku( $sku );
		return $productId ? wc_get_product( $productId ) : null;
	}
}
