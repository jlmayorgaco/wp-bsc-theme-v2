<?php
/**
 * Register Custom Post Type: Home Slides
 * Add Meta Box and Fields (No Plugin)
 *
 * @package BSC_2_0
 */

add_action( 'init', 'bsc_register_home_slide_post_type' );
/**
 * Register the Home Slide post type.
 */
function bsc_register_home_slide_post_type() {
	register_post_type(
		'home_slide',
		array(
			'labels'        => array(
				'name'               => __( 'BSC Home Slides', 'bsc-2-0' ),
				'singular_name'      => __( 'Home Slide', 'bsc-2-0' ),
				'add_new_item'       => __( 'Agregar slide', 'bsc-2-0' ),
				'edit_item'          => __( 'Editar slide', 'bsc-2-0' ),
				'featured_image'     => __( 'Imagen desktop', 'bsc-2-0' ),
				'set_featured_image' => __( 'Seleccionar imagen desktop', 'bsc-2-0' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'menu_icon'     => 'dashicons-slides',
			'supports'      => array( 'title', 'thumbnail' ),
			'show_in_rest'  => true,
			'menu_position' => 20,
			// BSC-021: hide CPT auto-menu; Hero Slides submenu lives under BSC in bsc-admin-menu.php.
			'show_in_menu'  => false,
		)
	);
}

// -----------------------------------------------------------------------------
// Meta Box Setup
// -----------------------------------------------------------------------------

add_action( 'add_meta_boxes', 'bsc_add_home_slide_meta_box' );
/**
 * Register the Home Slide options meta box.
 */
function bsc_add_home_slide_meta_box() {
	add_meta_box(
		'bsc_home_slide_fields',
		__( 'Slide Options', 'bsc-2-0' ),
		'bsc_render_home_slide_fields',
		'home_slide',
		'normal',
		'default'
	);
}

/**
 * The slide options box owns both responsive images, so the legacy featured
 * image box would be a confusing duplicate of the desktop selector.
 */
function bsc_remove_home_slide_featured_image_box() {
	remove_meta_box( 'postimagediv', 'home_slide', 'side' );
}
add_action( 'add_meta_boxes_home_slide', 'bsc_remove_home_slide_featured_image_box', 20 );

/**
 * Render one image selector in the Home Slide editor.
 *
 * @param string $field_name Form field name.
 * @param string $label      Visible field label.
 * @param string $help       Field guidance.
 * @param int    $image_id   Selected attachment ID.
 */
function bsc_render_home_slide_image_field( string $field_name, string $label, string $help, int $image_id ): void {
	$empty_message = 'slide_mobile_image_id' === $field_name
		? __( 'Sin imagen mobile propia. Se usara la imagen desktop.', 'bsc-2-0' )
		: __( 'Selecciona la imagen horizontal para desktop.', 'bsc-2-0' );
	$media_title   = 'slide_mobile_image_id' === $field_name
		? __( 'Seleccionar imagen mobile', 'bsc-2-0' )
		: __( 'Seleccionar imagen desktop', 'bsc-2-0' );
	?>
	<article class="bsc-home-slide-image-field" data-bsc-home-slide-image-field data-empty-message="<?php echo esc_attr( $empty_message ); ?>">
		<h3><?php echo esc_html( $label ); ?></h3>
		<p class="description"><?php echo esc_html( $help ); ?></p>
		<div class="bsc-home-slide-image-field__preview" data-bsc-home-slide-image-preview>
			<?php if ( $image_id > 0 ) : ?>
				<?php echo wp_get_attachment_image( $image_id, 'medium', false, array( 'alt' => '' ) ); ?>
			<?php else : ?>
				<p><?php echo esc_html( $empty_message ); ?></p>
			<?php endif; ?>
		</div>
		<input
			type="hidden"
			name="<?php echo esc_attr( $field_name ); ?>"
			value="<?php echo esc_attr( (string) $image_id ); ?>"
			data-bsc-home-slide-image-id
		>
		<div class="bsc-home-slide-image-field__actions">
			<button
				type="button"
				class="button"
				data-bsc-home-slide-image-select
				data-media-title="<?php echo esc_attr( $media_title ); ?>"
			>
				<?php echo esc_html( $image_id > 0 ? __( 'Cambiar imagen', 'bsc-2-0' ) : __( 'Seleccionar imagen', 'bsc-2-0' ) ); ?>
			</button>
			<button
				type="button"
				class="button-link-delete"
				data-bsc-home-slide-image-remove
				<?php if ( $image_id <= 0 ) : ?>
					hidden
				<?php endif; ?>
			>
				<?php esc_html_e( 'Quitar', 'bsc-2-0' ); ?>
			</button>
		</div>
	</article>
	<?php
}

/**
 * Render the editable Home Slide options.
 *
 * @param WP_Post $post Slide being edited.
 */
function bsc_render_home_slide_fields( $post ) {
	// Define all fields once for scalability.
	$fields = array(
		'slide_subtitle'    => array(
			'label' => 'Subtitle',
			'type'  => 'text',
		),
		'slide_button_text' => array(
			'label' => 'Button Text',
			'type'  => 'text',
		),
		'slide_button_link' => array(
			'label' => 'Button Link',
			'type'  => 'url',
		),
	);

	wp_nonce_field( 'bsc_save_slide_meta', 'bsc_slide_nonce' );

	printf(
		'<p><label for="slide_order"><strong>%1$s</strong></label><br><input class="small-text" type="number" min="0" step="1" name="menu_order" id="slide_order" value="%2$s"><span class="description bsc-home-slide-order-help">%3$s</span></p>',
		esc_html__( 'Orden del slide', 'bsc-2-0' ),
		esc_attr( (string) $post->menu_order ),
		esc_html__( 'Los numeros menores aparecen primero. Usa 1, 2, 3...', 'bsc-2-0' )
	);

	foreach ($fields as $name => $config) {
		$value = get_post_meta( $post->ID, "_{$name}", true );
		printf(
			'<p><label for="%1$s">%2$s</label><br>
            <input class="regular-text" type="%3$s" name="%1$s" id="%1$s" value="%4$s" /></p>',
			esc_attr( $name ),
			esc_html( $config['label'] ),
			esc_attr( $config['type'] ),
			esc_attr( $value )
		);
	}

	$desktop_image_id = (int) get_post_thumbnail_id( $post->ID );
	$mobile_image_id  = (int) get_post_meta( $post->ID, '_slide_mobile_image_id', true );
	?>
	<div class="bsc-home-slide-images">
		<?php
		bsc_render_home_slide_image_field(
			'slide_desktop_image_id',
			__( 'Imagen desktop', 'bsc-2-0' ),
			__( 'Recomendada: banner horizontal de alta resolucion.', 'bsc-2-0' ),
			$desktop_image_id
		);
		bsc_render_home_slide_image_field(
			'slide_mobile_image_id',
			__( 'Imagen mobile', 'bsc-2-0' ),
			__( 'Recomendada: composicion vertical o cuadrada optimizada para celular.', 'bsc-2-0' ),
			$mobile_image_id
		);
		?>
	</div>
	<?php
}

// -----------------------------------------------------------------------------
// Save Meta Fields
// -----------------------------------------------------------------------------

add_action( 'save_post_home_slide', 'bsc_save_home_slide_meta' );
/**
 * Save Home Slide content and responsive image selections.
 *
 * @param int $post_id Slide ID.
 */
function bsc_save_home_slide_meta( $post_id ) {
	// Verify nonce.
	if (
		!isset( $_POST['bsc_slide_nonce'] ) ||
		!wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_slide_nonce'] ) ), 'bsc_save_slide_meta' )
	) {
		return;
	}

	// Auto-save guard.
	if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
		return;
	}

	// Permissions check.
	if (!current_user_can( 'edit_post', $post_id )) {
		return;
	}

	// Save expected fields.
	$fields = array( 'slide_subtitle', 'slide_button_text', 'slide_button_link' );

	foreach ($fields as $field) {
		$value = sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) );
		$clean = 'slide_button_link' === $field
			? esc_url_raw( $value )
			: $value;

		update_post_meta( $post_id, "_$field", $clean );
	}

	$desktop_image_id = absint( wp_unslash( $_POST['slide_desktop_image_id'] ?? 0 ) );
	$mobile_image_id  = absint( wp_unslash( $_POST['slide_mobile_image_id'] ?? 0 ) );

	if ( 0 < $desktop_image_id && wp_attachment_is_image( $desktop_image_id ) ) {
		set_post_thumbnail( $post_id, $desktop_image_id );
	} else {
		delete_post_thumbnail( $post_id );
	}

	if ( 0 < $mobile_image_id && wp_attachment_is_image( $mobile_image_id ) ) {
		update_post_meta( $post_id, '_slide_mobile_image_id', $mobile_image_id );
	} else {
		delete_post_meta( $post_id, '_slide_mobile_image_id' );
	}
}

