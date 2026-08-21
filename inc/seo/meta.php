<?php
/**
 * SEO meta tags, indexation rules and editable SEO fields.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trim text for SEO meta fields.
 *
 * @param string $text       Text to trim.
 * @param int    $max_length Maximum output length.
 */
function bsc_seo_trim_text( string $text, int $max_length = 160 ): string {
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );

	if ( '' === $text || strlen( $text ) <= $max_length ) {
		return $text;
	}

	return rtrim( substr( $text, 0, $max_length - 3 ), " \t\n\r\0\x0B.,;:-" ) . '...';
}

/**
 * Return a trimmed post meta value.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 */
function bsc_seo_get_post_meta_value( int $post_id, string $key ): string {
	return trim( (string) get_post_meta( $post_id, $key, true ) );
}

/**
 * Return a trimmed term meta value.
 *
 * @param int    $term_id Term ID.
 * @param string $key     Meta key.
 */
function bsc_seo_get_term_meta_value( int $term_id, string $key ): string {
	return trim( (string) get_term_meta( $term_id, $key, true ) );
}

/**
 * Return the default SEO title.
 */
function bsc_seo_get_default_title(): string {
	$title = trim( (string) get_option( 'bsc_seo_default_title', '' ) );

	return '' !== $title ? $title : get_bloginfo( 'name' );
}

/**
 * Return the default SEO description.
 */
function bsc_seo_get_default_description(): string {
	$description = trim( (string) get_option( 'bsc_seo_default_description', '' ) );

	if ( '' !== $description ) {
		return bsc_seo_trim_text( $description );
	}

	$site_description = trim( (string) get_bloginfo( 'description' ) );
	if ( '' !== $site_description ) {
		return bsc_seo_trim_text( $site_description );
	}

	return bsc_seo_trim_text( 'Skincare coreano, maquillaje, hair care, inner beauty y dispositivos de belleza seleccionados por Bubble Skin Care.' );
}

/**
 * Return the current WooCommerce product when viewing a product page.
 */
function bsc_seo_get_current_product(): ?WC_Product {
	if ( ! function_exists( 'is_product' ) || ! is_product() || ! class_exists( 'WooCommerce' ) ) {
		return null;
	}

	$product = wc_get_product( get_the_ID() );

	return $product instanceof WC_Product ? $product : null;
}

/**
 * Build the title for the current request.
 */
function bsc_seo_get_current_title(): string {
	$product = bsc_seo_get_current_product();

	if ( $product instanceof WC_Product ) {
		$seo_title = bsc_seo_get_post_meta_value( $product->get_id(), '_bsc_seo_title' );
		return '' !== $seo_title ? $seo_title : $product->get_name() . ' | ' . get_bloginfo( 'name' );
	}

	if ( is_front_page() || is_home() ) {
		return bsc_seo_get_default_title();
	}

	if ( is_singular() ) {
		$post_id   = get_queried_object_id();
		$seo_title = $post_id ? bsc_seo_get_post_meta_value( $post_id, '_bsc_seo_title' ) : '';
		if ( '' !== $seo_title ) {
			return $seo_title;
		}

		$title = $post_id ? get_the_title( $post_id ) : '';
		return '' !== $title ? $title . ' | ' . get_bloginfo( 'name' ) : bsc_seo_get_default_title();
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$seo_title = bsc_seo_get_term_meta_value( $term->term_id, 'bsc_seo_title' );
			return '' !== $seo_title ? $seo_title : bsc_get_product_category_display_name( $term ) . ' | ' . get_bloginfo( 'name' );
		}
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return 'Tienda | ' . get_bloginfo( 'name' );
	}

	if ( is_search() ) {
		return 'Busqueda | ' . get_bloginfo( 'name' );
	}

	return bsc_seo_get_default_title();
}

/**
 * Build the description for the current request.
 */
