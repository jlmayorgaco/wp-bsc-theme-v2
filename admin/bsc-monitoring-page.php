<?php
/**
 * P3: Post-launch monitoring dashboard.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_monitoring_admin_assets' );
add_action( 'admin_init', 'bsc_handle_monitoring_settings_save' );

function bsc_enqueue_monitoring_admin_assets(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-monitoring' !== $page ) {
		return;
	}

	bsc_enqueue_admin_ui_assets();

	$css_path = get_template_directory() . '/admin/bsc-monitoring.css';

	wp_enqueue_style(
		'bsc-monitoring',
		get_template_directory_uri() . '/admin/bsc-monitoring.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);
}

function bsc_monitoring_default_settings(): array {
	return array(
		'owner_name'         => 'Operacion BSC',
		'owner_email'        => get_option( 'bsc_contact_email', get_option( 'admin_email' ) ),
		'escalation_channel' => 'WhatsApp operaciones / hosting',
		'daily_check_time'   => '09:00',
	);
}

function bsc_monitoring_get_settings(): array {
	$defaults = bsc_monitoring_default_settings();

	return array(
		'owner_name'         => (string) get_option( 'bsc_monitoring_owner_name', $defaults['owner_name'] ),
		'owner_email'        => (string) get_option( 'bsc_monitoring_owner_email', $defaults['owner_email'] ),
		'escalation_channel' => (string) get_option( 'bsc_monitoring_escalation_channel', $defaults['escalation_channel'] ),
		'daily_check_time'   => (string) get_option( 'bsc_monitoring_daily_check_time', $defaults['daily_check_time'] ),
	);
}

function bsc_handle_monitoring_settings_save(): void {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-monitoring' !== $page || ! isset( $_POST['bsc_monitoring_nonce'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
	}

	check_admin_referer( 'bsc_monitoring_save', 'bsc_monitoring_nonce' );

	$owner_name         = isset( $_POST['bsc_monitoring_owner_name'] )
		? sanitize_text_field( wp_unslash( $_POST['bsc_monitoring_owner_name'] ) )
		: '';
	$owner_email        = isset( $_POST['bsc_monitoring_owner_email'] )
		? sanitize_email( wp_unslash( $_POST['bsc_monitoring_owner_email'] ) )
		: '';
	$escalation_channel = isset( $_POST['bsc_monitoring_escalation_channel'] )
		? sanitize_text_field( wp_unslash( $_POST['bsc_monitoring_escalation_channel'] ) )
		: '';
	$daily_check_time   = isset( $_POST['bsc_monitoring_daily_check_time'] )
		? sanitize_text_field( wp_unslash( $_POST['bsc_monitoring_daily_check_time'] ) )
		: '';

	update_option( 'bsc_monitoring_owner_name', $owner_name !== '' ? $owner_name : 'Operacion BSC', false );
	update_option( 'bsc_monitoring_owner_email', is_email( $owner_email ) ? $owner_email : get_option( 'admin_email' ), false );
	update_option( 'bsc_monitoring_escalation_channel', $escalation_channel !== '' ? $escalation_channel : 'WhatsApp operaciones / hosting', false );
	update_option( 'bsc_monitoring_daily_check_time', preg_match( '/^\d{2}:\d{2}$/', $daily_check_time ) ? $daily_check_time : '09:00', false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'       => 'bsc-monitoring',
				'bsc_notice' => 'saved',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

function bsc_monitoring_status_label( string $status ): string {
	$labels = array(
		'ok'       => 'OK',
		'warning'  => 'Atencion',
		'critical' => 'Critico',
		'info'     => 'Info',
	);

	return $labels[ $status ] ?? 'Info';
}

function bsc_monitoring_badge_class( string $status ): string {
	if ( 'ok' === $status ) {
		return 'bsc-admin-badge--success';
	}

	if ( 'warning' === $status ) {
		return 'bsc-admin-badge--warning';
	}

	if ( 'critical' === $status ) {
		return 'bsc-admin-monitoring__badge--critical';
	}

	return 'bsc-admin-badge--muted';
}

function bsc_monitoring_count_orders( array $statuses, int $hours ): int {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$after = wp_date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $hours * HOUR_IN_SECONDS ) );

	$result = wc_get_orders(
		array(
			'limit'      => 1,
			'paginate'   => true,
			'return'     => 'ids',
			'status'     => $statuses,
			'date_after' => $after,
		)
	);

	return (int) ( $result->total ?? 0 );
}

function bsc_monitoring_count_email_failures( array $rows ): int {
	return count(
		array_filter(
			$rows,
			static fn( array $row ): bool => 'failed' === (string) ( $row['status'] ?? '' )
		)
	);
}

function bsc_monitoring_hours_since_mysql( string $mysql_datetime ): ?float {
	if ( '' === $mysql_datetime ) {
		return null;
	}

	$timestamp = strtotime( $mysql_datetime );

	if ( false === $timestamp ) {
		return null;
	}

	return max( 0, ( current_time( 'timestamp' ) - $timestamp ) / HOUR_IN_SECONDS );
}

function bsc_monitoring_rate_limit_blocks_since( int $hours ): int {
	if ( ! function_exists( 'bsc_count_rate_limit_blocks_since' ) ) {
		return 0;
	}

	return bsc_count_rate_limit_blocks_since( $hours * HOUR_IN_SECONDS );
}

function bsc_monitoring_recent_rate_limit_rows(): array {
	if ( ! function_exists( 'bsc_get_recent_rate_limit_blocks' ) ) {
		return array();
	}

	return bsc_get_recent_rate_limit_blocks( 12 );
}

function bsc_monitoring_format_bytes( float $bytes ): string {
	$bytes = max( 0, $bytes );
	$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	$index = 0;

	while ( $bytes >= 1024 && $index < count( $units ) - 1 ) {
		$bytes /= 1024;
		$index++;
	}

	$decimals = 0 === $index ? 0 : 1;

	return number_format_i18n( $bytes, $decimals ) . ' ' . $units[ $index ];
}

function bsc_monitoring_system_memory_kb( string $meminfo, string $key ): int {
	$pattern = '/^' . preg_quote( $key, '/' ) . ':\s+(\d+)\s+kB$/m';

	if ( 1 !== preg_match( $pattern, $meminfo, $matches ) ) {
		return 0;
	}

	return (int) $matches[1];
}

function bsc_monitoring_read_system_memory(): array {
	$empty = array(
		'total_bytes'     => 0.0,
		'available_bytes' => 0.0,
		'used_bytes'      => 0.0,
		'usage_percent'   => null,
		'source'          => 'No disponible en esta plataforma',
	);

	$meminfo_path = '/proc/meminfo';
	if ( ! is_readable( $meminfo_path ) ) {
		return $empty;
	}

	$meminfo = file_get_contents( $meminfo_path );
	if ( false === $meminfo ) {
		return $empty;
	}

	$total_kb     = bsc_monitoring_system_memory_kb( $meminfo, 'MemTotal' );
	$available_kb = bsc_monitoring_system_memory_kb( $meminfo, 'MemAvailable' );

	if ( $available_kb <= 0 ) {
		$available_kb = bsc_monitoring_system_memory_kb( $meminfo, 'MemFree' )
			+ bsc_monitoring_system_memory_kb( $meminfo, 'Buffers' )
			+ bsc_monitoring_system_memory_kb( $meminfo, 'Cached' );
	}

	$total_bytes     = (float) $total_kb * 1024;
	$available_bytes = min( $total_bytes, (float) $available_kb * 1024 );

	if ( $total_bytes <= 0 ) {
		return $empty;
	}

	$used_bytes = max( 0, $total_bytes - $available_bytes );

	return array(
		'total_bytes'     => $total_bytes,
		'available_bytes' => $available_bytes,
		'used_bytes'      => $used_bytes,
		'usage_percent'   => round( ( $used_bytes / $total_bytes ) * 100, 1 ),
		'source'          => 'Linux /proc/meminfo',
	);
}

function bsc_monitoring_read_cpu_cores(): int {
	$cpuinfo_path = '/proc/cpuinfo';

	if ( is_readable( $cpuinfo_path ) ) {
		$cpuinfo = file_get_contents( $cpuinfo_path );

		if ( false !== $cpuinfo ) {
			$cores = preg_match_all( '/^processor\s*:/m', $cpuinfo, $matches );

			if ( false !== $cores && $cores > 0 ) {
				return (int) $cores;
			}
		}
	}

	$environment_cores = getenv( 'NUMBER_OF_PROCESSORS' );

	return is_string( $environment_cores ) && ctype_digit( $environment_cores )
		? max( 1, (int) $environment_cores )
		: 0;
}

function bsc_monitoring_read_uptime_seconds(): ?float {
	$uptime_path = '/proc/uptime';

	if ( ! is_readable( $uptime_path ) ) {
		return null;
	}

	$uptime = file_get_contents( $uptime_path );
	$seconds = false !== $uptime ? (float) strtok( trim( $uptime ), ' ' ) : 0;

	return $seconds > 0 ? $seconds : null;
}

function bsc_monitoring_format_uptime( ?float $seconds ): string {
	if ( null === $seconds ) {
		return 'No disponible';
	}

	$total_minutes = max( 0, (int) floor( $seconds / 60 ) );
	$days          = intdiv( $total_minutes, 1440 );
	$hours         = intdiv( $total_minutes % 1440, 60 );
	$minutes       = $total_minutes % 60;
	$parts         = array();

	if ( $days > 0 ) {
		$parts[] = sprintf( '%d d', $days );
	}

	if ( $hours > 0 || $days > 0 ) {
		$parts[] = sprintf( '%d h', $hours );
	}

	$parts[] = sprintf( '%d min', $minutes );

	return implode( ' ', $parts );
}

function bsc_monitoring_measure_temp_directory( string $path ): array {
	$stats = array(
		'path'      => $path,
		'files'     => 0,
		'bytes'     => 0.0,
		'available' => false,
		'limited'   => false,
	);

	if ( ! is_dir( $path ) || ! is_readable( $path ) ) {
		return $stats;
	}

	$stats['available'] = true;

	try {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$path,
				FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
			),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $iterator as $file ) {
			if ( ! $file instanceof SplFileInfo || ! $file->isFile() || $file->isLink() ) {
				continue;
			}

			$stats['files']++;
			$stats['bytes'] += max( 0, (float) $file->getSize() );

			// Avoid making an admin request scan an unexpectedly large temp volume.
			if ( $stats['files'] >= 10000 ) {
				$stats['limited'] = true;
				break;
			}
		}
	} catch ( Throwable ) {
		$stats['limited'] = true;
	}

	return $stats;
}

function bsc_monitoring_status_severity( string $status ): int {
	return array(
		'ok'       => 0,
		'info'     => 1,
		'warning'  => 2,
		'critical' => 3,
	)[ $status ] ?? 1;
}

function bsc_monitoring_worst_status( array $statuses ): string {
	$worst_status    = 'ok';
	$worst_severity  = 0;

	foreach ( $statuses as $status ) {
		$severity = bsc_monitoring_status_severity( (string) $status );

		if ( $severity > $worst_severity ) {
			$worst_status   = (string) $status;
			$worst_severity = $severity;
		}
	}

	return $worst_status;
}

function bsc_monitoring_build_system_metrics(): array {
	$cached = get_transient( 'bsc_monitoring_system_metrics_v1' );

	if ( is_array( $cached ) && ! empty( $cached['generated_at'] ) ) {
		return $cached;
	}

	$disk_path  = defined( 'ABSPATH' ) && is_dir( ABSPATH ) ? ABSPATH : __DIR__;
	$disk_total = disk_total_space( $disk_path );
	$disk_free  = disk_free_space( $disk_path );
	$disk_card  = array(
		'label'    => 'Disco del volumen',
		'value'    => 'No disponible',
		'meta'     => 'No se pudo consultar el volumen de WordPress.',
		'progress' => null,
		'status'   => 'info',
	);

	if ( false !== $disk_total && false !== $disk_free && $disk_total > 0 ) {
		$disk_used    = max( 0, (float) $disk_total - (float) $disk_free );
		$disk_percent = round( ( $disk_used / (float) $disk_total ) * 100, 1 );
		$disk_status  = $disk_percent >= 90 ? 'critical' : ( $disk_percent >= 80 ? 'warning' : 'ok' );

		$disk_card = array(
			'label'    => 'Disco del volumen',
			'value'    => sprintf( '%s / %s usados', bsc_monitoring_format_bytes( $disk_used ), bsc_monitoring_format_bytes( (float) $disk_total ) ),
			'meta'     => sprintf( '%s libres · %s', bsc_monitoring_format_bytes( (float) $disk_free ), wp_normalize_path( $disk_path ) ),
			'progress' => $disk_percent,
			'status'   => $disk_status,
		);
	}

	$memory      = bsc_monitoring_read_system_memory();
	$memory_card = array(
		'label'    => 'Memoria RAM',
		'value'    => 'No disponible',
		'meta'     => 'El sistema no expone memoria total al proceso PHP.',
		'progress' => null,
		'status'   => 'info',
	);

	if ( $memory['total_bytes'] > 0 ) {
		$memory_percent = (float) $memory['usage_percent'];
		$memory_status  = $memory_percent >= 90 ? 'critical' : ( $memory_percent >= 80 ? 'warning' : 'ok' );

		$memory_card = array(
			'label'    => 'Memoria RAM',
			'value'    => sprintf( '%s / %s usados', bsc_monitoring_format_bytes( $memory['used_bytes'] ), bsc_monitoring_format_bytes( $memory['total_bytes'] ) ),
			'meta'     => sprintf( '%s disponibles · %s', bsc_monitoring_format_bytes( $memory['available_bytes'] ), $memory['source'] ),
			'progress' => $memory_percent,
			'status'   => $memory_status,
		);
	}

	$load       = function_exists( 'sys_getloadavg' ) ? sys_getloadavg() : array();
	$load_1m    = is_array( $load ) && isset( $load[0] ) ? (float) $load[0] : null;
	$cpu_cores  = bsc_monitoring_read_cpu_cores();
	$load_card  = array(
		'label'    => 'Carga del sistema',
		'value'    => 'No disponible',
		'meta'     => 'La plataforma no expone load average.',
		'progress' => null,
		'status'   => 'info',
	);

	if ( null !== $load_1m && $cpu_cores > 0 ) {
		$load_ratio = $load_1m / $cpu_cores;
		$load_card  = array(
			'label'    => 'Carga del sistema',
			'value'    => sprintf( '%s · %d CPU', number_format_i18n( $load_1m, 2 ), $cpu_cores ),
			'meta'     => 'Promedio de 1 minuto · saludable debajo de 1.00 por CPU.',
			'progress' => min( 100, round( $load_ratio * 100, 1 ) ),
			'status'   => $load_ratio >= 2 ? 'critical' : ( $load_ratio >= 1 ? 'warning' : 'ok' ),
		);
	}

	$uptime_seconds = bsc_monitoring_read_uptime_seconds();
	$uptime_card    = array(
		'label'    => 'Uptime del VPS',
		'value'    => bsc_monitoring_format_uptime( $uptime_seconds ),
		'meta'     => null !== $uptime_seconds ? 'Lectura desde Linux /proc/uptime.' : 'No disponible en esta plataforma.',
		'progress' => null,
		'status'   => null !== $uptime_seconds ? 'ok' : 'info',
	);

	$temp_stats = bsc_monitoring_measure_temp_directory( sys_get_temp_dir() );
	$temp_card  = array(
		'label'    => 'Archivos temporales',
		'value'    => 'No disponible',
		'meta'     => 'No se pudo leer el directorio temporal de PHP.',
		'progress' => null,
		'status'   => 'info',
	);

	if ( $temp_stats['available'] ) {
		$temp_status = $temp_stats['limited'] ? 'warning' : 'ok';
		$temp_meta   = sprintf( '%s · %s', wp_normalize_path( $temp_stats['path'] ), $temp_stats['limited'] ? 'lectura limitada a 10.000 archivos' : 'lectura completa' );

		$temp_card = array(
			'label'    => 'Archivos temporales',
			'value'    => sprintf( '%s · %s', number_format_i18n( $temp_stats['files'] ), bsc_monitoring_format_bytes( $temp_stats['bytes'] ) ),
			'meta'     => $temp_meta,
			'progress' => null,
			'status'   => $temp_status,
		);
	}

	$runtime_card = array(
		'label'    => 'Runtime',
		'value'    => 'PHP ' . PHP_VERSION,
		'meta'     => sprintf( '%s · WordPress %s', PHP_OS_FAMILY, get_bloginfo( 'version' ) ),
		'progress' => null,
		'status'   => version_compare( PHP_VERSION, '8.2', '>=' ) ? 'ok' : 'warning',
	);
	$cards        = array( $disk_card, $memory_card, $load_card, $uptime_card, $temp_card, $runtime_card );
	$health_status = bsc_monitoring_worst_status( wp_list_pluck( $cards, 'status' ) );
	$health_copy   = array(
		'ok'       => 'El VPS no muestra umbrales operativos críticos.',
		'info'     => 'Algunas métricas no están expuestas por la plataforma actual.',
		'warning'  => 'Hay métricas que requieren revisión operativa.',
		'critical' => 'Hay una métrica por encima de un umbral crítico.',
	);

	$metrics = array(
		'generated_at'  => current_time( 'timestamp' ),
		'health_status' => $health_status,
		'health_copy'   => $health_copy[ $health_status ],
		'cards'         => $cards,
	);

	set_transient( 'bsc_monitoring_system_metrics_v1', $metrics, MINUTE_IN_SECONDS );

	return $metrics;
}

function bsc_monitoring_build_checks(): array {
	$email_rows         = function_exists( 'bsc_get_recent_order_email_log_rows' ) ? bsc_get_recent_order_email_log_rows( 50 ) : array();
	$email_failures     = bsc_monitoring_count_email_failures( $email_rows );
	$last_email_sent_at = (string) ( $email_rows[0]['sent_at'] ?? '' );
	$failed_orders_24h  = bsc_monitoring_count_orders( array( 'failed' ), 24 );
	$cancelled_24h      = bsc_monitoring_count_orders( array( 'cancelled' ), 24 );
	$orders_24h         = bsc_monitoring_count_orders( array( 'processing', 'completed', 'preparing', 'shipped' ), 24 );
	$rate_blocks_24h    = bsc_monitoring_rate_limit_blocks_since( 24 );
	$next_followup      = wp_next_scheduled( 'bsc_run_daily_followup_emails' );
	$last_followup      = get_option( 'bsc_followup_email_last_run_summary', array() );
	$last_followup_at   = is_array( $last_followup ) ? (string) ( $last_followup['ran_at'] ?? '' ) : '';
	$last_followup_age  = bsc_monitoring_hours_since_mysql( $last_followup_at );
	$php_log_active     = defined( 'WP_DEBUG_LOG' ) && (bool) WP_DEBUG_LOG;
	$js_gate_exists     = file_exists( get_template_directory() . '/tests/e2e/smoke/console-errors.spec.js' );

	$checks = array();

	$checks[] = array(
		'title'       => 'PHP logs',
		'status'      => $php_log_active ? 'ok' : 'warning',
		'value'       => $php_log_active ? 'WP_DEBUG_LOG activo' : 'Log no detectable',
		'description' => $php_log_active
			? 'Revisar debug.log o el archivo configurado despues de cada deploy.'
			: 'Confirmar en hosting que PHP errors queda cubierto por logs del servidor.',
		'action'      => 'Revisar logs PHP 24h',
	);

	$checks[] = array(
		'title'       => 'JS storefront',
		'status'      => $js_gate_exists ? 'ok' : 'warning',
		'value'       => $js_gate_exists ? 'Smoke console activo' : 'Smoke no encontrado',
		'description' => 'El gate `console-errors.spec.js` cubre errores de consola en rutas publicas criticas.',
		'action'      => 'Ejecutar smoke JS',
	);

	$checks[] = array(
		'title'       => 'Checkout y pedidos',
		'status'      => $failed_orders_24h > 0 ? 'critical' : ( $cancelled_24h > 0 ? 'warning' : 'ok' ),
		'value'       => sprintf( '%d ok / %d failed / %d cancelados', $orders_24h, $failed_orders_24h, $cancelled_24h ),
		'description' => 'Senal rapida de conversion y problemas de pago en las ultimas 24 horas.',
		'action'      => 'Revisar Pedidos BSC',
		'url'         => admin_url( 'admin.php?page=bsc-orders' ),
	);

	$checks[] = array(
		'title'       => 'Emails de pedido',
		'status'      => $email_failures > 0 ? 'critical' : ( empty( $email_rows ) ? 'warning' : 'ok' ),
		'value'       => empty( $email_rows ) ? 'Sin eventos recientes' : sprintf( '%d fallos recientes', $email_failures ),
		'description' => $last_email_sent_at !== '' ? 'Ultimo evento: ' . $last_email_sent_at : 'Aun no hay log de emails asociados a orden.',
		'action'      => 'Abrir Emails BSC',
		'url'         => admin_url( 'admin.php?page=bsc-followup-emails' ),
	);

	$checks[] = array(
		'title'       => 'Followup cron',
		'status'      => ! $next_followup || ( null !== $last_followup_age && $last_followup_age > 36 ) ? 'warning' : 'ok',
		'value'       => $next_followup ? 'Programado' : 'Sin programar',
		'description' => $next_followup
			? 'Proxima ejecucion: ' . wp_date( 'Y-m-d H:i', $next_followup ) . ( $last_followup_at !== '' ? ' | Ultima: ' . $last_followup_at : '' )
			: 'Revisar WP-Cron o ejecutar seguimiento manual desde Emails BSC.',
		'action'      => 'Validar WP-Cron',
	);

	$checks[] = array(
		'title'       => 'Rate limit',
		'status'      => $rate_blocks_24h >= 10 ? 'critical' : ( $rate_blocks_24h > 0 ? 'warning' : 'ok' ),
		'value'       => sprintf( '%d bloqueos 24h', $rate_blocks_24h ),
		'description' => 'Picos altos pueden indicar abuso, bots o UI enviando requests duplicados.',
		'action'      => 'Revisar scopes',
	);

	return $checks;
}

function bsc_render_monitoring_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para ver esta pagina.', 'bsc-2-0' ) );
	}

	$settings        = bsc_monitoring_get_settings();
	$checks          = bsc_monitoring_build_checks();
	$system_metrics  = bsc_monitoring_build_system_metrics();
	$rate_limit_rows = bsc_monitoring_recent_rate_limit_rows();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag after redirect.
	$monitoring_saved = isset( $_GET['bsc_notice'] ) && 'saved' === sanitize_key( wp_unslash( $_GET['bsc_notice'] ) );
	?>
	<div class="wrap bsc-admin-monitoring">
		<div class="bsc-admin-page-header bsc-admin-page-header--compact">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Operacion</span>
				<h1>Monitoreo post-launch</h1>
				<p class="bsc-admin-page-header__description">Checklist operativo para revisar senales basicas despues de deploy y durante los primeros dias de operacion.</p>
			</div>
			<div class="bsc-admin-page-header__actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-dashboard' ) ); ?>" class="button">Dashboard</a>
			</div>
		</div>

		<?php if ( $monitoring_saved ) : ?>
			<div class="bsc-admin-note bsc-admin-note--success">Configuracion de monitoreo guardada.</div>
		<?php endif; ?>

		<section class="bsc-admin-monitoring__owner bsc-admin-panel">
			<div>
				<h2>Owner de incidentes</h2>
				<p>
					<strong><?php echo esc_html( $settings['owner_name'] ); ?></strong><br>
					<a href="mailto:<?php echo esc_attr( $settings['owner_email'] ); ?>"><?php echo esc_html( $settings['owner_email'] ); ?></a><br>
					Canal: <?php echo esc_html( $settings['escalation_channel'] ); ?><br>
					Revision diaria: <?php echo esc_html( $settings['daily_check_time'] ); ?>
				</p>
			</div>
			<form method="post" class="bsc-admin-monitoring__settings-form">
				<?php wp_nonce_field( 'bsc_monitoring_save', 'bsc_monitoring_nonce' ); ?>
				<label>
					Owner
					<input type="text" name="bsc_monitoring_owner_name" value="<?php echo esc_attr( $settings['owner_name'] ); ?>" class="regular-text">
				</label>
				<label>
					Email owner
					<input type="email" name="bsc_monitoring_owner_email" value="<?php echo esc_attr( $settings['owner_email'] ); ?>" class="regular-text">
				</label>
				<label>
					Canal de escalacion
					<input type="text" name="bsc_monitoring_escalation_channel" value="<?php echo esc_attr( $settings['escalation_channel'] ); ?>" class="regular-text">
				</label>
				<label>
					Hora checklist diario
					<input type="time" name="bsc_monitoring_daily_check_time" value="<?php echo esc_attr( $settings['daily_check_time'] ); ?>">
				</label>
				<button type="submit" class="button button-primary">Guardar owner</button>
			</form>
		</section>

		<section>
			<h2>Senales operativas</h2>
			<div class="bsc-admin-monitoring__grid">
				<?php foreach ( $checks as $check ) : ?>
					<article class="bsc-admin-monitoring__card bsc-admin-monitoring__card--<?php echo esc_attr( $check['status'] ); ?>">
						<div class="bsc-admin-monitoring__card-header">
							<h3><?php echo esc_html( $check['title'] ); ?></h3>
							<span class="bsc-admin-badge <?php echo esc_attr( bsc_monitoring_badge_class( $check['status'] ) ); ?>">
								<?php echo esc_html( bsc_monitoring_status_label( $check['status'] ) ); ?>
							</span>
						</div>
						<p class="bsc-admin-monitoring__value"><?php echo esc_html( $check['value'] ); ?></p>
						<p><?php echo esc_html( $check['description'] ); ?></p>
						<?php if ( ! empty( $check['url'] ) ) : ?>
							<a class="button button-small" href="<?php echo esc_url( $check['url'] ); ?>"><?php echo esc_html( $check['action'] ); ?></a>
						<?php else : ?>
							<span class="bsc-admin-monitoring__action"><?php echo esc_html( $check['action'] ); ?></span>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="bsc-admin-monitoring__section bsc-admin-monitoring__system-health">
			<div class="bsc-admin-monitoring__system-heading">
				<div>
					<span class="bsc-admin-page-header__eyebrow">Infraestructura</span>
					<h2>Salud del VPS</h2>
					<p class="bsc-admin-monitoring__intro">Lecturas de solo lectura del volumen donde vive WordPress, la memoria del sistema, carga, uptime y archivos temporales de PHP.</p>
				</div>
				<span class="bsc-admin-badge <?php echo esc_attr( bsc_monitoring_badge_class( $system_metrics['health_status'] ) ); ?>">
					<?php echo esc_html( bsc_monitoring_status_label( $system_metrics['health_status'] ) ); ?>
				</span>
			</div>

			<div class="bsc-admin-monitoring__health-summary bsc-admin-monitoring__health-summary--<?php echo esc_attr( $system_metrics['health_status'] ); ?>">
				<strong><?php echo esc_html( $system_metrics['health_copy'] ); ?></strong>
				<span>Actualizado: <?php echo esc_html( wp_date( 'Y-m-d H:i:s', (int) $system_metrics['generated_at'] ) ); ?></span>
			</div>

			<div class="bsc-admin-monitoring__system-grid">
				<?php foreach ( $system_metrics['cards'] as $metric_card ) : ?>
					<article class="bsc-admin-monitoring__system-card bsc-admin-monitoring__system-card--<?php echo esc_attr( $metric_card['status'] ); ?>">
						<div class="bsc-admin-monitoring__metric-header">
							<span class="bsc-admin-monitoring__metric-label"><?php echo esc_html( $metric_card['label'] ); ?></span>
							<span class="bsc-admin-badge <?php echo esc_attr( bsc_monitoring_badge_class( $metric_card['status'] ) ); ?>">
								<?php echo esc_html( bsc_monitoring_status_label( $metric_card['status'] ) ); ?>
							</span>
						</div>
						<strong class="bsc-admin-monitoring__metric-value"><?php echo esc_html( $metric_card['value'] ); ?></strong>
						<span class="bsc-admin-monitoring__metric-meta"><?php echo esc_html( (string) $metric_card['meta'] ); ?></span>
						<?php if ( null !== $metric_card['progress'] ) : ?>
							<progress class="bsc-admin-monitoring__progress" max="100" value="<?php echo esc_attr( (string) $metric_card['progress'] ); ?>"><?php echo esc_html( (string) $metric_card['progress'] ); ?>%</progress>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="bsc-admin-monitoring__section">
			<h2>Checklist diario</h2>
			<ol class="bsc-admin-monitoring__checklist">
				<li>Revisar esta pagina y capturar cualquier tarjeta en Atencion o Critico.</li>
				<li>Revisar logs PHP del hosting de las ultimas 24 horas.</li>
				<li>Ejecutar smoke de JS/consola y checkout antes de cambios grandes.</li>
				<li>Revisar pedidos failed, pagos rechazados y cancelados recientes.</li>
				<li>Revisar Emails BSC: fallos, duplicados y ultima ejecucion de followups.</li>
				<li>Revisar picos de rate limit por scope y confirmar si son abuso o bug de UI.</li>
			</ol>
		</section>

		<section class="bsc-admin-monitoring__section">
			<h2>Procedimiento de incidente</h2>
			<div class="bsc-admin-table-wrap bsc-admin-table-wrap--flush bsc-admin-monitoring__table-wrap"><table class="widefat striped bsc-admin-monitoring__table">
				<thead>
					<tr>
						<th>Severidad</th>
						<th>Owner</th>
						<th>Accion</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><span class="bsc-admin-badge bsc-admin-monitoring__badge--critical">Critico</span></td>
						<td><?php echo esc_html( $settings['owner_name'] ); ?></td>
						<td>Congelar deploys, avisar por el canal de escalacion, revisar logs y preparar rollback si checkout/admin esta roto.</td>
					</tr>
					<tr>
						<td><span class="bsc-admin-badge bsc-admin-badge--warning">Atencion</span></td>
						<td><?php echo esc_html( $settings['owner_name'] ); ?></td>
						<td>Crear tarea en ROADMAP_BSC.md, asignar responsable y revisar en la siguiente ventana operativa.</td>
					</tr>
					<tr>
						<td><span class="bsc-admin-badge bsc-admin-badge--success">OK</span></td>
						<td>Operacion</td>
						<td>Continuar monitoreo diario y guardar evidencia en git/roadmap cuando se cierre un ajuste.</td>
					</tr>
				</tbody>
			</table></div>
		</section>

		<section class="bsc-admin-monitoring__section">
			<h2>Rate limits recientes</h2>
			<?php if ( ! empty( $rate_limit_rows ) ) : ?>
				<div class="bsc-admin-table-wrap bsc-admin-table-wrap--flush bsc-admin-monitoring__table-wrap"><table class="widefat striped bsc-admin-monitoring__table">
					<thead>
						<tr>
							<th>Fecha</th>
							<th>Scope</th>
							<th>IP</th>
							<th>User ID</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rate_limit_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( (string) ( $row['blocked_at'] ?? '' ) ); ?></td>
								<td><code><?php echo esc_html( (string) ( $row['scope'] ?? '' ) ); ?></code></td>
								<td><?php echo esc_html( (string) ( $row['ip'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( $row['user_id'] ?? 0 ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table></div>
			<?php else : ?>
				<p>No hay bloqueos recientes registrados.</p>
			<?php endif; ?>
		</section>
	</div>
	<?php
}
