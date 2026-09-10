<?php
/**
 * Indexed customer state for low-cost follow-up email processing.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

// Direct queries are intentional for this dedicated queue table. All values use prepare()/wpdb helpers; identifiers are internal allow-listed names.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

const BSC_FOLLOWUP_STATE_DB_VERSION = '1.0.0';

/**
 * Return the customer follow-up state table name.
 */
function bsc_get_followup_state_table(): string {
	global $wpdb;

	return $wpdb->prefix . 'bsc_customer_followups';
}

/**
 * Confirm that the queue table is available before using it.
 */
function bsc_followup_state_table_exists(): bool {
	global $wpdb;

	$table = bsc_get_followup_state_table();

	return $table === $wpdb->get_var(
		$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) )
	);
}

/**
 * Install if needed and check table availability only once per request.
 */
function bsc_followup_state_table_is_ready(): bool {
	static $is_ready = null;

	if ( null === $is_ready ) {
		bsc_maybe_install_followup_state_table();
		$is_ready = bsc_followup_state_table_exists();
		if ( ! $is_ready && get_option( 'bsc_followup_state_db_version' ) === BSC_FOLLOWUP_STATE_DB_VERSION ) {
			delete_option( 'bsc_followup_state_db_version' );
			bsc_maybe_install_followup_state_table();
			$is_ready = bsc_followup_state_table_exists();
		}
	}

	return $is_ready;
}

/**
 * Remove one stale customer state row.
 *
 * @param string $contact_key Customer state key.
 */
function bsc_delete_followup_state( string $contact_key ): void {
	global $wpdb;

	$wpdb->delete( bsc_get_followup_state_table(), array( 'contact_key' => $contact_key ), array( '%s' ) );
}

/**
 * Install or update the small indexed state table once per schema version.
 */
function bsc_maybe_install_followup_state_table(): void {
	if ( get_option( 'bsc_followup_state_db_version' ) === BSC_FOLLOWUP_STATE_DB_VERSION ) {
		return;
	}

	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table   = bsc_get_followup_state_table();
	$charset = $wpdb->get_charset_collate();

	dbDelta(
		"CREATE TABLE {$table} (
			contact_key varchar(191) NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			latest_order_id bigint(20) unsigned NOT NULL,
			last_purchase_at datetime NOT NULL,
			repurchase_due_at datetime NOT NULL,
			repurchase_retry_at datetime NOT NULL,
			repurchase_sent_at datetime NULL,
			inactivity_due_at datetime NOT NULL,
			inactivity_retry_at datetime NOT NULL,
			inactivity_sent_at datetime NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (contact_key),
			KEY latest_order_id (latest_order_id),
			KEY user_id (user_id),
			KEY repurchase_retry_at (repurchase_retry_at),
			KEY inactivity_retry_at (inactivity_retry_at)
		) {$charset};"
	);
	if ( ! bsc_followup_state_table_exists() ) {
		return;
	}

	update_option( 'bsc_followup_state_db_version', BSC_FOLLOWUP_STATE_DB_VERSION, false );

	if ( false === get_option( 'bsc_followup_state_backfill_initialized', false ) ) {
		add_option( 'bsc_followup_state_backfill_initialized', 1, '', false );
		add_option( 'bsc_followup_state_backfill_page', 1, '', false );
		add_option( 'bsc_followup_state_backfill_complete', 0, '', false );
	}
}
add_action( 'init', 'bsc_maybe_install_followup_state_table', 6 );

/**
 * Return the purchase timestamp used to start both follow-up timers.
 *
 * @param object $order Order to inspect.
 */
function bsc_get_followup_purchase_timestamp( $order ): int {
	$date = $order->get_date_paid();
	if ( ! $date ) {
		$date = $order->get_date_created();
	}

	return $date ? $date->getTimestamp() : 0;
}

/**
 * Convert a legacy local order-meta date to a UTC database date.
 *
 * @param mixed $raw Raw order-meta value.
 */
