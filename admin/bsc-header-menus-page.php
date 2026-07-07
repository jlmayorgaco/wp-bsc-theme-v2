<?php
/**
 * Admin page for editable header menu content, cover images and links.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/components/header/header-menu-config.php';

add_action( 'admin_init', 'bsc_handle_header_menus_save' );
add_action( 'admin_enqueue_scripts', 'bsc_enqueue_header_menus_admin_assets' );
add_action( 'wp_ajax_bsc_header_menu_search_categories', 'bsc_ajax_header_menu_search_categories' );

/**
 * Enqueue assets for the Header Menus admin page.
 */
function bsc_enqueue_header_menus_admin_assets(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-header-menus' !== $page ) {
		return;
	}

	bsc_enqueue_admin_ui_assets();
	wp_enqueue_media();

	$css_path = get_template_directory() . '/admin/bsc-header-menus.css';
	$js_path  = get_template_directory() . '/js/admin/bsc-header-menus.js';

	wp_enqueue_style(
		'bsc-header-menus-admin',
		get_template_directory_uri() . '/admin/bsc-header-menus.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);

	wp_enqueue_script(
		'bsc-header-menus-admin',
		get_template_directory_uri() . '/js/admin/bsc-header-menus.js',
		array( 'jquery' ),
		file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
		true
	);

	wp_localize_script(
		'bsc-header-menus-admin',
		'bscHeaderMenus',
		array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'bsc_header_menus_search' ),
			'minSearchLength' => 2,
			'searchDelay'     => 250,
		)
	);
}

/**
 * Search product categories for Header Menus without rendering every category into every row.
 */
