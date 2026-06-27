<?php

class BSC_Product_Category_Meta {
	private WC_Product $product;
	private array $categories = array();

	public function __construct( WC_Product $product ) {
		$this->product = $product;
		$this->load_categories();
	}

	private function load_categories(): void {
		$terms = get_the_terms( $this->product->get_id(), 'product_cat' );
		if ($terms && !is_wp_error( $terms )) {
			$this->categories = $terms;
		}
	}

	public function render(): void {
		if (empty( $this->categories )) {
			return;
		}

		// Step 1: Define desired render order
		$render_order = array(
			'skin_type',
			'how_to_use',
			'rutine_steps',
		);

		// Step 2: Prepare content blocks grouped by key
		$blocks_by_key = array(
			'skin_type'    => array(),
			'how_to_use'   => array(),
			'rutine_steps' => array(),
		);
		$seen_values = array(
			'skin_type'    => array(),
			'how_to_use'   => array(),
			'rutine_steps' => array(),
		);

		$add_unique_value = static function ( string $key, string $value ) use ( &$blocks_by_key, &$seen_values ): void {
			$value = trim( $value );
			if ('' === $value) {
				return;
			}

			$normalized = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES, 'UTF-8' );
			$normalized = preg_replace( '/\s+/', ' ', $normalized ) ?: '';
			$normalized = trim( strtolower( $normalized ) );

			if ('' === $normalized || isset( $seen_values[ $key ][ $normalized ] )) {
				return;
			}

			$seen_values[ $key ][ $normalized ] = true;
			$blocks_by_key[ $key ][]            = $value;
		};

		$categories_reversed = array_reverse( $this->categories );

		foreach ($categories_reversed as $category) {
			$meta = get_term_meta( $category->term_id );

			$how_to_use   = $meta['bsc__how_to_use'][0] ?? '';
			$rutine_steps = $meta['bsc__rutine_steps'][0] ?? '';
			$skin_type    = $meta['bsc__skin_type_root'][0] ?? '';

			$add_unique_value( 'skin_type', (string) $skin_type );
			$add_unique_value( 'how_to_use', (string) $how_to_use );
			$add_unique_value( 'rutine_steps', (string) $rutine_steps );
		}

		// Step 3: Start container
		echo '<div class="bsc__product-details-categories">';

		// Step 4: Render each block in custom order
		foreach ($render_order as $key) {
			switch ($key) {
				case 'skin_type':
					if (!empty( $blocks_by_key['skin_type'] )) {
						$skin_html = implode( ', ', array_map( 'esc_html', $blocks_by_key['skin_type'] ) ) . '.';
						echo '<details class="bsc__product-detail-category">';
						echo '<summary>Tipo de Piel</summary>';
						echo '<p>Apto para ' . wp_kses_post( $skin_html ) . '</p>';
						echo '</details>';
					}
					break;

				case 'how_to_use':
					if (!empty( $blocks_by_key['how_to_use'] )) {
						echo '<details class="bsc__product-detail-category">';
						echo '<summary>¿Cómo usar?</summary>';
						foreach ($blocks_by_key['how_to_use'] as $html) {
							echo '<p>' . esc_html( $html ) . '</p>';
						}
						echo '</details>';
					}
					break;

				case 'rutine_steps':
					if (!empty( $blocks_by_key['rutine_steps'] )) {
						echo '<details class="bsc__product-detail-category">';
						echo '<summary>Paso de la rutina</summary>';
						foreach ($blocks_by_key['rutine_steps'] as $html) {
							echo '<p>' . esc_html( $html ) . '</p>';
						}
						echo '</details>';
					}
					break;
			}
		}

		// Step 5: End container
		echo '<i class="icon-chevron-up"></i>';
		echo '</div>';
	}
}
