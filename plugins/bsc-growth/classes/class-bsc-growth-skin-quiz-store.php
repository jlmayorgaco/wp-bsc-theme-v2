<?php
/**
 * Persistence, private image storage and sharing for Skin Quiz runs.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Skin_Quiz_Store {
	private const DB_VERSION       = '1.0.0';
	private const DB_VERSION_OPT   = 'bsc_skin_quiz_db_version';
	private const PHOTO_RETENTION  = 90;
	private const PRIVATE_SUBDIR   = 'bsc-private/skin-quiz';
	private const SHARE_TOKEN_SIZE = 32;

	public static function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 8 );
		add_action( 'bsc_skin_quiz_cleanup', array( __CLASS__, 'cleanup_expired_runs' ) );
		add_action( 'wp_ajax_bsc_skin_quiz_get_private_image', array( __CLASS__, 'ajax_private_image' ) );
		add_action( 'wp_ajax_bsc_skin_quiz_delete_run', array( __CLASS__, 'ajax_delete_run' ) );
		add_action( 'wp_ajax_bsc_skin_quiz_send_routine', array( __CLASS__, 'ajax_send_routine' ) );
		add_action( 'wp_ajax_nopriv_bsc_skin_quiz_send_routine', array( __CLASS__, 'ajax_send_routine' ) );
		add_action( 'woocommerce_account_dashboard', array( __CLASS__, 'render_account_panel' ), 38 );
	}

	public static function maybe_install(): void {
		if ( get_option( self::DB_VERSION_OPT ) === self::DB_VERSION ) {
			self::ensure_private_directory();
			self::ensure_cleanup_schedule();
			return;
		}

		self::install_tables();
		self::ensure_private_directory();
		update_option( self::DB_VERSION_OPT, self::DB_VERSION, false );
		self::ensure_cleanup_schedule();
	}

	public static function retention_days(): int {
		return max( 1, (int) get_option( 'bsc_skin_quiz_photo_retention_days', self::PHOTO_RETENTION ) );
	}

	public static function runs_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'bsc_skin_quiz_runs';
	}

	public static function events_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'bsc_skin_quiz_events';
	}

	public static function create_run( array $answers, array $response, array $file = array(), string $mode = 'ai' ): int {
		self::maybe_install();

		global $wpdb;

		$bundle      = isset( $response['bundles'][0] ) && is_array( $response['bundles'][0] ) ? $response['bundles'][0] : array();
		$ai          = isset( $response['ai'] ) && is_array( $response['ai'] ) ? $response['ai'] : array();
		$usage       = isset( $ai['usage'] ) && is_array( $ai['usage'] ) ? $ai['usage'] : array();
		$image       = 'ai' === $mode ? self::store_private_selfie( $file ) : array();
		$user        = is_user_logged_in() ? wp_get_current_user() : null;
		$user_id     = $user instanceof WP_User ? (int) $user->ID : 0;
		$email       = $user instanceof WP_User ? sanitize_email( (string) $user->user_email ) : '';
		$product_ids = wp_parse_id_list( (array) ( $bundle['product_ids'] ?? array() ) );
		$needs       = array_values( array_map( 'sanitize_key', (array) ( $answers['needs'] ?? array() ) ) );
		$created_at  = current_time( 'mysql' );
		$expires_at  = gmdate( 'Y-m-d H:i:s', strtotime( '+' . self::retention_days() . ' days', current_time( 'timestamp', true ) ) );
		$share_token = self::share_token();
		$variant     = self::current_variant();
		$ai_storage  = self::trim_ai_for_storage( $ai, $bundle, $product_ids );

		$inserted = $wpdb->insert(
			self::runs_table(),
			array(
				'created_at'      => $created_at,
				'expires_at'      => $expires_at,
				'user_id'         => $user_id,
				'email'           => $email,
				'mode'            => sanitize_key( $mode ),
				'status'          => ! empty( $ai['fallback'] ) ? 'fallback' : 'completed',
				'fallback'        => ! empty( $ai['fallback'] ) ? 1 : 0,
				'skin_type'       => sanitize_key( (string) ( $ai['skin_profile']['skin_type'] ?? $answers['skin_type'] ?? '' ) ),
				'needs_json'      => wp_json_encode( $needs ),
				'product_ids'     => implode( ',', $product_ids ),
				'bundle_title'    => sanitize_text_field( (string) ( $bundle['title'] ?? '' ) ),
				'total_raw'       => (float) ( $bundle['total_raw'] ?? 0 ),
				'currency'        => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
				'usage_json'      => wp_json_encode( $usage ),
				'answers_json'    => wp_json_encode( self::trim_answers_for_storage( $answers ) ),
				'ai_json'         => wp_json_encode( $ai_storage ),
				'image_path'      => (string) ( $image['path'] ?? '' ),
				'image_mime'      => (string) ( $image['mime_type'] ?? '' ),
				'image_size'      => (int) ( $image['size'] ?? 0 ),
				'original_name'   => sanitize_file_name( (string) ( $image['original_name'] ?? '' ) ),
				'share_token'     => $share_token,
				'variant'         => $variant,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			if ( ! empty( $image['path'] ) && file_exists( $image['path'] ) ) {
				wp_delete_file( $image['path'] );
			}
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	public static function record_event( string $event, string $mode, array $meta = array() ): void {
		self::maybe_install();

		global $wpdb;

		$run_id  = isset( $meta['run_id'] ) ? absint( $meta['run_id'] ) : 0;
		$variant = isset( $meta['variant'] ) ? sanitize_key( (string) $meta['variant'] ) : self::current_variant();

		$wpdb->insert(
			self::events_table(),
			array(
				'created_at' => current_time( 'mysql' ),
				'date'       => current_time( 'Y-m-d' ),
				'run_id'     => $run_id,
				'event'      => sanitize_key( $event ),
				'mode'       => in_array( $mode, array( 'normal', 'ai' ), true ) ? $mode : 'normal',
				'user_id'    => get_current_user_id(),
				'variant'    => $variant,
				'meta_json'  => wp_json_encode( $meta ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	public static function get_run( int $run_id ): ?array {
		self::maybe_install();

		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::runs_table() . ' WHERE id = %d', $run_id ),
			ARRAY_A
		);

		return is_array( $row ) ? self::normalize_run( $row ) : null;
	}

	public static function get_run_by_token( string $token ): ?array {
		self::maybe_install();

		global $wpdb;

		$token = sanitize_text_field( $token );
		if ( '' === $token ) {
			return null;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::runs_table() . ' WHERE share_token = %s', $token ),
			ARRAY_A
		);

		return is_array( $row ) ? self::normalize_run( $row ) : null;
	}

	public static function get_user_runs( int $user_id, int $limit = 8 ): array {
		self::maybe_install();

		if ( $user_id <= 0 ) {
			return array();
		}

		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::runs_table() . ' WHERE user_id = %d ORDER BY created_at DESC LIMIT %d',
				$user_id,
				max( 1, $limit )
			),
			ARRAY_A
		);

		return array_values( array_map( array( __CLASS__, 'normalize_run' ), is_array( $rows ) ? $rows : array() ) );
	}

	public static function recent_runs( int $limit = 50 ): array {
		self::maybe_install();

		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::runs_table() . ' ORDER BY created_at DESC LIMIT %d', max( 1, $limit ) ),
			ARRAY_A
		);

		return array_values( array_map( array( __CLASS__, 'normalize_run' ), is_array( $rows ) ? $rows : array() ) );
	}

	public static function summary( string $start_date, string $end_date ): array {
		self::maybe_install();

		global $wpdb;

		$runs_table   = self::runs_table();
		$events_table = self::events_table();
		$run_rows     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT mode, fallback, skin_type, needs_json, product_ids, usage_json FROM {$runs_table} WHERE DATE(created_at) BETWEEN %s AND %s",
				$start_date,
				$end_date
			),
			ARRAY_A
		);
		$event_rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event, mode FROM {$events_table} WHERE date BETWEEN %s AND %s",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		$summary = array(
			'total_runs'        => 0,
			'ai_runs'           => 0,
			'normal_runs'       => 0,
			'fallbacks'         => 0,
			'tokens'            => 0,
			'cost_usd'          => 0.0,
			'cost_cop'          => 0.0,
			'skin_types'        => array(),
			'needs'             => array(),
			'product_counts'    => array(),
			'events'            => array(),
		);

		foreach ( is_array( $run_rows ) ? $run_rows : array() as $row ) {
			$summary['total_runs']++;
			$mode = (string) ( $row['mode'] ?? '' );
			if ( 'ai' === $mode ) {
				$summary['ai_runs']++;
			} else {
				$summary['normal_runs']++;
			}
			if ( ! empty( $row['fallback'] ) ) {
				$summary['fallbacks']++;
			}
			$skin_type = sanitize_key( (string) ( $row['skin_type'] ?? '' ) );
			if ( '' !== $skin_type ) {
				$summary['skin_types'][ $skin_type ] = ( $summary['skin_types'][ $skin_type ] ?? 0 ) + 1;
			}
			$needs = json_decode( (string) ( $row['needs_json'] ?? '' ), true );
			foreach ( is_array( $needs ) ? $needs : array() as $need ) {
				$need = sanitize_key( (string) $need );
				if ( '' !== $need ) {
					$summary['needs'][ $need ] = ( $summary['needs'][ $need ] ?? 0 ) + 1;
				}
			}
			foreach ( wp_parse_id_list( (string) ( $row['product_ids'] ?? '' ) ) as $product_id ) {
				$summary['product_counts'][ $product_id ] = ( $summary['product_counts'][ $product_id ] ?? 0 ) + 1;
			}
			$usage = json_decode( (string) ( $row['usage_json'] ?? '' ), true );
			if ( is_array( $usage ) ) {
				$summary['tokens']   += (int) ( $usage['total_tokens'] ?? 0 );
				$summary['cost_usd'] += (float) ( $usage['cost_usd'] ?? 0 );
				$summary['cost_cop'] += (float) ( $usage['cost_cop'] ?? 0 );
			}
		}

		foreach ( is_array( $event_rows ) ? $event_rows : array() as $row ) {
			$event = sanitize_key( (string) ( $row['event'] ?? '' ) );
			if ( '' !== $event ) {
				$summary['events'][ $event ] = ( $summary['events'][ $event ] ?? 0 ) + 1;
			}
		}

		arsort( $summary['skin_types'] );
		arsort( $summary['needs'] );
		arsort( $summary['product_counts'] );

		return $summary;
	}

	public static function image_url( int $run_id ): string {
		return add_query_arg(
			array(
				'action' => 'bsc_skin_quiz_get_private_image',
				'run_id' => $run_id,
				'nonce'  => wp_create_nonce( 'bsc_skin_quiz_image_' . $run_id ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	public static function share_url( array $run ): string {
		$token = (string) ( $run['share_token'] ?? '' );

		return '' !== $token ? add_query_arg( 'routine_run', rawurlencode( $token ), home_url( '/skin-quiz/' ) ) : home_url( '/skin-quiz/' );
	}

	public static function whatsapp_url( array $run ): string {
		$title    = (string) ( $run['bundle_title'] ?? 'Mi rutina BSC' );
		$products = array();
		foreach ( wp_parse_id_list( (string) ( $run['product_ids'] ?? '' ) ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$products[] = wp_strip_all_tags( $product->get_name() );
			}
		}

		$message = trim(
			$title . "\n" .
			( ! empty( $products ) ? 'Productos: ' . implode( ', ', array_slice( $products, 0, 5 ) ) . "\n" : '' ) .
			self::share_url( $run )
		);

		return 'https://wa.me/?text=' . rawurlencode( $message );
	}

	public static function ajax_private_image(): void {
		$run_id = isset( $_GET['run_id'] ) ? absint( $_GET['run_id'] ) : 0;
		if ( $run_id <= 0 ) {
			status_header( 404 );
			exit;
		}

		check_ajax_referer( 'bsc_skin_quiz_image_' . $run_id, 'nonce' );
		$run = self::get_run( $run_id );

		if ( ! $run || ! self::can_view_run( $run ) || empty( $run['image_path'] ) || ! file_exists( $run['image_path'] ) ) {
			status_header( 403 );
			exit;
		}

		$mime = in_array( $run['image_mime'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ? $run['image_mime'] : 'image/jpeg';
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . (string) filesize( $run['image_path'] ) );
		header( 'Cache-Control: private, max-age=300' );
		readfile( $run['image_path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	public static function ajax_delete_run(): void {
		check_ajax_referer( 'bsc_growth_action', 'nonce' );
		$run_id = isset( $_POST['run_id'] ) ? absint( $_POST['run_id'] ) : 0;
		$run    = self::get_run( $run_id );

		if ( ! $run || ! self::can_delete_run( $run ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos para borrar este historial.' ), 403 );
		}

		self::delete_run( $run_id );
		wp_send_json_success( array( 'deleted' => true ) );
	}

	public static function ajax_send_routine(): void {
		check_ajax_referer( 'bsc_growth_action', 'nonce' );
		if ( function_exists( 'bsc_rate_limit' ) ) {
			bsc_rate_limit( 'skin_quiz_send_routine', 5, 10 * MINUTE_IN_SECONDS );
		}

		$run_id = isset( $_POST['run_id'] ) ? absint( $_POST['run_id'] ) : 0;
		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$token  = isset( $_POST['share_token'] ) ? sanitize_text_field( wp_unslash( $_POST['share_token'] ) ) : '';
		$run    = self::get_run( $run_id );

		if ( ! $run || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Confirma un correo válido para enviar tu rutina.' ), 400 );
		}

		if ( ! self::can_view_shared_or_owned_run( $run, $token ) ) {
			wp_send_json_error( array( 'message' => 'No fue posible enviar esta rutina.' ), 403 );
		}

		$sent = wp_mail(
			$email,
			'Tu rutina BSC personalizada',
			self::email_body( $run ),
			array( 'Content-Type: text/html; charset=UTF-8' )
		);

		if ( ! $sent ) {
			wp_send_json_error( array( 'message' => 'No fue posible enviar el correo.' ), 500 );
		}

		wp_send_json_success( array( 'message' => 'Rutina enviada a tu correo.' ) );
	}

	public static function render_account_panel(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$runs = self::get_user_runs( get_current_user_id(), 5 );
		if ( empty( $runs ) ) {
			return;
		}

		?>
		<section class="bsc-skin-quiz-account" aria-labelledby="bsc-skin-quiz-account-title">
			<div class="bsc-skin-quiz-account__header">
				<h2 id="bsc-skin-quiz-account-title">Mi Skin Quiz</h2>
				<a href="<?php echo esc_url( home_url( '/skin-quiz/?mode=ai' ) ); ?>">Repetir quiz</a>
			</div>
			<div class="bsc-skin-quiz-account__list">
				<?php foreach ( $runs as $run ) : ?>
					<article class="bsc-skin-quiz-account__item" data-bsc-run-id="<?php echo esc_attr( (string) $run['id'] ); ?>">
						<?php if ( ! empty( $run['image_path'] ) ) : ?>
							<img src="<?php echo esc_url( self::image_url( (int) $run['id'] ) ); ?>" alt="Selfie guardada del Skin Quiz" loading="lazy">
						<?php endif; ?>
						<div>
							<span><?php echo esc_html( mysql2date( get_option( 'date_format' ), $run['created_at'] ) ); ?></span>
							<strong><?php echo esc_html( $run['bundle_title'] ? $run['bundle_title'] : 'Rutina BSC' ); ?></strong>
							<p><?php echo esc_html( $run['skin_type'] ? ucfirst( $run['skin_type'] ) : 'Lectura guardada' ); ?> · <?php echo wp_kses_post( BSC_Growth_Plugin::price_html( (float) $run['total_raw'] ) ); ?></p>
							<div class="bsc-skin-quiz-account__actions">
								<a class="bsc__button" href="<?php echo esc_url( self::share_url( $run ) ); ?>">Ver rutina</a>
								<button type="button" class="bsc-skin-quiz__ghost-button" data-bsc-delete-run="<?php echo esc_attr( (string) $run['id'] ); ?>">Eliminar fotos</button>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	public static function cleanup_expired_runs(): void {
		self::maybe_install();

		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT id, image_path FROM ' . self::runs_table() . ' WHERE expires_at < %s', current_time( 'mysql' ) ),
			ARRAY_A
		);

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			self::delete_run( (int) $row['id'] );
		}
	}

	public static function delete_run( int $run_id ): void {
		self::maybe_install();

		global $wpdb;

		$run = self::get_run( $run_id );
		if ( $run && ! empty( $run['image_path'] ) && file_exists( $run['image_path'] ) ) {
			wp_delete_file( $run['image_path'] );
		}

		$wpdb->delete( self::runs_table(), array( 'id' => $run_id ), array( '%d' ) );
	}

	private static function install_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$runs    = self::runs_table();
		$events  = self::events_table();

		dbDelta(
			"CREATE TABLE {$runs} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL,
				expires_at datetime NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				email varchar(190) NOT NULL DEFAULT '',
				mode varchar(16) NOT NULL DEFAULT 'normal',
				status varchar(32) NOT NULL DEFAULT 'completed',
				fallback tinyint(1) NOT NULL DEFAULT 0,
				skin_type varchar(60) NOT NULL DEFAULT '',
				needs_json longtext NULL,
				product_ids text NULL,
				bundle_title varchar(255) NOT NULL DEFAULT '',
				total_raw decimal(18,2) NOT NULL DEFAULT 0,
				currency varchar(12) NOT NULL DEFAULT '',
				usage_json longtext NULL,
				answers_json longtext NULL,
				ai_json longtext NULL,
				image_path text NULL,
				image_mime varchar(80) NOT NULL DEFAULT '',
				image_size bigint(20) unsigned NOT NULL DEFAULT 0,
				original_name varchar(255) NOT NULL DEFAULT '',
				share_token varchar(80) NOT NULL DEFAULT '',
				variant varchar(80) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY share_token (share_token),
				KEY created_at (created_at),
				KEY expires_at (expires_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$events} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL,
				date date NOT NULL,
				run_id bigint(20) unsigned NOT NULL DEFAULT 0,
				event varchar(80) NOT NULL DEFAULT '',
				mode varchar(16) NOT NULL DEFAULT 'normal',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				variant varchar(80) NOT NULL DEFAULT '',
				meta_json longtext NULL,
				PRIMARY KEY  (id),
				KEY date (date),
				KEY event (event),
				KEY run_id (run_id),
				KEY user_id (user_id)
			) {$charset};"
		);
	}

	private static function ensure_cleanup_schedule(): void {
		if ( ! wp_next_scheduled( 'bsc_skin_quiz_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'bsc_skin_quiz_cleanup' );
		}
	}

	private static function ensure_private_directory(): string {
		$upload_dir = wp_upload_dir();
		$base       = trailingslashit( (string) $upload_dir['basedir'] ) . self::PRIVATE_SUBDIR;

		if ( ! is_dir( $base ) ) {
			wp_mkdir_p( $base );
		}

		if ( is_dir( $base ) ) {
			$index = trailingslashit( $base ) . 'index.php';
			$deny  = trailingslashit( $base ) . '.htaccess';

			if ( ! file_exists( $index ) ) {
				file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
			$rules = "Deny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n";
			if ( ! file_exists( $deny ) || ! str_contains( (string) file_get_contents( $deny ), 'Require all denied' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				file_put_contents( $deny, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}

		return $base;
	}

	private static function store_private_selfie( array $file ): array {
		$error = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );
		if ( UPLOAD_ERR_OK !== $error ) {
			return array();
		}

		$tmp_name = (string) ( $file['tmp_name'] ?? '' );
		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
			return array();
		}

		$imageinfo = @getimagesize( $tmp_name );
		$mime_type = is_array( $imageinfo ) ? (string) ( $imageinfo['mime'] ?? '' ) : '';
		$extensions = array(
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/webp' => 'webp',
		);

		if ( ! isset( $extensions[ $mime_type ] ) ) {
			return array();
		}

		$dir       = self::ensure_private_directory();
		$filename  = wp_generate_uuid4() . '-' . wp_generate_password( 10, false, false ) . '.' . $extensions[ $mime_type ];
		$dest_path = trailingslashit( $dir ) . $filename;

		if ( ! @copy( $tmp_name, $dest_path ) ) {
			return array();
		}

		@chmod( $dest_path, 0640 );

		return array(
			'path'          => $dest_path,
			'mime_type'     => $mime_type,
			'size'          => (int) filesize( $dest_path ),
			'original_name' => (string) ( $file['name'] ?? '' ),
		);
	}

	private static function trim_answers_for_storage( array $answers ): array {
		unset( $answers['vision_signals']['progress_signature'] );

		return $answers;
	}

	private static function trim_ai_for_storage( array $ai, array $bundle = array(), array $product_ids = array() ): array {
		unset( $ai['after_image_data_uri'] );

		if ( ! empty( $bundle ) ) {
			$ai['routine'] = array(
				'steps'       => array_values( (array) ( $bundle['steps'] ?? array() ) ),
				'products'    => self::trim_products_for_storage( (array) ( $bundle['products'] ?? array() ) ),
				'product_ids' => array_values( array_map( 'absint', $product_ids ) ),
				'total_raw'   => (float) ( $bundle['total_raw'] ?? 0 ),
			);
		}

		return $ai;
	}

	private static function trim_products_for_storage( array $products ): array {
		return array_values(
			array_map(
				static function ( array $product ): array {
					return array(
						'id'                  => absint( $product['id'] ?? 0 ),
						'name'                => sanitize_text_field( (string) ( $product['name'] ?? '' ) ),
						'permalink'           => esc_url_raw( (string) ( $product['permalink'] ?? '' ) ),
						'image'               => esc_url_raw( (string) ( $product['image'] ?? '' ) ),
						'brand'               => sanitize_text_field( (string) ( $product['brand'] ?? '' ) ),
						'price'               => sanitize_text_field( (string) ( $product['price'] ?? '' ) ),
						'price_html'          => wp_kses_post( (string) ( $product['price_html'] ?? '' ) ),
						'price_raw'           => (float) ( $product['price_raw'] ?? 0 ),
						'stock_status'        => sanitize_key( (string) ( $product['stock_status'] ?? '' ) ),
						'stock_label'         => sanitize_text_field( (string) ( $product['stock_label'] ?? '' ) ),
						'is_addable'          => ! empty( $product['is_addable'] ),
						'replaces_product_id' => absint( $product['replaces_product_id'] ?? 0 ),
						'replacement_label'   => sanitize_text_field( (string) ( $product['replacement_label'] ?? '' ) ),
					);
				},
				array_filter( $products, 'is_array' )
			)
		);
	}

	private static function normalize_run( array $row ): array {
		$row['id']         = (int) ( $row['id'] ?? 0 );
		$row['user_id']    = (int) ( $row['user_id'] ?? 0 );
		$row['fallback']   = ! empty( $row['fallback'] );
		$row['total_raw']  = (float) ( $row['total_raw'] ?? 0 );
		$row['image_size'] = (int) ( $row['image_size'] ?? 0 );
		$row['usage']      = json_decode( (string) ( $row['usage_json'] ?? '' ), true );
		$row['answers']    = json_decode( (string) ( $row['answers_json'] ?? '' ), true );
		$row['ai']         = json_decode( (string) ( $row['ai_json'] ?? '' ), true );
		$row['needs']      = json_decode( (string) ( $row['needs_json'] ?? '' ), true );

		$row['usage']   = is_array( $row['usage'] ) ? $row['usage'] : array();
		$row['answers'] = is_array( $row['answers'] ) ? $row['answers'] : array();
		$row['ai']      = is_array( $row['ai'] ) ? $row['ai'] : array();
		$row['needs']   = is_array( $row['needs'] ) ? $row['needs'] : array();

		return $row;
	}

	private static function can_view_run( array $run ): bool {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		return is_user_logged_in() && (int) $run['user_id'] === get_current_user_id();
	}

	private static function can_delete_run( array $run ): bool {
		return self::can_view_run( $run );
	}

	private static function can_view_shared_or_owned_run( array $run, string $share_token = '' ): bool {
		if ( self::can_view_run( $run ) ) {
			return true;
		}

		$stored_token = (string) ( $run['share_token'] ?? '' );

		return '' !== $stored_token && hash_equals( $stored_token, $share_token );
	}

	private static function share_token(): string {
		return bin2hex( random_bytes( self::SHARE_TOKEN_SIZE / 2 ) );
	}

	private static function current_variant(): string {
		$variant = isset( $_COOKIE['bsc_skin_quiz_variant'] ) ? sanitize_key( wp_unslash( $_COOKIE['bsc_skin_quiz_variant'] ) ) : '';

		return '' !== $variant ? $variant : 'control';
	}

	private static function email_body( array $run ): string {
		$product_items = '';
		foreach ( wp_parse_id_list( (string) ( $run['product_ids'] ?? '' ) ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$product_items .= '<li><a href="' . esc_url( get_permalink( $product_id ) ) . '">' . esc_html( wp_strip_all_tags( $product->get_name() ) ) . '</a></li>';
		}

		return '<p>Esta es tu rutina personalizada de Bubbles Skin Care.</p>'
			. '<h2>' . esc_html( (string) ( $run['bundle_title'] ?? 'Rutina BSC' ) ) . '</h2>'
			. ( '' !== $product_items ? '<ul>' . $product_items . '</ul>' : '' )
			. '<p>Total estimado: ' . wp_kses_post( BSC_Growth_Plugin::price_html( (float) ( $run['total_raw'] ?? 0 ) ) ) . '</p>'
			. '<p><a href="' . esc_url( self::share_url( $run ) ) . '">Ver mi rutina</a></p>'
			. '<p><a href="' . esc_url( BSC_Growth_Plugin::checkout_url() ) . '">Ir a checkout</a></p>';
	}
}
