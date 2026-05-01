<?php
/**
 * WooCommerce Compatibility File
 *
 * @link https://woocommerce.com/
 *
 * @package BSC2
 */

/**
 * WooCommerce setup function.
 *
 * @link https://docs.woocommerce.com/document/third-party-custom-theme-compatibility/
 * @link https://github.com/woocommerce/woocommerce/wiki/Enabling-product-gallery-features-(zoom,-swipe,-lightbox)
 * @link https://github.com/woocommerce/woocommerce/wiki/Declaring-WooCommerce-support-in-themes
 *
 * @return void
 */
function bsc_2_0_woocommerce_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 150,
			'single_image_width'    => 300,
			'product_grid'          => array(
				'default_rows'    => 3,
				'min_rows'        => 1,
				'default_columns' => 4,
				'min_columns'     => 1,
				'max_columns'     => 6,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'bsc_2_0_woocommerce_setup' );

/**
 * WooCommerce specific scripts & stylesheets.
 *
 * @return void
 */
function bsc_2_0_woocommerce_scripts() {
	wp_enqueue_style( 'bsc-2-0-woocommerce-style', get_template_directory_uri() . '/woocommerce.css', array(), _S_VERSION );

	$font_path   = WC()->plugin_url() . '/assets/fonts/';
	$inline_font = '@font-face {
			font-family: "star";
			src: url("' . $font_path . 'star.eot");
			src: url("' . $font_path . 'star.eot?#iefix") format("embedded-opentype"),
				url("' . $font_path . 'star.woff") format("woff"),
				url("' . $font_path . 'star.ttf") format("truetype"),
				url("' . $font_path . 'star.svg#star") format("svg");
			font-weight: normal;
			font-style: normal;
		}';

	wp_add_inline_style( 'bsc-2-0-woocommerce-style', $inline_font );
}
add_action( 'wp_enqueue_scripts', 'bsc_2_0_woocommerce_scripts' );

/**
 * Disable the default WooCommerce stylesheet.
 *
 * Removing the default WooCommerce stylesheet and enqueing your own will
 * protect you during WooCommerce core updates.
 *
 * @link https://docs.woocommerce.com/document/disable-the-default-stylesheet/
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Add 'woocommerce-active' class to the body tag.
 *
 * @param  array $classes CSS classes applied to the body tag.
 * @return array $classes modified to include 'woocommerce-active' class.
 */
function bsc_2_0_woocommerce_active_body_class( $classes ) {
	$classes[] = 'woocommerce-active';

	return $classes;
}
add_filter( 'body_class', 'bsc_2_0_woocommerce_active_body_class' );

/**
 * Related Products Args.
 *
 * @param array $args related products args.
 * @return array $args related products args.
 */
function bsc_2_0_woocommerce_related_products_args( $args ) {
	$defaults = array(
		'posts_per_page' => 3,
		'columns'        => 3,
	);

	$args = wp_parse_args( $defaults, $args );

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'bsc_2_0_woocommerce_related_products_args' );

/**
 * Remove default WooCommerce wrapper.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

if ( ! function_exists( 'bsc_2_0_woocommerce_wrapper_before' ) ) {
	/**
	 * Before Content.
	 *
	 * Wraps all WooCommerce content in wrappers which match the theme markup.
	 *
	 * @return void
	 */
	function bsc_2_0_woocommerce_wrapper_before() {
		?>
			<main id="primary" class="site-main">
		<?php
	}
}
add_action( 'woocommerce_before_main_content', 'bsc_2_0_woocommerce_wrapper_before' );

if ( ! function_exists( 'bsc_2_0_woocommerce_wrapper_after' ) ) {
	/**
	 * After Content.
	 *
	 * Closes the wrapping divs.
	 *
	 * @return void
	 */
	function bsc_2_0_woocommerce_wrapper_after() {
		?>
			</main><!-- #main -->
		<?php
	}
}
add_action( 'woocommerce_after_main_content', 'bsc_2_0_woocommerce_wrapper_after' );

/**
 * Sample implementation of the WooCommerce Mini Cart.
 *
 * You can add the WooCommerce Mini Cart to header.php like so ...
 *
	<?php
		if ( function_exists( 'bsc_2_0_woocommerce_header_cart' ) ) {
			bsc_2_0_woocommerce_header_cart();
		}
	?>
 */