function bsc_seo_get_current_description(): string {
	$product = bsc_seo_get_current_product();

	if ( $product instanceof WC_Product ) {
		$seo_description = bsc_seo_get_post_meta_value( $product->get_id(), '_bsc_seo_description' );
		if ( '' !== $seo_description ) {
			return bsc_seo_trim_text( $seo_description );
		}

		$product_description = $product->get_short_description();
		if ( '' === $product_description ) {
			$product_description = $product->get_description();
		}

		return bsc_seo_trim_text( $product_description );
	}

	if ( is_front_page() || is_home() ) {
		return bsc_seo_get_default_description();
	}

	if ( is_singular() ) {
		$post_id         = get_queried_object_id();
		$seo_description = $post_id ? bsc_seo_get_post_meta_value( $post_id, '_bsc_seo_description' ) : '';
		if ( '' !== $seo_description ) {
			return bsc_seo_trim_text( $seo_description );
		}

		$excerpt = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : '';
		$content = '' !== $excerpt ? $excerpt : get_post_field( 'post_content', $post_id );
		return bsc_seo_trim_text( $content );
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$seo_description = bsc_seo_get_term_meta_value( $term->term_id, 'bsc_seo_description' );
			if ( '' !== $seo_description ) {
				return bsc_seo_trim_text( $seo_description );
			}

			$term_description = bsc_seo_trim_text( $term->description );
			if ( '' !== $term_description ) {
				return $term_description;
			}

			return bsc_seo_trim_text( sprintf( 'Compra %s en Bubble Skin Care: skincare coreano, maquillaje, hair care, inner beauty y dispositivos de belleza seleccionados.', $term->name ) );
		}
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return bsc_seo_trim_text( 'Productos coreanos de skincare, maquillaje, hair care, inner beauty y dispositivos en Bubble Skin Care.' );
	}

	return bsc_seo_get_default_description();
}

/**
 * Build a canonical URL for the current request.
 */
function bsc_seo_get_canonical_url(): string {
	if ( is_singular() ) {
		$url = wp_get_canonical_url();
		return $url ? $url : get_permalink();
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );
			return is_wp_error( $link ) ? home_url( '/' ) : (string) $link;
		}
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			return (string) wc_get_page_permalink( 'shop' );
		}

		return home_url( '/shop/' );
	}

	if ( is_search() ) {
		return home_url( '/' );
	}

	if ( is_front_page() || is_home() ) {
		return home_url( '/' );
	}

	return home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) );
}

/**
 * Check whether the current catalog URL contains filter parameters.
 */
