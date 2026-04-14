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
	add_theme_support( 'wc-product-gallery-zoom' );
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
		<a class="cart-contents" href="<?php echo esc_url( wc_get_cart_url() ); ?>" title="<?php esc_attr_e( 'View your shopping cart', 'bsc-2-0' ); ?>">
			<?php
			$item_count_text = sprintf(
				/* translators: number of items in the mini cart. */
				_n( '%d item', '%d items', WC()->cart->get_cart_contents_count(), 'bsc-2-0' ),
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
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    });


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
  // País, Departamento, Ciudad, etc.
  $fields['billing']['billing_state']['placeholder'] = 'Selecciona un departamento…';
  $fields['billing']['billing_city']['placeholder'] = 'Selecciona una ciudad…';
  $fields['billing']['billing_country']['placeholder'] = 'Selecciona un país…';

  return $fields;
}

add_filter('woocommerce_order_button_text', 'bsc_custom_order_button_text');
function bsc_custom_order_button_text($button_text) {
    return '¡ Hacer Compra !';
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
add_filter('woocommerce_package_rates', 'bsc_force_shipping_by_location', 20, 2);
function bsc_force_shipping_by_location( array $rates, array $package ): array {
    $state = $package['destination']['state'] ?? '';
    $city  = strtolower( trim( $package['destination']['city'] ?? '' ) );

    return bsc_apply_location_shipping_rates( $rates, (string) $state, (string) $city );
}

function bsc_apply_location_shipping_rates( array $rates, string $state, string $city ): array {
    if ( trim( $state ) === '' || trim( $city ) === '' ) {
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
    $target_cost = $qualifies_for_free_shipping ? 0 : ( $is_local ? 9000 : 20000 );
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

function bsc_shipping_text_contains_any( string $haystack, array $needles ): bool {
    foreach ( $needles as $needle ) {
        if ( $needle !== '' && strpos( $haystack, $needle ) !== false ) {
            return true;
        }
    }

    return false;
}

function bsc_is_bogota_or_cundinamarca_destination( string $state, string $city ): bool {
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
