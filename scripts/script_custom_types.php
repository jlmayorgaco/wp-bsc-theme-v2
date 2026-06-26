<?php
/**
 * Register Custom Post Type: Home Slides
 * Add Meta Box and Fields (No Plugin)
 */

add_action( 'init', 'bsc_register_home_slide_post_type' );
function bsc_register_home_slide_post_type() {
	register_post_type(
		'home_slide',
		array(
			'labels'        => array(
				'name'          => __( 'BSC Home Slides', 'bsc' ),
				'singular_name' => __( 'Home Slide', 'bsc' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'menu_icon'     => 'dashicons-slides',
			'supports'      => array( 'title', 'thumbnail' ),
			'show_in_rest'  => true,
			'menu_position' => 20,
			// BSC-021: hide CPT auto-menu; Hero Slides submenu lives under BSC in bsc-admin-menu.php
			'show_in_menu'  => false,
		)
	);
}

// -----------------------------------------------------------------------------
// Meta Box Setup
// -----------------------------------------------------------------------------

add_action( 'add_meta_boxes', 'bsc_add_home_slide_meta_box' );
function bsc_add_home_slide_meta_box() {
	add_meta_box(
		'bsc_home_slide_fields',
		__( 'Slide Options', 'bsc' ),
		'bsc_render_home_slide_fields',
		'home_slide',
		'normal',
		'default'
	);
}

function bsc_render_home_slide_fields( $post ) {
	// Define all fields once for scalability
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
}

// -----------------------------------------------------------------------------
// Save Meta Fields
// -----------------------------------------------------------------------------

add_action( 'save_post_home_slide', 'bsc_save_home_slide_meta' );
function bsc_save_home_slide_meta( $post_id ) {
	// Verify nonce
	if (
		!isset( $_POST['bsc_slide_nonce'] ) ||
		!wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_slide_nonce'] ) ), 'bsc_save_slide_meta' )
	) {
		return;
	}

	// Auto-save guard
	if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
		return;
	}

	// Permissions check
	if (!current_user_can( 'edit_post', $post_id )) {
		return;
	}

	// Save expected fields
	$fields = array( 'slide_subtitle', 'slide_button_text', 'slide_button_link' );

	foreach ($fields as $field) {
		$value = sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) );
		$clean = $field === 'slide_button_link'
			? esc_url_raw( $value )
			: $value;

		update_post_meta( $post_id, "_$field", $clean );
	}
}

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
			do_settings_sections( 'bsc-home-favorites' );
			submit_button( 'Guardar productos del home' );
			?>
		</form>
	</div>
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
			<strong data-bsc-home-product-count>0 seleccionados</strong>
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
