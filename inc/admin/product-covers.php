<?php
defined( 'ABSPATH' ) || exit;

/**
 * BSC Product Covers
 * Adds cover and dual-stock fields to native Woo product edit screens.
 */

add_action(
	'add_meta_boxes',
	function (): void {
		add_meta_box(
			'bsc_product_covers',
			'Covers de Producto',
			'bsc_product_covers_render',
			'product',
			'side',
			'default'
		);
	}
);

function bsc_product_covers_render( WP_Post $post ): void {
	wp_nonce_field( 'bsc_product_covers_save', 'bsc_product_covers_nonce' );

	$fields = array(
		'bsc_cover_desktop' => 'Cover Desktop',
		'bsc_cover_mobile'  => 'Cover Mobile',
	);

	foreach ($fields as $key => $label) :
		$attachment_id = (int) get_post_meta( $post->ID, $key, true );
		$image_src     = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
		?>
		<div class="bsc-cover-field bsc-admin-cover-field">
			<p class="bsc-admin-cover-label"><strong><?php echo esc_html( $label ); ?></strong></p>
			<div class="bsc-cover-preview bsc-admin-cover-preview">
				<?php if ($image_src) : ?>
					<img src="<?php echo esc_url( $image_src ); ?>" class="bsc-admin-cover-image" alt="">
				<?php endif; ?>
			</div>
			<input
				type="hidden"
				name="<?php echo esc_attr( $key ); ?>"
				id="<?php echo esc_attr( $key ); ?>"
				value="<?php echo esc_attr( $attachment_id ?: '' ); ?>"
			>
			<button type="button" class="button bsc-cover-select" data-field="<?php echo esc_attr( $key ); ?>">
				<?php echo $attachment_id ? esc_html__( 'Cambiar imagen' ) : esc_html__( 'Seleccionar imagen' ); ?>
			</button>
			<?php if ($attachment_id) : ?>
				<button type="button" class="button bsc-cover-remove bsc-admin-cover-remove" data-field="<?php echo esc_attr( $key ); ?>">
					Eliminar
				</button>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>

	<hr class="bsc-admin-cover-divider">
	<p class="bsc-admin-cover-extra-heading"><strong>Imagenes extra (se muestran debajo del resumen del producto)</strong></p>

	<?php for ($i = 1; $i <= 3; $i++) : ?>
		<?php
		$extra_key = "_bsc_extra_image_{$i}";
		$extra_id  = (int) get_post_meta( $post->ID, $extra_key, true );
		$extra_src = $extra_id ? wp_get_attachment_image_url( $extra_id, 'thumbnail' ) : '';
		?>
		<div class="bsc-cover-field bsc-admin-cover-field">
			<p class="bsc-admin-cover-label"><strong>Imagen extra <?php echo (int) $i; ?></strong></p>
			<div class="bsc-cover-preview bsc-admin-cover-preview">
				<?php if ($extra_src) : ?>
					<img src="<?php echo esc_url( $extra_src ); ?>" class="bsc-admin-cover-image" alt="">
				<?php endif; ?>
			</div>
			<input
				type="hidden"
				name="<?php echo esc_attr( $extra_key ); ?>"
				id="<?php echo esc_attr( $extra_key ); ?>"
				value="<?php echo esc_attr( $extra_id ?: '' ); ?>"
			>
			<button type="button" class="button bsc-cover-select" data-field="<?php echo esc_attr( $extra_key ); ?>">
				<?php echo $extra_id ? esc_html__( 'Cambiar imagen' ) : esc_html__( 'Seleccionar imagen' ); ?>
			</button>
			<?php if ($extra_id) : ?>
				<button type="button" class="button bsc-cover-remove bsc-admin-cover-remove" data-field="<?php echo esc_attr( $extra_key ); ?>">
					Eliminar
				</button>
			<?php endif; ?>
		</div>
		<?php
	endfor;
}

add_action(
	'save_post_product',
	function ( int $post_id ): void {
		if (
		!isset( $_POST['bsc_product_covers_nonce'] ) ||
		!wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['bsc_product_covers_nonce'] ) ),
			'bsc_product_covers_save'
		)
		) {
			return;
		}

		if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
			return;
		}

		$all_keys = array(
			'bsc_cover_desktop',
			'bsc_cover_mobile',
			'_bsc_extra_image_1',
			'_bsc_extra_image_2',
			'_bsc_extra_image_3',
		);

		foreach ($all_keys as $key) {
			if (!isset( $_POST[ $key ] )) {
				continue;
			}

			$value = absint( $_POST[ $key ] );
			if ($value > 0) {
				update_post_meta( $post_id, $key, $value );
			} else {
				delete_post_meta( $post_id, $key );
			}
		}
	}
);