function bsc_followup_meta_date_to_utc( $raw ): string {
	if ( is_numeric( $raw ) ) {
		return gmdate( 'Y-m-d H:i:s', (int) $raw );
	}

	$value = trim( (string) $raw );
	if ( '' === $value ) {
		return '';
	}

	try {
		$date = new DateTimeImmutable( $value, wp_timezone() );
	} catch ( Exception $exception ) {
		return '';
	}

	return gmdate( 'Y-m-d H:i:s', $date->getTimestamp() );
}

/**
 * Store the newest qualifying purchase for one customer.
 *
 * Older orders encountered during backfill never overwrite newer state.
 *
 * @param object $order Order to store.
 */
function bsc_sync_followup_state_for_order( $order ): bool {
	if ( ! in_array( $order->get_status(), bsc_get_followup_paid_statuses(), true ) ) {
		return false;
	}

	$purchase_timestamp = bsc_get_followup_purchase_timestamp( $order );
	if ( $purchase_timestamp <= 0 ) {
		return false;
	}

	if ( ! bsc_followup_state_table_is_ready() ) {
		return false;
	}

	global $wpdb;

	$contact_key       = bsc_get_followup_contact_key( $order );
	$repurchase_due    = $purchase_timestamp + ( bsc_get_repurchase_inactivity_days() * DAY_IN_SECONDS );
	$inactivity_due    = bsc_get_inactivity_due_timestamp( $purchase_timestamp, bsc_get_inactivity_followup_months() );
	$repurchase_sent   = bsc_followup_meta_date_to_utc( $order->get_meta( '_bsc_repurchase_recommendation_email_sent_at', true ) );
	$inactivity_sent   = bsc_followup_meta_date_to_utc( $order->get_meta( '_bsc_inactivity_5_month_email_sent_at', true ) );
	$table             = bsc_get_followup_state_table();
	$last_purchase_sql = gmdate( 'Y-m-d H:i:s', $purchase_timestamp );
	$repurchase_sql    = gmdate( 'Y-m-d H:i:s', $repurchase_due );
	$inactivity_sql    = gmdate( 'Y-m-d H:i:s', $inactivity_due );
	$updated_sql       = gmdate( 'Y-m-d H:i:s' );
	$is_newer          = '(VALUES(last_purchase_at) > last_purchase_at OR (VALUES(last_purchase_at) = last_purchase_at AND VALUES(latest_order_id) > latest_order_id))';

	$sql = $wpdb->prepare(
		"INSERT INTO {$table}
			(contact_key, user_id, latest_order_id, last_purchase_at, repurchase_due_at, repurchase_retry_at, repurchase_sent_at, inactivity_due_at, inactivity_retry_at, inactivity_sent_at, updated_at)
		VALUES
			(%s, %d, %d, %s, %s, %s, NULLIF(%s, ''), %s, %s, NULLIF(%s, ''), %s)
		ON DUPLICATE KEY UPDATE
			user_id = IF({$is_newer}, VALUES(user_id), user_id),
			repurchase_due_at = IF({$is_newer}, VALUES(repurchase_due_at), repurchase_due_at),
			repurchase_retry_at = IF({$is_newer}, VALUES(repurchase_retry_at), repurchase_retry_at),
			repurchase_sent_at = IF({$is_newer}, VALUES(repurchase_sent_at), repurchase_sent_at),
			inactivity_due_at = IF({$is_newer}, VALUES(inactivity_due_at), inactivity_due_at),
			inactivity_retry_at = IF({$is_newer}, VALUES(inactivity_retry_at), inactivity_retry_at),
			inactivity_sent_at = IF({$is_newer}, VALUES(inactivity_sent_at), inactivity_sent_at),
			updated_at = IF({$is_newer}, VALUES(updated_at), updated_at),
			latest_order_id = IF({$is_newer}, VALUES(latest_order_id), latest_order_id),
			last_purchase_at = IF(VALUES(last_purchase_at) > last_purchase_at, VALUES(last_purchase_at), last_purchase_at)",
		$contact_key,
		(int) $order->get_user_id(),
		$order->get_id(),
		$last_purchase_sql,
		$repurchase_sql,
		$repurchase_sql,
		$repurchase_sent,
		$inactivity_sql,
		$inactivity_sql,
		$inactivity_sent,
		$updated_sql
	);

	return false !== $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Update state as soon as an order reaches a paid fulfillment status.
 *
 * @param int $order_id Order ID.
 */
function bsc_handle_followup_payment_complete( int $order_id ): void {
	$order = is_callable( 'wc_get_order' ) ? call_user_func( 'wc_get_order', $order_id ) : false;
	if ( is_object( $order ) ) {
		bsc_sync_followup_state_for_order( $order );
	}
}
add_action( 'woocommerce_payment_complete', 'bsc_handle_followup_payment_complete', 30, 1 );

/**
 * Keep state aligned with qualifying order-status changes.
 *
 * @param int    $order_id Order ID.
 * @param string $from     Previous status.
 * @param string $to       New status.
 * @param object $order    Order object.
 */
function bsc_handle_followup_order_status_change( int $order_id, string $from, string $to, $order ): void {
	$paid_statuses = bsc_get_followup_paid_statuses();
	if ( in_array( $to, $paid_statuses, true ) ) {
		bsc_sync_followup_state_for_order( $order );
		return;
	}

	if ( in_array( $from, $paid_statuses, true ) ) {
		bsc_refresh_followup_state_after_order_change( $order );
	}
}
add_action( 'woocommerce_order_status_changed', 'bsc_handle_followup_order_status_change', 30, 4 );

/**
 * Replace state when the latest qualifying order is refunded or cancelled.
 *
 * @param object $changed_order Changed order.
 */
function bsc_refresh_followup_state_after_order_change( $changed_order ): void {
	if ( ! bsc_followup_state_table_is_ready() ) {
		return;
	}

	global $wpdb;

	$table       = bsc_get_followup_state_table();
	$contact_key = bsc_get_followup_contact_key( $changed_order );
	$stored_id   = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT latest_order_id FROM {$table} WHERE contact_key = %s",
			$contact_key
		)
	); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( $stored_id !== $changed_order->get_id() ) {
		return;
	}

	$wpdb->delete( $table, array( 'contact_key' => $contact_key ), array( '%s' ) );

	$query_args = array(
		'status'  => bsc_get_followup_paid_statuses(),
		'orderby' => 'date',
		'order'   => 'DESC',
		'limit'   => 1,
	);
	$candidates = array();
	$user_id    = (int) $changed_order->get_user_id();
	if ( $user_id > 0 ) {
		$user_orders = is_callable( 'wc_get_orders' )
			? call_user_func( 'wc_get_orders', array_merge( $query_args, array( 'customer_id' => $user_id ) ) )
			: array();
		if ( is_array( $user_orders ) ) {
			$candidates = array_merge( $candidates, $user_orders );
		}
	}

	$email = sanitize_email( (string) $changed_order->get_billing_email() );
	if ( '' !== $email ) {
		$email_orders = is_callable( 'wc_get_orders' )
			? call_user_func( 'wc_get_orders', array_merge( $query_args, array( 'billing_email' => $email ) ) )
			: array();
		if ( is_array( $email_orders ) ) {
			$candidates = array_merge( $candidates, $email_orders );
		}
	}

	usort(
		$candidates,
		static function ( $left, $right ): int {
			return bsc_get_followup_purchase_timestamp( $right ) <=> bsc_get_followup_purchase_timestamp( $left );
		}
	);
	$order = reset( $candidates );
	if ( is_object( $order ) ) {
		bsc_sync_followup_state_for_order( $order );
	}
}

