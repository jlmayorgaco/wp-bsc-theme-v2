<?php
/**
 * Lightweight abandoned cart capture and recovery.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

function bsc_abandoned_cart_defaults(): array {
	return array(
		'enabled'        => 1,
		'delay_hours'    => 4,
		'retention_days' => 14,
		'max_rows'       => 250,
	);
}

function bsc_abandoned_cart_setting( string $key ) {
	$defaults = bsc_abandoned_cart_defaults();
	return get_option( 'bsc_abandoned_cart_' . $key, $defaults[ $key ] ?? null );
}

function bsc_abandoned_cart_is_enabled(): bool {
	return (bool) bsc_abandoned_cart_setting( 'enabled' );
}

function bsc_abandoned_cart_get_rows(): array {
	$rows = get_option( 'bsc_abandoned_carts', array() );
	return is_array( $rows ) ? $rows : array();
}

function bsc_abandoned_cart_save_rows( array $rows ): void {
	$max_rows = max( 50, (int) bsc_abandoned_cart_setting( 'max_rows' ) );
	update_option( 'bsc_abandoned_carts', array_slice( array_values( $rows ), -1 * $max_rows ), false );
}

function bsc_abandoned_cart_get_cart_items(): array {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return array();
	}

	$items = array();

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = $cart_item['data'] ?? null;
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$items[] = array(
			'product_id'   => (int) ( $cart_item['product_id'] ?? $product->get_id() ),
			'variation_id' => (int) ( $cart_item['variation_id'] ?? 0 ),
			'variation'    => is_array( $cart_item['variation'] ?? null ) ? $cart_item['variation'] : array(),
			'quantity'     => max( 1, (int) ( $cart_item['quantity'] ?? 1 ) ),
			'name'         => wp_strip_all_tags( $product->get_name() ),
			'url'          => get_permalink( $product->get_id() ),
			'image_url'    => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ?: '',
			'price_html'   => wp_strip_all_tags( $product->get_price_html() ),
		);
	}

	return $items;
}

function bsc_abandoned_cart_capture(): void {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );

	if ( function_exists( 'bsc_rate_limit' ) ) {
		bsc_rate_limit( 'abandoned_cart', 30, MINUTE_IN_SECONDS );
	}

	if ( ! bsc_abandoned_cart_is_enabled() || ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		wp_send_json_error( array( 'message' => 'Cart capture unavailable.' ), 400 );
	}

	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Email required.' ), 400 );
	}

	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
	$items = bsc_abandoned_cart_get_cart_items();

	if ( empty( $items ) ) {
		wp_send_json_error( array( 'message' => 'Empty cart.' ), 400 );
	}

	$cart_hash = md5( strtolower( $email ) . wp_json_encode( wp_list_pluck( $items, 'product_id' ) ) );
	$rows      = bsc_abandoned_cart_get_rows();
	$now       = current_time( 'mysql' );
	$found     = false;

	foreach ( $rows as &$row ) {
		if ( ! is_array( $row ) || (string) ( $row['cart_hash'] ?? '' ) !== $cart_hash ) {
			continue;
		}

		$row   = array_merge(
			$row,
			array(
				'email'        => $email,
				'name'         => $name,
				'phone'        => $phone,
				'items'        => $items,
				'total_html'   => wp_strip_all_tags( WC()->cart->get_total() ),
				'updated_at'   => $now,
				'converted_at' => '',
			)
		);
		$found = true;
		break;
	}
	unset( $row );

	if ( ! $found ) {
		$rows[] = array(
			'cart_hash'    => $cart_hash,
			'token'        => wp_generate_password( 32, false, false ),
			'email'        => $email,
			'name'         => $name,
			'phone'        => $phone,
			'items'        => $items,
			'total_html'   => wp_strip_all_tags( WC()->cart->get_total() ),
			'created_at'   => $now,
			'updated_at'   => $now,
			'reminded_at'  => '',
			'recovered_at' => '',
			'converted_at' => '',
		);

		if ( function_exists( 'bsc_metrics_record_counter' ) ) {
			bsc_metrics_record_counter( 'abandoned_cart_capture' );
		}
	}

	bsc_abandoned_cart_save_rows( bsc_abandoned_cart_prune_rows( $rows ) );

	wp_send_json_success( array( 'captured' => true ) );
}
add_action( 'wp_ajax_bsc_capture_abandoned_cart', 'bsc_abandoned_cart_capture' );
add_action( 'wp_ajax_nopriv_bsc_capture_abandoned_cart', 'bsc_abandoned_cart_capture' );

function bsc_abandoned_cart_prune_rows( array $rows ): array {
	$retention_seconds = max( 1, (int) bsc_abandoned_cart_setting( 'retention_days' ) ) * DAY_IN_SECONDS;
	$threshold         = current_time( 'timestamp' ) - $retention_seconds;

	return array_values(
		array_filter(
			$rows,
			static function ( $row ) use ( $threshold ): bool {
				if ( ! is_array( $row ) ) {
					return false;
				}

				$updated_at = strtotime( (string) ( $row['updated_at'] ?? $row['created_at'] ?? '' ) );
				return false !== $updated_at && $updated_at >= $threshold;
			}
		)
	);
}

function bsc_abandoned_cart_schedule(): void {
	if ( ! wp_next_scheduled( 'bsc_process_abandoned_carts' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'bsc_process_abandoned_carts' );
	}
}
add_action( 'init', 'bsc_abandoned_cart_schedule' );

function bsc_abandoned_cart_recover_url( array $row ): string {
	return add_query_arg(
		array(
			'bsc_recover_cart' => (string) ( $row['token'] ?? '' ),
		),
		wc_get_checkout_url()
	);
}

function bsc_process_abandoned_carts(): int {
	if ( ! bsc_abandoned_cart_is_enabled() || ! function_exists( 'wc_get_checkout_url' ) ) {
		return 0;
	}

	$rows          = bsc_abandoned_cart_get_rows();
	$delay_seconds = max( 1, (int) bsc_abandoned_cart_setting( 'delay_hours' ) ) * HOUR_IN_SECONDS;
	$now_timestamp = current_time( 'timestamp' );
	$sent_count    = 0;

	foreach ( $rows as &$row ) {
		if ( ! is_array( $row ) || ! empty( $row['reminded_at'] ) || ! empty( $row['converted_at'] ) ) {
			continue;
		}

		$updated_at = strtotime( (string) ( $row['updated_at'] ?? $row['created_at'] ?? '' ) );
		if ( false === $updated_at || ( $updated_at + $delay_seconds ) > $now_timestamp ) {
			continue;
		}

		$email = sanitize_email( (string) ( $row['email'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			continue;
		}

		$sent = bsc_send_email_from_template(
			$email,
			'Tu carrito BSC te esta esperando',
			'bsc-abandoned-cart.php',
			array(
				'customer_name' => (string) ( $row['name'] ?? '' ),
				'items'         => is_array( $row['items'] ?? null ) ? $row['items'] : array(),
				'total_html'    => (string) ( $row['total_html'] ?? '' ),
				'recover_url'   => bsc_abandoned_cart_recover_url( $row ),
				'shop_url'      => function_exists( 'bsc_get_email_shop_url' ) ? bsc_get_email_shop_url() : wc_get_page_permalink( 'shop' ),
			)
		);

		if ( $sent ) {
			$row['reminded_at'] = current_time( 'mysql' );
			if ( function_exists( 'bsc_metrics_record_counter' ) ) {
				bsc_metrics_record_counter( 'abandoned_cart_email' );
			}
			++$sent_count;
		}
	}
	unset( $row );

	bsc_abandoned_cart_save_rows( bsc_abandoned_cart_prune_rows( $rows ) );
	update_option( 'bsc_abandoned_cart_last_run', current_time( 'mysql' ), false );

	return $sent_count;
}
add_action( 'bsc_process_abandoned_carts', 'bsc_process_abandoned_carts' );

function bsc_abandoned_cart_restore_from_token(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Recovery token comes from the email link, not an admin form.
	if ( empty( $_GET['bsc_recover_cart'] ) || ! function_exists( 'WC' ) || ! WC()->cart || ! function_exists( 'wc_get_checkout_url' ) ) {
		return;
	}

	$token = sanitize_text_field( wp_unslash( $_GET['bsc_recover_cart'] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$rows = bsc_abandoned_cart_get_rows();

	foreach ( $rows as &$row ) {
		if ( ! is_array( $row ) || ! hash_equals( (string) ( $row['token'] ?? '' ), $token ) ) {
			continue;
		}

		$items = is_array( $row['items'] ?? null ) ? $row['items'] : array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$product_id   = absint( $item['product_id'] ?? 0 );
			$variation_id = absint( $item['variation_id'] ?? 0 );
			$quantity     = max( 1, absint( $item['quantity'] ?? 1 ) );
			$variation    = is_array( $item['variation'] ?? null ) ? $item['variation'] : array();
			$product      = wc_get_product( $variation_id ?: $product_id );

			if ( $product instanceof WC_Product && $product->is_purchasable() && $product->is_in_stock() ) {
				WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );
			}
		}

		$row['recovered_at'] = current_time( 'mysql' );
		if ( function_exists( 'bsc_metrics_record_counter' ) ) {
			bsc_metrics_record_counter( 'abandoned_cart_recovered' );
		}
		bsc_abandoned_cart_save_rows( $rows );
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
	unset( $row );
}
add_action( 'template_redirect', 'bsc_abandoned_cart_restore_from_token', 5 );

function bsc_abandoned_cart_mark_converted( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$email = strtolower( sanitize_email( (string) $order->get_billing_email() ) );
	if ( '' === $email ) {
		return;
	}

	$rows = bsc_abandoned_cart_get_rows();
	foreach ( $rows as &$row ) {
		if ( is_array( $row ) && strtolower( (string) ( $row['email'] ?? '' ) ) === $email ) {
			if ( empty( $row['converted_at'] ) && function_exists( 'bsc_metrics_record_counter' ) ) {
				bsc_metrics_record_counter( 'abandoned_cart_converted' );
			}
			$row['converted_at'] = current_time( 'mysql' );
			$row['order_id']     = $order_id;
		}
	}
	unset( $row );

	bsc_abandoned_cart_save_rows( $rows );
}
add_action( 'woocommerce_checkout_order_processed', 'bsc_abandoned_cart_mark_converted', 20 );
