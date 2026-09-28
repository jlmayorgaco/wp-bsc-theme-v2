<?php
/** Administrator-only configuration, encrypted exports and reviewed imports. */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', static function () {
	add_submenu_page( 'bsc-dashboard', 'Recuperar ventas', 'Recuperar ventas', 'manage_options', 'bsc-sale-recovery', 'bsc_render_sale_recovery' );
}, 30 );
add_action( 'admin_init', 'bsc_sale_recovery_actions' );
add_action( 'admin_enqueue_scripts', static function () {
	if ( 'bsc-sale-recovery' === ( $_GET['page'] ?? '' ) ) {
		bsc_enqueue_admin_ui_assets();
		wp_enqueue_style( 'bsc-sale-recovery', get_template_directory_uri() . '/admin/bsc-sale-recovery.css', array( 'bsc-admin-ui' ), BSC_THEME_VERSION );
	}
} );

function bsc_sale_recovery_settings(): array {
	$settings = BSC_Sale_Backup::settings();
	if ( empty( $settings['key'] ) ) {
		$settings = array_merge( $settings, array( 'key' => base64_encode( random_bytes( 32 ) ), 'site_id' => wp_generate_uuid4(), 'enabled' => false, 'recipient' => '' ) );
		update_option( BSC_Sale_Backup::OPTION, $settings, false );
	}
	return $settings;
}

function bsc_sale_recovery_upload( string $name, int $limit ): string {
	$file = $_FILES[ $name ] ?? null;
	if ( ! is_array( $file ) || UPLOAD_ERR_OK !== ( $file['error'] ?? -1 ) || ! is_string( $file['tmp_name'] ?? null ) || ! is_uploaded_file( $file['tmp_name'] ) ) { throw new RuntimeException( 'Selecciona un archivo válido para continuar.' ); }
	if ( filesize( $file['tmp_name'] ) > $limit ) { throw new RuntimeException( 'El archivo supera el tamaño permitido.' ); }
	$content = file_get_contents( $file['tmp_name'], false, null, 0, $limit + 1 );
	if ( false === $content || strlen( $content ) > $limit ) { throw new RuntimeException( 'No se pudo leer el archivo.' ); }
	return $content;
}

function bsc_sale_recovery_download( string $name, string $content ): void {
	nocache_headers();
	header( 'Content-Type: application/octet-stream' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $name ) . '"' );
	header( 'Content-Length: ' . strlen( $content ) );
	// Authenticated download of generated JSON bytes, never an HTML response.
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