function bsc_seo_has_filter_query(): bool {
	$filter_keys = array( 'orderby', 'min_price', 'max_price', 'rating_filter', 'bsc_recover_cart' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only query inspection for indexation rules.
	foreach ( array_keys( $_GET ) as $key ) {
		$key = sanitize_key( wp_unslash( $key ) );

		if ( in_array( $key, $filter_keys, true ) || 0 === strpos( $key, 'filter_' ) || 0 === strpos( $key, 'query_type_' ) ) {
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			return true;
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	return false;
}

/**
 * Determine whether the current page should be noindexed.
 */
function bsc_seo_should_noindex(): bool {
	if ( is_404() || is_search() || is_preview() ) {
		return true;
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return true;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return true;
	}

	if ( ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_category' ) && is_product_category() ) ) {
		return bsc_seo_has_filter_query();
	}

	return false;
}

/**
 * Return the best image URL for social previews.
 */
function bsc_seo_get_primary_image_url(): string {
	$product = bsc_seo_get_current_product();
	if ( $product instanceof WC_Product ) {
		$urls = bsc_seo_get_product_image_urls( $product );
		if ( ! empty( $urls[0] ) ) {
			return $urls[0];
		}
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
			$url          = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'full' ) : '';
			if ( $url ) {
				return esc_url_raw( $url );
			}
		}
	}

	$default_image_url = esc_url_raw( (string) get_option( 'bsc_seo_default_image_url', '' ) );
	if ( '' !== $default_image_url ) {
		return $default_image_url;
	}

	$site_icon_url = get_site_icon_url( 512 );
	if ( $site_icon_url ) {
		return esc_url_raw( $site_icon_url );
	}

	$custom_logo_id = (int) get_theme_mod( 'custom_logo' );
	$url            = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : '';

	if ( $url ) {
		return esc_url_raw( $url );
	}

	return esc_url_raw( get_template_directory_uri() . '/images/bsc__placeholder_product.jpg' );
}

/**
 * Return product identifiers used by schema and Merchant Center.
 *
 * @param WC_Product $product Product instance.
 */
function bsc_seo_get_product_identifier( WC_Product $product ): array {
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

/**
 * Filter the WordPress document title.
 *
 * @param string $title Existing document title.
 */
function bsc_seo_filter_document_title( string $title ): string {
	$seo_title = bsc_seo_get_current_title();

	return '' !== $seo_title ? $seo_title : $title;
}
add_filter( 'pre_get_document_title', 'bsc_seo_filter_document_title', 20 );

/**
 * Filter robots directives for SEO-sensitive routes.
 *
 * @param array $robots Existing robots directives.
 */
function bsc_seo_filter_robots( array $robots ): array {
	$robots['max-image-preview'] = 'large';

	if ( bsc_seo_should_noindex() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'] );
	}

	return $robots;
}
add_filter( 'wp_robots', 'bsc_seo_filter_robots', 999 );

/**
 * Output central SEO meta tags.
 */
function bsc_seo_output_meta_tags(): void {
	$title       = bsc_seo_get_current_title();
	$description = bsc_seo_get_current_description();
	$canonical   = bsc_seo_get_canonical_url();
	$image       = bsc_seo_get_primary_image_url();
	$type        = bsc_seo_get_current_product() instanceof WC_Product ? 'product' : 'website';

	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}

	if ( '' !== $canonical && ! bsc_seo_should_noindex() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
	}

	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( '' !== $description ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
	}
	if ( '' !== $canonical ) {
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $canonical ) );
	}
	if ( '' !== $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
	}

	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( '' !== $description ) {
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
	}
	if ( '' !== $image ) {
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
	}
}
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', 'bsc_seo_output_meta_tags', 8 );

/**
 * Parse category FAQ lines.
 *
 * @param string $value Raw FAQ textarea value.
 */
function bsc_seo_parse_faq_lines( string $value ): array {
	$faqs = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $value ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || false === strpos( $line, '|' ) ) {
			continue;
		}

		list( $question, $answer ) = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$faqs[] = array(
			'question' => sanitize_text_field( $question ),
			'answer'   => sanitize_textarea_field( $answer ),
		);
	}

	return array_slice( $faqs, 0, 8 );
}

/**
 * Return stored FAQ data for a product category.
 *
 * @param int $term_id Product category term ID.
 */
function bsc_seo_get_term_faqs( int $term_id ): array {
	$raw = (string) get_term_meta( $term_id, 'bsc_seo_faqs', true );
	if ( '' === $raw ) {
		return array();
	}

	$faqs = json_decode( $raw, true );
	if ( ! is_array( $faqs ) ) {
		return array();
	}

	return array_values(
		array_filter(
			$faqs,
			static function ( $faq ): bool {
				return is_array( $faq ) && ! empty( $faq['question'] ) && ! empty( $faq['answer'] );
			}
		)
	);
}

/**
 * Render visible SEO content for product categories.
 *
 * @param WP_Term $term Product category term.
 */
function bsc_seo_render_product_category_content( WP_Term $term ): void {
	$bottom_copy = bsc_seo_get_term_meta_value( $term->term_id, 'bsc_seo_bottom_copy' );
	$faqs        = bsc_seo_get_term_faqs( $term->term_id );

	if ( '' === $bottom_copy && empty( $faqs ) ) {
		return;
	}

	echo '<section class="bsc__category-seo-content">';

	if ( '' !== $bottom_copy ) {
		echo '<div class="bsc__category-seo-copy">' . wp_kses_post( wpautop( $bottom_copy ) ) . '</div>';
	}

	if ( ! empty( $faqs ) ) {
		echo '<div class="bsc__category-seo-faq">';
		echo '<h2 class="bsc__title bsc__title--seo-faq">Preguntas frecuentes</h2>';
		foreach ( $faqs as $faq ) {
			echo '<details class="bsc__category-seo-faq-item">';
			echo '<summary>' . esc_html( $faq['question'] ) . '</summary>';
			echo '<p>' . esc_html( $faq['answer'] ) . '</p>';
			echo '</details>';
		}
		echo '</div>';
	}

	echo '</section>';
}