if ( ! function_exists( 'bsc_2_0_woocommerce_cart_link_fragment' ) ) {
	/**
	 * Cart Fragments.
	 *
	 * Ensure cart contents update when products are added to the cart via AJAX.
	 *
	 * @param array $fragments Fragments to refresh via AJAX.
	 * @return array Fragments to refresh via AJAX.
	 */
	function bsc_2_0_woocommerce_cart_link_fragment( $fragments ) {
		ob_start();
		bsc_2_0_woocommerce_cart_link();
		$fragments['a.cart-contents'] = ob_get_clean();

		return $fragments;
	}
}
add_filter( 'woocommerce_add_to_cart_fragments', 'bsc_2_0_woocommerce_cart_link_fragment' );

if ( ! function_exists( 'bsc_2_0_woocommerce_cart_link' ) ) {
	/**
	 * Cart Link.
	 *
	 * Displayed a link to the cart including the number of items present and the cart total.
	 *
	 * @return void
	 */
	function bsc_2_0_woocommerce_cart_link() {
		?>
		<a class="cart-contents" href="<?php echo esc_url( wc_get_checkout_url() ); ?>" title="<?php esc_attr_e( 'Ir al checkout', 'bsc-2-0' ); ?>">
			<?php
			$item_count_text = sprintf(
				/* translators: number of items in the mini cart. */
				_n( '%d producto', '%d productos', WC()->cart->get_cart_contents_count(), 'bsc-2-0' ),
				WC()->cart->get_cart_contents_count()
			);
			?>
			<span class="amount"><?php echo wp_kses_data( WC()->cart->get_cart_subtotal() ); ?></span> <span class="count"><?php echo esc_html( $item_count_text ); ?></span>
		</a>
		<?php
	}
}

if ( ! function_exists( 'bsc_2_0_woocommerce_header_cart' ) ) {
	/**
	 * Display Header Cart.
	 *
	 * @return void
	 */
	function bsc_2_0_woocommerce_header_cart() {
		if ( is_cart() ) {
			$class = 'current-menu-item';
		} else {
			$class = '';
		}
		?>
		<ul id="site-header-cart" class="site-header-cart">
			<li class="<?php echo esc_attr( $class ); ?>">
				<?php bsc_2_0_woocommerce_cart_link(); ?>
			</li>
			<li>
				<?php
				$instance = array(
					'title' => '',
				);

				the_widget( 'WC_Widget_Cart', $instance );
				?>
			</li>
		</ul>
		<?php
	}
}



    add_action('after_setup_theme', function () {
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    });

/**
 * BSC-089: Product gallery image sizes tuned for PDP quality/performance balance.
 *
 * @param array $size Image size config.
 * @return array
 */
function bsc_woocommerce_single_image_size( $size ) {
	return array(
		'width'  => 900,
		'height' => 900,
		'crop'   => 0,
	);
}
add_filter( 'woocommerce_get_image_size_single', 'bsc_woocommerce_single_image_size' );

/**
 * BSC-089: Keep only the real PDP gallery main image eager for faster LCP.
 *
 * @param array        $attr          Image attributes.
 * @param int          $attachment_id Attachment ID.
 * @param string|array $size          Requested image size.
 * @param bool         $main_image    Whether this is the main gallery image.
 * @return array
 */
function bsc_product_gallery_image_loading_attrs( $attr, $attachment_id, $size, $main_image ) {
	if ( ! is_product() ) {
		return $attr;
	}

	if ( $main_image ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'sync';
	} else {
		$attr['loading']  = 'lazy';
		$attr['decoding'] = 'async';
		unset( $attr['fetchpriority'] );
	}

	return $attr;
}
add_filter( 'woocommerce_gallery_image_html_attachment_image_params', 'bsc_product_gallery_image_loading_attrs', 10, 4 );


  add_filter('woocommerce_checkout_fields', 'bsc_add_billing_cedula_field');
function bsc_add_billing_cedula_field($fields) {
  $fields['billing']['billing_cedula'] = [
    'type'        => 'text',
    'label'       => 'Cédula',
    'required'    => true,
    'class'       => ['form-row-wide'],
    'priority'    => 21,
    'placeholder' => 'Cédula',
  ];

  return $fields;
}


add_action('after_setup_theme', function () {
  load_textdomain('woocommerce', WP_LANG_DIR . '/woocommerce/woocommerce-es_ES.mo');
});