function bsc_sale_recovery_actions(): void {
	if ( 'bsc-sale-recovery' !== ( $_GET['page'] ?? '' ) || ! current_user_can( 'manage_options' ) ) { return; }
	if ( '1' !== get_option( 'bsc_sale_backup_schema' ) ) {
		BSC_Sale_Backup::install();
		update_option( 'bsc_sale_backup_schema', '1', false );
	}
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { return; }
	check_admin_referer( 'bsc_sale_recovery' );
	$action = isset( $_POST['sale_action'] ) && is_string( $_POST['sale_action'] ) ? sanitize_key( wp_unslash( $_POST['sale_action'] ) ) : '';
	$notice = '';
	$token = '';
	try {
		$settings = bsc_sale_recovery_settings();
		if ( 'key' === $action ) {
			$settings['key_exported'] = true;
			update_option( BSC_Sale_Backup::OPTION, $settings, false );
			bsc_sale_recovery_download( 'bsc-clave-recuperacion.bsc-key', wp_json_encode( array( 'format' => 'bsc-recovery-key', 'version' => 1, 'site_id' => $settings['site_id'], 'key' => $settings['key'] ), JSON_PRETTY_PRINT ) );
		} elseif ( 'settings' === $action ) {
			$email = isset( $_POST['recipient'] ) && is_string( $_POST['recipient'] ) ? sanitize_email( wp_unslash( $_POST['recipient'] ) ) : '';
			$enabled = ! empty( $_POST['enabled'] );
			if ( $enabled && ( ! is_email( $email ) || empty( $settings['key_exported'] ) ) ) { throw new RuntimeException( 'Descarga tu clave e indica un correo válido antes de activar los respaldos.' ); }
			$settings['recipient'] = $email;
			$settings['enabled'] = $enabled;
			update_option( BSC_Sale_Backup::OPTION, $settings, false );
			$notice = 'Configuración guardada. ' . ( $enabled ? 'Se respaldarán las próximas compras confirmadas.' : 'El envío automático está desactivado.' );
		} elseif ( 'preview' === $action ) {
			$key = $settings['key'];
			$expected_site = $settings['site_id'];
			if ( isset( $_FILES['recovery_key'] ) && UPLOAD_ERR_NO_FILE !== (int) $_FILES['recovery_key']['error'] ) {
				$external = json_decode( bsc_sale_recovery_upload( 'recovery_key', 4096 ), true );
				if ( ! is_array( $external ) || 'bsc-recovery-key' !== ( $external['format'] ?? '' ) || ! is_string( $external['key'] ?? null ) || ! wp_is_uuid( $external['site_id'] ?? '' ) ) { throw new RuntimeException( 'La clave de recuperación no es válida.' ); }
				$key = $external['key'];
				$expected_site = $external['site_id'];
			}
			$data = BSC_Sale_Backup::decrypt( bsc_sale_recovery_upload( 'sale_file', BSC_Sale_Backup::MAX_BYTES ), $key );
			if ( is_wp_error( $data ) ) { throw new RuntimeException( $data->get_error_message() ); }
			if ( $expected_site !== $data['site_id'] ) { throw new RuntimeException( 'La clave pertenece a otra tienda.' ); }
			$token = wp_generate_password( 32, false, false );
			// Never persist an uploaded recovery key or plaintext customer data in the preview.
			set_transient( 'bsc_sale_preview_' . get_current_user_id() . '_' . $token, BSC_Sale_Backup::encrypt( $data, $settings['key'] ), 15 * MINUTE_IN_SECONDS );
		} elseif ( 'restore' === $action ) {
			$token = isset( $_POST['preview_token'] ) && is_string( $_POST['preview_token'] ) ? sanitize_key( wp_unslash( $_POST['preview_token'] ) ) : '';
			$cache = 'bsc_sale_preview_' . get_current_user_id() . '_' . $token;
			$payload = get_transient( $cache );
			if ( ! is_string( $payload ) ) { throw new RuntimeException( 'La vista previa venció. Vuelve a subir el archivo.' ); }
			$data = BSC_Sale_Backup::decrypt( $payload, $settings['key'] );
			if ( is_wp_error( $data ) ) { throw new RuntimeException( $data->get_error_message() ); }
			$result = BSC_Sale_Recovery::restore( $data, ! empty( $_POST['restore_stock'] ) );
			if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
			delete_transient( $cache );
			$token = '';
			$notice = $result['already_exists'] ? 'La venta ya existe como pedido #' . $result['id'] . '. No se duplicó.' : 'Venta recuperada como pedido #' . $result['id'] . ' y agregada a la cuenta del cliente.';
		} elseif ( in_array( $action, array( 'download', 'retry' ), true ) ) {
			global $wpdb;
			$id = absint( $_POST['backup_id'] ?? 0 );
			$table = BSC_Sale_Backup::table();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
			if ( ! $row ) { throw new RuntimeException( 'El respaldo no está disponible.' ); }
			if ( 'download' === $action ) { bsc_sale_recovery_download( 'venta-' . $row->order_id . '-' . $id . '.bsc-sale', $row->payload ); }
			if ( empty( $settings['enabled'] ) ) { throw new RuntimeException( 'Activa el envío automático antes de reintentar.' ); }
			$wpdb->update( $table, array( 'attempts' => 0, 'state' => 'pending' ), array( 'id' => $id ) );
			BSC_Sale_Backup::sweep();
			$notice = 'El respaldo quedó en cola para reenviarse.';
		}
	} catch ( Throwable $error ) {
		set_transient( 'bsc_sale_notice_' . get_current_user_id(), array( 'error' => true, 'text' => $error->getMessage() ), 60 );
	}
	if ( $notice ) { set_transient( 'bsc_sale_notice_' . get_current_user_id(), array( 'error' => false, 'text' => $notice ), 60 ); }
	wp_safe_redirect( add_query_arg( array_filter( array( 'page' => 'bsc-sale-recovery', 'preview' => $token ) ), admin_url( 'admin.php' ) ) );
	exit;
}

function bsc_sale_recovery_action_field( string $action ): void {
	wp_nonce_field( 'bsc_sale_recovery' );
	?> <input type="hidden" name="sale_action" value="<?php echo esc_attr( $action ); ?>"> <?php
}