/**
 * Render product-level SEO admin fields.
 */
function bsc_seo_render_product_fields(): void {
	if ( ! function_exists( 'woocommerce_wp_text_input' ) || ! function_exists( 'woocommerce_wp_textarea_input' ) ) {
		return;
	}

	echo '<div class="options_group">';
	echo '<p class="form-field"><strong>SEO BSC</strong></p>';
	woocommerce_wp_text_input(
		array(
			'id'          => '_bsc_seo_title',
			'label'       => 'Meta title',
			'desc_tip'    => true,
			'description' => 'Titulo para Google/Open Graph. Recomendado: 50-60 caracteres.',
		)
	);
	woocommerce_wp_textarea_input(
		array(
			'id'          => '_bsc_seo_description',
			'label'       => 'Meta description',
			'desc_tip'    => true,
			'description' => 'Descripcion para resultados de busqueda. Recomendado: 140-160 caracteres.',
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'          => '_bsc_search_intent',
			'label'       => 'Intencion SEO',
			'desc_tip'    => true,
			'description' => 'Uso interno: keyword o intencion principal de busqueda.',
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'          => '_bsc_gtin',
			'label'       => 'GTIN/EAN',
			'desc_tip'    => true,
			'description' => 'Identificador para Product schema y Merchant Center cuando exista.',
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'          => '_bsc_mpn',
			'label'       => 'MPN',
			'desc_tip'    => true,
			'description' => 'Referencia de fabricante para Merchant Center cuando exista.',
		)
	);
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'bsc_seo_render_product_fields' );

/**
 * Save product-level SEO admin fields.
 *
 * @param WC_Product $product Product being saved.
 */
function bsc_seo_save_product_fields( WC_Product $product ): void {
	$fields = array(
		'_bsc_seo_title'       => 'sanitize_text_field',
		'_bsc_seo_description' => 'sanitize_textarea_field',
		'_bsc_search_intent'   => 'sanitize_text_field',
		'_bsc_gtin'            => 'sanitize_text_field',
		'_bsc_mpn'             => 'sanitize_text_field',
	);

	foreach ( $fields as $key => $callback ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WooCommerce verifies the product edit nonce; value is sanitized through an allowlisted callback before saving.
		$value = isset( $_POST[ $key ] ) ? call_user_func( $callback, wp_unslash( $_POST[ $key ] ) ) : '';
		if ( '' === $value ) {
			$product->delete_meta_data( $key );
		} else {
			$product->update_meta_data( $key, $value );
		}
	}
}
add_action( 'woocommerce_admin_process_product_object', 'bsc_seo_save_product_fields' );

/**
 * Register the page and post SEO metabox.
 */
