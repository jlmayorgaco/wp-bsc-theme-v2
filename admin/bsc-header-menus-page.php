<?php
/**
 * Admin page for editable header menu cover images and links.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', 'bsc_handle_header_menus_save' );
add_action( 'admin_enqueue_scripts', 'bsc_enqueue_header_menus_admin_assets' );

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

	update_option(
		BSC_HEADER_MENU_COVERS_OPTION,
		bsc_sanitize_header_menu_cover_options( $submitted ),
		false
	);

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

	$definitions = bsc_get_header_menu_cover_definitions();
	$saved       = bsc_get_header_menu_cover_options();
	?>
	<div class="wrap bsc-header-menus-admin">
		<div class="bsc-admin-page-header">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Header</span>
				<h1>Header Menus</h1>
				<p class="bsc-admin-page-header__description">Cambia la imagen y el enlace del cover que aparece en los dropdowns principales del menu superior.</p>
			</div>
		</div>

		<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag. ?>
		<?php if ( isset( $_GET['bsc_notice'] ) && 'saved' === sanitize_key( wp_unslash( $_GET['bsc_notice'] ) ) ) : ?>
			<div class="bsc-admin-note bsc-admin-note--success">Header menus guardados.</div>
		<?php endif; ?>

		<form method="post" class="bsc-header-menus-admin__form">
			<?php wp_nonce_field( 'bsc_header_menus_save', 'bsc_header_menus_nonce' ); ?>
			<div class="bsc-header-menus-admin__grid">
				<?php foreach ( $definitions as $slug => $definition ) : ?>
					<?php bsc_render_header_menu_cover_card( $slug, $definition, $saved[ $slug ] ?? array() ); ?>
				<?php endforeach; ?>
			</div>
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
