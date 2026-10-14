<?php
/**
 * Bubble Points order award hooks.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bsc_bp_normalize_order_status' ) ) {
	/**
	 * Normalize WooCommerce status keys for comparisons.
	 *
	 * @param string $status Raw order status.
	 * @return string
	 */
	function bsc_bp_normalize_order_status( string $status ): string {
		return preg_replace( '/^wc-/', '', sanitize_key( $status ) );
	}
}

if ( ! function_exists( 'bsc_bp_awardable_order_statuses' ) ) {
	/**
	 * Get order statuses that should award Bubble Points.
	 *
	 * @return array
	 */
	function bsc_bp_awardable_order_statuses(): array {
		$statuses = apply_filters(
			'bsc_bp_awardable_order_statuses',
			array( 'processing', 'preparing', 'shipped', 'completed' )
		);

		return array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $status ): string {
							return bsc_bp_normalize_order_status( (string) $status );
						},
						(array) $statuses
					)
				)
			)
		);
	}
}

if ( ! function_exists( 'bsc_bp_order_is_awardable' ) ) {
	/**
	 * Check whether an order is in a points-awarding status.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	function bsc_bp_order_is_awardable( WC_Order $order ): bool {
		return in_array(
			bsc_bp_normalize_order_status( $order->get_status() ),
			bsc_bp_awardable_order_statuses(),
			true
		);
	}
}

if ( ! function_exists( 'bsc_bp_calculate_order_points' ) ) {
	/**
	 * Calculate points earned by an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return int
	 */
	function bsc_bp_calculate_order_points( WC_Order $order ): int {
		if ( function_exists( 'bsc_calculate_order_bubble_points' ) ) {
			return max( 0, (int) bsc_calculate_order_bubble_points( $order ) );
		}

		return max( 0, (int) floor( (float) $order->get_total() / 1000 ) );
	}
}

if ( ! function_exists( 'bsc_bp_award_order_points' ) ) {
	/**
	 * Award Bubble Points for an order once and record the ledger entry.
	 *
	 * @param WC_Order|int $order_or_id Order object or ID.
	 * @param string       $source      Source context for the ledger metadata.
	 * @return array
	 */
	function bsc_bp_award_order_points( $order_or_id, string $source = 'order_status' ): array {
		if ( ! function_exists( 'wc_get_order' ) || ! function_exists( 'bsc_bp_add_ledger_entry' ) ) {
			return array(
				'ok'      => false,
				'error'   => 'dependencies_missing',
				'balance' => 0,
			);
		}

		$order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order( absint( $order_or_id ) );
		if ( ! $order instanceof WC_Order ) {
			return array(
				'ok'      => false,
				'error'   => 'invalid_order',
				'balance' => 0,
			);
		}

		if ( ! bsc_bp_order_is_awardable( $order ) ) {
			return array(
				'ok'      => false,
				'error'   => 'ineligible_status',
				'balance' => bsc_bp_get_balance( (int) $order->get_user_id() ),
			);
		}

		$user_id = (int) $order->get_user_id();
		if ( $user_id <= 0 ) {
			return array(
				'ok'      => false,
				'error'   => 'invalid_user',
				'balance' => 0,
			);
		}

		$existing_points = (int) $order->get_meta( '_bsc_bp_points_awarded', true );
		if ( $existing_points > 0 ) {
			return array(
				'ok'              => true,
				'error'           => null,
				'already_awarded' => true,
				'points'          => $existing_points,
				'balance'         => bsc_bp_get_balance( $user_id ),
			);
		}

		$points = bsc_bp_calculate_order_points( $order );
		if ( $points <= 0 ) {
			return array(
				'ok'      => false,
				'error'   => 'no_points',
				'points'  => 0,
				'balance' => bsc_bp_get_balance( $user_id ),
			);
		}

		$result = bsc_bp_add_ledger_entry(
			$user_id,
			$points,
			'order_complete',
			$order->get_id(),
			array(
				'order_total' => $order->get_total(),
				'currency'    => $order->get_currency(),
				'status'      => $order->get_status(),
				'source'      => sanitize_key( $source ),
			)
		);

		if ( empty( $result['ok'] ) ) {
			return $result;
		}
		$points = isset( $result['delta'] ) ? (int) $result['delta'] : $points;

		$order->update_meta_data( '_bsc_bp_points_awarded', $points );
		$order->update_meta_data( '_bsc_bp_points_awarded_at', current_time( 'mysql' ) );
		$order->update_meta_data( '_bsc_bp_points_ledger_id', (int) ( $result['insert_id'] ?? 0 ) );
		$order->save_meta_data();

		$result['points'] = $points;

		return $result;
	}
}

