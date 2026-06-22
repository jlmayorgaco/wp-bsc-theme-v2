<?php
/**
 * Product-first search results.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! class_exists( 'BSC_Products_Card' ) && class_exists( 'WooCommerce' ) ) {
	require_once get_template_directory() . '/components/products/card.php';
}

if ( ! function_exists( 'bsc_search_get_product_ids' ) ) {
	function bsc_search_get_product_ids( string $query, int $limit, int $paged ): array {
		$query = sanitize_text_field( $query );
		if ( '' === $query || ! class_exists( 'WooCommerce' ) ) {
			return array(
				'ids'   => array(),
				'total' => 0,
			);
		}

		$collected_ids = array();

		$title_args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			's'                      => $query,
			'fields'                 => 'ids',
			'posts_per_page'         => $limit * 4,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		if ( function_exists( 'bsc_search_apply_public_query_constraints' ) ) {
			$title_args = bsc_search_apply_public_query_constraints( $title_args );
		}

		$title_query  = new WP_Query( $title_args );
		$collected_ids = array_merge( $collected_ids, array_map( 'absint', $title_query->posts ) );

		$sku_args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'posts_per_page'         => $limit * 2,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => '_sku',
					'value'   => $query,
					'compare' => 'LIKE',
				),
			),
		);
		if ( function_exists( 'bsc_search_apply_public_query_constraints' ) ) {
			$sku_args = bsc_search_apply_public_query_constraints( $sku_args );
		}

		$sku_query     = new WP_Query( $sku_args );
		$collected_ids = array_merge( $collected_ids, array_map( 'absint', $sku_query->posts ) );

		$matching_terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'name__like' => $query,
				'fields'     => 'ids',
				'hide_empty' => true,
			)
		);

		if ( ! empty( $matching_terms ) && ! is_wp_error( $matching_terms ) ) {
			$term_args = array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'posts_per_page'         => $limit * 2,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => array_map( 'absint', $matching_terms ),
					),
				),
			);
			if ( function_exists( 'bsc_search_apply_public_query_constraints' ) ) {
				$term_args = bsc_search_apply_public_query_constraints( $term_args );
			}

			$term_query    = new WP_Query( $term_args );
			$collected_ids = array_merge( $collected_ids, array_map( 'absint', $term_query->posts ) );
		}

		$eligible_ids = function_exists( 'bsc_search_filter_eligible_product_ids' )
			? bsc_search_filter_eligible_product_ids( $collected_ids, 0 )
			: array_values( array_unique( array_map( 'absint', $collected_ids ) ) );

		$total  = count( $eligible_ids );
		$offset = max( 0, ( $paged - 1 ) * $limit );

		return array(
			'ids'   => array_slice( $eligible_ids, $offset, $limit ),
			'total' => $total,
		);
	}
}

$search_query = get_search_query( false );
$paged        = max( 1, (int) get_query_var( 'paged' ) );
$per_page     = 24;
$results      = bsc_search_get_product_ids( $search_query, $per_page, $paged );

if ( function_exists( 'bsc_metrics_record_search' ) ) {
	bsc_metrics_record_search( $search_query, (int) $results['total'] );
}
?>

<main id="primary" class="site-main bsc bsc__shop bsc__search-page">
	<div class="bsc__container">
		<header class="shop__header bsc__search-header">
			<h1 class="bsc__title">
				<strong>Resultados</strong> para "<?php echo esc_html( $search_query ); ?>"
			</h1>

			<form role="search" method="get" class="bsc__search-results-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="bsc-search-results-input">Buscar productos</label>
				<input id="bsc-search-results-input" type="search" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="Buscar producto, marca o SKU">
				<button type="submit" class="bsc__button">Buscar</button>
			</form>

			<p class="bsc__search-count">
				<?php echo esc_html( sprintf( '%d producto(s) encontrado(s)', (int) $results['total'] ) ); ?>
			</p>
		</header>

		<section class="shop__content">
			<div class="shop__products">
				<?php if ( ! empty( $results['ids'] ) ) : ?>
					<?php foreach ( $results['ids'] as $product_id ) : ?>
						<?php
						$product = wc_get_product( $product_id );
						if ( ! $product instanceof WC_Product ) {
							continue;
						}

						$card = new BSC_Products_Card();
						$card->setProduct( $product );
						$card->render();
						?>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="bsc__search-empty">
						<p>No encontramos productos para esa busqueda.</p>
						<a class="bsc__button" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">Volver a la tienda</a>
					</div>
				<?php endif; ?>
			</div>

			<?php
			$total_pages = (int) ceil( (int) $results['total'] / $per_page );
			if ( $total_pages > 1 ) :
				echo '<div class="shop__pagination">';
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg(
								array(
									's'     => $search_query,
									'paged' => '%#%',
								),
								home_url( '/' )
							),
							'format'    => '',
							'current'   => $paged,
							'total'     => $total_pages,
							'prev_text' => '&laquo; Anterior',
							'next_text' => 'Siguiente &raquo;',
						)
					)
				);
				echo '</div>';
			endif;
			?>
		</section>
	</div>
</main>

<?php
get_footer();
