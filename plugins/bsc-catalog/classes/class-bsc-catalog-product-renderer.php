<?php

class BSC_Catalog_Product_Renderer {
	public function render_query_results( WP_Query $query ): string {
		if (!class_exists( 'BSC_Products_Card' )) {
			throw new RuntimeException( 'La clase BSC_Products_Card no está disponible.' );
		}

		ob_start();

		try {
			if ($query->have_posts()) {
				while ($query->have_posts()) {
					$query->the_post();
					$product = wc_get_product( get_the_ID() );

					if (!$product instanceof WC_Product) {
						throw new RuntimeException( 'No se pudo obtener una instancia válida de WC_Product.' );
					}

					$card = new BSC_Products_Card();
					$card->setProduct( $product );
					$card->render();
				}
			} else {
				bsc_render_products_empty_state( false );
			}
		} finally {
			wp_reset_postdata();
		}

		return (string) ob_get_clean();
	}

	/**
	 * Render pagination for a catalog query using only public filter arguments.
	 *
	 * @param WP_Query                   $query       Catalog query.
	 * @param BSC_Catalog_Request_Context $context     Catalog request context.
	 * @param int                         $current_page Current page number.
	 *
	 * @return string
	 */
	public function render_pagination( WP_Query $query, BSC_Catalog_Request_Context $context, int $current_page = 1 ): string {
		$total_pages = (int) $query->max_num_pages;
		if ( $total_pages < 2 ) {
			return '';
		}

		global $wp_rewrite;

		$format     = $wp_rewrite instanceof WP_Rewrite && $wp_rewrite->using_permalinks()
			? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' )
			: '?paged=%#%';
		$base_url    = $this->get_catalog_base_url( $context );
		$public_args = $context->get_public_query_args( new BSC_Catalog_Filter_Config() );
		$page_links  = paginate_links(
			array(
				'base'      => trailingslashit( $base_url ) . '%_%',
				'format'    => $format,
				'current'   => max( 1, $current_page ),
				'total'     => $total_pages,
				'add_args'  => $public_args,
				'prev_text' => '&laquo; Anterior',
				'next_text' => 'Siguiente &raquo;',
				'type'      => 'array',
			)
		);

		if ( ! is_array( $page_links ) || empty( $page_links ) ) {
			return '';
		}

		$clean_links = array_map(
			function ( string $link ) use ( $public_args ): string {
				if ( ! preg_match( '/href="([^"]+)"/', $link, $matches ) ) {
					return $link;
				}

				$parts     = wp_parse_url( html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' ) );
				$clean_url = home_url( $parts['path'] ?? '/' );
				$clean_url = add_query_arg( $public_args, $clean_url );

				return (string) preg_replace(
					'/href="[^"]+"/',
					'href="' . esc_attr( $clean_url ) . '"',
					$link,
					1
				);
			},
			$page_links
		);

		return '<nav class="shop__pagination" aria-label="' . esc_attr__( 'Paginación de productos', 'bsc-2-0' ) . '">' . wp_kses_post( implode( "\n", $clean_links ) ) . '</nav>';
	}

	private function get_catalog_base_url( BSC_Catalog_Request_Context $context ): string {
		$term_link = $context->get_category() !== ''
			? get_term_link( $context->get_category(), 'product_cat' )
			: false;

		if ( is_string( $term_link ) && '' !== $term_link ) {
			return $term_link;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
			: '/';
		$path = (string) ( wp_parse_url( $request_uri, PHP_URL_PATH ) ?? '/' );
		$path = preg_replace( '#/page/\d+/?$#', '/', $path );
		$path = is_string( $path ) && '' !== $path ? $path : '/';

		return home_url( $path );
	}
}