if ( ! function_exists( 'bsc_bp_award_order_points_on_status_change' ) ) {
	/**
	 * Award points when an order enters an eligible status.
	 *
	 * @param int           $order_id   Order ID.
	 * @param string        $old_status Previous status.
	 * @param string        $new_status New status.
	 * @param WC_Order|null $order      Order object when provided by WooCommerce.
	 * @return void
	 */
	function bsc_bp_award_order_points_on_status_change( $order_id, $old_status, $new_status, $order = null ): void {
		if ( ! in_array( bsc_bp_normalize_order_status( (string) $new_status ), bsc_bp_awardable_order_statuses(), true ) ) {
			return;
		}

		bsc_bp_award_order_points(
			$order instanceof WC_Order ? $order : absint( $order_id ),
			'status_' . bsc_bp_normalize_order_status( (string) $new_status )
		);
	}
}

if ( ! function_exists( 'bsc_bp_award_order_points_on_payment_complete' ) ) {
	/**
	 * Award points after WooCommerce marks a payment complete.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	function bsc_bp_award_order_points_on_payment_complete( $order_id ): void {
		bsc_bp_award_order_points( absint( $order_id ), 'payment_complete' );
	}
}

add_action( 'woocommerce_order_status_changed', 'bsc_bp_award_order_points_on_status_change', 20, 4 );
add_action( 'woocommerce_payment_complete', 'bsc_bp_award_order_points_on_payment_complete', 20, 1 );

/** Reconcile the order's net benefit, including partial refunds and later reinstatement. */
function bsc_bp_reconcile_order_points( int $order_id ): void {
	$reconciled = BSC_DB_Lock::run( 'points-order:' . $order_id, static function () use ( $order_id ) {
		global $wpdb;
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->get_user_id() ) {
			return;
		}
		$user_id = (int) $order->get_user_id();
		return bsc_bp_with_user_lock( $user_id, static function () use ( $order, $order_id, $user_id, $wpdb ) {
			$table = bsc_bp_table();
			$rows = $wpdb->get_row( $wpdb->prepare(
				"SELECT MAX(id) AS last_id, SUM(delta) AS net, SUM(CASE WHEN reason = 'order_complete' THEN delta ELSE 0 END) AS awarded FROM {$table} WHERE user_id = %d AND order_id = %d AND reason IN ('order_complete', 'order_refund', 'order_reconcile')",
				$user_id, $order_id
			) );
			$awarded = (int) ( $rows->awarded ?? 0 );
			if ( $awarded <= 0 ) {
				return;
			}
			$total = (float) $order->get_total();
			$target = $total > 0 ? (int) floor( $awarded * max( 0, $total - (float) $order->get_total_refunded() ) / $total ) : 0;
			if ( $order->has_status( array( 'cancelled', 'failed', 'refunded' ) ) ) {
				$target = 0;
			}
			$delta = $target - (int) ( $rows->net ?? 0 );
			if ( 0 === $delta ) {
				return;
			}
			$result = bsc_bp_add_ledger_entry( $user_id, $delta, 'order_reconcile', $order_id, array(
				'event_key' => 'reconcile:order:' . $order_id . ':after:' . (int) $rows->last_id . ':target:' . $target,
				'refunded_total' => $order->get_total_refunded(),
				'status' => $order->get_status(),
			), true );
			return $result;
		} );
	} );
	if ( is_wp_error( $reconciled ) || ( is_array( $reconciled ) && empty( $reconciled['ok'] ) ) ) {
		$order = wc_get_order( $order_id );
		if ( $order ) { $order->add_order_note( 'Conciliación de Bubble Points pendiente; se reintentará automáticamente.' ); }
		// WP-Cron deduplicates pending retries; this also handles failure to acquire either lock.
		wp_schedule_single_event( time() + 300, 'bsc_bp_reconcile_order_points', array( $order_id ) );
	}
}
add_action( 'bsc_bp_reconcile_order_points', 'bsc_bp_reconcile_order_points' );
add_action( 'woocommerce_order_status_changed', 'bsc_bp_reconcile_order_points', 30, 1 );
add_action( 'woocommerce_payment_complete', 'bsc_bp_reconcile_order_points', 30, 1 );
add_action( 'woocommerce_order_refunded', 'bsc_bp_reconcile_order_points', 30, 1 );
