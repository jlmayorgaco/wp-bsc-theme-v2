<?php
/**
 * Encrypted, portable sale snapshots and a durable mail outbox.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Internal outbox/ledger table names; values are prepared, reads must stay fresh under locks.
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Binary authenticated encryption is serialized as JSON-safe base64, never executed.

/** Portable sale archive with a retryable delivery queue. */
final class BSC_Sale_Backup {

	public const OPTION    = 'bsc_sale_backup_settings';
	public const MAX_BYTES = 5242880;
	/**
	 * Orders awaiting snapshot capture.
	 *
	 * @var array<int, bool>
	 */
	private static array $dirty = array();
	/**
	 * Suppress capture while importing historical records.
	 *
	 * @var bool
	 */
	public static bool $restoring = false;

	/** Register lifecycle hooks. */
	public static function init(): void {
		add_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'changed' ), 100 );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'changed' ), 100 );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'changed' ), 100 );
		add_action( 'shutdown', array( __CLASS__, 'flush' ), 15 );
		add_action( 'bsc_sale_backup_send', array( __CLASS__, 'send' ) );
		add_action( 'bsc_sale_backup_sweep', array( __CLASS__, 'sweep' ) );
	}

	/** Read the private configuration. */
	public static function settings(): array {
		return (array) get_option( self::OPTION, array() );
	}

	/** Return the internally owned table name. */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bsc_sale_backups';
	}

	/** Install the durable queue and periodic retry sweep. */
	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$collate = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE $table (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint unsigned NOT NULL,
			order_uuid varchar(36) NOT NULL,
			fingerprint varchar(64) NOT NULL,
			payload longtext NOT NULL,
			state varchar(20) NOT NULL DEFAULT 'pending',
			attempts int unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			attempted_at datetime NULL,
			error varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			UNIQUE KEY snapshot (order_uuid,fingerprint),
			KEY delivery (state,attempted_at)
		) $collate;"
		);
		if ( ! wp_next_scheduled( 'bsc_sale_backup_sweep' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'bsc_sale_backup_sweep' );
		}
	}

	/**
	 * Collect changed order IDs until domain updates finish.
	 *
	 * @param mixed $order order.
	 * @return void
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function changed( $order ): void {
		if ( self::$restoring || empty( self::settings()['enabled'] ) ) {
			return;
		}
		$id = $order instanceof WC_Order ? $order->get_id() : ( is_numeric( $order ) ? (int) $order : 0 );
		if ( $id ) {
			self::$dirty[ $id ] = true; }
	}

	/** Capture after payment, inventory and points callbacks finish. */
	public static function flush(): void {
		$ids         = array_keys( self::$dirty );
		self::$dirty = array();
		foreach ( $ids as $id ) {
			try {
				$result = self::capture( $id );
				if ( is_wp_error( $result ) ) {
					self::log_failure( $id, $result->get_error_code() ); }
			} catch ( Throwable $error ) {
				self::log_failure( $id, 'snapshot_failed' );
			}
		}
	}

	/**
	 * Record a capture failure without exposing customer data.
	 *
	 * @param int    $id id.
	 * @param string $code code.
	 * @return void
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	private static function log_failure( int $id, string $code ): void {
		update_option(
			'bsc_sale_backup_last_error',
			array(
				'order_id' => $id,
				'code'     => $code,
				'date'     => gmdate( 'c' ),
			),
			false
		);
		wc_get_logger()->error(
			'Sale snapshot failed: ' . $code,
			array(
				'source'   => 'bsc-sale-backup',
				'order_id' => $id,
			)
		);
	}

	/**
	 * Export only supported operational metadata, excluding credentials.
	 *
	 * @param WC_Data $entity object.
	 * @param bool    $item item.
	 * @return array
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function metadata( WC_Data $entity, bool $item = false ): array {
		$result = array();
		foreach ( $entity->get_meta_data() as $meta ) {
			$key     = (string) $meta->key;
			$allowed = preg_match( '/^_bsc_(stock_|product_variant_|bp_points_|wompi_approved_id$|is_showroom_sale$|tracking_|archived_at$)/', $key );
			if ( $allowed || ( $item && ! str_starts_with( $key, '_' ) ) || in_array( $key, array( '_reduced_stock', '_refunded_item_id' ), true ) ) {
				if ( ! preg_match( '/password|secret|token|api.?key|card|cvv/i', $key ) ) {
					$result[ $key ] = self::plain( $meta->value );
				}
			}
		}
		return $result;
	}

	/**
	 * Convert dates and nested values to portable JSON data.
	 *
	 * @param mixed $value value.
	 * @return mixed
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	private static function plain( $value ) {
		if ( $value instanceof DateTimeInterface ) {
			return $value->getTimestamp(); }
		if ( is_array( $value ) ) {
			return array_map( array( __CLASS__, 'plain' ), $value ); }
		return is_scalar( $value ) || null === $value ? $value : null;
	}

	/**
	 * Export typed order lines and their product identities.
	 *
	 * @param WC_Abstract_Order $order order.
	 * @return array
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function items( WC_Abstract_Order $order ): array {
		$items = array();
		foreach ( array( 'line_item', 'shipping', 'fee', 'tax', 'coupon' ) as $type ) {
			foreach ( $order->get_items( $type ) as $item ) {
				$data = $item->get_data();
				unset( $data['meta_data'], $data['order_id'] );
				$product = $item instanceof WC_Order_Item_Product ? $item->get_product() : false;
				$items[] = array(
					'type'    => $type,
					'data'    => self::plain( $data ),
					'meta'    => self::metadata( $item, true ),
					'product' => $product ? array(
						'sku'  => $product->get_sku(),
						'slug' => $product->get_slug(),
						'name' => $product->get_name(),
					) : null,
				);
			}
		}
		return $items;
	}

	/**
	 * Build a complete supported sale snapshot from committed domain state.
	 *
	 * @param WC_Order $order order.
	 * @return array
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function snapshot( WC_Order $order ): array {
		global $wpdb;
		$uuid = (string) $order->get_meta( '_bsc_sale_uuid' );
		if ( ! $uuid ) {
			$uuid = wp_generate_uuid4();
			$order->update_meta_data( '_bsc_sale_uuid', $uuid );
			$order->save_meta_data();
		}
		$data   = $order->get_data();
		$fields = array( 'id', 'status', 'currency', 'prices_include_tax', 'date_created', 'date_paid', 'date_completed', 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax', 'billing', 'shipping', 'payment_method', 'payment_method_title', 'transaction_id', 'customer_note', 'order_key' );
		$data   = self::plain( array_intersect_key( $data, array_flip( $fields ) ) );
		$user   = $order->get_user();
		$points = array();
		if ( $user && function_exists( 'bsc_bp_table' ) ) {
			$table = bsc_bp_table();
			$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d AND (order_id = %d OR reason = 'redeem') ORDER BY id", $user->ID, $order->get_id() ), ARRAY_A );
			$codes = array_map( 'strtolower', $order->get_coupon_codes() );
			foreach ( $rows as $row ) {
				$extra = json_decode( (string) $row['meta'], true );
				$extra = is_array( $extra ) ? $extra : array();
				if ( (int) $row['order_id'] === $order->get_id() || in_array( strtolower( (string) ( $extra['coupon_code'] ?? '' ) ), $codes, true ) ) {
					$row['meta'] = $extra;
					$points[]    = $row;
				}
			}
		}
		$refunds = array();
		foreach ( $order->get_refunds() as $refund ) {
			$refunds[] = array(
				'id'           => $refund->get_id(),
				'amount'       => $refund->get_amount(),
				'reason'       => $refund->get_reason(),
				'date_created' => $refund->get_date_created() ? $refund->get_date_created()->getTimestamp() : null,
				'items'        => self::items( $refund ),
			);
		}
		$notes = array();
		foreach ( wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'limit'    => 100,
				'orderby'  => 'date_created',
				'order'    => 'ASC',
			)
		) as $note ) {
			$notes[] = array(
				'content'  => wp_strip_all_tags( $note->content ),
				'date'     => $note->date_created->getTimestamp(),
				'customer' => (bool) $note->customer_note,
			);
		}
		return array(
			'format'                   => 'bsc-sale',
			'version'                  => 1,
			'site_id'                  => self::settings()['site_id'],
			'order_uuid'               => $uuid,
			'order_number'             => $order->get_order_number(),
			'order'                    => $data,
			'customer'                 => array(
				'email'      => $user ? $user->user_email : $order->get_billing_email(),
				'registered' => (bool) $user,
				'first_name' => $user ? $user->first_name : $order->get_billing_first_name(),
				'last_name'  => $user ? $user->last_name : $order->get_billing_last_name(),
			),
			'items'                    => self::items( $order ),
			'meta'                     => self::metadata( $order ),
			'points'                   => $points,
			'points_balance_reference' => $user ? bsc_bp_get_balance( $user->ID ) : 0,
			'refunds'                  => $refunds,
			'notes'                    => $notes,
		);
	}

	/**
	 * Encrypt and authenticate a portable snapshot with AES-256-GCM.
	 *
	 * @param array  $snapshot snapshot.
	 * @param string $key key.
	 * @return string
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function encrypt( array $snapshot, string $key ): string {
		$raw = base64_decode( $key, true );
		if ( ! is_string( $raw ) || strlen( $raw ) !== 32 || ! function_exists( 'openssl_encrypt' ) ) {
			throw new RuntimeException( 'La clave de recuperación no es válida o falta OpenSSL.' ); }
		$iv        = random_bytes( 12 );
		$tag       = '';
		$encrypted = openssl_encrypt( wp_json_encode( $snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ), 'aes-256-gcm', $raw, OPENSSL_RAW_DATA, $iv, $tag, 'bsc-sale:1' );
		if ( false === $encrypted ) {
			throw new RuntimeException( 'No se pudo cifrar el respaldo.' ); }
		$encoded = wp_json_encode(
			array(
				'format'  => 'bsc-sale-encrypted',
				'version' => 1,
				'iv'      => base64_encode( $iv ),
				'tag'     => base64_encode( $tag ),
				'data'    => base64_encode( $encrypted ),
			),
			JSON_THROW_ON_ERROR
		);
		if ( strlen( $encoded ) > self::MAX_BYTES ) {
			throw new RuntimeException( 'El respaldo supera el límite de 5 MB.' ); }
		return $encoded;
	}

	/**
	 * Authenticate, decrypt and validate an uploaded snapshot.
	 *
	 * @param string $file file.
	 * @param string $key key.
	 * @return mixed
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function decrypt( string $file, string $key ) {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return new WP_Error( 'openssl', 'Este servidor necesita OpenSSL para abrir el respaldo.' ); }
		if ( strlen( $file ) > self::MAX_BYTES ) {
			return new WP_Error( 'size', 'El archivo supera el límite de 5 MB.' ); }
		$envelope = json_decode( $file, true, 64 );
		$raw      = base64_decode( $key, true );
		if ( ! is_array( $envelope ) || 'bsc-sale-encrypted' !== ( $envelope['format'] ?? '' ) || 1 !== ( $envelope['version'] ?? 0 ) || ! is_string( $raw ) || 32 !== strlen( $raw ) ) {
			return new WP_Error( 'format', 'El archivo o la clave de recuperación no son válidos.' ); }
		foreach ( array( 'iv', 'tag', 'data' ) as $field ) {
			if ( ! isset( $envelope[ $field ] ) || ! is_string( $envelope[ $field ] ) ) {
				return new WP_Error( 'format', 'El archivo está incompleto.' ); }
			$envelope[ $field ] = base64_decode( $envelope[ $field ], true );
			if ( false === $envelope[ $field ] ) {
				return new WP_Error( 'format', 'El archivo está dañado.' ); }
		}
		if ( 12 !== strlen( $envelope['iv'] ) || 16 !== strlen( $envelope['tag'] ) ) {
			return new WP_Error( 'format', 'El archivo está dañado.' ); }
		$plain = openssl_decrypt( $envelope['data'], 'aes-256-gcm', $raw, OPENSSL_RAW_DATA, $envelope['iv'], $envelope['tag'], 'bsc-sale:1' );
		if ( false === $plain ) {
			return new WP_Error( 'authentication', 'La clave no corresponde al respaldo o el archivo fue modificado.' ); }
		$data = json_decode( $plain, true, 64 );
		if ( ! is_array( $data ) || 'bsc-sale' !== ( $data['format'] ?? '' ) || 1 !== ( $data['version'] ?? 0 ) ) {
			return new WP_Error( 'schema', 'El respaldo no contiene una venta compatible.' ); }
		$valid = self::validate( $data );
		return is_wp_error( $valid ) ? $valid : $data;
	}

	/**
	 * Reject incomplete or unsupported snapshots before any import.
	 *
	 * @param array $data data.
	 * @return mixed
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function validate( array $data ) {
		$invalid = new WP_Error( 'schema', 'El respaldo está incompleto o contiene datos no compatibles.' );
		foreach ( array( 'items', 'points', 'meta', 'refunds', 'customer', 'order' ) as $field ) {
			if ( ! isset( $data[ $field ] ) || ! is_array( $data[ $field ] ) ) {
				return $invalid; }
		}
		foreach ( array( 'order_uuid', 'site_id', 'order_number', 'captured_at' ) as $field ) {
			if ( ! isset( $data[ $field ] ) || ! is_string( $data[ $field ] ) ) {
				return $invalid; }
		}
		if ( ! wp_is_uuid( $data['order_uuid'] ) || ! wp_is_uuid( $data['site_id'] ) || ! is_string( $data['customer']['email'] ?? null ) || ! is_email( $data['customer']['email'] ) || ! is_numeric( $data['order']['id'] ?? null ) || ! is_array( $data['order']['billing'] ?? null ) || ! is_array( $data['order']['shipping'] ?? null ) || ! is_string( $data['order']['status'] ?? null ) || ! isset( wc_get_order_statuses()[ 'wc-' . $data['order']['status'] ] ) ) {
			return $invalid; }
		foreach ( array( 'total', 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total_tax' ) as $field ) {
			if ( ! is_numeric( $data['order'][ $field ] ?? null ) || (float) $data['order'][ $field ] < 0 ) {
				return $invalid; }
		}
		if ( count( $data['items'] ) > 1000 || count( $data['refunds'] ) > 1000 || count( $data['points'] ) > 10000 ) {
			return $invalid; }
		$sets = array( $data['items'] );
		foreach ( $data['refunds'] as $refund ) {
			if ( ! is_array( $refund ) || ! is_array( $refund['items'] ?? null ) || ! is_numeric( $refund['amount'] ?? null ) || ! is_string( $refund['reason'] ?? null ) || ! isset( $refund['date_created'] ) ) {
				return $invalid; }
			$sets[] = $refund['items'];
		}
		foreach ( $sets as $entries ) {
			foreach ( $entries as $entry ) {
				if ( ! is_array( $entry ) || ! in_array( $entry['type'] ?? '', array( 'line_item', 'shipping', 'fee', 'tax', 'coupon' ), true ) || ! is_array( $entry['data'] ?? null ) || ! is_array( $entry['meta'] ?? null ) || ! is_numeric( $entry['data']['id'] ?? null ) || ! is_string( $entry['data']['name'] ?? null ) ) {
					return $invalid; }
				if ( 'line_item' === $entry['type'] && ( ! is_numeric( $entry['data']['quantity'] ?? null ) || ! is_numeric( $entry['data']['product_id'] ?? null ) || ! is_numeric( $entry['data']['variation_id'] ?? null ) ) ) {
					return $invalid; }
			}
		}
		foreach ( $data['points'] as $row ) {
			if ( ! is_array( $row ) || ! is_numeric( $row['id'] ?? null ) || ! is_numeric( $row['delta'] ?? null ) || ! is_string( $row['reason'] ?? null ) || ! is_string( $row['created_at'] ?? null ) || ! is_array( $row['meta'] ?? null ) ) {
				return $invalid; }
		}
		return true;
	}

	/**
	 * Persist a deduplicated encrypted snapshot in the durable outbox.
	 *
	 * @param int $id id.
	 * @return mixed
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function capture( int $id ) {
		$settings = self::settings();
		if ( empty( $settings['enabled'] ) || self::$restoring ) {
			return 0; }
		return BSC_DB_Lock::run(
			'sale-backup:' . $id,
			static function () use ( $id, $settings ) {
				global $wpdb;
				$order = wc_get_order( $id );
				if ( ! $order instanceof WC_Order || ( ! $order->get_date_paid() && ! $order->is_paid() ) ) {
					return 0; }
				$snapshot    = self::snapshot( $order );
				$fingerprint = hash( 'sha256', wp_json_encode( $snapshot ) );
				$table       = self::table();
				$existing    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE order_uuid = %s AND fingerprint = %s", $snapshot['order_uuid'], $fingerprint ) );
				if ( $existing ) {
					return (int) $existing; }
				$snapshot['captured_at'] = gmdate( 'c' );
				$payload                 = self::encrypt( $snapshot, $settings['key'] );
				$ok                      = $wpdb->insert(
					$table,
					array(
						'order_id'    => $id,
						'order_uuid'  => $snapshot['order_uuid'],
						'fingerprint' => $fingerprint,
						'payload'     => $payload,
						'state'       => 'pending',
						'created_at'  => current_time( 'mysql', true ),
					)
				);
				if ( false === $ok ) {
					return new WP_Error( 'outbox_write', 'No se pudo guardar el respaldo en la cola.' ); }
				$job = (int) $wpdb->insert_id;
				self::schedule( $job );
				return $job;
			}
		);
	}

	/**
	 * Schedule delivery with a WP-Cron fallback.
	 *
	 * @param int $id id.
	 * @param int $delay delay.
	 * @return void
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	private static function schedule( int $id, int $delay = 0 ): void {
		if ( function_exists( 'as_schedule_single_action' ) && as_schedule_single_action( time() + $delay, 'bsc_sale_backup_send', array( $id ), 'bsc-sale-backup', true ) ) {
			return; }
		if ( ! wp_next_scheduled( 'bsc_sale_backup_send', array( $id ) ) ) {
			wp_schedule_single_event( time() + max( 10, $delay ), 'bsc_sale_backup_send', array( $id ) ); }
	}

	/**
	 * Deliver encrypted bytes without blocking the payment callback.
	 *
	 * @param int $id id.
	 * @return void
	 * @throws RuntimeException When the operation cannot be completed safely.
	 */
	public static function send( int $id ): void {
		BSC_DB_Lock::run(
			'sale-mail:' . $id,
			static function () use ( $id ) {
				global $wpdb;
				$settings = self::settings();
				if ( empty( $settings['enabled'] ) || ! is_email( $settings['recipient'] ?? '' ) ) {
					return; }
				$table = self::table();
				$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
				if ( ! $row || 'sent' === $row->state || (int) $row->attempts >= 5 ) {
					return; }
				// Temp directory is outside public uploads; only encrypted bytes touch disk.
				$file = tempnam( sys_get_temp_dir(), 'bsc-sale-' );
				$sent = false;
				try {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Private OS temporary file contains only ciphertext and must not require interactive FTP credentials.
					if ( $file && false !== file_put_contents( $file, $row->payload ) ) {
						$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
						$sent    = wp_mail( $settings['recipient'], 'BSC: respaldo de venta #' . $row->order_id, "Adjunto: respaldo cifrado de la venta.\nDescarga el archivo e impórtalo en BSC > Recuperar ventas.\nConserva tu clave de recuperación por separado.\nReferencia: " . $row->order_uuid, $headers, array( 'venta-' . $row->order_id . '-' . $id . '.bsc-sale' => $file ) );
					}
				} catch ( Throwable $error ) {
					$sent = false;
				} finally {
					if ( $file && file_exists( $file ) ) {
						wp_delete_file( $file ); }
				}
				$attempts = (int) $row->attempts + 1;
				$wpdb->update(
					$table,
					array(
						'state'        => $sent ? 'sent' : ( $attempts >= 5 ? 'failed' : 'pending' ),
						'attempts'     => $attempts,
						'attempted_at' => current_time( 'mysql', true ),
						'error'        => $sent ? '' : 'El transporte de correo no aceptó el respaldo.',
					),
					array( 'id' => $id )
				);
				if ( ! $sent && $attempts < 5 ) {
					self::schedule( $id, 60 * ( 2 ** $attempts ) ); }
			}
		);
	}

	/** Recover pending jobs and expire accepted copies. */
	public static function sweep(): void {
		if ( empty( self::settings()['enabled'] ) ) {
			return; }
		global $wpdb;
		$table = self::table();
		$ids   = $wpdb->get_col( "SELECT id FROM $table WHERE state = 'pending' AND attempts < 5 ORDER BY id LIMIT 50" );
		foreach ( $ids as $id ) {
			self::schedule( (int) $id ); }
		// The mailbox retains the external archive. Local accepted copies have bounded retention.
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE state = 'sent' AND attempted_at < %s", gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ) );
	}
}
BSC_Sale_Backup::init();
