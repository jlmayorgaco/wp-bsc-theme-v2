<?php
/**
 * GA4 ecommerce data layer integration.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

function bsc_ga4_get_measurement_id(): string {
	$from_constant  = defined( 'BSC_GA4_MEASUREMENT_ID' ) ? (string) BSC_GA4_MEASUREMENT_ID : '';
	$measurement_id = $from_constant !== ''
		? $from_constant
		: (string) get_option( 'bsc_ga4_measurement_id', '' );

	return preg_match( '/^G-[A-Z0-9]+$/', $measurement_id ) ? $measurement_id : '';
}

function bsc_ga4_consent_required(): bool {
	return defined( 'BSC_GA4_REQUIRE_CONSENT' ) ? (bool) BSC_GA4_REQUIRE_CONSENT : true;
}

function bsc_ga4_consent_state(): string {
	if ( ! bsc_ga4_consent_required() ) {
		return 'granted';
	}

	$value = isset( $_COOKIE['bsc_analytics_consent'] ) ? sanitize_key( wp_unslash( $_COOKIE['bsc_analytics_consent'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only consent state.

	return in_array( $value, array( 'granted', 'denied' ), true ) ? $value : 'unknown';
}

function bsc_ga4_has_consent(): bool {
	return 'granted' === bsc_ga4_consent_state();
}

function bsc_ga4_get_product_item( WC_Product $product, int $quantity = 1, int $index = 0 ): array {
	$terms      = get_the_terms( $product->get_id(), 'product_cat' );
	$categories = array();
	$brand      = '';

	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			if ( false !== strpos( $term->slug, '-marca' ) && '' === $brand ) {
				$brand = $term->name;
			} elseif ( count( $categories ) < 5 ) {
				$categories[] = $term->name;
			}
		}
	}

	$item = array(
		'item_id'   => $product->get_sku() ?: (string) $product->get_id(),
		'item_name' => wp_strip_all_tags( $product->get_name() ),
		'price'     => (float) wc_get_price_to_display( $product ),
		'quantity'  => max( 1, $quantity ),
		'index'     => max( 0, $index ),
	);

	if ( $brand !== '' ) {
		$item['item_brand'] = $brand;
	}

	foreach ( $categories as $category_index => $category_name ) {
		$key          = 0 === $category_index ? 'item_category' : 'item_category' . ( $category_index + 1 );
		$item[ $key ] = $category_name;
	}

	return $item;
}

function bsc_ga4_get_cart_items(): array {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return array();
	}

	$items = array();
	$index = 0;

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = $cart_item['data'] ?? null;
		if ( $product instanceof WC_Product ) {
			$items[] = bsc_ga4_get_product_item( $product, (int) ( $cart_item['quantity'] ?? 1 ), $index++ );
		}
	}

	return $items;
}

function bsc_ga4_get_cart_value(): float {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	return (float) WC()->cart->get_total( 'edit' );
}

function bsc_ga4_get_order_payload( WC_Order $order ): array {
	$items = array();
	$index = 0;

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( $product instanceof WC_Product ) {
			$items[] = bsc_ga4_get_product_item( $product, (int) $item->get_quantity(), $index++ );
		}
	}

	return array(
		'transaction_id' => (string) $order->get_order_number(),
		'value'          => (float) $order->get_total(),
		'tax'            => (float) $order->get_total_tax(),
		'shipping'       => (float) $order->get_shipping_total(),
		'currency'       => $order->get_currency(),
		'coupon'         => implode( ',', $order->get_coupon_codes() ),
		'items'          => $items,
	);
}

function bsc_ga4_render_consent_banner(): void {
	if ( ! bsc_ga4_consent_required() || '' === bsc_ga4_get_measurement_id() ) {
		return;
	}
	?>
	<section class="bsc-analytics-consent" data-bsc-analytics-consent-banner role="dialog" aria-labelledby="bsc-analytics-consent-title" aria-describedby="bsc-analytics-consent-description" hidden>
		<div class="bsc-analytics-consent__copy">
			<h2 id="bsc-analytics-consent-title">Analítica opcional</h2>
			<p id="bsc-analytics-consent-description">Usamos Google Analytics para entender qué contenido ayuda a mejorar la tienda. Puedes aceptar o rechazar estas cookies; las funciones esenciales seguirán disponibles. <a href="<?php echo esc_url( bsc_get_static_page_url( 'cookies' ) ); ?>">Ver política de cookies</a>.</p>
		</div>
		<div class="bsc-analytics-consent__actions">
			<button type="button" class="bsc__button bsc__button--outline" data-bsc-analytics-consent="denied">No gracias</button>
			<button type="button" class="bsc__button" data-bsc-analytics-consent="granted">Aceptar analítica</button>
		</div>
	</section>
	<?php
}
add_action( 'wp_footer', 'bsc_ga4_render_consent_banner', 4 );

function bsc_ga4_event_script( string $event_name, array $params, string $dedupe_key = '' ): string {
	$payload = array(
		'event'     => $event_name,
		'ecommerce' => $params,
	);

	$encoded_payload = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$encoded_key     = wp_json_encode( $dedupe_key );
	$requires_consent = bsc_ga4_consent_required() ? 'true' : 'false';

	return "(function(){if({$requires_consent}&&!document.cookie.split(';').some(function(cookie){return cookie.trim()==='bsc_analytics_consent=granted';})){return;}var payload={$encoded_payload};var key={$encoded_key};if(key&&window.sessionStorage&&sessionStorage.getItem(key)){return;}window.dataLayer=window.dataLayer||[];window.dataLayer.push({ecommerce:null});window.dataLayer.push(payload);if(key&&window.sessionStorage){sessionStorage.setItem(key,'1');}}());";
}

function bsc_ga4_output_page_events(): void {
	if ( ! bsc_ga4_has_consent() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$event_name = '';
	$params     = array();
	$dedupe_key = '';

	if ( is_product() ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product instanceof WC_Product ) {
			$event_name = 'view_item';
			$params     = array(
				'currency' => get_woocommerce_currency(),
				'value'    => (float) wc_get_price_to_display( $product ),
				'items'    => array( bsc_ga4_get_product_item( $product ) ),
			);
		}
	} elseif ( is_cart() ) {
		$items = bsc_ga4_get_cart_items();
		if ( ! empty( $items ) ) {
			$event_name = 'view_cart';
			$params     = array(
				'currency' => get_woocommerce_currency(),
				'value'    => bsc_ga4_get_cart_value(),
				'items'    => $items,
			);
		}
	} elseif ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
		$items = bsc_ga4_get_cart_items();
		if ( ! empty( $items ) ) {
			$event_name = 'begin_checkout';
			$params     = array(
				'currency' => get_woocommerce_currency(),
				'value'    => bsc_ga4_get_cart_value(),
				'items'    => $items,
			);
			$dedupe_key = 'bsc_ga4_begin_checkout_' . md5( wp_json_encode( $items ) );
		}
	} elseif ( is_wc_endpoint_url( 'order-received' ) ) {
		$order_id = absint( get_query_var( 'order-received' ) );
		$order    = $order_id > 0 ? wc_get_order( $order_id ) : false;
		if ( $order instanceof WC_Order ) {
			$event_name = 'purchase';
			$params     = bsc_ga4_get_order_payload( $order );
			$dedupe_key = 'bsc_ga4_purchase_' . $order->get_id();
		}
	} elseif ( is_shop() || is_product_category() || is_search() ) {
		$product_ids = array();

		if ( is_product_category() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$product_ids = wc_get_products(
					array(
						'limit'    => 12,
						'status'   => 'publish',
						'return'   => 'ids',
						'category' => array( $term->slug ),
					)
				);
			}
		} elseif ( is_shop() ) {
			$product_ids = wc_get_products(
				array(
					'limit'  => 12,
					'status' => 'publish',
					'return' => 'ids',
				)
			);
		} else {
			$query = get_search_query( false );
			if ( '' !== $query ) {
				$product_ids = get_posts(
					array(
						'post_type'      => 'product',
						'post_status'    => 'publish',
						's'              => $query,
						'fields'         => 'ids',
						'posts_per_page' => 12,
						'no_found_rows'  => true,
					)
				);
			}
		}

		$items = array();
		foreach ( array_values( array_unique( array_map( 'absint', $product_ids ) ) ) as $index => $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product instanceof WC_Product ) {
				$items[] = bsc_ga4_get_product_item( $product, 1, $index );
			}
		}

		if ( ! empty( $items ) ) {
			$event_name = 'view_item_list';
			$params     = array(
				'currency'       => get_woocommerce_currency(),
				'item_list_id'   => is_search() ? 'search_results' : 'catalog',
				'item_list_name' => is_search() ? 'Search results' : 'Catalog',
				'items'          => $items,
			);
		}
	}

	if ( '' === $event_name || empty( $params ) ) {
		return;
	}

	wp_print_inline_script_tag( bsc_ga4_event_script( $event_name, $params, $dedupe_key ) );
}
add_action( 'wp_footer', 'bsc_ga4_output_page_events', 25 );

function bsc_ga4_localize_cart_data( array $data = array() ): array {
	$data['currency'] = get_woocommerce_currency();
	return $data;
}