add_filter('woocommerce_checkout_fields', 'bsc_translate_placeholders');
function bsc_translate_placeholders($fields) {
  $fields['billing']['billing_state']['placeholder']   = 'Selecciona un departamento…';
  $fields['billing']['billing_city']['placeholder']    = 'Selecciona una ciudad…';
  $fields['billing']['billing_country']['placeholder'] = 'Selecciona un país…';
  return $fields;
}

add_filter('woocommerce_billing_fields', 'bsc_billing_field_placeholders');
function bsc_billing_field_placeholders($fields) {
  $map = [
    'billing_first_name' => 'Tu nombre',
    'billing_last_name'  => 'Tu apellido',
    'billing_company'    => 'Empresa (opcional)',
    'billing_address_1'  => 'Dirección (calle, barrio, número…)',
    'billing_address_2'  => 'Complemento (apto, piso, torre…)',
    'billing_postcode'   => 'Código postal',
    'billing_phone'      => 'Teléfono de contacto',
    'billing_email'      => 'Correo electrónico',
    'billing_state'      => 'Selecciona un departamento…',
    'billing_city'       => 'Selecciona una ciudad…',
    'billing_country'    => 'Selecciona un país…',
  ];
  foreach ( $map as $key => $placeholder ) {
    if ( isset($fields[$key]) ) {
      $fields[$key]['placeholder'] = $placeholder;
    }
  }
  return $fields;
}

add_filter('woocommerce_shipping_fields', 'bsc_shipping_field_placeholders');
function bsc_shipping_field_placeholders($fields) {
  $map = [
    'shipping_first_name' => 'Tu nombre',
    'shipping_last_name'  => 'Tu apellido',
    'shipping_company'    => 'Empresa (opcional)',
    'shipping_address_1'  => 'Dirección (calle, barrio, número…)',
    'shipping_address_2'  => 'Complemento (apto, piso, torre…)',
    'shipping_postcode'   => 'Código postal',
    'shipping_state'      => 'Selecciona un departamento…',
    'shipping_city'       => 'Selecciona una ciudad…',
    'shipping_country'    => 'Selecciona un país…',
  ];
  foreach ( $map as $key => $placeholder ) {
    if ( isset($fields[$key]) ) {
      $fields[$key]['placeholder'] = $placeholder;
    }
  }
  return $fields;
}

add_filter('woocommerce_order_button_text', 'bsc_custom_order_button_text');
function bsc_custom_order_button_text($button_text) {
    return '¡ Hacer Compra !';
}


function bsc_calculate_order_bubble_points( WC_Order $order ): int {
    return max( 0, (int) floor( (float) $order->get_total() / 1000 ) );
}

function bsc_get_order_bubble_points_earned( WC_Order $order ): int {
    return bsc_calculate_order_bubble_points( $order );
}

function bsc_get_order_bubble_points_balance( WC_Order $order ): int {
    return bsc_calculate_order_bubble_points( $order );
}

function bsc_cart_has_free_shipping_coupon(): bool {
    if ( ! function_exists('WC') || ! WC()->cart ) {
        return false;
    }

    foreach ( WC()->cart->get_coupons() as $coupon ) {
        if ( is_object( $coupon ) && method_exists( $coupon, 'get_free_shipping' ) && $coupon->get_free_shipping() ) {
            return true;
        }
    }

    return false;
}

function bsc_cart_qualifies_for_free_shipping(): bool {
    if ( ! function_exists('WC') || ! WC()->cart ) {
        return false;
    }

    $subtotal = (float) WC()->cart->get_subtotal();
    $discount = (float) WC()->cart->get_discount_total();
    $subtotal_after_discount = max( 0, $subtotal - $discount );
    $min_amount = (float) get_option('bsc_free_shipping_threshold', 300000); // BSC-064: configurable

    return $subtotal_after_discount >= $min_amount || bsc_cart_has_free_shipping_coupon();
}

add_filter('woocommerce_package_rates', 'bsc_force_hide_free_shipping_if_under_discount_threshold', 10, 2);