/**
 * Gradually seed existing customers without loading the full order history.
 *
 * @param int $limit Maximum orders to inspect in this run.
 * @return array{processed: int, complete: bool}
 */
function bsc_backfill_followup_state_batch( int $limit = 100 ): array {
	if ( ! class_exists( 'WooCommerce' ) || (bool) get_option( 'bsc_followup_state_backfill_complete', false ) ) {
		return array(
			'processed' => 0,
			'complete'  => true,
		);
	}

	if ( ! bsc_followup_state_table_is_ready() ) {
		return array(
			'processed' => 0,
			'complete'  => false,
		);
	}

	$page   = max( 1, (int) get_option( 'bsc_followup_state_backfill_page', 1 ) );
	$limit  = max( 10, min( 250, $limit ) );
	$result = is_callable( 'wc_get_orders' )
		? call_user_func(
			'wc_get_orders',
			array(
				'status'   => bsc_get_followup_paid_statuses(),
				'orderby'  => 'date',
				'order'    => 'DESC',
				'limit'    => $limit,
				'page'     => $page,
				'paginate' => true,
			)
		)
		: null;

	$orders    = is_object( $result ) && isset( $result->orders ) && is_array( $result->orders ) ? $result->orders : array();
	$max_pages = max( 1, (int) ( $result->max_num_pages ?? 1 ) );

	foreach ( $orders as $order ) {
		if ( is_object( $order ) ) {
			bsc_sync_followup_state_for_order( $order );
		}
	}

	$is_complete = $page >= $max_pages;
	if ( $is_complete ) {
		update_option( 'bsc_followup_state_backfill_complete', 1, false );
		delete_option( 'bsc_followup_state_backfill_page' );
	} else {
		update_option( 'bsc_followup_state_backfill_page', $page + 1, false );
	}

	return array(
		'processed' => count( $orders ),
		'complete'  => $is_complete,
	);
}