add_action(
	'add_meta_boxes',
	function (): void {
		add_meta_box(
			'bsc_product_stock',
			'BSC Stock Dual',
			'bsc_product_stock_render',
			'product',
			'side',
			'default'
		);
	}
);

function bsc_product_stock_render( WP_Post $post ): void {
	wp_nonce_field( 'bsc_product_stock_save', 'bsc_product_stock_nonce' );

	$bodega     = (int) get_post_meta( $post->ID, '_stock_bodega', true );
	$tienda     = (int) get_post_meta( $post->ID, '_stock_tienda', true );
	$envio_tipo = get_post_meta( $post->ID, '_envio_tipo', true ) ?: 'bodega';
	?>
	<div class="bsc-admin-stock-field">
		<label for="_stock_bodega" class="bsc-admin-stock-label">
			📦 Stock en Bodega <span class="bsc-admin-stock-label-note">(despacho web)</span>
		</label>
		<input type="number" id="_stock_bodega" name="_stock_bodega" value="<?php echo esc_attr( $bodega ); ?>" min="0" class="bsc-admin-stock-input">
	</div>
	<div class="bsc-admin-stock-field">
		<label for="_stock_tienda" class="bsc-admin-stock-label">
			🏪 Stock en Tienda <span class="bsc-admin-stock-label-note">(showroom)</span>
		</label>
		<input type="number" id="_stock_tienda" name="_stock_tienda" value="<?php echo esc_attr( $tienda ); ?>" min="0" class="bsc-admin-stock-input">
	</div>
	<div class="bsc-admin-stock-field">
		<label for="_envio_tipo" class="bsc-admin-stock-label">Origen de despacho</label>
		<select id="_envio_tipo" name="_envio_tipo" class="bsc-admin-stock-input">
			<option value="bodega" <?php selected( $envio_tipo, 'bodega' ); ?>>Desde Bodega</option>
			<option value="tienda" <?php selected( $envio_tipo, 'tienda' ); ?>>Desde Tienda</option>
			<option value="ambos" <?php selected( $envio_tipo, 'ambos' ); ?>>Ambos</option>
		</select>
	</div>
	<?php
}

add_action(
	'save_post_product',
	function ( int $post_id ): void {
		if (
		!isset( $_POST['bsc_product_stock_nonce'] ) ||
		!wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['bsc_product_stock_nonce'] ) ),
			'bsc_product_stock_save'
		)
		) {
			return;
		}

		if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
			return;
		}

		foreach (array( '_stock_bodega', '_stock_tienda' ) as $key) {
			if (isset( $_POST[ $key ] )) {
				update_post_meta( $post_id, $key, max( 0, absint( $_POST[ $key ] ) ) );
			}
		}

		if ( class_exists( 'BSC_Stock' ) ) {
			BSC_Stock::sync_stock_status( $post_id );
		}

		if (isset( $_POST['_envio_tipo'] )) {
			$allowed = array( 'bodega', 'tienda', 'ambos' );
			$value   = sanitize_text_field( wp_unslash( $_POST['_envio_tipo'] ) );
			if (in_array( $value, $allowed, true )) {
				update_post_meta( $post_id, '_envio_tipo', $value );
			}
		}
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( string $hook ): void {
		$screen            = get_current_screen();
		$on_native_product = in_array( $hook, array( 'post.php', 'post-new.php' ), true )
		&& $screen
		&& $screen->post_type === 'product';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing.
		$on_bsc_product_edit = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ) === 'bsc-product-edit';

		if (!$on_native_product && !$on_bsc_product_edit) {
			return;
		}

		wp_enqueue_media();

		$css_path = get_template_directory() . '/admin/bsc-product-edit.css';
		wp_enqueue_style(
			'bsc-product-edit-admin',
			get_template_directory_uri() . '/admin/bsc-product-edit.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
		);

		$js_path = get_template_directory() . '/js/admin/product-covers.js';
		wp_enqueue_script(
			'bsc-product-covers-admin',
			get_template_directory_uri() . '/js/admin/product-covers.js',
			array( 'jquery' ),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
			true
		);
	}
);