function bsc_force_hide_free_shipping_if_under_discount_threshold($rates, $package) {
    if ( bsc_cart_has_free_shipping_coupon() ) {
        return $rates;
    }

    $subtotal = WC()->cart->get_subtotal();
    $discount = WC()->cart->get_discount_total();
    $subtotal_after_discount = $subtotal - $discount;
    $min_amount = (int) get_option('bsc_free_shipping_threshold', 300000); // BSC-064: configurable
    // Si no alcanza el mínimo, eliminamos el envío gratuito
    foreach ($rates as $rate_id => $rate) {
        if ($rate->method_id === 'free_shipping' && $subtotal_after_discount < $min_amount) {
            unset($rates[$rate_id]);
        }
    }


    return $rates;
}

// BSC-058: removed woocommerce_before_calculate_totals/calculate_shipping() — caused infinite loops

// BSC-058: force correct flat rate based on billing state + city
add_filter('woocommerce_cart_shipping_packages', 'bsc_force_checkout_shipping_package_destination', 20);
add_filter('woocommerce_package_rates', 'bsc_force_shipping_by_location', 20, 2);
function bsc_force_shipping_by_location( array $rates, array $package ): array {
    $destination = bsc_get_checkout_shipping_destination( $package );
    $state = $destination['state'];
    $city  = $destination['city'];

    return bsc_apply_location_shipping_rates( $rates, $state, $city );
}

add_action('woocommerce_checkout_update_order_review', 'bsc_update_customer_destination_from_checkout_post', 5);
function bsc_update_customer_destination_from_checkout_post( string $post_data = '' ): void {
    $posted = [];
    if ( $post_data !== '' ) {
        parse_str( $post_data, $posted );
    }

    bsc_sync_customer_shipping_destination( $posted ?: null );
}

function bsc_force_checkout_shipping_package_destination( array $packages ): array {
    $destination = bsc_get_checkout_shipping_destination();
    if ( ! bsc_checkout_destination_has_rate_context( $destination ) ) {
        return $packages;
    }

    foreach ( $packages as $package_index => $package ) {
        $package_destination = is_array( $package['destination'] ?? null )
            ? $package['destination']
            : [];

        $packages[ $package_index ]['destination'] = array_merge(
            $package_destination,
            [
                'country'  => $destination['country'] ?: 'CO',
                'state'    => $destination['state'],
                'city'     => $destination['city'],
                'postcode' => $destination['postcode'],
            ]
        );
    }

    return $packages;
}

function bsc_get_checkout_shipping_destination( array $package = [] ): array {
    $posted_destination = bsc_normalize_checkout_destination( bsc_get_posted_checkout_destination() );
    if ( bsc_checkout_destination_has_rate_context( $posted_destination ) ) {
        return $posted_destination;
    }

    $package_destination = $package['destination'] ?? [];
    $package_destination = bsc_normalize_checkout_destination(
        [
            'country'  => $package_destination['country'] ?? 'CO',
            'state'    => $package_destination['state'] ?? '',
            'city'     => $package_destination['city'] ?? '',
            'postcode' => $package_destination['postcode'] ?? '',
        ]
    );

    if ( bsc_checkout_destination_has_rate_context( $package_destination ) ) {
        return $package_destination;
    }

    $customer_destination = bsc_get_customer_checkout_destination();
    if ( bsc_checkout_destination_has_rate_context( $customer_destination ) ) {
        return $customer_destination;
    }

    return [
        'country'  => 'CO',
        'state'    => '',
        'city'     => '',
        'postcode' => '',
    ];
}

function bsc_get_posted_checkout_destination( ?array $posted = null ): array {
    $posted = $posted ?? wp_unslash( $_POST );

    if ( isset( $posted['post_data'] ) && is_string( $posted['post_data'] ) ) {
        $checkout_post_data = [];
        parse_str( wp_unslash( $posted['post_data'] ), $checkout_post_data );
        $posted = array_merge( $checkout_post_data, $posted );
    }

    $normalized_destination = bsc_normalize_checkout_destination(
        [
            'country'  => $posted['s_country'] ?? $posted['country'] ?? 'CO',
            'state'    => $posted['s_state'] ?? $posted['state'] ?? '',
            'city'     => $posted['s_city'] ?? $posted['city'] ?? '',
            'postcode' => $posted['s_postcode'] ?? $posted['postcode'] ?? '',
        ]
    );

    if ( bsc_checkout_destination_is_complete( $normalized_destination ) ) {
        return $normalized_destination;
    }

    $ship_to_different = ! empty( $posted['ship_to_different_address'] );
    $prefix = $ship_to_different && ! empty( $posted['shipping_state'] )
        ? 'shipping'
        : 'billing';

    return bsc_normalize_checkout_destination( [
        'country'  => sanitize_text_field( (string) ( $posted[ "{$prefix}_country" ] ?? 'CO' ) ),
        'state'    => sanitize_text_field( (string) ( $posted[ "{$prefix}_state" ] ?? '' ) ),
        'city'     => sanitize_text_field( (string) ( $posted[ "{$prefix}_city" ] ?? '' ) ),
        'postcode' => sanitize_text_field( (string) ( $posted[ "{$prefix}_postcode" ] ?? '' ) ),
    ] );
}