/**
 * Load the media selector only while editing Home Slides.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function bsc_home_slide_admin_assets( string $hook_suffix ): void {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'home_slide' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();

	$css_path = get_template_directory() . '/admin/bsc-home-slides.css';
	$js_path  = get_template_directory() . '/js/admin/bsc-home-slides.js';

	if ( file_exists( $css_path ) ) {
		wp_enqueue_style(
			'bsc-home-slides-admin',
			get_template_directory_uri() . '/admin/bsc-home-slides.css',
			array(),
			(string) filemtime( $css_path )
		);
	}

	if ( file_exists( $js_path ) ) {
		wp_enqueue_script(
			'bsc-home-slides-admin',
			get_template_directory_uri() . '/js/admin/bsc-home-slides.js',
			array( 'media-editor' ),
			(string) filemtime( $js_path ),
			true
		);
	}
}
add_action( 'admin_enqueue_scripts', 'bsc_home_slide_admin_assets' );

/**
 * Show the configured slide order in the admin list.
 *
 * @param array $columns Existing list columns.
 * @return array
 */
function bsc_home_slide_admin_columns( array $columns ): array {
	$ordered_columns = array();

	foreach ( $columns as $key => $label ) {
		if ( 'date' === $key ) {
			$ordered_columns['menu_order'] = __( 'Orden', 'bsc-2-0' );
		}
		$ordered_columns[ $key ] = $label;
	}

	return $ordered_columns;
}
add_filter( 'manage_home_slide_posts_columns', 'bsc_home_slide_admin_columns' );

