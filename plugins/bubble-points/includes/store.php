<?php
// plugins/bubble-points/includes/store.php
if (!defined( 'ABSPATH' )) {
	exit;
}

/**
 * Helpers to read/write Bubble Points and record ledger entries.
 * Single source of truth for balance + history.
 */

if (!function_exists( 'bsc_bp_table' )) {
	function bsc_bp_table() {
		global $wpdb;
		return $wpdb->prefix . 'bsc_points_ledger';
	}
}

if (!function_exists( 'bsc_bp_get_balance' )) {
	function bsc_bp_get_balance( $user_id ) {
		return (int) get_user_meta( $user_id, 'bsc_bubble_points', true );
	}
}

if (!function_exists( 'bsc_bp_set_balance' )) {
	function bsc_bp_set_balance( $user_id, $points ) {
		return update_user_meta( $user_id, 'bsc_bubble_points', (int) $points );
	}
}

if ( ! function_exists( 'bsc_bp_with_user_lock' ) ) {
	function bsc_bp_with_user_lock( $user_id, callable $callback ) {
		$result = BSC_DB_Lock::run( 'points-user:' . (int) $user_id, static function () use ( $user_id, $callback ) {
			wp_cache_delete( (int) $user_id, 'user_meta' );
			try {
				return $callback();
			} finally {
				wp_cache_delete( (int) $user_id, 'user_meta' );
			}
		} );
		return is_wp_error( $result ) ? array( 'ok' => false, 'balance' => 0, 'error' => $result->get_error_code(), 'insert_id' => null ) : $result;
	}
}

if ( ! function_exists( 'bsc_bp_add_ledger_entry' ) ) {
	/** The ledger event and balance commit atomically; refunds may create a debt. */
	function bsc_bp_add_ledger_entry( $user_id, $delta, $reason, $order_id = null, array $extra = array(), $allow_negative = false ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return array( 'ok' => false, 'balance' => 0, 'error' => 'invalid_user', 'insert_id' => null );
		}
		return bsc_bp_with_user_lock( $user_id, static function () use ( $wpdb, $user_id, $delta, $reason, $order_id, $extra, $allow_negative ) {
			$result = BSC_DB_Lock::transaction( static function () use ( $wpdb, $user_id, $delta, $reason, $order_id, $extra, $allow_negative ) {
				$table = bsc_bp_table();
				$delta = (int) $delta;
				$reason = sanitize_key( $reason );
				$order_id = $order_id ? (int) $order_id : null;
				$event_key = isset( $extra['event_key'] ) ? substr( sanitize_text_field( (string) $extra['event_key'] ), 0, 191 ) : null;
				if ( 'order_complete' === $reason && $order_id ) {
					$event_key = 'earn:order:' . $order_id;
					// Recognize pre-migration awards whose event_key is NULL.
					$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, delta FROM {$table} WHERE user_id = %d AND order_id = %d AND reason = %s ORDER BY id LIMIT 1", $user_id, $order_id, 'order_complete' ) );
				} else {
					$existing = $event_key ? $wpdb->get_row( $wpdb->prepare( "SELECT id, delta FROM {$table} WHERE event_key = %s", $event_key ) ) : null;
				}
				$current = bsc_bp_get_balance( $user_id );
				if ( $existing ) {
					return array( 'ok' => true, 'balance' => $current, 'error' => null, 'insert_id' => (int) $existing->id, 'delta' => (int) $existing->delta, 'already_applied' => true );
				}
				$new = $current + $delta;
				if ( ! $allow_negative && $new < 0 ) {
					return new WP_Error( 'insufficient_points', 'No tienes puntos suficientes.' );
				}
				$inserted = $wpdb->insert( $table, array(
					'user_id' => $user_id, 'delta' => $delta, 'balance_after' => $new,
					'reason' => $reason, 'order_id' => $order_id, 'event_key' => $event_key,
					'meta' => $extra ? wp_json_encode( $extra ) : null, 'created_at' => current_time( 'mysql' ),
				), array( '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s' ) );
				if ( false === $inserted ) {
					return new WP_Error( 'ledger_insert_failed', 'No se pudo registrar el movimiento.' );
				}
				$insert_id = (int) $wpdb->insert_id;
				if ( $new !== $current && false === bsc_bp_set_balance( $user_id, $new ) ) {
					return new WP_Error( 'balance_write_failed', 'No se pudo actualizar el saldo.' );
				}
				return array( 'ok' => true, 'balance' => $new, 'error' => null, 'insert_id' => $insert_id, 'delta' => $delta );
			} );
			wp_cache_delete( $user_id, 'user_meta' );
			return is_wp_error( $result ) ? array( 'ok' => false, 'balance' => bsc_bp_get_balance( $user_id ), 'error' => $result->get_error_code(), 'insert_id' => null ) : $result;
		} );
	}
}

if (!function_exists( 'bsc_bp_recalc_balance_from_ledger' )) {
	/**
	 * Recalculate balance from ledger SUM(delta) and store it in user meta.
	 */
	function bsc_bp_recalc_balance_from_ledger( $user_id ) {
		global $wpdb;
		return bsc_bp_with_user_lock( $user_id, static function () use ( $user_id, $wpdb ) {
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ledger table name is internal; user_id is prepared.
		$sum = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(delta),0) FROM ' . bsc_bp_table() . ' WHERE user_id = %d',
				$user_id
			)
		);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		bsc_bp_set_balance( $user_id, $sum );
		return $sum;
		} );
	}
}

if (!function_exists( 'bsc_bp_get_user_ledger' )) {
	/**
	 * Fetch paginated ledger for a user.
	 *
	 * @return array { 'rows'=>array, 'total'=>int }
	 */
	function bsc_bp_get_user_ledger( $user_id, $per_page = 25, $paged = 1 ) {
		global $wpdb;
		$user_id  = (int) $user_id;
		$per_page = max( 1, (int) $per_page );
		$offset   = max( 0, ( $paged - 1 ) * $per_page );

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ledger table name is internal; pagination values are prepared.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, delta, balance_after, reason, order_id, meta, created_at
                 FROM ' . bsc_bp_table() . '
                 WHERE user_id = %d
                 ORDER BY created_at DESC, id DESC
                 LIMIT %d OFFSET %d',
				$user_id,
				$per_page,
				$offset
			)
		);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ledger table name is internal; user_id is prepared.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . bsc_bp_table() . ' WHERE user_id = %d',
				$user_id
			)
		);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return array(
			'rows'  => $rows,
			'total' => $total,
		);
	}
}