function bsc_get_customer_checkout_destination(): array {
    if ( ! function_exists('WC') || ! WC()->customer ) {
        return [
            'country'  => 'CO',
            'state'    => '',
            'city'     => '',
            'postcode' => '',
        ];
    }

    $customer_state = WC()->customer->get_shipping_state() ?: WC()->customer->get_billing_state();
    $customer_city = WC()->customer->get_shipping_city() ?: WC()->customer->get_billing_city();

    return bsc_normalize_checkout_destination( [
        'country'  => WC()->customer->get_shipping_country() ?: WC()->customer->get_billing_country() ?: 'CO',
        'state'    => $customer_state,
        'city'     => $customer_city,
        'postcode' => WC()->customer->get_shipping_postcode() ?: WC()->customer->get_billing_postcode(),
    ] );
}

function bsc_checkout_destination_is_complete( array $destination ): bool {
    return trim( (string) ( $destination['state'] ?? '' ) ) !== ''
        && trim( (string) ( $destination['city'] ?? '' ) ) !== '';
}

function bsc_checkout_destination_has_rate_context( array $destination ): bool {
    return trim( (string) ( $destination['state'] ?? '' ) ) !== '';
}

function bsc_normalize_checkout_destination( array $destination ): array {
    $destination = [
        'country'  => 'CO',
        'state'    => sanitize_text_field( (string) ( $destination['state'] ?? '' ) ),
        'city'     => sanitize_text_field( (string) ( $destination['city'] ?? '' ) ),
        'postcode' => sanitize_text_field( (string) ( $destination['postcode'] ?? '' ) ),
    ];

    $city_location = bsc_lookup_colombia_city_location( $destination['city'] );
    if ( ! empty( $city_location['state'] ) && ( $destination['state'] === '' || ! empty( $city_location['matched_by_code'] ) ) ) {
        $destination['state'] = $city_location['state'];
    }

    return $destination;
}

function bsc_sync_customer_shipping_destination( ?array $posted = null ): void {
    if ( ! function_exists('WC') || ! WC()->customer ) {
        return;
    }

    $destination = bsc_get_posted_checkout_destination( $posted );
    if ( ! bsc_checkout_destination_has_rate_context( $destination ) ) {
        return;
    }

    WC()->customer->set_billing_country( $destination['country'] ?: 'CO' );
    WC()->customer->set_billing_state( $destination['state'] );
    WC()->customer->set_billing_postcode( $destination['postcode'] );
    WC()->customer->set_shipping_country( $destination['country'] ?: 'CO' );
    WC()->customer->set_shipping_state( $destination['state'] );
    WC()->customer->set_shipping_postcode( $destination['postcode'] );

    if ( $destination['city'] !== '' ) {
        WC()->customer->set_billing_city( $destination['city'] );
        WC()->customer->set_shipping_city( $destination['city'] );
    }

    WC()->customer->save();

    bsc_clear_cached_shipping_packages();
}

function bsc_clear_cached_shipping_packages(): void {
    if ( ! function_exists('WC') || ! WC()->session || ! WC()->cart ) {
        return;
    }

    foreach ( WC()->cart->get_shipping_packages() as $package_index => $package ) {
        WC()->session->__unset( 'shipping_for_package_' . $package_index );
    }
}

