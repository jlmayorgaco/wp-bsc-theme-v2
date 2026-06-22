<?php
/**
 * Clean OOP refactor of product category template
 * Following SOLID, DRY, and KISS principles — single file version
 */

require_once get_template_directory() . '/components/products/card.php';
require_once get_template_directory() . '/components/products/filters.php';




class BSCShopPage {

	private ?WP_Term $category;
	private ?WP_Term $parent;
	private ?WP_Term $grandparent;
	private bool $showFilters;
	private int $urlDepth; // NEW: depth after "product-category" in the URL

	// ------------------------------
	// CONSTRUCTOR & CONTEXT
	// ------------------------------
	public function __construct() {
		$this->category    = get_queried_object();
		$this->parent      = ( $this->category && $this->category->parent )
			? get_term( $this->category->parent, 'product_cat' )
			: null;
		$this->grandparent = ( $this->parent && $this->parent->parent )
			? get_term( $this->parent->parent, 'product_cat' )
			: null;

		$this->urlDepth    = $this->computeUrlDepth();
		$this->showFilters = $this->computeShowFilters();
	}


	/**
	 * Map "group-*" slugs to their default child slug.
	 * Example: group-skin-care → skin-care-rutina
	 */
	private function getDefaultChildTermForGroup( WP_Term $cat ): ?WP_Term {
		$cache_key      = 'bsc_default_child_term_' . $cat->term_id;
		$cached_term_id = (int) get_transient( $cache_key );
		if ( $cached_term_id > 0 ) {
			$cached_term = get_term( $cached_term_id, 'product_cat' );
			if ( $cached_term instanceof WP_Term && ! is_wp_error( $cached_term ) ) {
				return $cached_term;
			}
		}

		// Dictionary: parent group slug → child slug
		$map = array(
			'group-skin-care' => array( 'sk-rutina', 'skin-care-rutina', 'rutina-skin-care', 'skin-care' ),
			'group-hair-care' => array( 'hc-rutina', 'hair-care-rutina', 'rutina-hair-care', 'hair-care' ),
			'group-make-up'   => array( 'mk-productos', 'make-up-productos', 'productos-make-up', 'make-up' ),
		);

		if (!isset( $map[ $cat->slug ] )) {
			return null;
		}

		$candidates = array();

		foreach ($map[ $cat->slug ] as $child_slug) {
			$child_term = get_term_by( 'slug', $child_slug, 'product_cat' );
			if ($child_term instanceof WP_Term) {
				$candidates[ $child_term->term_id ] = $child_term;
			}
		}

		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'parent'     => $cat->term_id,
			)
		);

		if (is_array( $children ) && !is_wp_error( $children )) {
			foreach ($children as $child_term) {
				if ($child_term instanceof WP_Term) {
					$candidates[ $child_term->term_id ] = $child_term;
				}
			}
		}

		foreach ($candidates as $candidate) {
			if ($this->termHasVisibleProducts( $candidate )) {
				set_transient( $cache_key, (string) $candidate->term_id, 5 * MINUTE_IN_SECONDS );
				return $candidate;
			}
		}

		if ($this->termHasVisibleProducts( $cat )) {
			set_transient( $cache_key, (string) $cat->term_id, 5 * MINUTE_IN_SECONDS );
			return $cat;
		}

		$fallback = reset( $candidates ) ?: null;
		if ( $fallback instanceof WP_Term ) {
			set_transient( $cache_key, (string) $fallback->term_id, 5 * MINUTE_IN_SECONDS );
		}

		return $fallback;
	}

	/**
	 * Depth from URL: number of segments after "product-category"
	 * /product-category/                      → depth 0
	 * /product-category/group-skin-care/      → depth 1
	 * /product-category/group-skin-care/foo/  → depth 2
	 */
	private function computeUrlDepth(): int {
		$path      = isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
			: '/';
		$cleanPath = trim( parse_url( $path, PHP_URL_PATH ) ?? '', '/' );
		$segments  = explode( '/', $cleanPath );

		$index = array_search( 'product-category', $segments, true );
		if ($index === false) {
			return 0;
		}

		// number of segments after "product-category"
		return count( $segments ) - $index - 1;
	}

	private function computeShowFilters(): bool {
		// Show filters only from depth >= 2:
		// /product-category/.../... → Level3 (products with filters)
		return $this->urlDepth >= 2;
	}

	// ------------------------------
	// HELPERS
	// ------------------------------
	private function getImageUrl( WP_Term $term ): string {
		$thumb    = get_term_meta( $term->term_id, 'thumbnail_id', true );
		$fallback = get_template_directory_uri() . '/images/bsc_default_category.jpeg';
		return $thumb ? wp_get_attachment_url( $thumb ) : $fallback;
	}

	private function addAvailableStockConstraint( array $args ): array {
		if (function_exists( 'bsc_apply_public_product_query_constraints' )) {
			$args = bsc_apply_public_product_query_constraints( $args );
		}

		if (class_exists( 'BSC_Stock' )) {
			$args['meta_query'][] = BSC_Stock::get_available_stock_meta_query();
		}

		return $args;
	}

	private function termHasVisibleProducts( WP_Term $term ): bool {
		$cache_key = 'bsc_term_has_visible_products_' . $term->term_id;
		$cached    = get_transient( $cache_key );
		if ( '1' === $cached ) {
			return true;
		}
		if ( '0' === $cached ) {
			return false;
		}

		$has_products = ! empty( $this->getVisibleProductIdsForTerm( $term, 1, 24 ) );
		set_transient( $cache_key, $has_products ? '1' : '0', 5 * MINUTE_IN_SECONDS );

		return $has_products;
	}

	private function normalizeProductIds( array $product_ids ): array {
		return array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
	}

	private function primeProductCardCaches( array $product_ids ): void {
		$product_ids = $this->normalizeProductIds( $product_ids );
		if ( empty( $product_ids ) ) {
			return;
		}

		update_meta_cache( 'post', $product_ids );
		update_object_term_cache( $product_ids, 'product' );

		$image_ids = array();
		foreach ( $product_ids as $product_id ) {
			$image_id = (int) get_post_meta( $product_id, '_thumbnail_id', true );
			if ( $image_id > 0 ) {
				$image_ids[] = $image_id;
			}
		}

		$image_ids = $this->normalizeProductIds( $image_ids );
		if ( ! empty( $image_ids ) ) {
			update_meta_cache( 'post', $image_ids );
		}
	}

	private function getProductCategoryTermsByProductId( array $product_ids ): array {
		$product_ids = $this->normalizeProductIds( $product_ids );
		if ( empty( $product_ids ) ) {
			return array();
		}

		$terms = wp_get_object_terms(
			$product_ids,
			'product_cat',
			array(
				'fields' => 'all_with_object_id',
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$terms_by_product_id = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$object_id = (int) ( $term->object_id ?? 0 );
			if ( $object_id <= 0 ) {
				continue;
			}

			$terms_by_product_id[ $object_id ][] = $term;
		}

		return $terms_by_product_id;
	}

	private function productIdHasCatalogStock( int $product_id ): bool {
		if ( $product_id <= 0 ) {
			return false;
		}

		if ( class_exists( 'BSC_Stock' ) && BSC_Stock::has_dual_stock( $product_id ) ) {
			return BSC_Stock::get_total_stock( $product_id ) > 0;
		}

		return 'instock' === (string) get_post_meta( $product_id, '_stock_status', true );
	}

	private function getVisibleProductIdsForTerm( WP_Term $term, int $limit, int $batch_size = 240 ): array {
		$limit      = max( 1, $limit );
		$batch_size = max( $limit, $batch_size );
		$visible    = array();
		$page       = 1;

		do {
			$tax_query = array(
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => array( $term->term_id ),
					'include_children' => true,
				),
			);

			if ( function_exists( 'bsc_get_public_product_visibility_tax_query' ) ) {
				$visibility_tax_query = bsc_get_public_product_visibility_tax_query( 'catalog' );
				if ( ! empty( $visibility_tax_query ) ) {
					$tax_query[] = $visibility_tax_query;
				}
			}

			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}

			$query = new WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'fields'                 => 'ids',
					'posts_per_page'         => $batch_size,
					'paged'                  => $page,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'orderby'                => array(
						'menu_order' => 'ASC',
						'date'       => 'DESC',
					),
					'tax_query'              => $tax_query,
				)
			);

			$candidate_ids = $this->normalizeProductIds( $query->posts );
			if ( empty( $candidate_ids ) ) {
				break;
			}

			update_meta_cache( 'post', $candidate_ids );

			foreach ( $candidate_ids as $product_id ) {
				if ( '1' === (string) get_post_meta( $product_id, '_bsc_product_archived', true ) ) {
					continue;
				}

				if ( ! $this->productIdHasCatalogStock( $product_id ) ) {
					continue;
				}

				$visible[] = $product_id;
				if ( count( $visible ) >= $limit ) {
					break 2;
				}
			}

			++$page;
		} while ( count( $candidate_ids ) === $batch_size && $page <= 5 );

		return array_slice( $visible, 0, $limit );
	}

	private function shouldCollapseMiddleBreadcrumb( ?WP_Term $grandparent, ?WP_Term $parent ): bool {
		if (!( $grandparent instanceof WP_Term ) || !( $parent instanceof WP_Term )) {
			return false;
		}

		$rootGroupSlugs = array(
			'group-skin-care',
			'group-hair-care',
			'group-make-up',
		);

		return in_array( $grandparent->slug, $rootGroupSlugs, true );
	}

	private function renderBreadcrumbs( ?WP_Term $grandparent, ?WP_Term $parent, ?WP_Term $current ): void {
		$showParent = $parent && !$this->shouldCollapseMiddleBreadcrumb( $grandparent, $parent );

		echo '<nav class="bsc__shop-nav">';
		if ($grandparent) {
			printf(
				'<a href="%s">%s</a> → ',
				esc_url( get_term_link( $grandparent ) ),
				esc_html( $grandparent->name )
			);
		}
		if ($showParent) {
			printf(
				'<a href="%s">%s</a> → ',
				esc_url( get_term_link( $parent ) ),
				esc_html( $parent->name )
			);
		}
		if ($current) {
			printf( '<a class="active">%s</a>', esc_html( $current->name ) );
		}
		echo '</nav>';
	}

	private function renderCategoryGrid( array $terms ): void {
		echo '<div class="bsc__category-grid">';
		foreach ($terms as $term) {
			if (!$term instanceof WP_Term) {
				continue;
			}

			$image = $this->getImageUrl( $term );
			printf(
				'<div class="bsc__category-card">
                    <a class="category-card__link" href="%s">
                        <img class="category-card__image" src="%s" alt="%s">
                        <h2 class="category-card__title">%s</h2>
                    </a>
                </div>',
				esc_url( get_term_link( $term ) ),
				esc_url( $image ),
				esc_attr( $term->name ),
				esc_html( $term->name )
			);
		}
		echo '</div>';
	}

	// ------------------------------
	// RENDER METHODS
	// ------------------------------
	private function renderLevel1(): void {
		$cat = $this->category;
		// En /product-category/ normalmente no hay término, pero por si acaso:
		$this->renderBreadcrumbs( null, null, $cat );

		// 🌈 Header block
		?>
		<section class="bsc-hero">
			<div class="bsc-hero__icon">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/images/bsc_rainbow.png' ); ?>"
				alt="K-Beauty rainbow icon" loading="lazy">
			</div>
			<h2 class="bsc-hero__title">Paraíso de <strong>K-Beauty</strong></h2>

			<p class="bsc-hero__quote">
			“Hace más de 15 años probé mi primer producto coreano y desde entonces quedé completamente enamorada del K-Beauty.
			Con los años seguí explorando este universo: probando nuevas fórmulas, aprendiendo de las tendencias y viajando a Corea
			para conocer de cerca su increíble tecnología. Así nació BSC: escuchando a nuestra comunidad,
			soñando con un espacio donde el K-Beauty se sintiera cercano, real y confiable. <strong>Bubbles es literalmente un paraíso K-Beauty:
			aquí no solo encuentras marcas cuidadosamente seleccionadas con los más altos estándares coreanos,
			también te ayudamos a crear una rutina efectiva, personalizada y pensada para tu piel :)</strong> ”
			</p>
			<p class="bsc-hero__author">
			Male
			<img
				src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/bsc_icon_white_heart.png"
				alt="Corazones BSC"
				width="50"
				decoding="async"
			/>
			</p>

			<div class="bsc-hero__divider"></div>
			<h3 class="bsc-hero__subtitle bsc__title">
			<strong>Bienvenido</strong> al paraíso del K-Beauty <strong>Bubble lover</strong> !
			</h3>
		</section>

		<?php
		// 🌸 Category showcase (6 blocks)
		$groups = array(
			array(
				'slug'  => 'skin-care',
				'title' => 'SKIN CARE',
				'image' => 'images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
			),
			array(
				'slug'  => 'hair-care',
				'title' => 'HAIR CARE',
				'image' => 'images/shop/2PAG_INTERNAR_IMAGENES_WEB.jpg',
			),
			array(
				'slug'  => 'make-up',
				'title' => 'MAKE UP',
				'image' => 'images/shop/3PAG_INTERNAR_IMAGENES_WEB.jpg',
			),
			/*
			[
				'slug'  => 'dispositivos',
				'title' => 'DISPOSITIVOS',
				'image' => 'images/shop/4PAG_INTERNAR_IMAGENES_WEB.jpg',
			],
			[
				'slug'  => 'inner-beauty',
				'title' => 'INNER BEAUTY',
				'image' => 'images/shop/5PAG_INTERNAR_IMAGENES_WEB.jpg',
			],
			[
				'slug'  => 'spa-kbeauty',
				'title' => 'SPA KBEAUTY',
				'image' => 'images/shop/6PAG_INTERNAR_IMAGENES_WEB.jpg',
			],
			*/
		);

		echo '<section class="bsc-kb-grid">';
		foreach ($groups as $g) {
			$term_link = '#'; // fallback if category not found
			$term      = get_term_by( 'slug', $g['slug'], 'product_cat' );
			if ($term) {
				$term_link = get_term_link( $term );
			}

			echo '<article class="bsc-kb-card">';
			echo '<a href="' . esc_url( $term_link ) . '" class="bsc-kb-card__link">';
			echo '<div class="bsc-kb-card__imgwrap">';
			bsc_responsive_theme_image(
				$g['image'],
				$g['title'],
				array(
					'loading' => 'lazy',
				),
				'(max-width: 768px) 100vw, 33vw'
			);
			echo '</div>';
			echo '<div class="bsc-kb-card__label">' . esc_html( $g['title'] ) . '</div>';
			echo '</a>';
			echo '</article>';
		}
		echo '</section>';
	}

	private function renderLevel2(): void {
		$cat = $this->category;
		if (!$cat instanceof WP_Term) {
			echo '<p>Error: categoría no válida.</p>';
			return;
		}

		$this->renderBreadcrumbs( null, null, $cat );

		echo "<h2 class='bsc__title bsc-hero__subtitle bsc__title--subcategory'>" . esc_html( $cat->name ) . '</h2>';

		if (!empty( $cat->description )) {
			echo "<p class='bsc__description bsc__description--description-category'>" . wp_kses_post( $cat->description ) . '</p>';
		}

		// 2) Default child según diccionario (skin-care-rutina, etc.)
		$defaultChild = $this->getDefaultChildTermForGroup( $cat );

		if (!( $defaultChild instanceof WP_Term )) {
			echo '<p>No hay categoría por defecto configurada para este grupo.</p>';
			return;
		}

		$filter_context     = null;
		$has_filter_sidebar = function_exists( 'bsc_render_custom_filters_sidebar' ) && class_exists( 'BSC_Catalog_Request_Context' );
		if ($has_filter_sidebar) {
			$filter_context = BSC_Catalog_Request_Context::from_request(
				array(
					'group'    => $cat->slug,
					'category' => $defaultChild->slug,
				)
			);
		}

		echo "<div class='shop__main shop__main--group'>";
		echo "<section class='shop__content'>";

		// --- sub-subcategorías (botones) ---
		$subsubcats = get_terms(
			array(
				'taxonomy'               => 'product_cat',
				'hide_empty'             => false,
				'parent'                 => $defaultChild->term_id,
				'update_term_meta_cache' => false,
			)
		);

		// Para la lógica de filtrado, queremos todos los descendientes
		$descendants = get_terms(
			array(
				'taxonomy'               => 'product_cat',
				'hide_empty'             => false,
				'child_of'               => $defaultChild->term_id,
				'fields'                 => 'id=>slug',
				'update_term_meta_cache' => false,
			)
		);

		$descendant_slugs = array();
		if ( is_array( $descendants ) && ! is_wp_error( $descendants ) ) {
			$descendant_slugs = array_values( array_map( 'strval', $descendants ) );
		}

		echo "<section class='bsc__default-subsubcategory'>";

		if (is_array( $subsubcats ) && !empty( $subsubcats )) {
			$filter_items = array(
				array(
					'slug'  => 'all',
					'label' => 'Todos',
				),
			);

			usort(
				$subsubcats,
				function ( $a, $b ) {
					return strnatcmp( $a->slug, $b->slug );
				}
			);

			foreach ($subsubcats as $term) {
				if (!$term instanceof WP_Term) {
					continue;
				}

				$filter_items[] = array(
					'slug'  => $term->slug,
					'label' => $term->name,
				);
			}

			$modal_id = 'bsc-subsubcategory-modal-' . $defaultChild->term_id;

			echo "<div class='bsc__subsubcategory-mobile-toolbar'>";
			echo '<button type="button" class="bsc__subsubcategory-mobile-trigger" aria-haspopup="dialog" aria-controls="' . esc_attr( $modal_id ) . '" aria-expanded="false">';
			echo '<span class="bsc__subsubcategory-mobile-trigger-label">Filter</span>';
			echo '<span class="bsc__subsubcategory-mobile-count" hidden>1</span>';
			echo '</button>';
			echo '<button type="button" class="bsc__subsubcategory-chip" hidden aria-label="Limpiar filtro activo">';
			echo '<span class="bsc__subsubcategory-chip-label"></span>';
			echo '<span class="bsc__subsubcategory-chip-close" aria-hidden="true">x</span>';
			echo '</button>';
			echo '</div>';

			echo '<div class="bsc__subsubcategory-modal" id="' . esc_attr( $modal_id ) . '" hidden>';
			echo '<button type="button" class="bsc__subsubcategory-modal-backdrop" data-filter-close="true" aria-label="Cerrar filtros"></button>';
			echo '<div class="bsc__subsubcategory-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="' . esc_attr( $modal_id . '-title' ) . '">';
			echo '<div class="bsc__subsubcategory-modal-header">';
			echo '<h3 id="' . esc_attr( $modal_id . '-title' ) . '" class="bsc__subsubcategory-modal-title">Filtrar categoria</h3>';
			echo '<div  class="bsc__subsubcategory-modal-close">';
			echo '<button type="button" data-filter-close="true" aria-label="Cerrar">x</button>';
			echo '</div>';
			echo '</div>';
			echo '<div class="bsc__subsubcategory-modal-options">';

			foreach ($filter_items as $item) {
				$active_class = $item['slug'] === 'all' ? ' bsc__subsubcategory-modal-option--active' : '';

				printf(
					'<button type="button" class="bsc__subsubcategory-modal-option%s" data-filter="%s" data-filter-label="%s">%s</button>',
					esc_attr( $active_class ),
					esc_attr( $item['slug'] ),
					esc_attr( $item['label'] ),
					esc_html( $item['label'] )
				);
			}

			echo '</div>';
			echo '</div>';
			echo '</div>';

			echo "<div class='bsc__subsubcategory-links'>";

			foreach ($filter_items as $item) {
				$active_class = $item['slug'] === 'all' ? ' bsc__subsubcategory-link--active' : '';

				printf(
					'<button type="button" class="bsc__subsubcategory-link%s" data-filter="%s" data-filter-label="%s">%s</button>',
					esc_attr( $active_class ),
					esc_attr( $item['slug'] ),
					esc_attr( $item['label'] ),
					esc_html( $item['label'] )
				);
			}

			echo '</div>'; // .bsc__subsubcategory-links
		}

		// --- productos del defaultChild + todos sus descendientes ---
		// Cap at 120 products: client-side filter needs all records upfront,
		// but -1 causes full table scan and OOM on large catalogues.
		$product_ids_for_level = $this->getVisibleProductIdsForTerm( $defaultChild, 120 );
		$products_query        = new WP_Query(
			array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'post__in'               => ! empty( $product_ids_for_level ) ? $product_ids_for_level : array( 0 ),
					'orderby'                => 'post__in',
					'posts_per_page'         => max( 1, count( $product_ids_for_level ) ),
					'no_found_rows'  => true, // skip COUNT(*) — pagination not needed here
					'update_post_meta_cache' => true,
					'update_post_term_cache' => true,
					'tax_query'              => array(
						array(
							'taxonomy'         => 'product_cat',
							'field'            => 'term_id',
							'terms'            => array( $defaultChild->term_id ),
							'include_children' => true,
						),
					),
			)
		);

		$catalog_layout_class = $has_filter_sidebar ? 'shop__catalog-layout' : 'shop__catalog-layout shop__catalog-layout--full';

		echo '<div class="' . esc_attr( $catalog_layout_class ) . '">';

		if ($has_filter_sidebar) {
			echo "<aside class='shop__sidebar'>";
			bsc_render_custom_filters_sidebar( $filter_context );
			echo '</aside>';
		}

		echo "<div id='bscProductsContainer' class='shop__products'>";

		if ($products_query->have_posts()) {

			$product_ids                 = wp_list_pluck( $products_query->posts, 'ID' );
			$this->primeProductCardCaches( $product_ids );
			$product_terms_by_product_id = $this->getProductCategoryTermsByProductId( $product_ids );
			$card_index                  = 0;
			while ($products_query->have_posts()) {
				$products_query->the_post();
				global $product;

				if (!$product instanceof WC_Product) {
					continue;
				}

				// Categorías del producto para usar en data-subcat
				$product_id     = $product->get_id();
				$prod_terms     = $product_terms_by_product_id[ $product_id ] ?? array();
				$slugs_for_data = array();

				if (!is_wp_error( $prod_terms ) && !empty( $prod_terms )) {
					foreach ($prod_terms as $pt) {
						if (!$pt instanceof WP_Term) {
							continue;
						}
						// Sólo nos interesan los descendientes de $defaultChild
						if (in_array( $pt->slug, $descendant_slugs, true )) {
							$slugs_for_data[] = $pt->slug;
						}
					}
				}

				// Si no encontramos descendiente, al menos marca la categoría default
				if (empty( $slugs_for_data )) {
					$slugs_for_data[] = $defaultChild->slug;
				}

				$data_subcat = implode( ' ', $slugs_for_data );

				echo '<div class="bsc-product-card" data-subcat="' . esc_attr( $data_subcat ) . '">';

				// Render card normal
				$card = new BSC_Products_Card();
				$card->setProduct( $product, $prod_terms );
				$card->setImagePriority( $card_index === 0 );
				$card->render();
				++$card_index;

				echo '</div>';
			}

			wp_reset_postdata();

		} else {
			echo '<p>No hay productos en esta categoría.</p>';
		}

		echo '</div>'; // #bscProductsContainer
		echo '</div>'; // .shop__catalog-layout
		if ( function_exists( 'bsc_seo_render_product_category_content' ) ) {
			bsc_seo_render_product_category_content( $cat );
		}
		echo '</section>';
		echo '</section>';
		echo '</div>';
	}


	private function renderLevel3(): void {
		$cat = $this->category;
		if (!$cat instanceof WP_Term) {
			echo '<p>Error: categoría no válida.</p>';
			return;
		}

		// Aquí sí usamos parent y grandparent si existen
		$this->renderBreadcrumbs( $this->grandparent, $this->parent, $cat );

		echo "<div class='shop__header'>";
		echo "<h1 class='bsc__title'><strong>" . esc_html( $cat->name ) . '</strong></h1>';
		if (!empty( $cat->description )) {
			echo "<p class='bsc__description'>" . esc_html( $cat->description ) . '</p>';
		}
		echo '</div>';

		echo "<div class='shop__main'>";
		if ($this->showFilters && function_exists( 'bsc_render_custom_filters_sidebar' )) {
			echo "<aside class='shop__sidebar'>";
			bsc_render_custom_filters_sidebar();
			echo '</aside>';
		}

		echo "<section class='shop__content'><div id='bscProductsContainer' class='shop__products'>";
		$products_query = $this->renderProducts( $cat );
		echo '</div>';

		// Pagination — only show if more than 1 page
		if ($products_query->max_num_pages > 1) {
			$paged = max( 1, get_query_var( 'paged' ) );
			echo "<div class='shop__pagination'>";
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
						'format'    => '?paged=%#%',
						'current'   => $paged,
						'total'     => $products_query->max_num_pages,
						'prev_text' => '&laquo; Anterior',
						'next_text' => 'Siguiente &raquo;',
					)
				)
			);
			echo '</div>';
		}

		if ( function_exists( 'bsc_seo_render_product_category_content' ) ) {
			bsc_seo_render_product_category_content( $cat );
		}

		echo '</section></div>';
	}

	private function renderProducts( WP_Term $category ): WP_Query {
		$paged = max( 1, get_query_var( 'paged' ) );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only product ordering filter.
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';

		$args = $this->addAvailableStockConstraint(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => 24,
				'paged'                  => $paged,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
				'tax_query'              => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => $category->slug,
					),
				),
			)
		);

		if (class_exists( 'BSC_Catalog_Filter_Config' ) && class_exists( 'BSC_Catalog_Product_Query' )) {
			$config = new BSC_Catalog_Filter_Config();
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only catalog filter query.
			$request_params = BSC_Catalog_Request_Context::sanitize_request_array( $_GET );
			$context        = BSC_Catalog_Request_Context::from_request(
				array_merge(
					$request_params,
					array(
						'group'    => $this->grandparent instanceof WP_Term ? $this->grandparent->slug : '',
						'category' => $category->slug,
						'orderby'  => $orderby,
					)
				),
				$config
			);
			$catalog_args   = ( new BSC_Catalog_Product_Query( $config ) )->get_query_args( $context );
			$args           = array_merge( $args, array_intersect_key( $catalog_args, array_flip( array( 'orderby', 'order', 'meta_key' ) ) ) );
		}

		$query = new WP_Query( $args );

		if ($query->have_posts()) {
			$product_ids                 = wp_list_pluck( $query->posts, 'ID' );
			$this->primeProductCardCaches( $product_ids );
			$product_terms_by_product_id = $this->getProductCategoryTermsByProductId( $product_ids );
			$card_index                  = 0;
			while ($query->have_posts()) {
				$query->the_post();
				global $product;
				if ($product instanceof WC_Product) {
					$card = new BSC_Products_Card();
					$card->setProduct( $product, $product_terms_by_product_id[ $product->get_id() ] ?? array() );
					$card->setImagePriority( $card_index === 0 );
					$card->render();
					++$card_index;
				}
			}
			wp_reset_postdata();
		} else {
			echo '<p class="bsc__empty-category">No hay productos en esta categoría.</p>';
		}

		return $query;
	}

	// ------------------------------
	// MAIN ENTRY POINT
	// ------------------------------
	public function render(): void {
		echo '<div class="bsc bsc__shop"><div class="bsc__container">';

		// Mapeo por profundidad de URL:
		// depth 0: /product-category/                 → Level1
		// depth 1: /product-category/group-skin-care/ → Level2
		// depth 2+: /product-category/.../...         → Level3
		if ($this->urlDepth === 0) {
			// En /product-category/ a veces no hay término; igual renderizamos Level1
			$this->renderLevel1();
		} elseif ($this->urlDepth === 1) {
			$this->renderLevel2();
		} else {
			$this->renderLevel3();
		}

		echo '</div></div>';
	}
}

// --------------------------------------------------
// 🏁 ENTRY POINT
// --------------------------------------------------
$page = new BSCShopPage();
$page->render();

?>

<!-- Category filter script enqueued via js/category-filter.js -->
