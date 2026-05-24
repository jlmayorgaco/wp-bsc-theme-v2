<?php
/**
 * Lightweight ecommerce metrics for the BSC admin dashboard.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

function bsc_metrics_default_store(): array {
	return array(
		'days'         => array(),
		'product_days' => array(),
		'search_days'  => array(),
	);
}

function bsc_metrics_get_store(): array {
	$store = get_option( 'bsc_ecommerce_metrics', array() );

	if ( ! is_array( $store ) ) {
		return bsc_metrics_default_store();
	}

	return array_merge( bsc_metrics_default_store(), $store );
}

function bsc_metrics_save_store( array $store ): void {
	update_option( 'bsc_ecommerce_metrics', bsc_metrics_prune_store( $store ), false );
}

function bsc_metrics_retention_days(): int {
	return max( 30, (int) get_option( 'bsc_metrics_retention_days', 120 ) );
}

function bsc_metrics_prune_store( array $store ): array {
	$retention_days = bsc_metrics_retention_days();
	$cutoff         = strtotime( '-' . $retention_days . ' days', current_time( 'timestamp' ) );

	foreach ( array( 'days', 'product_days', 'search_days' ) as $bucket ) {
		if ( empty( $store[ $bucket ] ) || ! is_array( $store[ $bucket ] ) ) {
			$store[ $bucket ] = array();
			continue;
		}

		foreach ( array_keys( $store[ $bucket ] ) as $date ) {
			$date_timestamp = strtotime( (string) $date . ' 00:00:00' );
			if ( false === $date_timestamp || $date_timestamp < $cutoff ) {
				unset( $store[ $bucket ][ $date ] );
			}
		}
	}

	return $store;
}

function bsc_metrics_empty_counters(): array {
	return array(
		'view_item'                => 0,
		'view_item_list'           => 0,
		'view_cart'                => 0,
		'begin_checkout'           => 0,
		'add_to_cart'              => 0,
		'purchase'                 => 0,
		'purchase_revenue'         => 0.0,
		'search'                   => 0,
		'search_no_results'        => 0,
		'abandoned_cart_capture'   => 0,
		'abandoned_cart_email'     => 0,
		'abandoned_cart_recovered' => 0,
		'abandoned_cart_converted' => 0,
	);
}

function bsc_metrics_current_date(): string {
	return current_time( 'Y-m-d' );
}

function bsc_metrics_record_counter( string $counter, int $amount = 1, float $value = 0.0 ): void {
	$counter = sanitize_key( $counter );
	if ( '' === $counter || $amount < 1 ) {
		return;
	}

	$store = bsc_metrics_get_store();
	$date  = bsc_metrics_current_date();

	if ( empty( $store['days'][ $date ] ) || ! is_array( $store['days'][ $date ] ) ) {
		$store['days'][ $date ] = bsc_metrics_empty_counters();
	} else {
		$store['days'][ $date ] = array_merge( bsc_metrics_empty_counters(), $store['days'][ $date ] );
	}

	$store['days'][ $date ][ $counter ] = (float) ( $store['days'][ $date ][ $counter ] ?? 0 ) + $amount;

	if ( 'purchase' === $counter && $value > 0 ) {
		$store['days'][ $date ]['purchase_revenue'] = (float) $store['days'][ $date ]['purchase_revenue'] + $value;
	}

	bsc_metrics_save_store( $store );
}

function bsc_metrics_record_product_event( int $product_id, string $event, int $amount = 1, float $value = 0.0 ): void {
	$product_id = absint( $product_id );
	$event      = sanitize_key( $event );
	$amount     = max( 1, $amount );

	if ( $product_id < 1 || '' === $event ) {
		return;
	}

	$store = bsc_metrics_get_store();
	$date  = bsc_metrics_current_date();

	if ( empty( $store['product_days'][ $date ] ) || ! is_array( $store['product_days'][ $date ] ) ) {
		$store['product_days'][ $date ] = array();
	}

	if ( empty( $store['product_days'][ $date ][ $product_id ] ) || ! is_array( $store['product_days'][ $date ][ $product_id ] ) ) {
		$store['product_days'][ $date ][ $product_id ] = array(
			'views'       => 0,
			'add_to_cart' => 0,
			'purchases'   => 0,
			'revenue'     => 0.0,
		);
	}

	if ( 'view_item' === $event ) {
		$store['product_days'][ $date ][ $product_id ]['views'] += $amount;
	} elseif ( 'add_to_cart' === $event ) {
		$store['product_days'][ $date ][ $product_id ]['add_to_cart'] += $amount;
	} elseif ( 'purchase' === $event ) {
		$store['product_days'][ $date ][ $product_id ]['purchases'] += $amount;
		$store['product_days'][ $date ][ $product_id ]['revenue']   += max( 0.0, $value );
	}

	bsc_metrics_save_store( $store );
}

function bsc_metrics_normalize_query( string $query ): string {
	$query = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $query ) ) );
	$query = function_exists( 'mb_strtolower' ) ? mb_strtolower( $query, 'UTF-8' ) : strtolower( $query );

	return substr( sanitize_text_field( $query ), 0, 120 );
}

function bsc_metrics_record_search( string $query, int $results_count ): void {
	$normalized_query = bsc_metrics_normalize_query( $query );
	if ( strlen( $normalized_query ) < 2 ) {
		return;
	}

	$store       = bsc_metrics_get_store();
	$date        = bsc_metrics_current_date();
	$results     = max( 0, $results_count );
	$search_hash = md5( $normalized_query );

	if ( empty( $store['days'][ $date ] ) || ! is_array( $store['days'][ $date ] ) ) {
		$store['days'][ $date ] = bsc_metrics_empty_counters();
	} else {
		$store['days'][ $date ] = array_merge( bsc_metrics_empty_counters(), $store['days'][ $date ] );
	}

	++$store['days'][ $date ]['search'];
	if ( 0 === $results ) {
		++$store['days'][ $date ]['search_no_results'];
	}

	if ( empty( $store['search_days'][ $date ] ) || ! is_array( $store['search_days'][ $date ] ) ) {
		$store['search_days'][ $date ] = array();
	}

	if ( empty( $store['search_days'][ $date ][ $search_hash ] ) || ! is_array( $store['search_days'][ $date ][ $search_hash ] ) ) {
		$store['search_days'][ $date ][ $search_hash ] = array(
			'query'      => $normalized_query,
			'count'      => 0,
			'no_results' => 0,
			'last_at'    => '',
		);
	}

	++$store['search_days'][ $date ][ $search_hash ]['count'];
	if ( 0 === $results ) {
		++$store['search_days'][ $date ][ $search_hash ]['no_results'];
	}
	$store['search_days'][ $date ][ $search_hash ]['last_at'] = current_time( 'mysql' );

	bsc_metrics_save_store( $store );
}

function bsc_metrics_record_frontend_context(): void {
	if ( is_admin() || wp_doing_ajax() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( is_product() ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product instanceof WC_Product ) {
			bsc_metrics_record_counter( 'view_item' );
			bsc_metrics_record_product_event( $product->get_id(), 'view_item' );
		}
		return;
	}

	if ( is_cart() ) {
		bsc_metrics_record_counter( 'view_cart' );
		return;
	}

	if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
		bsc_metrics_record_counter( 'begin_checkout' );
		return;
	}

	if ( is_shop() || is_product_category() || is_search() ) {
		bsc_metrics_record_counter( 'view_item_list' );
	}
}
add_action( 'wp', 'bsc_metrics_record_frontend_context', 30 );

function bsc_metrics_record_add_to_cart_from_hook( string $cart_item_key, int $product_id, int $quantity, int $variation_id = 0 ): void {
	$tracked_product_id = $variation_id > 0 ? ( wp_get_post_parent_id( $variation_id ) ?: $variation_id ) : $product_id;

	bsc_metrics_record_counter( 'add_to_cart' );
	bsc_metrics_record_product_event( $tracked_product_id, 'add_to_cart', max( 1, $quantity ) );
}
add_action( 'woocommerce_add_to_cart', 'bsc_metrics_record_add_to_cart_from_hook', 20, 4 );

function bsc_metrics_record_purchase_from_order( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order || $order->get_meta( '_bsc_metrics_purchase_recorded' ) ) {
		return;
	}

	bsc_metrics_record_counter( 'purchase', 1, (float) $order->get_total() );

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$product_id = $product->get_id();
		if ( $product instanceof WC_Product_Variation ) {
			$product_id = wp_get_post_parent_id( $product_id ) ?: $product_id;
		}

		bsc_metrics_record_product_event(
			$product_id,
			'purchase',
			(int) $item->get_quantity(),
			(float) $item->get_total()
		);
	}

	$order->update_meta_data( '_bsc_metrics_purchase_recorded', current_time( 'mysql' ) );
	$order->save();
}
add_action( 'woocommerce_checkout_order_processed', 'bsc_metrics_record_purchase_from_order', 30 );

function bsc_metrics_date_in_range( string $date, string $start_date, string $end_date ): bool {
	return $date >= $start_date && $date <= $end_date;
}

function bsc_metrics_get_abandoned_cart_snapshot(): array {
	$rows = get_option( 'bsc_abandoned_carts', array() );

	if ( ! is_array( $rows ) ) {
		$rows = array();
	}

	$snapshot = array(
		'active'    => 0,
		'reminded'  => 0,
		'recovered' => 0,
		'converted' => 0,
	);

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		if ( empty( $row['converted_at'] ) ) {
			++$snapshot['active'];
		}

		if ( ! empty( $row['reminded_at'] ) ) {
			++$snapshot['reminded'];
		}

		if ( ! empty( $row['recovered_at'] ) ) {
			++$snapshot['recovered'];
		}

		if ( ! empty( $row['converted_at'] ) ) {
			++$snapshot['converted'];
		}
	}

	return $snapshot;
}

function bsc_metrics_get_summary( string $start_date, string $end_date ): array {
	$store    = bsc_metrics_get_store();
	$counters = bsc_metrics_empty_counters();
	$products = array();
	$searches = array();

	foreach ( $store['days'] as $date => $day_counters ) {
		if ( ! is_array( $day_counters ) || ! bsc_metrics_date_in_range( (string) $date, $start_date, $end_date ) ) {
			continue;
		}

		$day_counters = array_merge( bsc_metrics_empty_counters(), $day_counters );
		foreach ( $counters as $counter => $current_value ) {
			$counters[ $counter ] = $current_value + (float) ( $day_counters[ $counter ] ?? 0 );
		}
	}

	foreach ( $store['product_days'] as $date => $day_products ) {
		if ( ! is_array( $day_products ) || ! bsc_metrics_date_in_range( (string) $date, $start_date, $end_date ) ) {
			continue;
		}

		foreach ( $day_products as $product_id => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$product_id = absint( $product_id );
			if ( $product_id < 1 ) {
				continue;
			}

			if ( empty( $products[ $product_id ] ) ) {
				$products[ $product_id ] = array(
					'product_id'  => $product_id,
					'title'       => get_the_title( $product_id ),
					'views'       => 0,
					'add_to_cart' => 0,
					'purchases'   => 0,
					'revenue'     => 0.0,
					'edit_url'    => admin_url( 'admin.php?page=bsc-product-edit&id=' . $product_id ),
				);
			}

			$products[ $product_id ]['views']       += (int) ( $row['views'] ?? 0 );
			$products[ $product_id ]['add_to_cart'] += (int) ( $row['add_to_cart'] ?? 0 );
			$products[ $product_id ]['purchases']   += (int) ( $row['purchases'] ?? 0 );
			$products[ $product_id ]['revenue']     += (float) ( $row['revenue'] ?? 0 );
		}
	}

	foreach ( $store['search_days'] as $date => $day_searches ) {
		if ( ! is_array( $day_searches ) || ! bsc_metrics_date_in_range( (string) $date, $start_date, $end_date ) ) {
			continue;
		}

		foreach ( $day_searches as $hash => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$query = (string) ( $row['query'] ?? '' );
			if ( '' === $query ) {
				continue;
			}

			if ( empty( $searches[ $hash ] ) ) {
				$searches[ $hash ] = array(
					'query'      => $query,
					'count'      => 0,
					'no_results' => 0,
					'last_at'    => '',
				);
			}

			$searches[ $hash ]['count']      += (int) ( $row['count'] ?? 0 );
			$searches[ $hash ]['no_results'] += (int) ( $row['no_results'] ?? 0 );
			$searches[ $hash ]['last_at']     = max( $searches[ $hash ]['last_at'], (string) ( $row['last_at'] ?? '' ) );
		}
	}

	uasort(
		$products,
		static function ( array $a, array $b ): int {
			return $b['views'] <=> $a['views'];
		}
	);

	$no_result_searches = array_filter(
		$searches,
		static fn( array $row ): bool => (int) $row['no_results'] > 0
	);
	uasort(
		$no_result_searches,
		static function ( array $a, array $b ): int {
			return $b['no_results'] <=> $a['no_results'];
		}
	);

	return array(
		'counters'           => $counters,
		'top_products'       => array_slice( array_values( $products ), 0, 8 ),
		'no_result_searches' => array_slice( array_values( $no_result_searches ), 0, 8 ),
		'abandoned_snapshot' => bsc_metrics_get_abandoned_cart_snapshot(),
	);
}

function bsc_metrics_rate( float $numerator, float $denominator ): float {
	if ( $denominator <= 0 ) {
		return 0.0;
	}

	return round( ( $numerator / $denominator ) * 100, 1 );
}