/**
 * Return a conservative per-stage email batch size for a small VPS.
 */
function bsc_get_followup_email_batch_size(): int {
	return max( 1, min( 100, (int) apply_filters( 'bsc_followup_email_batch_size', 25 ) ) );
}

/**
 * Fetch only indexed state rows that are ready for one follow-up stage.
 *
 * @param string $stage Queue stage: repurchase or inactive.
 * @param int    $limit Maximum state rows to return.
 * @return array<int, array<string, mixed>>
 */
function bsc_get_due_followup_states( string $stage, int $limit = 25 ): array {
	if ( ! bsc_followup_state_table_is_ready() ) {
		return array();
	}

	global $wpdb;

	$table = bsc_get_followup_state_table();
	$now   = gmdate( 'Y-m-d H:i:s' );
	$limit = max( 1, min( 100, $limit ) );

	if ( 'repurchase' === $stage ) {
		$expiry_clause = (bool) bsc_get_followup_email_setting( 'bsc_inactive_email_enabled' )
			? $wpdb->prepare( ' AND inactivity_due_at > %s', $now )
			: '';
		$sql           = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE repurchase_sent_at IS NULL
				AND repurchase_due_at <= %s
				AND repurchase_retry_at <= %s{$expiry_clause}
			ORDER BY repurchase_retry_at ASC
			LIMIT %d",
			$now,
			$now,
			$limit
		);
	} elseif ( 'inactive' === $stage ) {
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE inactivity_sent_at IS NULL
				AND inactivity_due_at <= %s
				AND inactivity_retry_at <= %s
			ORDER BY inactivity_retry_at ASC
			LIMIT %d",
			$now,
			$now,
			$limit
		);
	} else {
		return array();
	}

	$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return is_array( $rows ) ? $rows : array();
}

/**
 * Recalculate pending dates after an administrator changes either threshold.
 *
 * This is one indexed-table update and never scans WooCommerce orders.
 */
function bsc_reschedule_pending_followup_states(): void {
	if ( ! bsc_followup_state_table_is_ready() ) {
		return;
	}

	global $wpdb;

	$table  = bsc_get_followup_state_table();
	$days   = bsc_get_repurchase_inactivity_days();
	$months = bsc_get_inactivity_followup_months();
	$now    = gmdate( 'Y-m-d H:i:s' );

	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET
				repurchase_due_at = DATE_ADD(last_purchase_at, INTERVAL %d DAY),
				repurchase_retry_at = IF(repurchase_sent_at IS NULL, DATE_ADD(last_purchase_at, INTERVAL %d DAY), repurchase_retry_at),
				inactivity_due_at = DATE_ADD(last_purchase_at, INTERVAL %d MONTH),
				inactivity_retry_at = IF(inactivity_sent_at IS NULL, DATE_ADD(last_purchase_at, INTERVAL %d MONTH), inactivity_retry_at),
				updated_at = %s",
			$days,
			$days,
			$months,
			$months,
			$now
		)
	); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Delay one failed or temporarily empty follow-up until the next nightly run.
 *
 * @param string $contact_key Customer state key.
 * @param string $stage       Queue stage.
 */
