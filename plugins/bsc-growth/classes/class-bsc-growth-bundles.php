<?php
/**
 * Routine bundle registration and cart actions.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Bundles {
	public static function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ), 30 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_' . BSC_Growth_Bundle_Repository::POST_TYPE, array( __CLASS__, 'save_bundle_meta' ), 10, 2 );
		add_action( 'wp_ajax_bsc_growth_add_bundle_to_cart', array( __CLASS__, 'ajax_add_bundle_to_cart' ) );
		add_action( 'wp_ajax_nopriv_bsc_growth_add_bundle_to_cart', array( __CLASS__, 'ajax_add_bundle_to_cart' ) );
		add_action( 'wp_ajax_bsc_growth_add_products_to_cart', array( __CLASS__, 'ajax_add_products_to_cart' ) );
		add_action( 'wp_ajax_nopriv_bsc_growth_add_products_to_cart', array( __CLASS__, 'ajax_add_products_to_cart' ) );
	}

	public static function register_post_type(): void {
		register_post_type(
			BSC_Growth_Bundle_Repository::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => 'Rutinas BSC',
					'singular_name' => 'Rutina BSC',
					'add_new_item'  => 'Agregar rutina',
					'edit_item'     => 'Editar rutina',
					'menu_name'     => 'Rutinas',
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => false,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'capabilities' => array(
					'edit_post'          => 'manage_woocommerce',
					'read_post'          => 'manage_woocommerce',
					'delete_post'        => 'manage_woocommerce',
					'edit_posts'         => 'manage_woocommerce',
					'edit_others_posts'  => 'manage_woocommerce',
					'publish_posts'      => 'manage_woocommerce',
					'read_private_posts' => 'manage_woocommerce',
				),
				'map_meta_cap' => false,
			)
		);
	}

	public static function register_admin_menu(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		add_submenu_page(
			'bsc-dashboard',
			'Rutinas BSC',
			'Rutinas',
			'manage_woocommerce',
			'edit.php?post_type=' . BSC_Growth_Bundle_Repository::POST_TYPE,
			''
		);
	}

	public static function register_meta_boxes(): void {
		add_meta_box(
			'bsc_growth_bundle_settings',
			'Configuracion de rutina',
			array( __CLASS__, 'render_meta_box' ),
			BSC_Growth_Bundle_Repository::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function render_meta_box( WP_Post $post ): void {
		$product_ids = (string) get_post_meta( $post->ID, BSC_Growth_Bundle_Repository::META_PRODUCT_IDS, true );
		$needs       = (string) get_post_meta( $post->ID, BSC_Growth_Bundle_Repository::META_NEEDS, true );
		$skin_types  = (string) get_post_meta( $post->ID, BSC_Growth_Bundle_Repository::META_SKIN_TYPES, true );
		$badge       = (string) get_post_meta( $post->ID, BSC_Growth_Bundle_Repository::META_BADGE, true );
		$discount    = (string) get_post_meta( $post->ID, BSC_Growth_Bundle_Repository::META_DISCOUNT, true );
		$search      = (string) get_post_meta( $post->ID, BSC_Growth_Bundle_Repository::META_SEARCH, true );

		wp_nonce_field( 'bsc_growth_bundle_save', 'bsc_growth_bundle_nonce' );
		?>
		<div class="bsc-growth-metabox">
			<p>
				<label for="bsc_bundle_product_ids"><strong>Productos del carrito prearmado</strong></label>
				<input id="bsc_bundle_product_ids" class="widefat" type="text" name="bsc_bundle_product_ids" value="<?php echo esc_attr( $product_ids ); ?>" placeholder="12131, 12168, 12157">
				<span class="description">IDs separados por coma. Si queda vacio, se intentan resolver por terminos de busqueda.</span>
			</p>
			<p>
				<label for="bsc_bundle_search_terms"><strong>Terminos de busqueda fallback</strong></label>
				<textarea id="bsc_bundle_search_terms" class="widefat" rows="2" name="bsc_bundle_search_terms" placeholder="limpiador, toner, protector solar"><?php echo esc_textarea( $search ); ?></textarea>
			</p>
			<p>
				<label for="bsc_bundle_needs"><strong>Necesidades</strong></label>
				<input id="bsc_bundle_needs" class="widefat" type="text" name="bsc_bundle_needs" value="<?php echo esc_attr( $needs ); ?>" placeholder="acne, manchas, hidratacion">
			</p>
			<p>
				<label for="bsc_bundle_skin_types"><strong>Tipos de piel</strong></label>
				<input id="bsc_bundle_skin_types" class="widefat" type="text" name="bsc_bundle_skin_types" value="<?php echo esc_attr( $skin_types ); ?>" placeholder="grasa, mixta, seca, sensible">
			</p>
			<p>
				<label for="bsc_bundle_badge"><strong>Etiqueta</strong></label>
				<input id="bsc_bundle_badge" class="widefat" type="text" name="bsc_bundle_badge" value="<?php echo esc_attr( $badge ); ?>" placeholder="Control brillo">
			</p>
			<p>
				<label for="bsc_bundle_discount_label"><strong>Texto de beneficio</strong></label>
				<input id="bsc_bundle_discount_label" class="widefat" type="text" name="bsc_bundle_discount_label" value="<?php echo esc_attr( $discount ); ?>" placeholder="Kit sugerido / precio especial">
			</p>
		</div>
		<?php
	}

	public static function save_bundle_meta( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! isset( $_POST['bsc_growth_bundle_nonce'] ) ) {
			return;
		}

		check_admin_referer( 'bsc_growth_bundle_save', 'bsc_growth_bundle_nonce' );

		$fields = array(
			BSC_Growth_Bundle_Repository::META_PRODUCT_IDS => 'bsc_bundle_product_ids',
			BSC_Growth_Bundle_Repository::META_NEEDS       => 'bsc_bundle_needs',
			BSC_Growth_Bundle_Repository::META_SKIN_TYPES  => 'bsc_bundle_skin_types',
			BSC_Growth_Bundle_Repository::META_BADGE       => 'bsc_bundle_badge',
			BSC_Growth_Bundle_Repository::META_DISCOUNT    => 'bsc_bundle_discount_label',
			BSC_Growth_Bundle_Repository::META_SEARCH      => 'bsc_bundle_search_terms',
		);

		foreach ( $fields as $meta_key => $request_key ) {
			$value = isset( $_POST[ $request_key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $request_key ] ) ) : '';

			if ( '' === $value ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	public static function ajax_add_bundle_to_cart(): void {
		check_ajax_referer( 'bsc_growth_action', 'nonce' );

		$bundle_id  = isset( $_POST['bundle_id'] ) ? sanitize_text_field( wp_unslash( $_POST['bundle_id'] ) ) : '';
		$run_id     = isset( $_POST['run_id'] ) ? absint( $_POST['run_id'] ) : 0;
		$repository = new BSC_Growth_Bundle_Repository();
		$bundle     = $repository->find_bundle( $bundle_id );

		if ( ! $bundle || empty( $bundle['product_ids'] ) ) {
			wp_send_json_error( array( 'message' => 'No encontramos productos disponibles para esta rutina.' ), 404 );
		}

		$added = self::add_products_to_cart( $bundle['product_ids'], $bundle['id'], $bundle['title'], $run_id );

		if ( $added <= 0 ) {
			wp_send_json_error( array( 'message' => 'Los productos de esta rutina no estan disponibles.' ), 409 );
		}

		$cart = BSC_Growth_Plugin::cart();

		wp_send_json_success(
			array(
				'message'      => 'Rutina agregada al carrito.',
				'cart_url'     => BSC_Growth_Plugin::cart_url(),
				'checkout_url' => BSC_Growth_Plugin::checkout_url(),
				'cart_count'   => is_object( $cart ) && is_callable( array( $cart, 'get_cart_contents_count' ) ) ? (int) call_user_func( array( $cart, 'get_cart_contents_count' ) ) : 0,
			)
		);
	}

	public static function ajax_add_products_to_cart(): void {
		check_ajax_referer( 'bsc_growth_action', 'nonce' );

		$product_ids = isset( $_POST['product_ids'] ) ? wp_parse_id_list( wp_unslash( $_POST['product_ids'] ) ) : array();
		$run_id      = isset( $_POST['run_id'] ) ? absint( $_POST['run_id'] ) : 0;

		if ( empty( $product_ids ) ) {
			wp_send_json_error( array( 'message' => 'No encontramos productos para agregar.' ), 400 );
		}

		$added = self::add_products_to_cart( $product_ids, 'dynamic-routine', 'Rutina recomendada', $run_id );

		if ( $added <= 0 ) {
			wp_send_json_error( array( 'message' => 'Los productos recomendados no estan disponibles.' ), 409 );
		}

		$cart = BSC_Growth_Plugin::cart();

		wp_send_json_success(
			array(
				'message'      => 'Rutina agregada al carrito.',
				'cart_url'     => BSC_Growth_Plugin::cart_url(),
				'checkout_url' => BSC_Growth_Plugin::checkout_url(),
				'cart_count'   => is_object( $cart ) && is_callable( array( $cart, 'get_cart_contents_count' ) ) ? (int) call_user_func( array( $cart, 'get_cart_contents_count' ) ) : 0,
			)
		);
	}

	private static function add_products_to_cart( array $product_ids, string $bundle_id, string $bundle_title, int $run_id = 0 ): int {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 0;
		}

		$cart = BSC_Growth_Plugin::cart();

		if ( function_exists( 'wc_load_cart' ) && ! is_object( $cart ) ) {
			wc_load_cart();
			$cart = BSC_Growth_Plugin::cart();
		}

		if ( ! is_object( $cart ) || ! is_callable( array( $cart, 'add_to_cart' ) ) ) {
			return 0;
		}

		$added = 0;

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( (int) $product_id );

			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}

			$cart_item_data = array(
				'bsc_bundle_id'    => $bundle_id,
				'bsc_bundle_title' => $bundle_title,
			);

			if ( $run_id > 0 ) {
				$cart_item_data['bsc_skin_quiz_run_id'] = $run_id;
			}

			$result = call_user_func(
				array( $cart, 'add_to_cart' ),
				$product->get_id(),
				1,
				0,
				array(),
				$cart_item_data
			);

			if ( $result ) {
				++$added;
			}
		}

		return $added;
	}
}