function bsc_apply_location_shipping_rates( array $rates, string $state, string $city ): array {
    if ( trim( $state ) === '' ) {
        return $rates;
    }

    $has_free_shipping = false;
    foreach ( $rates as $rate ) {
        if ( $rate->method_id === 'free_shipping' ) {
            $has_free_shipping = true;
            break;
        }
    }

    if ( $has_free_shipping ) {
        foreach ( $rates as $rate_id => $rate ) {
            if ( $rate->method_id === 'flat_rate' ) {
                unset( $rates[ $rate_id ] );
            }
        }

        return $rates;
    }

    $is_local = bsc_is_bogota_or_cundinamarca_destination( $state, $city );
    $qualifies_for_free_shipping = bsc_cart_qualifies_for_free_shipping();
    $bogota_cost = (float) bsc_get_bogota_shipping_price();
    $other_cost  = (float) bsc_get_other_shipping_price();
    $target_cost = $qualifies_for_free_shipping ? 0 : ( $is_local ? $bogota_cost : $other_cost );
    $target_label = $qualifies_for_free_shipping
        ? 'Envio gratis'
        : ( $is_local ? 'Envio Bogota/Cundinamarca' : 'Envio nacional' );
    $first_flat_rate_id = null;
    $preferred_flat_rate_id = null;

    foreach ( $rates as $rate_id => $rate ) {
        if ( $rate->method_id !== 'flat_rate' ) {
            continue;
        }

        if ( null === $first_flat_rate_id ) {
            $first_flat_rate_id = $rate_id;
        }

        if ( null === $preferred_flat_rate_id && bsc_flat_rate_matches_destination( $rate, $is_local ) ) {
            $preferred_flat_rate_id = $rate_id;
        }

        bsc_set_shipping_rate_cost( $rate, $target_cost );

        if ( method_exists( $rate, 'set_label' ) ) {
            $rate->set_label( $target_label );
        }
    }

    $flat_rate_to_keep = $preferred_flat_rate_id ?: $first_flat_rate_id;
    if ( null === $flat_rate_to_keep ) {
        return $rates;
    }

    foreach ( $rates as $rate_id => $rate ) {
        if ( $rate->method_id === 'flat_rate' && $rate_id !== $flat_rate_to_keep ) {
            unset( $rates[ $rate_id ] );
        }
    }

    return $rates;
}

function bsc_get_bogota_shipping_price(): int {
    return max( 0, (int) get_option( 'bsc_bogota_shipping_price', 10000 ) );
}

function bsc_get_other_shipping_price(): int {
    return max( 0, (int) get_option( 'bsc_other_shipping_price', 17000 ) );
}

function bsc_normalize_shipping_text( string $value ): string {
    $value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

    if ( function_exists( 'remove_accents' ) ) {
        $value = remove_accents( $value );
    }

    $value = strtolower( trim( $value ) );
    $value = preg_replace( '/[^a-z0-9]+/', ' ', $value ) ?: '';
    $value = preg_replace( '/\s+/', ' ', $value ) ?: '';

    return trim( $value );
}

function bsc_get_colombia_shipping_places(): array {
    static $colombia_places = null;

    if ( null !== $colombia_places ) {
        return $colombia_places;
    }

    $colombia_places = [];
    $places_file = WP_PLUGIN_DIR . '/wc-departamentos-y-ciudades-colombia/assets/places/CO-cities.php';

    if ( file_exists( $places_file ) ) {
        global $places;

        if ( ! is_array( $places ?? null ) ) {
            $places = [];
        }

        include $places_file;

        if ( isset( $places['CO'] ) && is_array( $places['CO'] ) ) {
            $colombia_places = $places['CO'];
        }
    }

    return $colombia_places;
}

function bsc_lookup_colombia_city_location( string $city ): array {
    $city = trim( $city );
    if ( $city === '' ) {
        return [];
    }

    $city_code = '';
    if ( preg_match( '/\b(\d{8})\b/', $city, $matches ) ) {
        $city_code = $matches[1];
    }

    $normalized_city = bsc_normalize_shipping_text( $city );

    foreach ( bsc_get_colombia_shipping_places() as $state => $cities ) {
        if ( ! is_array( $cities ) ) {
            continue;
        }

        foreach ( $cities as $code => $label ) {
            $code = (string) $code;
            $label = (string) $label;

            if ( $city_code !== '' && $code === $city_code ) {
                return [
                    'state'           => (string) $state,
                    'city'            => $label,
                    'code'            => $code,
                    'matched_by_code' => true,
                ];
            }

            if ( $normalized_city !== '' && $normalized_city === bsc_normalize_shipping_text( $label ) ) {
                return [
                    'state'           => (string) $state,
                    'city'            => $label,
                    'code'            => $code,
                    'matched_by_code' => false,
                ];
            }
        }
    }

    return [];
}