function bsc_delay_followup_state_retry( string $contact_key, string $stage ): void {
	global $wpdb;

	$column = 'repurchase' === $stage ? 'repurchase_retry_at' : 'inactivity_retry_at';
	$table  = bsc_get_followup_state_table();
	$retry  = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );

	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET {$column} = %s, updated_at = %s WHERE contact_key = %s",
			$retry,
			gmdate( 'Y-m-d H:i:s' ),
			$contact_key
		)
	); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Mark a stage as sent in both indexed state and order metadata.
 *
 * @param string $contact_key        Customer state key.
 * @param string $stage              Follow-up stage.
 * @param object $order              Source order.
 * @param int[]  $recommendation_ids Product IDs included in a recommendation email.
 */
function bsc_mark_followup_state_sent( string $contact_key, string $stage, $order, array $recommendation_ids = array() ): void {
	global $wpdb;

	$table      = bsc_get_followup_state_table();
	$sent_at    = gmdate( 'Y-m-d H:i:s' );
	$sent_field = 'repurchase' === $stage ? 'repurchase_sent_at' : 'inactivity_sent_at';

	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET {$sent_field} = %s, updated_at = %s WHERE contact_key = %s AND latest_order_id = %d",
			$sent_at,
			$sent_at,
			$contact_key,
			$order->get_id()
		)
	); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( 'repurchase' === $stage ) {
		$order->update_meta_data( '_bsc_repurchase_recommendation_email_sent_at', current_time( 'mysql' ) );
		$order->update_meta_data( '_bsc_repurchase_recommendation_product_ids', array_values( array_map( 'absint', $recommendation_ids ) ) );
	} else {
		$order->update_meta_data( '_bsc_inactivity_5_month_email_sent_at', current_time( 'mysql' ) );
	}
	$order->save_meta_data();
}

/**
 * Validate that a queued row still points at the customer's qualifying order.
 *
 * @param array<string, mixed> $state Queue state row.
 * @return object|null
 */
function bsc_get_valid_followup_state_order( array $state ) {
	$order = is_callable( 'wc_get_order' ) ? call_user_func( 'wc_get_order', (int) ( $state['latest_order_id'] ?? 0 ) ) : false;
	if ( ! is_object( $order ) ) {
		bsc_delete_followup_state( (string) ( $state['contact_key'] ?? '' ) );
		return null;
	}

	if ( ! in_array( $order->get_status(), bsc_get_followup_paid_statuses(), true ) ) {
		bsc_refresh_followup_state_after_order_change( $order );
		return null;
	}

	if ( bsc_get_followup_contact_key( $order ) !== (string) ( $state['contact_key'] ?? '' ) ) {
		bsc_delete_followup_state( (string) ( $state['contact_key'] ?? '' ) );
		bsc_sync_followup_state_for_order( $order );
		return null;
	}

	return $order;
}

/**
 * Prevent cron and manual execution from processing the same batch together.
 */
function bsc_acquire_followup_state_lock(): bool {
	$lock_key = 'bsc_followup_state_processing_lock';
	$now      = time();
	if ( add_option( $lock_key, $now, '', false ) ) {
		return true;
	}

	$locked_at = (int) get_option( $lock_key, 0 );
	if ( $locked_at > 0 && $locked_at < ( $now - ( 15 * MINUTE_IN_SECONDS ) ) ) {
		delete_option( $lock_key );
		return add_option( $lock_key, $now, '', false );
	}

	return false;
}

/**
 * Release the shared follow-up processing lock.
 */
function bsc_release_followup_state_lock(): void {
	delete_option( 'bsc_followup_state_processing_lock' );
}