function bsc_seo_register_page_metabox(): void {
	foreach ( array( 'page', 'post' ) as $post_type ) {
		add_meta_box(
			'bsc-seo-meta',
			'SEO BSC',
			'bsc_seo_render_page_metabox',
			$post_type,
			'normal',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'bsc_seo_register_page_metabox' );

/**
 * Render the page and post SEO metabox.
 *
 * @param WP_Post $post Current post.
 */
function bsc_seo_render_page_metabox( WP_Post $post ): void {
	wp_nonce_field( 'bsc_seo_page_meta', 'bsc_seo_page_meta_nonce' );
	?>
	<p>
		<label for="bsc_seo_page_title"><strong>Meta title</strong></label>
		<input type="text" class="widefat" id="bsc_seo_page_title" name="bsc_seo_page_title" value="<?php echo esc_attr( bsc_seo_get_post_meta_value( $post->ID, '_bsc_seo_title' ) ); ?>">
	</p>
	<p>
		<label for="bsc_seo_page_description"><strong>Meta description</strong></label>
		<textarea class="widefat" id="bsc_seo_page_description" name="bsc_seo_page_description" rows="3"><?php echo esc_textarea( bsc_seo_get_post_meta_value( $post->ID, '_bsc_seo_description' ) ); ?></textarea>
	</p>
	<p class="description">Estos campos alimentan Google, canonical context, Open Graph y Twitter Cards.</p>
	<?php
}

/**
 * Save the page and post SEO metabox.
 *
 * @param int $post_id Current post ID.
 */
function bsc_seo_save_page_metabox( int $post_id ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if (
		! isset( $_POST['bsc_seo_page_meta_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_seo_page_meta_nonce'] ) ), 'bsc_seo_page_meta' )
	) {
		return;
	}

	$title       = isset( $_POST['bsc_seo_page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_seo_page_title'] ) ) : '';
	$description = isset( $_POST['bsc_seo_page_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bsc_seo_page_description'] ) ) : '';

	if ( '' === $title ) {
		delete_post_meta( $post_id, '_bsc_seo_title' );
	} else {
		update_post_meta( $post_id, '_bsc_seo_title', $title );
	}

	if ( '' === $description ) {
		delete_post_meta( $post_id, '_bsc_seo_description' );
	} else {
		update_post_meta( $post_id, '_bsc_seo_description', $description );
	}
}
add_action( 'save_post_page', 'bsc_seo_save_page_metabox' );
add_action( 'save_post_post', 'bsc_seo_save_page_metabox' );

/**
 * Render SEO fields when creating a product category.
 */
function bsc_seo_render_product_cat_add_fields(): void {
	?>
	<div class="form-field">
		<label for="bsc_seo_title">Meta title BSC</label>
		<input type="text" name="bsc_seo_title" id="bsc_seo_title" value="">
		<p>Titulo SEO para la categoria.</p>
	</div>
	<div class="form-field">
		<label for="bsc_seo_description">Meta description BSC</label>
		<textarea name="bsc_seo_description" id="bsc_seo_description" rows="3"></textarea>
		<p>Descripcion SEO corta para Google y redes.</p>
	</div>
	<div class="form-field">
		<label for="bsc_seo_bottom_copy">Copy SEO inferior</label>
		<textarea name="bsc_seo_bottom_copy" id="bsc_seo_bottom_copy" rows="5"></textarea>
		<p>Contenido visible al final de la categoria.</p>
	</div>
	<div class="form-field">
		<label for="bsc_seo_faqs">FAQs SEO</label>
		<textarea name="bsc_seo_faqs" id="bsc_seo_faqs" rows="5" placeholder="Pregunta | Respuesta"></textarea>
		<p>Una pregunta por linea, separando pregunta y respuesta con |.</p>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'bsc_seo_render_product_cat_add_fields' );

/**
 * Render SEO fields when editing a product category.
 *
 * @param WP_Term $term Product category term.
 */
function bsc_seo_render_product_cat_edit_fields( WP_Term $term ): void {
	$faqs      = bsc_seo_get_term_faqs( $term->term_id );
	$faq_lines = array_map(
		static function ( array $faq ): string {
			return ( $faq['question'] ?? '' ) . ' | ' . ( $faq['answer'] ?? '' );
		},
		$faqs
	);
	?>
	<tr class="form-field">
		<th scope="row"><label for="bsc_seo_title">Meta title BSC</label></th>
		<td>
			<input type="text" name="bsc_seo_title" id="bsc_seo_title" value="<?php echo esc_attr( bsc_seo_get_term_meta_value( $term->term_id, 'bsc_seo_title' ) ); ?>">
			<p class="description">Titulo SEO para la categoria.</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="bsc_seo_description">Meta description BSC</label></th>
		<td>
			<textarea name="bsc_seo_description" id="bsc_seo_description" rows="3"><?php echo esc_textarea( bsc_seo_get_term_meta_value( $term->term_id, 'bsc_seo_description' ) ); ?></textarea>
			<p class="description">Descripcion SEO corta para Google y redes.</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="bsc_seo_bottom_copy">Copy SEO inferior</label></th>
		<td>
			<textarea name="bsc_seo_bottom_copy" id="bsc_seo_bottom_copy" rows="5"><?php echo esc_textarea( bsc_seo_get_term_meta_value( $term->term_id, 'bsc_seo_bottom_copy' ) ); ?></textarea>
			<p class="description">Contenido visible al final de la categoria.</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="bsc_seo_faqs">FAQs SEO</label></th>
		<td>
			<textarea name="bsc_seo_faqs" id="bsc_seo_faqs" rows="5" placeholder="Pregunta | Respuesta"><?php echo esc_textarea( implode( "\n", $faq_lines ) ); ?></textarea>
			<p class="description">Una pregunta por linea, separando pregunta y respuesta con |.</p>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'bsc_seo_render_product_cat_edit_fields' );