function bsc_shipping_text_contains_any( string $haystack, array $needles ): bool {
    foreach ( $needles as $needle ) {
        if ( $needle !== '' && strpos( $haystack, $needle ) !== false ) {
            return true;
        }
    }

    return false;
}

function bsc_is_bogota_or_cundinamarca_destination( string $state, string $city ): bool {
    $city_location = bsc_lookup_colombia_city_location( $city );
    if ( ! empty( $city_location['state'] ) && ( trim( $state ) === '' || ! empty( $city_location['matched_by_code'] ) ) ) {
        $state = $city_location['state'];
    }

    $state = bsc_normalize_shipping_text( $state );
    $city  = bsc_normalize_shipping_text( $city );

    $local_states = [
        'bog',
        'bogota',
        'bogota d c',
        'bogota dc',
        'capital district',
        'cun',
        'co cun',
        'cundinamarca',
        'd c',
        'dc',
        'distrito capital',
    ];

    $bogota_cities = [
        'bog',
        'bogota',
        'bogota d c',
        'bogota dc',
        'santa fe de bogota',
    ];

    return in_array( $state, $local_states, true )
        || bsc_shipping_text_contains_any( $state, [ 'bogota', 'cundinamarca', 'distrito capital' ] )
        || in_array( $city, $bogota_cities, true )
        || bsc_shipping_text_contains_any( $city, [ 'bogota' ] );
}

function bsc_flat_rate_matches_destination( $rate, bool $is_local ): bool {
    $label = method_exists( $rate, 'get_label' )
        ? bsc_normalize_shipping_text( (string) $rate->get_label() )
        : bsc_normalize_shipping_text( (string) ( $rate->label ?? '' ) );

    $is_local_label = bsc_shipping_text_contains_any( $label, [ 'bogot', 'cundinamarca', 'local' ] );

    if ( $is_local ) {
        return $is_local_label;
    }

    return bsc_shipping_text_contains_any( $label, [ 'nacional', 'colombia', 'general' ] ) || ! $is_local_label;
}

function bsc_set_shipping_rate_cost( $rate, float $target_cost ): void {
    if ( ! method_exists( $rate, 'set_cost' ) ) {
        return;
    }

    $current_cost = method_exists( $rate, 'get_cost' ) ? (float) $rate->get_cost() : 0;
    $rate->set_cost( $target_cost );

    if ( ! method_exists( $rate, 'get_taxes' ) || ! method_exists( $rate, 'set_taxes' ) ) {
        return;
    }

    $taxes = $rate->get_taxes();
    if ( empty( $taxes ) || ! is_array( $taxes ) ) {
        return;
    }

    foreach ( $taxes as $tax_id => $tax ) {
        $taxes[ $tax_id ] = $current_cost > 0
            ? wc_format_decimal( (float) $tax * ( $target_cost / $current_cost ) )
            : 0;
    }

    $rate->set_taxes( $taxes );
}

add_filter('default_checkout_billing_country', function() {
  return 'CO';
});
add_filter('default_checkout_shipping_country', function() {
  return 'CO';
});

function bsc_limit_wc_countries_to_colombia( array $countries ): array {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return $countries;
    }

    return [
        'CO' => $countries['CO'] ?? 'Colombia',
    ];
}
add_filter( 'woocommerce_countries_allowed_countries', 'bsc_limit_wc_countries_to_colombia', 20 );
add_filter( 'woocommerce_countries_shipping_countries', 'bsc_limit_wc_countries_to_colombia', 20 );

function bsc_force_checkout_posted_countries_to_colombia( array $data ): array {
    $data['billing_country']  = 'CO';
    $data['shipping_country'] = 'CO';

    return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'bsc_force_checkout_posted_countries_to_colombia', 20 );

// ── BSC-032: Custom order statuses ────────────────────────────────────
add_action('init', 'bsc_register_order_statuses');
function bsc_register_order_statuses(): void {
    register_post_status('wc-preparing', [
        'label'                     => _x('En preparación', 'Order status', 'bsc-2-0'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'En preparación <span class="count">(%s)</span>',
            'En preparación <span class="count">(%s)</span>'
        ),
    ]);
    register_post_status('wc-shipped', [
        'label'                     => _x('Enviado', 'Order status', 'bsc-2-0'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Enviado <span class="count">(%s)</span>',
            'Enviados <span class="count">(%s)</span>'
        ),
    ]);
}