function bsc_render_sale_recovery(): void {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'No tienes permiso para recuperar ventas.' ); }
	global $wpdb;
	$settings = BSC_Sale_Backup::settings();
	$notice = get_transient( 'bsc_sale_notice_' . get_current_user_id() );
	delete_transient( 'bsc_sale_notice_' . get_current_user_id() );
	$token = isset( $_GET['preview'] ) && is_string( $_GET['preview'] ) ? sanitize_key( wp_unslash( $_GET['preview'] ) ) : '';
	$payload = $token ? get_transient( 'bsc_sale_preview_' . get_current_user_id() . '_' . $token ) : false;
	$data = is_string( $payload ) ? BSC_Sale_Backup::decrypt( $payload, $settings['key'] ?? '' ) : false;
	$table = BSC_Sale_Backup::table();
	$rows = $wpdb->get_results( "SELECT id, order_id, state, attempts, created_at, error FROM $table ORDER BY id DESC LIMIT 30" );
	?>
	<div class="wrap bsc-sale-recovery">
		<header class="bsc-admin-page-header"><div><span class="bsc-admin-page-header__eyebrow">BSC · Respaldo de compras</span><h1>Recuperar ventas</h1><p class="bsc-admin-page-header__description">Guarda cada compra confirmada y recupera una venta faltante en la cuenta de tu cliente.</p></div></header>
		<?php if ( $notice ) : ?><div class="notice <?php echo $notice['error'] ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice['text'] ); ?></p></div><?php endif; ?>
		<?php if ( $data && ! is_wp_error( $data ) ) : $preview = BSC_Sale_Recovery::preview( $data ); ?>
		<section class="bsc-recovery-card" aria-labelledby="recovery-preview-title">
			<h2 id="recovery-preview-title">Revisar venta #<?php echo esc_html( $data['order_number'] ); ?></h2>
			<dl class="bsc-recovery-facts"><div><dt>Cuenta del cliente</dt><dd><?php echo esc_html( $data['customer']['email'] ); ?></dd></div><div><dt>Total registrado</dt><dd><?php echo esc_html( $data['order']['total'] . ' ' . $data['order']['currency'] ); ?></dd></div><div><dt>Estado guardado</dt><dd><?php echo esc_html( wc_get_order_status_name( $data['order']['status'] ) ); ?></dd></div><div><dt>Respaldo creado</dt><dd><?php echo esc_html( $data['captured_at'] ); ?></dd></div><div><dt>Movimiento neto de puntos</dt><dd><?php echo esc_html( (string) $preview['points'] ); ?></dd></div><div><dt>Referencia de pago</dt><dd><?php echo esc_html( $data['order']['transaction_id'] ?: 'Sin referencia' ); ?></dd></div></dl>
			<p><strong>Dirección de envío:</strong> <?php echo esc_html( implode( ', ', array_filter( array_intersect_key( $data['order']['shipping'], array_flip( array( 'first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ) ) ) ) ) ); ?></p>
			<div class="bsc-recovery-table"><table class="widefat striped"><thead><tr><th>Concepto</th><th>Cantidad</th><th>Importe</th></tr></thead><tbody><?php foreach ( $data['items'] as $item ) : ?><tr><td><?php echo esc_html( $item['data']['name'] ?? $item['type'] ); ?></td><td><?php echo esc_html( (string) ( $item['data']['quantity'] ?? '—' ) ); ?></td><td><?php echo esc_html( (string) ( $item['data']['total'] ?? $item['data']['discount'] ?? '—' ) ); ?></td></tr><?php endforeach; ?></tbody></table></div>
			<?php if ( $preview['existing'] ) : ?><p class="bsc-recovery-warning">Esta venta ya existe como pedido #<?php echo esc_html( (string) $preview['existing'] ); ?>. No se sobrescribirá ni se agregarán puntos nuevamente.</p>
			<?php else : ?>
				<p><?php echo $preview['user_id'] ? 'La venta se agregará a la cuenta existente.' : 'Se creará una cuenta de cliente con este correo. El cliente podrá establecer su contraseña con «¿Olvidaste tu contraseña?». No se enviará un correo automático.'; ?></p>
				<?php if ( $preview['missing_products'] ) : ?><p class="bsc-recovery-warning">Productos que ya no están en el catálogo: <?php echo esc_html( implode( ', ', $preview['missing_products'] ) ); ?>. Se conservarán sus nombres e importes en el historial.</p><?php endif; ?>
				<form method="post"><?php bsc_sale_recovery_action_field( 'restore' ); ?><input type="hidden" name="preview_token" value="<?php echo esc_attr( $token ); ?>">
					<label class="bsc-recovery-check"><input type="checkbox" name="restore_stock" value="1" <?php disabled( (bool) $preview['missing_products'] ); ?>> Descontar también el inventario de esta venta</label>
					<p class="description">Márcalo solo si restauraste una base de datos anterior a esos movimientos. Si el inventario ya los incluye, déjalo desmarcado.</p>
					<p>Se conservarán los importes y las fechas originales. Se agregarán únicamente los movimientos de puntos que falten. La recuperación no realiza cobros ni envía confirmaciones de compra. Revisa en la pasarela si hubo pagos o devoluciones posteriores a este archivo.</p>
					<?php submit_button( 'Recuperar venta y agregar a la cuenta', 'primary', 'submit', false ); ?>
				</form>
			<?php endif; ?>
		</section>
		<?php elseif ( $token ) : ?><div class="notice notice-error"><p>La vista previa venció o no es válida. Vuelve a subir el archivo.</p></div><?php endif; ?>
		<div class="bsc-recovery-grid">
			<section class="bsc-recovery-card" aria-labelledby="recovery-upload-title"><h2 id="recovery-upload-title">Importar una venta</h2><p>Sube el adjunto del correo de respaldo. Podrás revisar la venta antes de recuperarla.</p>
				<form method="post" enctype="multipart/form-data"><?php bsc_sale_recovery_action_field( 'preview' ); ?>
					<label for="sale-file">Archivo de la venta</label><input id="sale-file" type="file" name="sale_file" required accept=".bsc-sale,.json"><p class="description">Archivo cifrado de BSC. Máximo 5 MB.</p>
					<label for="recovery-key">Clave de recuperación guardada (opcional)</label><input id="recovery-key" type="file" name="recovery_key" accept=".bsc-key,.json"><p class="description">Necesaria si la copia restaurada no conserva la clave original. La clave subida se usa solo para abrir este archivo.</p>
					<?php submit_button( 'Revisar archivo', 'primary', 'submit', false ); ?>
				</form>
			</section>
			<section class="bsc-recovery-card" aria-labelledby="recovery-settings-title"><h2 id="recovery-settings-title">Respaldo por correo</h2><p><strong><?php echo empty( $settings['enabled'] ) ? 'Envío automático desactivado' : 'Envío automático activado'; ?></strong></p>
				<form method="post"><?php bsc_sale_recovery_action_field( 'key' ); ?><p>1. Descarga tu clave y guárdala fuera del servidor, separada de los respaldos. La necesitarás después de una pérdida de datos.</p><?php submit_button( 'Descargar clave de recuperación', 'secondary', 'submit', false ); ?></form>
				<form method="post"><?php bsc_sale_recovery_action_field( 'settings' ); ?><p>2. Configura un buzón exclusivo para conservar los archivos.</p><label for="backup-recipient">Correo de destino</label><input id="backup-recipient" type="email" name="recipient" value="<?php echo esc_attr( $settings['recipient'] ?? '' ); ?>" autocomplete="off">
					<label class="bsc-recovery-check"><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> Enviar respaldos de compras confirmadas y sus cambios</label>
					<p class="description">Usa el correo configurado en BSC → Emails. El envío queda en cola y se reintenta si falla. Complementa tus copias de UpdraftPlus.</p><?php submit_button( 'Guardar configuración', 'primary', 'submit', false ); ?></form>
			</section>
		</div>
		<section class="bsc-recovery-card" aria-labelledby="recovery-history-title"><h2 id="recovery-history-title">Últimos respaldos</h2><p>«Aceptado por SMTP» indica que el servidor de correo aceptó el envío; comprueba su llegada al buzón. Las copias locales aceptadas se conservan durante 30 días.</p>
		<?php $last_error = get_option( 'bsc_sale_backup_last_error' ); if ( $last_error ) : ?><p class="bsc-recovery-warning">No se pudo generar un respaldo del pedido #<?php echo esc_html( (string) $last_error['order_id'] ); ?> (<?php echo esc_html( $last_error['date'] ); ?>). Revisa el registro bsc-sale-backup en WooCommerce.</p><?php endif; ?>
		<?php if ( ! $rows ) : ?><p>No hay respaldos todavía. Al activar el envío, las próximas compras confirmadas aparecerán aquí.</p><?php else : ?>
		<div class="bsc-recovery-table"><table class="widefat striped"><thead><tr><th>Pedido</th><th>Creado (UTC)</th><th>Estado</th><th>Intentos</th><th>Acciones</th></tr></thead><tbody>
		<?php foreach ( $rows as $row ) : ?><tr><td>#<?php echo esc_html( (string) $row->order_id ); ?></td><td><?php echo esc_html( $row->created_at ); ?></td><td><?php echo esc_html( array( 'sent' => 'Aceptado por SMTP', 'pending' => 'En cola', 'failed' => 'Envío fallido' )[ $row->state ] ?? $row->state ); ?></td><td><?php echo esc_html( (string) $row->attempts ); ?></td><td><form method="post"><?php wp_nonce_field( 'bsc_sale_recovery' ); ?><input type="hidden" name="backup_id" value="<?php echo esc_attr( (string) $row->id ); ?>"><button class="button" name="sale_action" value="download">Descargar archivo</button> <button class="button" name="sale_action" value="retry">Reenviar</button></form></td></tr><?php endforeach; ?>
		</tbody></table></div><?php endif; ?>
		</section>
	</div>
	<?php
}