/**
 * Render custom Home Slide admin columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Slide ID.
 */
function bsc_home_slide_admin_column_content( string $column, int $post_id ): void {
	if ( 'menu_order' === $column ) {
		echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
	}
}
add_action( 'manage_home_slide_posts_custom_column', 'bsc_home_slide_admin_column_content', 10, 2 );

/**
 * Make the Order column sortable.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function bsc_home_slide_sortable_columns( array $columns ): array {
	$columns['menu_order'] = 'menu_order';

	return $columns;
}
add_filter( 'manage_edit-home_slide_sortable_columns', 'bsc_home_slide_sortable_columns' );

/**
 * Keep the Home Slides list in the same order as the storefront by default.
 *
 * @param WP_Query $query Current admin query.
 */
function bsc_home_slide_admin_default_order( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() || 'home_slide' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( ! $query->get( 'orderby' ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'bsc_home_slide_admin_default_order' );

// BSC-021: bsc-home-favorites is registered as a submenu of BSC in bsc-admin-menu.php.
// The standalone add_menu_page() has been removed to avoid a duplicate top-level entry.

function bsc_home_favorites_settings_init_legacy() {
	register_setting( 'bsc_home_favorites_group', 'bsc_home_favorites' );

	add_settings_section(
		'bsc_fav_section',
		'Select Favorite Product SKUs (comma-separated)',
		null,
		'bsc-home-favorites'
	);

	$categories = array(
		'ultimos_lanzamientos' => 'Últimos Lanzamientos',
		'piel_seca'            => 'Piel Seca',
		'piel_normal'          => 'Piel Normal',
		'piel_mixta'           => 'Piel Mixta',
		'piel_grasa'           => 'Piel Grasa',
		'hair_care'            => 'Hair Care',
		'maquillaje'           => 'Maquillaje',
	);

	foreach ($categories as $key => $label) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key ) {
				$options = get_option( 'bsc_home_favorites' );
				$value   = isset( $options[ $key ] ) ? esc_attr( $options[ $key ] ) : '';
				printf(
					"<input class='regular-text' type='text' name='bsc_home_favorites[%s]' value='%s' placeholder='e.g. BSC:SK:1,BSC:HC:99' />",
					esc_attr( $key ),
					esc_attr( $value )
				);
			},
			'bsc-home-favorites',
			'bsc_fav_section'
		);
	}
}
// Legacy text-field settings callbacks are kept unhooked for rollback reference.