add_filter('wc_order_statuses', 'bsc_add_order_statuses_to_woo');
function bsc_add_order_statuses_to_woo(array $statuses): array {
    $new = [];
    foreach ($statuses as $key => $label) {
        $new[$key] = $label;
        if ($key === 'wc-processing') {
            $new['wc-preparing'] = _x('En preparación', 'Order status', 'bsc-2-0');
        }
    }
    $new['wc-shipped'] = _x('Enviado', 'Order status', 'bsc-2-0');
    return $new;
}

// ── BSC-036: Deduct bodega stock when web order moves to processing ───
add_action('woocommerce_order_status_processing', function(int $order_id): void {
    if (class_exists('BSC_Stock')) {
        BSC_Stock::deduct_bodega($order_id);
    }
});

// Allow email triggers for custom statuses
add_filter('woocommerce_valid_order_statuses_for_payment_complete', function(array $statuses): array {
    $statuses[] = 'preparing';
    $statuses[] = 'shipped';
    return $statuses;
});

// ── BSC: Unified WC status → BSC progress bar state map ──────────────
function bsc_map_order_status_to_bar(string $wc_status): string {
    require_once get_template_directory() . '/components/orders/order-progress-bar.php';
    $map = [
        'processing' => BSC_Order_Progress_Bar::RECEIVED,
        'on-hold'    => BSC_Order_Progress_Bar::RECEIVED,
        'pending'    => BSC_Order_Progress_Bar::RECEIVED,
        'preparing'  => BSC_Order_Progress_Bar::RECEIVED,
        'shipped'    => BSC_Order_Progress_Bar::SHIPPED,
        'completed'  => BSC_Order_Progress_Bar::DONE,
        'refunded'   => BSC_Order_Progress_Bar::REFUNDED,
        'cancelled'  => BSC_Order_Progress_Bar::CANCELLED,
        'failed'     => BSC_Order_Progress_Bar::CANCELLED,
    ];
    return $map[$wc_status] ?? BSC_Order_Progress_Bar::CANCELLED;
}

// ── BSC: Auto-archive orders after N days (daily WP cron) ────────────
add_action('init', 'bsc_schedule_order_archiver');
function bsc_schedule_order_archiver(): void {
    if (!wp_next_scheduled('bsc_auto_archive_orders')) {
        wp_schedule_event(time(), 'daily', 'bsc_auto_archive_orders');
    }
}

add_action('bsc_auto_archive_orders', 'bsc_run_order_archiver');
function bsc_mark_order_archived(WC_Order $order, string $bucket, int $days): void {
    if ($order->get_meta('_bsc_archived_at', true)) {
        return;
    }

    $order->update_meta_data('_bsc_archived_at', gmdate('Y-m-d H:i:s'));
    $order->update_meta_data('_bsc_archive_bucket', $bucket);
    $order->save_meta_data();
    $order->add_order_note(sprintf('Auto-archivado tras %d dias en estado %s.', $days, $bucket));
}

function bsc_run_order_archiver(): void {
    $days_shipped   = (int) apply_filters('bsc_auto_archive_days_shipped',   15);
    $days_cancelled = (int) apply_filters('bsc_auto_archive_days_cancelled',  30);

    $cutoff_shipped   = gmdate('Y-m-d H:i:s', strtotime("-{$days_shipped} days"));
    $cutoff_cancelled = gmdate('Y-m-d H:i:s', strtotime("-{$days_cancelled} days"));

    $shipped_ids = wc_get_orders([
        'status'      => ['shipped'],
        'date_before' => $cutoff_shipped,
        'limit'       => -1,
        'return'      => 'ids',
    ]);
    foreach ($shipped_ids as $id) {
        $order = wc_get_order($id);
        if ($order instanceof WC_Order) {
            bsc_mark_order_archived($order, 'shipped', $days_shipped);
        }
    }

    $cancelled_ids = wc_get_orders([
        'status'      => ['cancelled'],
        'date_before' => $cutoff_cancelled,
        'limit'       => -1,
        'return'      => 'ids',
    ]);
    foreach ($cancelled_ids as $id) {
        $order = wc_get_order($id);
        if ($order instanceof WC_Order) {
            bsc_mark_order_archived($order, 'cancelled', $days_cancelled);
        }
    }
}