function bsc_ajax_header_menu_search_categories(): void {
	check_ajax_referer( 'bsc_header_menus_search', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'No tienes permisos.', 'bsc-2-0' ),
			),
			403
		);
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce is verified above.
	$query    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$selected = isset( $_GET['selected'] ) ? absint( wp_unslash( $_GET['selected'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	wp_send_json_success(
		array(
			'choices' => bsc_search_header_menu_product_category_choices( $query, $selected, 30 ),
		)
	);
}

/**
 * Save Header Menus admin settings.
 */
function bsc_handle_header_menus_save(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-header-menus' !== $page || ! isset( $_POST['bsc_header_menus_nonce'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
	}

	check_admin_referer( 'bsc_header_menus_save', 'bsc_header_menus_nonce' );

	$submitted = isset( $_POST['bsc_header_menu_covers'] ) && is_array( $_POST['bsc_header_menu_covers'] )
		? map_deep( wp_unslash( $_POST['bsc_header_menu_covers'] ), 'sanitize_text_field' )
		: array();
	$clean_covers = bsc_sanitize_header_menu_cover_options( $submitted );

	update_option(
		BSC_HEADER_MENU_COVERS_OPTION,
		$clean_covers,
		false
	);

	$content_submitted = isset( $_POST['bsc_header_menu_content'] ) && is_array( $_POST['bsc_header_menu_content'] )
		? map_deep( wp_unslash( $_POST['bsc_header_menu_content'] ), 'sanitize_text_field' )
		: array();
	$clean_content = bsc_sanitize_header_menu_content_options( $content_submitted );

	update_option(
		BSC_HEADER_MENU_CONTENT_OPTION,
		$clean_content,
		false
	);

	bsc_save_header_menu_native_from_options( $clean_content, $clean_covers );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'       => 'bsc-header-menus',
				'bsc_notice' => 'saved',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/**
 * Render the Header Menus admin page.
 */
function bsc_render_header_menus_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
	}

	bsc_ensure_header_menu_native_menu();

	$native_configs   = bsc_get_header_menu_native_configs( true );
	$definitions      = bsc_get_header_menu_cover_definitions();
	$saved            = bsc_get_header_menu_cover_options();
	$menu_options     = !empty( $native_configs )
		? bsc_header_menu_configs_to_content_options( $native_configs )
		: bsc_get_header_menu_content_options();
	?>
	<div class="wrap bsc-header-menus-admin">
		<div class="bsc-admin-page-header">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Header</span>
				<h1>Header Menus</h1>
				<p class="bsc-admin-page-header__description">Edita los dropdowns principales del menu superior: titulo, columnas, items, categoria destino y cover.</p>
			</div>
		</div>

		<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag. ?>
		<?php if ( isset( $_GET['bsc_notice'] ) && 'saved' === sanitize_key( wp_unslash( $_GET['bsc_notice'] ) ) ) : ?>
			<div class="bsc-admin-note bsc-admin-note--success">Header menus guardados.</div>
		<?php endif; ?>

		<form method="post" class="bsc-header-menus-admin__form">
			<?php wp_nonce_field( 'bsc_header_menus_save', 'bsc_header_menus_nonce' ); ?>
			<section class="bsc-header-menus-admin__section">
				<div class="bsc-header-menus-admin__section-header">
					<div>
						<h2>Covers del dropdown</h2>
						<p>Imagen y link principal de cada mega menu.</p>
					</div>
				</div>
				<div class="bsc-header-menus-admin__grid">
				<?php foreach ( $definitions as $slug => $definition ) : ?>
					<?php bsc_render_header_menu_cover_card( $slug, $definition, $saved[ $slug ] ?? array() ); ?>
				<?php endforeach; ?>
				</div>
			</section>

			<section class="bsc-header-menus-admin__section">
				<div class="bsc-header-menus-admin__section-header">
					<div>
						<h2>Contenido del menu</h2>
						<p>Edita cada columna y selecciona la categoria destino de los items. El numero se genera automaticamente cuando la columna tiene numeracion activa.</p>
					</div>
				</div>
				<div class="bsc-header-menus-admin__editors">
					<?php foreach ( $menu_options as $slug => $menu_config ) : ?>
						<?php bsc_render_header_menu_editor( $slug, $menu_config ); ?>
					<?php endforeach; ?>
				</div>
			</section>
			<?php submit_button( 'Guardar header menus' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Render one editable header menu cover card.
 *
 * @param string $slug       Header menu slug.
 * @param array  $definition Header menu cover default definition.
 * @param array  $saved      Saved header menu cover values.
 */
function bsc_render_header_menu_cover_card( string $slug, array $definition, array $saved ): void {
	$image_id          = absint( $saved['image_id'] ?? 0 );
	$saved_link        = (string) ( $saved['link'] ?? '' );
	$label             = (string) ( $definition['label'] ?? $slug );
	$default_image_url = (string) ( $definition['default_image'] ?? '' );
	$default_link      = (string) ( $definition['default_link'] ?? '' );
	$preview_url       = bsc_get_header_menu_cover_image_url( $slug, 'medium' );
	$field_id          = 'bsc-header-menu-cover-' . sanitize_html_class( strtolower( $slug ) );
	$link_field_id     = $field_id . '-link';
	?>
	<section
		class="bsc-header-menu-card bsc-admin-panel"
		data-bsc-header-menu-card
		data-default-image-url="<?php echo esc_url( $default_image_url ); ?>"
	>
		<div class="bsc-admin-panel__header">
			<div>
				<h2 class="bsc-admin-panel__title"><?php echo esc_html( $label ); ?></h2>
				<p class="bsc-admin-panel__description">Cover del dropdown del header.</p>
			</div>
			<span class="bsc-header-menu-card__status" data-bsc-header-menu-status>
				<?php echo 0 < $image_id ? esc_html__( 'Imagen personalizada', 'bsc-2-0' ) : esc_html__( 'Imagen por defecto', 'bsc-2-0' ); ?>
			</span>
		</div>

		<div class="bsc-header-menu-card__preview" data-bsc-header-menu-preview>
			<?php if ( '' !== $preview_url ) : ?>
				<img src="<?php echo esc_url( $preview_url ); ?>" alt="">
			<?php endif; ?>
		</div>

		<input
			type="hidden"
			id="<?php echo esc_attr( $field_id ); ?>"
			name="bsc_header_menu_covers[<?php echo esc_attr( $slug ); ?>][image_id]"
			value="<?php echo esc_attr( (string) $image_id ); ?>"
			data-bsc-header-menu-image-id
		>

		<div class="bsc-header-menu-card__actions">
			<button type="button" class="button" data-bsc-header-menu-select>Seleccionar imagen</button>
			<button type="button" class="button" data-bsc-header-menu-reset <?php disabled( $image_id <= 0 ); ?>>Usar imagen por defecto</button>
		</div>

		<label class="bsc-header-menu-card__field" for="<?php echo esc_attr( $link_field_id ); ?>">
			<span>Link del cover</span>
			<input
				type="text"
				id="<?php echo esc_attr( $link_field_id ); ?>"
				name="bsc_header_menu_covers[<?php echo esc_attr( $slug ); ?>][link]"
				value="<?php echo esc_attr( $saved_link ); ?>"
				class="regular-text"
				placeholder="<?php echo esc_attr( $default_link ); ?>"
			>
		</label>
		<p class="description">Deja el campo vacio para usar <code><?php echo esc_html( $default_link ); ?></code>.</p>
	</section>
	<?php
}

/**
 * Render one editable mega menu.
 *
 * @param string $slug        Header menu slug.
 * @param array  $menu_config Saved or default menu content config.
 */
function bsc_render_header_menu_editor( string $slug, array $menu_config ): void {
	$field_name = 'bsc_header_menu_content[' . $slug . ']';
	$field_id   = 'bsc-header-menu-editor-' . sanitize_html_class( strtolower( $slug ) );
	$sections   = isset( $menu_config['sections'] ) && is_array( $menu_config['sections'] )
		? array_values( $menu_config['sections'] )
		: array();
	$name       = (string) ( $menu_config['name'] ?? $slug );
	$enabled    = ! empty( $menu_config['enabled'] );
	$link       = (string) ( $menu_config['link'] ?? '' );
	?>
	<article class="bsc-header-menu-editor bsc-admin-panel" data-bsc-header-menu-editor data-menu-slug="<?php echo esc_attr( $slug ); ?>">
		<div class="bsc-header-menu-editor__header">
			<div class="bsc-header-menu-editor__title-field">
				<label for="<?php echo esc_attr( $field_id ); ?>-name">Titulo del menu</label>
				<input
					type="text"
					id="<?php echo esc_attr( $field_id ); ?>-name"
					name="<?php echo esc_attr( $field_name ); ?>[name]"
					value="<?php echo esc_attr( $name ); ?>"
					class="regular-text"
				>
			</div>
			<?php if ( empty( $sections ) ) : ?>
				<div class="bsc-header-menu-editor__title-field">
					<label for="<?php echo esc_attr( $field_id ); ?>-link">Link principal</label>
					<input
						type="text"
						id="<?php echo esc_attr( $field_id ); ?>-link"
						name="<?php echo esc_attr( $field_name ); ?>[link]"
						value="<?php echo esc_attr( $link ); ?>"
						placeholder="/contact-us/"
					>
				</div>
			<?php else : ?>
				<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>[link]" value="<?php echo esc_attr( $link ); ?>">
			<?php endif; ?>
			<label class="bsc-header-menu-editor__enabled">
				<input
					type="checkbox"
					name="<?php echo esc_attr( $field_name ); ?>[enabled]"
					value="1"
					<?php checked( $enabled ); ?>
				>
				Activo en header
			</label>
		</div>

		<div class="bsc-header-menu-editor__sections" data-bsc-header-menu-sections>
			<?php foreach ( $sections as $section_index => $section ) : ?>
				<?php bsc_render_header_menu_section( $slug, (string) $section_index, $section ); ?>
			<?php endforeach; ?>
		</div>

		<div class="bsc-header-menu-editor__actions">
			<button type="button" class="button" data-bsc-header-menu-add-section>Agregar columna</button>
		</div>

		<template data-bsc-header-menu-section-template>
			<?php
			bsc_render_header_menu_section(
				$slug,
				'__SECTION_INDEX__',
				array(
					'title'    => '',
					'numbered' => false,
					'items'    => array(),
				),
				true
			);
			?>
		</template>
		<template data-bsc-header-menu-item-template>
			<?php
			bsc_render_header_menu_item(
				$slug,
				'__SECTION_INDEX__',
				'__ITEM_INDEX__',
				array(
					'label'   => '',
					'term_id' => 0,
				),
				true
			);
			?>
		</template>
	</article>
	<?php
}

/**
 * Render one editable menu column.
 *
 * @param string $menu_slug        Header menu slug.
 * @param string $section_index    Section index or template placeholder.
 * @param array  $section          Section config.
 * @param bool   $is_template      Whether this is a JS template.
 */
function bsc_render_header_menu_section( string $menu_slug, string $section_index, array $section, bool $is_template = false ): void {
	$field_name     = 'bsc_header_menu_content[' . $menu_slug . '][sections][' . $section_index . ']';
	$field_id       = 'bsc-header-menu-' . sanitize_html_class( strtolower( $menu_slug ) ) . '-section-' . sanitize_html_class( $section_index );
	$items          = isset( $section['items'] ) && is_array( $section['items'] ) ? array_values( $section['items'] ) : array();
	$title          = (string) ( $section['title'] ?? '' );
	$numbered       = ! empty( $section['numbered'] );
	$section_number = is_numeric( $section_index ) ? ( (int) $section_index + 1 ) : 1;
	?>
	<section class="bsc-header-menu-section" data-bsc-header-menu-section>
		<div class="bsc-header-menu-section__header">
			<span class="bsc-header-menu-section__index" data-bsc-header-menu-section-number><?php echo esc_html( (string) $section_number ); ?></span>
			<label class="bsc-header-menu-section__title" for="<?php echo esc_attr( $field_id ); ?>-title">
				<span>Titulo de columna</span>
				<input
					type="text"
					id="<?php echo esc_attr( $field_id ); ?>-title"
					name="<?php echo esc_attr( $field_name ); ?>[title]"
					value="<?php echo esc_attr( $title ); ?>"
					placeholder="SKIN CARE"
					data-bsc-header-menu-section-title
				>
			</label>
			<label class="bsc-header-menu-section__numbered">
				<input
					type="checkbox"
					name="<?php echo esc_attr( $field_name ); ?>[numbered]"
					value="1"
					data-bsc-header-menu-section-numbered
					<?php checked( $numbered ); ?>
				>
				Numerar items
			</label>
			<div class="bsc-header-menu-section__move-actions">
				<button type="button" class="button button-small" data-bsc-header-menu-section-up>Subir</button>
				<button type="button" class="button button-small" data-bsc-header-menu-section-down>Bajar</button>
				<button type="button" class="button button-small bsc-header-menu-section__remove" data-bsc-header-menu-remove-section>Quitar</button>
			</div>
		</div>

		<div class="bsc-header-menu-section__items" data-bsc-header-menu-items>
			<?php foreach ( $items as $item_index => $item ) : ?>
				<?php bsc_render_header_menu_item( $menu_slug, $section_index, (string) $item_index, $item, $is_template ); ?>
			<?php endforeach; ?>
		</div>

		<button type="button" class="button button-small" data-bsc-header-menu-add-item>Agregar item</button>
	</section>
	<?php
}

/**
 * Render one editable menu item.
 *
 * @param string $menu_slug        Header menu slug.
 * @param string $section_index    Section index or template placeholder.
 * @param string $item_index       Item index or template placeholder.
 * @param array  $item             Item config.
 * @param bool   $is_template      Whether this is a JS template.
 */
function bsc_render_header_menu_item( string $menu_slug, string $section_index, string $item_index, array $item, bool $is_template = false ): void {
	$field_name       = 'bsc_header_menu_content[' . $menu_slug . '][sections][' . $section_index . '][items][' . $item_index . ']';
	$field_id         = 'bsc-header-menu-' . sanitize_html_class( strtolower( $menu_slug ) ) . '-section-' . sanitize_html_class( $section_index ) . '-item-' . sanitize_html_class( $item_index );
	$item_number      = is_numeric( $item_index ) ? ( (int) $item_index + 1 ) : 1;
	$label            = bsc_header_menu_strip_auto_number( (string) ( $item['label'] ?? $item['title'] ?? '' ) );
	$term_id          = absint( $item['term_id'] ?? 0 );
	$selected_choice  = $is_template ? array() : bsc_get_header_menu_product_category_choice( $term_id );
	$selected_display = (string) ( $selected_choice['label'] ?? '' );
	?>
	<div class="bsc-header-menu-item" data-bsc-header-menu-item>
		<span class="bsc-header-menu-item__number" data-bsc-header-menu-item-number><?php echo esc_html( (string) $item_number ); ?></span>
		<label class="bsc-header-menu-item__label" for="<?php echo esc_attr( $field_id ); ?>-label">
			<span>Texto</span>
			<input
				type="text"
				id="<?php echo esc_attr( $field_id ); ?>-label"
				name="<?php echo esc_attr( $field_name ); ?>[label]"
				value="<?php echo esc_attr( $label ); ?>"
				placeholder="Nombre visible"
				data-bsc-header-menu-item-label
			>
		</label>
		<div class="bsc-header-menu-item__category">
			<label for="<?php echo esc_attr( $field_id ); ?>-category-search">Buscar categoria</label>
			<input
				type="search"
				id="<?php echo esc_attr( $field_id ); ?>-category-search"
				class="bsc-header-menu-item__category-search"
				placeholder="Buscar por nombre o slug..."
				value="<?php echo esc_attr( $selected_display ); ?>"
				autocomplete="off"
				data-bsc-header-menu-category-search
			>
			<?php bsc_render_header_menu_category_select( $field_id . '-category', $field_name . '[term_id]', $term_id, $selected_choice ); ?>
		</div>
		<div class="bsc-header-menu-item__actions">
			<button type="button" class="button button-small" data-bsc-header-menu-item-up>Subir</button>
			<button type="button" class="button button-small" data-bsc-header-menu-item-down>Bajar</button>
			<button type="button" class="button button-small bsc-header-menu-item__remove" data-bsc-header-menu-remove-item>Quitar</button>
		</div>
	</div>
	<?php
}

/**
 * Render a product category select.
 *
 * @param string $field_id         Field id.
 * @param string $field_name       Field name.
 * @param int    $selected_term_id Selected term id.
 * @param array  $selected_choice  Selected product category choice.
 */
function bsc_render_header_menu_category_select( string $field_id, string $field_name, int $selected_term_id, array $selected_choice = array() ): void {
	?>
	<select
		id="<?php echo esc_attr( $field_id ); ?>"
		name="<?php echo esc_attr( $field_name ); ?>"
		data-bsc-header-menu-category-select
	>
		<option value="">Selecciona categoria</option>
		<?php if ( ! empty( $selected_choice ) ) : ?>
			<option
				value="<?php echo esc_attr( (string) ( $selected_choice['id'] ?? $selected_term_id ) ); ?>"
				data-name="<?php echo esc_attr( (string) ( $selected_choice['name'] ?? '' ) ); ?>"
				data-slug="<?php echo esc_attr( (string) ( $selected_choice['slug'] ?? '' ) ); ?>"
				selected
			>
				<?php echo esc_html( (string) ( $selected_choice['label'] ?? '' ) ); ?>
			</option>
		<?php endif; ?>
	</select>
	<?php
}