/**
 * Save SEO fields for a product category.
 *
 * @param int $term_id Product category term ID.
 */
function bsc_seo_save_product_cat_fields( int $term_id ): void {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers the manage_woocommerce capability.
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WordPress taxonomy screens verify their own nonce before term save hooks.
	$title       = isset( $_POST['bsc_seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_seo_title'] ) ) : '';
	$description = isset( $_POST['bsc_seo_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bsc_seo_description'] ) ) : '';
	$bottom_copy = isset( $_POST['bsc_seo_bottom_copy'] ) ? wp_kses_post( wp_unslash( $_POST['bsc_seo_bottom_copy'] ) ) : '';
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- bsc_seo_parse_faq_lines sanitizes each parsed FAQ field.
	$faqs = isset( $_POST['bsc_seo_faqs'] ) ? bsc_seo_parse_faq_lines( (string) wp_unslash( $_POST['bsc_seo_faqs'] ) ) : array();
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	update_term_meta( $term_id, 'bsc_seo_title', $title );
	update_term_meta( $term_id, 'bsc_seo_description', $description );
	update_term_meta( $term_id, 'bsc_seo_bottom_copy', $bottom_copy );

	if ( empty( $faqs ) ) {
		delete_term_meta( $term_id, 'bsc_seo_faqs' );
	} else {
		update_term_meta( $term_id, 'bsc_seo_faqs', wp_json_encode( $faqs ) );
	}
}
add_action( 'created_product_cat', 'bsc_seo_save_product_cat_fields' );
add_action( 'edited_product_cat', 'bsc_seo_save_product_cat_fields' );

/**
 * Remove private WooCommerce pages from core sitemaps.
 *
 * @param array  $args      Sitemap query args.
 * @param string $post_type Sitemap post type.
 */
function bsc_seo_filter_sitemap_pages( array $args, string $post_type ): array {
	if ( 'page' !== $post_type || ! function_exists( 'wc_get_page_id' ) ) {
		return $args;
	}

	$excluded = array_filter(
		array_map(
			'absint',
			array(
				wc_get_page_id( 'cart' ),
				wc_get_page_id( 'checkout' ),
				wc_get_page_id( 'myaccount' ),
			)
		)
	);

	if ( empty( $excluded ) ) {
		return $args;
	}

	$args['post__not_in'] = array_values( array_unique( array_merge( $args['post__not_in'] ?? array(), $excluded ) ) );

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'bsc_seo_filter_sitemap_pages', 10, 2 );

/**
 * Add BSC-specific crawl directives to robots.txt.
 *
 * @param string $output    Existing robots.txt output.
 * @param bool   $is_public Whether the site is public.
 */
function bsc_seo_filter_robots_txt( string $output, bool $is_public ): string {
	if ( ! $is_public ) {
		return $output;
	}

	$lines = array(
		'Disallow: /cart/',
		'Disallow: /checkout/',
		'Disallow: /my-account/',
		'Disallow: /*?s=',
		'Disallow: /*?orderby=',
		'Disallow: /*filter_',
		'Disallow: /*min_price=',
		'Disallow: /*max_price=',
		'Sitemap: ' . home_url( '/wp-sitemap.xml' ),
	);

	return rtrim( $output ) . "\n" . implode( "\n", $lines ) . "\n";
}
add_filter( 'robots_txt', 'bsc_seo_filter_robots_txt', 20, 2 );