function bsc_home_favorites_categories(): array {
	return array(
		'productos_destacados' => array(
			'label' => 'Productos Destacados',
			'help'  => 'Controla el slider "Productos Destacados" del home.',
		),
		'ultimos_lanzamientos' => array(
			'label' => 'Ultimos Lanzamientos',
			'help'  => 'Controla el slider superior de lanzamientos.',
		),
		'piel_seca'            => array(
			'label' => 'Piel Seca',
			'help'  => 'Favoritos para la pestana Piel Seca.',
		),
		'piel_normal'          => array(
			'label' => 'Piel Normal',
			'help'  => 'Favoritos para la pestana Piel Normal.',
		),
		'piel_mixta'           => array(
			'label' => 'Piel Mixta',
			'help'  => 'Favoritos para la pestana Piel Mixta.',
		),
		'piel_grasa'           => array(
			'label' => 'Piel Grasa',
			'help'  => 'Favoritos para la pestana Piel Grasa.',
		),
		'hair_care'            => array(
			'label' => 'Hair Care',
			'help'  => 'Favoritos para la pestana Hair Care.',
		),
		'maquillaje'           => array(
			'label' => 'Maquillaje',
			'help'  => 'Favoritos para la pestana Maquillaje.',
		),
	);
}

function bsc_home_favorites_settings_page() {
	$categories    = bsc_home_favorites_categories();
	$featured_keys = array( 'productos_destacados', 'ultimos_lanzamientos' );
	$tab_keys      = array( 'piel_seca', 'piel_normal', 'piel_mixta', 'piel_grasa', 'hair_care', 'maquillaje' );
	?>
	<div class="wrap bsc-home-products-admin">
		<div class="bsc-admin-page-header">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Home</span>
				<h1>Productos del Home</h1>
				<p class="bsc-admin-page-header__description">Selecciona los productos que aparecen en destacados, lanzamientos y favoritos. Los productos elegidos se guardan en el orden visible del selector.</p>
			</div>
		</div>
		<form method="post" action="options.php" class="bsc-home-products-admin__form">
			<?php
			settings_fields( 'bsc_home_favorites_group' );
			?>
			<section class="bsc-home-products-admin__section" aria-labelledby="bsc-home-products-main-title">
				<div class="bsc-home-products-admin__section-header">
					<div>
						<span class="bsc-home-products-admin__section-kicker">Vistas principales</span>
						<h2 id="bsc-home-products-main-title">Productos destacados y ultimos lanzamientos</h2>
					</div>
					<p>Estos dos bloques quedan visibles al mismo tiempo para comparar rapido.</p>
				</div>
				<div class="bsc-home-products-admin__priority-grid">
					<?php foreach ( $featured_keys as $key ) : ?>
						<?php
						if ( empty( $categories[ $key ] ) ) {
							continue;
						}
						bsc_home_favorites_render_selector_card( $key, $categories[ $key ], 'principal' );
						?>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="bsc-home-products-admin__section bsc-home-products-admin__section--tabs" aria-labelledby="bsc-home-products-tabs-title" data-bsc-home-product-tabs>
				<div class="bsc-home-products-admin__section-header">
					<div>
						<span class="bsc-home-products-admin__section-kicker">Favoritos por categoria</span>
						<h2 id="bsc-home-products-tabs-title">Selecciona una pestana y marca sus productos</h2>
					</div>
					<p>Primero escoge el tipo de piel o categoria, luego busca y selecciona los productos que van en ese slider.</p>
				</div>
				<div class="bsc-home-products-admin__tabs" role="tablist" aria-label="Favoritos del home">
					<?php foreach ( $tab_keys as $index => $key ) : ?>
						<?php
						if ( empty( $categories[ $key ] ) ) {
							continue;
						}
						$tab_id   = 'bsc-home-products-tab-' . sanitize_html_class( $key );
						$panel_id = 'bsc-home-products-panel-' . sanitize_html_class( $key );
						?>
						<button
							type="button"
							id="<?php echo esc_attr( $tab_id ); ?>"
							class="bsc-home-products-admin__tab<?php echo $index === 0 ? ' is-active' : ''; ?>"
							role="tab"
							aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
							aria-controls="<?php echo esc_attr( $panel_id ); ?>"
							data-bsc-home-product-tab="<?php echo esc_attr( $key ); ?>"
						>
							<?php echo esc_html( $categories[ $key ]['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
				<div class="bsc-home-products-admin__tab-panels">
					<?php foreach ( $tab_keys as $index => $key ) : ?>
						<?php
						if ( empty( $categories[ $key ] ) ) {
							continue;
						}
						$tab_id   = 'bsc-home-products-tab-' . sanitize_html_class( $key );
						$panel_id = 'bsc-home-products-panel-' . sanitize_html_class( $key );
						?>
						<div
							id="<?php echo esc_attr( $panel_id ); ?>"
							class="bsc-home-products-admin__tab-panel<?php echo $index === 0 ? ' is-active' : ''; ?>"
							role="tabpanel"
							aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
							<?php echo $index === 0 ? '' : 'hidden'; ?>
							data-bsc-home-product-panel="<?php echo esc_attr( $key ); ?>"
						>
							<?php bsc_home_favorites_render_selector_card( $key, $categories[ $key ], 'pestana' ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
			<?php bsc_home_brands_render_admin_section(); ?>
			<?php submit_button( 'Guardar productos y marcas del home' ); ?>
		</form>
	</div>
	<?php
}

function bsc_home_favorites_render_selector_card( string $key, array $config, string $context = '' ): void {
	$label   = isset( $config['label'] ) ? (string) $config['label'] : $key;
	$help    = isset( $config['help'] ) ? (string) $config['help'] : '';
	$context = $context !== '' ? $context : 'selector';
	?>
	<div class="bsc-home-products-admin__selector-card" data-bsc-home-product-card="<?php echo esc_attr( $key ); ?>">
		<div class="bsc-home-products-admin__selector-heading">
			<span><?php echo esc_html( ucfirst( $context ) ); ?></span>
			<h3><?php echo esc_html( $label ); ?></h3>
		</div>
		<?php
		bsc_home_favorites_render_product_selector(
			array(
				'key'   => $key,
				'label' => $label,
				'help'  => $help,
			)
		);
		?>
	</div>
	<?php
}

function bsc_home_brands_render_admin_section(): void {
	$brands = bsc_get_home_brand_items();
	$terms  = bsc_home_brands_get_available_terms();
	?>
	<section class="bsc-home-products-admin__section bsc-home-products-admin__section--brands" aria-labelledby="bsc-home-brands-title">
		<div class="bsc-home-products-admin__section-header">
			<div>
				<span class="bsc-home-products-admin__section-kicker">Marcas</span>
				<h2 id="bsc-home-brands-title">Marcas destacadas del home</h2>
			</div>
			<p>Cambia la marca/categoria destino y la imagen de cada item del bloque de marcas.</p>
		</div>
		<?php if ( empty( $terms ) ) : ?>
			<p class="bsc-home-brands-admin__empty">No hay categorias de producto disponibles para seleccionar marcas.</p>
		<?php endif; ?>
		<div class="bsc-home-brands-admin__grid">
			<?php foreach ( $brands as $index => $brand ) : ?>
				<?php bsc_home_brands_render_admin_card( (int) $index, $brand, $terms ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

function bsc_home_brands_render_admin_card( int $index, array $brand, array $terms ): void {
	$slot_number       = $index + 1;
	$term_id           = absint( $brand['term_id'] ?? 0 );
	$name              = (string) ( $brand['name'] ?? '' );
	$slug              = (string) ( $brand['slug'] ?? '' );
	$image_id          = absint( $brand['image_id'] ?? 0 );
	$preview_url       = bsc_home_brand_get_image_url( $brand, 'medium' );
	$default_image_url = bsc_home_brand_get_default_image_url( $brand );
	$field_id          = 'bsc-home-brand-' . $index;
	$field_name        = 'bsc_home_brands[' . $index . ']';
	?>
	<article
		class="bsc-home-brand-card"
		data-bsc-home-brand-card
		data-default-image-url="<?php echo esc_url( $default_image_url ); ?>"
	>
		<div class="bsc-home-brand-card__header">
			<div>
				<span class="bsc-home-brand-card__slot">Slot <?php echo esc_html( (string) $slot_number ); ?></span>
				<h3 data-bsc-home-brand-current><?php echo esc_html( '' !== $name ? $name : 'Marca sin seleccionar' ); ?></h3>
			</div>
			<span class="bsc-home-brand-card__status" data-bsc-home-brand-status>
				<?php echo 0 < $image_id ? esc_html__( 'Imagen personalizada', 'bsc-2-0' ) : esc_html__( 'Imagen por defecto', 'bsc-2-0' ); ?>
			</span>
		</div>

		<div class="bsc-home-brand-card__preview" data-bsc-home-brand-preview>
			<?php if ( '' !== $preview_url ) : ?>
				<img src="<?php echo esc_url( $preview_url ); ?>" alt="">
			<?php endif; ?>
		</div>

		<input
			type="hidden"
			name="<?php echo esc_attr( $field_name ); ?>[image_id]"
			value="<?php echo esc_attr( (string) $image_id ); ?>"
			data-bsc-home-brand-image-id
		>
		<input
			type="hidden"
			name="<?php echo esc_attr( $field_name ); ?>[slug]"
			value="<?php echo esc_attr( $slug ); ?>"
			data-bsc-home-brand-slug
		>

		<label class="bsc-home-brand-card__field" for="<?php echo esc_attr( $field_id ); ?>-term">
			<span>Marca / categoria destino</span>
			<select
				id="<?php echo esc_attr( $field_id ); ?>-term"
				name="<?php echo esc_attr( $field_name ); ?>[term_id]"
				data-bsc-home-brand-term
				<?php disabled( empty( $terms ) ); ?>
			>
				<option value="">Selecciona una marca</option>
				<?php foreach ( $terms as $term ) : ?>
					<option
						value="<?php echo esc_attr( (string) $term->term_id ); ?>"
						data-slug="<?php echo esc_attr( (string) $term->slug ); ?>"
						data-name="<?php echo esc_attr( (string) $term->name ); ?>"
						<?php selected( $term_id, (int) $term->term_id ); ?>
					>
						<?php echo esc_html( bsc_home_brands_get_term_label( $term ) . ' (' . $term->slug . ')' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<div class="bsc-home-brand-card__actions">
			<button type="button" class="button" data-bsc-home-brand-select>Seleccionar imagen</button>
			<button type="button" class="button" data-bsc-home-brand-reset <?php disabled( $image_id <= 0 ); ?>>Usar imagen por defecto</button>
		</div>
	</article>
	<?php
}

function bsc_home_favorites_settings_init() {
	register_setting(
		'bsc_home_favorites_group',
		'bsc_home_favorites',
		array(
			'sanitize_callback' => 'bsc_home_favorites_sanitize_options',
		)
	);

	register_setting(
		'bsc_home_favorites_group',
		BSC_HOME_BRANDS_OPTION,
		array(
			'sanitize_callback' => 'bsc_sanitize_home_brand_items',
		)
	);

	add_settings_section(
		'bsc_fav_section',
		'Selectores de productos',
		function () {
			echo '<p>Busca productos por nombre, SKU o ID. Marca los productos que deben salir en cada slider.</p>';
		},
		'bsc-home-favorites'
	);

	foreach ( bsc_home_favorites_categories() as $key => $config ) {
		add_settings_field(
			$key,
			$config['label'],
			'bsc_home_favorites_render_product_selector',
			'bsc-home-favorites',
			'bsc_fav_section',
			array(
				'key'   => $key,
				'label' => $config['label'],
				'help'  => $config['help'],
			)
		);
	}
}
add_action( 'admin_init', 'bsc_home_favorites_settings_init' );

function bsc_home_favorites_admin_assets() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( $page !== 'bsc-home-favorites' ) {
		return;
	}

	wp_enqueue_media();

	$css_path = get_template_directory() . '/admin/bsc-home-products.css';
	$js_path  = get_template_directory() . '/js/admin/bsc-home-products.js';

	if ( function_exists( 'bsc_enqueue_admin_ui_assets' ) ) {
		bsc_enqueue_admin_ui_assets();
	}

	if ( file_exists( $css_path ) ) {
		wp_enqueue_style(
			'bsc-home-products-admin',
			get_template_directory_uri() . '/admin/bsc-home-products.css',
			array( 'bsc-admin-ui' ),
			(string) filemtime( $css_path )
		);
	}

	if ( file_exists( $js_path ) ) {
		wp_enqueue_script(
			'bsc-home-products-admin',
			get_template_directory_uri() . '/js/admin/bsc-home-products.js',
			array(),
			(string) filemtime( $js_path ),
			true
		);
	}
}
add_action( 'admin_enqueue_scripts', 'bsc_home_favorites_admin_assets' );

function bsc_home_favorites_sanitize_options( $input ): array {
	$previous = get_option( 'bsc_home_favorites', array() );
	$clean    = is_array( $previous ) ? $previous : array();
	$input    = is_array( $input ) ? $input : array();

	foreach ( array_keys( bsc_home_favorites_categories() ) as $key ) {
		$raw    = isset( $input[ $key ] ) ? (string) wp_unslash( $input[ $key ] ) : '';
		$tokens = preg_split( '/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY );
		$ids    = array();

		foreach ( $tokens as $token ) {
			$product_id = bsc_home_favorites_token_to_product_id( $token );
			if ( $product_id > 0 && ! in_array( $product_id, $ids, true ) ) {
				$ids[] = $product_id;
			}
		}

		$clean[ $key ] = implode( ',', $ids );
	}

	return $clean;
}

function bsc_home_favorites_render_product_selector( array $args ): void {
	$key          = (string) $args['key'];
	$options      = get_option( 'bsc_home_favorites', array() );
	$value        = is_array( $options ) && isset( $options[ $key ] ) ? (string) $options[ $key ] : '';
	$selected_ids = bsc_home_favorites_value_to_product_ids( $value );
	$products     = bsc_home_favorites_get_selector_products( $selected_ids );
	$field_id     = 'bsc-home-products-' . sanitize_html_class( $key );
	$hidden_value = implode( ',', $selected_ids );

	if ( ! function_exists( 'wc_get_product' ) ) {
		echo '<p class="description">WooCommerce no esta disponible para cargar productos.</p>';
		return;
	}
	?>
	<div class="bsc-home-product-selector" data-bsc-home-product-selector>
		<input
			type="hidden"
			id="<?php echo esc_attr( $field_id ); ?>"
			name="bsc_home_favorites[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( $hidden_value ); ?>"
			data-bsc-home-product-selected-input
		>
		<?php if ( ! empty( $args['help'] ) ) : ?>
			<p class="bsc-home-product-selector__help"><?php echo esc_html( $args['help'] ); ?></p>
		<?php endif; ?>
		<div class="bsc-home-product-selector__summary">
			<div class="bsc-home-product-selector__status">
				<strong data-bsc-home-product-count>0 seleccionados</strong>
				<span data-bsc-home-product-visible-count></span>
			</div>
			<button type="button" class="button button-small" data-bsc-home-product-clear>Limpiar</button>
		</div>
		<div class="bsc-home-product-selector__chips" data-bsc-home-product-chips></div>
		<label class="screen-reader-text" for="<?php echo esc_attr( $field_id ); ?>-search">
			Buscar productos para <?php echo esc_html( (string) $args['label'] ); ?>
		</label>
		<input
			type="search"
			id="<?php echo esc_attr( $field_id ); ?>-search"
			class="bsc-home-product-selector__search"
			placeholder="Buscar producto, SKU o ID..."
			data-bsc-home-product-search
		>
		<div class="bsc-home-product-selector__list" data-bsc-home-product-list>
			<?php if ( empty( $products ) ) : ?>
				<p class="bsc-home-product-selector__empty">No hay productos para seleccionar.</p>
			<?php endif; ?>
			<?php foreach ( $products as $product ) : ?>
				<?php
				$product_id    = (int) $product->get_id();
				$product_name  = $product->get_name();
				$product_sku   = $product->get_sku();
				$is_selected   = in_array( $product_id, $selected_ids, true );
				$search_string = strtolower( $product_name . ' ' . $product_sku . ' ' . $product_id );
				?>
				<label class="bsc-home-product-selector__row<?php echo esc_attr( $is_selected ? ' is-selected' : '' ); ?>" data-bsc-home-product-row data-search="<?php echo esc_attr( $search_string ); ?>">
					<input
						type="checkbox"
						value="<?php echo esc_attr( (string) $product_id ); ?>"
						data-label="<?php echo esc_attr( $product_name ); ?>"
						data-sku="<?php echo esc_attr( $product_sku ); ?>"
						<?php checked( $is_selected ); ?>
					>
					<span class="bsc-home-product-selector__name"><?php echo esc_html( $product_name ); ?></span>
					<span class="bsc-home-product-selector__meta">
						#<?php echo esc_html( (string) $product_id ); ?>
						<?php if ( $product_sku !== '' ) : ?>
							/ <?php echo esc_html( $product_sku ); ?>
						<?php endif; ?>
					</span>
				</label>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

function bsc_home_favorites_value_to_product_ids( string $value ): array {
	$tokens = preg_split( '/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY );
	$ids    = array();

	foreach ( $tokens as $token ) {
		$product_id = bsc_home_favorites_token_to_product_id( $token );
		if ( $product_id > 0 && ! in_array( $product_id, $ids, true ) ) {
			$ids[] = $product_id;
		}
	}

	return $ids;
}

function bsc_home_favorites_token_to_product_id( string $token ): int {
	$token = trim( $token );
	if ( $token === '' ) {
		return 0;
	}

	if ( ctype_digit( $token ) ) {
		return absint( $token );
	}

	return function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( $token ) : 0;
}

function bsc_home_favorites_get_selector_products( array $selected_ids ): array {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	static $base_products = null;

	if ( $base_products === null ) {
		$query = new WP_Query(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$base_products = array();
		foreach ( $query->posts as $product_id ) {
			$product = wc_get_product( (int) $product_id );
			if ( $product ) {
				$base_products[ (int) $product_id ] = $product;
			}
		}
	}

	$products = $base_products;
	foreach ( $selected_ids as $product_id ) {
		if ( ! isset( $products[ $product_id ] ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$products[ $product_id ] = $product;
			}
		}
	}

	$selected_positions = array_flip( array_values( $selected_ids ) );
	uasort(
		$products,
		function ( $a, $b ) use ( $selected_positions ) {
			$a_id  = (int) $a->get_id();
			$b_id  = (int) $b->get_id();
			$a_pos = $selected_positions[ $a_id ] ?? PHP_INT_MAX;
			$b_pos = $selected_positions[ $b_id ] ?? PHP_INT_MAX;

			if ( $a_pos !== $b_pos ) {
				return $a_pos <=> $b_pos;
			}

			return strnatcasecmp( $a->get_name(), $b->get_name() );
		}
	);

	return $products;
}
