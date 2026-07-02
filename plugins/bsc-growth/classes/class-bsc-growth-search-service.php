<?php
/**
 * Search enrichment for products, routines and customer needs.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Search_Service {
	public static function get_suggestions( string $query, array $product_ids = array() ): array {
		$query = trim( sanitize_text_field( $query ) );

		if ( strlen( $query ) < 2 ) {
			return array();
		}

		$repository  = new BSC_Growth_Bundle_Repository();
		$suggestions = array_merge(
			self::need_suggestions( $query, $repository ),
			self::bundle_suggestions( $query, $repository ),
			self::term_suggestions( $query, $product_ids )
		);

		return array_slice( self::unique_suggestions( $suggestions ), 0, 8 );
	}

	private static function need_suggestions( string $query, BSC_Growth_Bundle_Repository $repository ): array {
		$needle      = self::normalize( $query );
		$suggestions = array();

		foreach ( $repository->get_need_catalog() as $key => $need ) {
			$label = (string) ( $need['label'] ?? '' );
			$url   = (string) ( $need['url'] ?? '' );
			$hay   = self::normalize( $key . ' ' . $label );

			if ( ! str_contains( $hay, $needle ) && ! str_contains( $needle, self::normalize( $label ) ) ) {
				continue;
			}

			$suggestions[] = array(
				'type'  => 'need',
				'label' => $label,
				'meta'  => 'Necesidad',
				'url'   => esc_url_raw( $url ),
			);
		}

		return $suggestions;
	}

	private static function bundle_suggestions( string $query, BSC_Growth_Bundle_Repository $repository ): array {
		$needle      = self::normalize( $query );
		$suggestions = array();

		foreach ( $repository->get_bundles( 8 ) as $bundle ) {
			$haystack = self::normalize(
				implode(
					' ',
					array_merge(
						array( $bundle['title'], $bundle['summary'] ),
						(array) $bundle['needs'],
						(array) $bundle['search_terms']
					)
				)
			);

			if ( ! str_contains( $haystack, $needle ) ) {
				continue;
			}

			$suggestions[] = array(
				'type'  => 'routine',
				'label' => $bundle['title'],
				'meta'  => $bundle['discount_label'] ? $bundle['discount_label'] : 'Rutina',
				'url'   => esc_url_raw( $bundle['url'] ),
			);
		}

		return $suggestions;
	}

	private static function term_suggestions( string $query, array $product_ids ): array {
		$suggestions = array();
		$terms       = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'name__like' => $query,
				'hide_empty' => true,
				'number'     => 8,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$is_brand      = str_contains( $term->slug, '-marca' );
			$suggestions[] = array(
				'type'  => $is_brand ? 'brand' : 'category',
				'label' => $term->name,
				'meta'  => $is_brand ? 'Marca' : '',
				'url'   => esc_url_raw( $link ),
			);
		}

		if ( empty( $suggestions ) && ! empty( $product_ids ) ) {
			$product_terms = wp_get_object_terms(
				array_slice( array_map( 'absint', $product_ids ), 0, 4 ),
				'product_cat',
				array(
					'fields' => 'all',
					'number' => 4,
				)
			);

			if ( ! is_wp_error( $product_terms ) ) {
				foreach ( $product_terms as $term ) {
					$link = get_term_link( $term );

					if ( is_wp_error( $link ) ) {
						continue;
					}

					$suggestions[] = array(
						'type'  => str_contains( $term->slug, '-marca' ) ? 'brand' : 'category',
						'label' => $term->name,
						'meta'  => str_contains( $term->slug, '-marca' ) ? 'Marca' : '',
						'url'   => esc_url_raw( $link ),
					);
				}
			}
		}

		return $suggestions;
	}

	private static function unique_suggestions( array $suggestions ): array {
		$seen = array();

		return array_values(
			array_filter(
				$suggestions,
				static function ( array $suggestion ) use ( &$seen ): bool {
					$type  = (string) ( $suggestion['type'] ?? '' );
					$label = (string) ( $suggestion['label'] ?? '' );
					$url   = (string) ( $suggestion['url'] ?? '' );
					$key   = in_array( $type, array( 'brand', 'category' ), true )
						? sanitize_key( $type . '-' . self::normalize( $label ) )
						: sanitize_key( $type . '-' . $label . '-' . $url );

					if ( isset( $seen[ $key ] ) || empty( $label ) || empty( $url ) ) {
						return false;
					}

					$seen[ $key ] = true;
					return true;
				}
			)
		);
	}

	private static function normalize( string $value ): string {
		return sanitize_key( remove_accents( strtolower( $value ) ) );
	}
}
