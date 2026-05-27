<?php
/**
 * BSC-082: Admin page for customer email flows and follow-up settings.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_followup_admin_assets' );
function bsc_enqueue_followup_admin_assets(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-followup-emails' !== $page ) {
		return;
	}

	$css_path = get_template_directory() . '/admin/bsc-admin-communications.css';

	wp_enqueue_style(
		'bsc-admin-communications',
		get_template_directory_uri() . '/admin/bsc-admin-communications.css',
		array(),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);
}

function bsc_get_preview_email_manifest_row( string $slug ): array {
	foreach ( bsc_get_email_template_manifest() as $row ) {
		if ( (string) ( $row['slug'] ?? '' ) === $slug ) {
			return $row;
		}
	}

	return array();
}

function bsc_send_preview_email_to_target( string $slug, string $target_email ) {
	$slug         = sanitize_key( $slug );
	$target_email = sanitize_email( $target_email );
	$debug        = array(
		'time'    => current_time( 'mysql' ),
		'slug'    => $slug,
		'to'      => $target_email,
		'smtp'    => function_exists( 'bsc_get_smtp_settings' ) ? bsc_get_smtp_settings() : array(),
		'steps'   => array(),
		'errors'  => array(),
	);

	if ( ! is_email( $target_email ) ) {
		$debug['steps'][] = 'FAIL: email destino invalido';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_target_invalid', 'Ingresa un email destino valido para enviar la prueba.' );
	}
	$debug['steps'][] = 'OK: email destino valido';

	if ( ! function_exists( 'bsc_get_email_preview_definitions' ) || ! function_exists( 'bsc_get_email_preview_context' ) ) {
		$debug['steps'][] = 'FAIL: sistema de previews no disponible';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_unavailable', 'El sistema de previews no esta disponible.' );
	}
	$debug['steps'][] = 'OK: sistema de previews cargado';

	$definitions = bsc_get_email_preview_definitions();
	if ( ! isset( $definitions[ $slug ] ) ) {
		$debug['steps'][] = 'FAIL: template no encontrado en definiciones';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_template_missing', 'Template de preview no encontrado.' );
	}
	$debug['steps'][] = 'OK: template encontrado: ' . ( $definitions[ $slug ]['template'] ?? '?' );

	$debug['template_file'] = (string) ( $definitions[ $slug ]['template'] ?? '' );

	$html = bsc_render_email_template(
		(string) $definitions[ $slug ]['template'],
		bsc_get_email_preview_context( $slug )
	);

	if ( $html === '' ) {
		$debug['steps'][] = 'FAIL: render del template devolvio string vacio';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_render_failed', 'No se pudo renderizar el email de prueba.' );
	}
	$debug['steps'][] = 'OK: template renderizado (' . strlen( $html ) . ' bytes)';

	$row     = bsc_get_preview_email_manifest_row( $slug );
	$label   = (string) ( $row['label'] ?? $definitions[ $slug ]['label'] ?? $slug );
	$subject = sprintf( 'Preview BSC: %s', $label );
	$debug['subject'] = $subject;

	$phpmailer_error = null;
	add_action(
		'wp_mail_failed',
		function ( $error ) use ( &$phpmailer_error ) {
			$phpmailer_error = $error;
		}
	);

	$captured_phpmailer_debug = bsc_capture_phpmailer_debug();

	$sent = wp_mail(
		$target_email,
		$subject,
		$html,
		bsc_get_email_headers(
			array(
				'X-BSC-Email-Preview: 1',
			)
		)
	);

	$debug['wp_mail_result']  = $sent ? 'true' : 'false';
	$debug['phpmailer_debug'] = $captured_phpmailer_debug;

	if ( $phpmailer_error instanceof WP_Error ) {
		$debug['errors'][] = 'PHPMailer error: ' . $phpmailer_error->get_error_message();
		$debug['errors'][] = 'PHPMailer data: ' . wp_json_encode( $phpmailer_error->get_error_data() );
	}

	if ( ! $sent ) {
		$debug['steps'][] = 'FAIL: wp_mail() devolvio false';
		$debug['errors'][] = 'wp_mail() returned false — posible fallo SMTP, credenciales, puerto, o firewall';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_send_failed', 'WordPress no pudo enviar el email de prueba. Revisa los logs de debug abajo.' );
	}

	$debug['steps'][] = 'OK: wp_mail() devolvio true';
	bsc_store_preview_debug( $debug );

	return array(
		'label'   => $label,
		'subject' => $subject,
		'to'      => $target_email,
	);
}

function bsc_store_preview_debug( array $debug ): void {
	$key   = 'bsc_preview_email_debug';
	$stored = get_transient( $key );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	array_unshift( $stored, $debug );
	$stored = array_slice( $stored, 0, 10 );
	set_transient( $key, $stored, HOUR_IN_SECONDS );
}

function bsc_get_preview_debug_logs(): array {
	$stored = get_transient( 'bsc_preview_email_debug' );
	return is_array( $stored ) ? $stored : array();
}

function bsc_capture_phpmailer_debug(): string {
	global $phpmailer;
	if ( ! $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer && ! $phpmailer instanceof \PHPMailer ) {
		return 'PHPMailer global no disponible antes del envio';
	}

	ob_start();
	if ( method_exists( $phpmailer, 'getSMTPInstance' ) ) {
		$smtp = $phpmailer->getSMTPInstance();
		if ( $smtp && method_exists( $smtp, 'getDebugOutput' ) ) {
			echo esc_html( (string) $smtp->getDebugOutput() );
		}
	}
	$output = (string) ob_get_clean();

	if ( $output === '' ) {
		return 'Sin debug SMTP capturado. Verifica que WP_DEBUG este activo.';
	}

	return $output;
}

function bsc_sanitize_smtp_secure_value( string $secure ): string {
	$secure = strtolower( sanitize_key( $secure ) );
	return in_array( $secure, array( 'ssl', 'tls', '' ), true ) ? $secure : 'ssl';
}

function bsc_save_email_transport_settings_from_post(): void {
	update_option( 'bsc_email_from_name', sanitize_text_field( (string) wp_unslash( $_POST['bsc_email_from_name'] ?? 'Bubble Skin Care' ) ) );
	update_option( 'bsc_email_from_address', sanitize_email( (string) wp_unslash( $_POST['bsc_email_from_address'] ?? '' ) ) );

	update_option( 'bsc_smtp_enabled', isset( $_POST['bsc_smtp_enabled'] ) ? 1 : 0, false );
	update_option( 'bsc_smtp_host', sanitize_text_field( (string) wp_unslash( $_POST['bsc_smtp_host'] ?? '' ) ), false );
	update_option( 'bsc_smtp_port', max( 1, absint( wp_unslash( $_POST['bsc_smtp_port'] ?? 465 ) ) ), false );
	update_option( 'bsc_smtp_secure', bsc_sanitize_smtp_secure_value( (string) wp_unslash( $_POST['bsc_smtp_secure'] ?? 'ssl' ) ), false );
	update_option( 'bsc_smtp_auth', isset( $_POST['bsc_smtp_auth'] ) ? 1 : 0, false );
	update_option( 'bsc_smtp_username', sanitize_text_field( (string) wp_unslash( $_POST['bsc_smtp_username'] ?? '' ) ), false );

	if ( isset( $_POST['bsc_smtp_clear_password'] ) ) {
		delete_option( 'bsc_smtp_password' );
		return;
	}

	$password = sanitize_text_field( (string) wp_unslash( $_POST['bsc_smtp_password'] ?? '' ) );
	if ( $password !== '' ) {
		update_option( 'bsc_smtp_password', $password, false );
	}
}

function bsc_render_followup_emails_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
	}

	$defaults        = bsc_get_followup_email_defaults();
	$manifest        = bsc_get_email_template_manifest();
	$notice          = '';
	$notice_class    = 'notice-success';
	$run_now_summary = array();
	$current_user    = wp_get_current_user();
	$target_email    = sanitize_email( (string) get_user_meta( get_current_user_id(), 'bsc_preview_target_email', true ) );

	if ( $target_email === '' && $current_user instanceof WP_User ) {
		$target_email = sanitize_email( (string) $current_user->user_email );
	}

	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

	if ( 'POST' === $request_method && isset( $_POST['bsc_followup_emails_nonce'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_followup_emails_nonce'] ) ), 'bsc_followup_emails_action' ) ) {
			wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
		}

		if ( isset( $_POST['bsc_save_followup_emails'] ) ) {
			bsc_save_email_transport_settings_from_post();

			update_option( 'bsc_followup_emails_enabled', isset( $_POST['bsc_followup_emails_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_welcome_email_enabled', isset( $_POST['bsc_welcome_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_password_reset_email_enabled', isset( $_POST['bsc_password_reset_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_birthday_email_enabled', isset( $_POST['bsc_birthday_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_inactive_email_enabled', isset( $_POST['bsc_inactive_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_inactive_email_days', max( 1, absint( wp_unslash( $_POST['bsc_inactive_email_days'] ?? $defaults['bsc_inactive_email_days'] ) ) ) );
			update_option( 'bsc_repurchase_email_enabled', isset( $_POST['bsc_repurchase_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_default_repurchase_days', max( 1, absint( wp_unslash( $_POST['bsc_default_repurchase_days'] ?? $defaults['bsc_default_repurchase_days'] ) ) ) );

			$notice = 'Configuración guardada.';
		}

		if ( isset( $_POST['bsc_run_followup_now'] ) ) {
			$run_now_summary = bsc_run_followup_email_jobs();
			$notice          = 'Seguimiento ejecutado manualmente.';
		}

		if ( isset( $_POST['bsc_send_preview_email'] ) ) {
			$target_email = sanitize_email( (string) wp_unslash( $_POST['bsc_preview_target_email'] ?? '' ) );
			$slug         = sanitize_key( (string) wp_unslash( $_POST['bsc_preview_template'] ?? '' ) );
			$result       = bsc_send_preview_email_to_target( $slug, $target_email );

			if ( is_wp_error( $result ) ) {
				$notice       = $result->get_error_message();
				$notice_class = 'notice-error';
			} else {
				update_user_meta( get_current_user_id(), 'bsc_preview_target_email', $target_email );
				$notice = sprintf(
					'Preview "%s" enviado a %s.',
					(string) $result['label'],
					(string) $result['to']
				);
			}
		}

		if ( isset( $_POST['bsc_clear_preview_debug'] ) ) {
			delete_transient( 'bsc_preview_email_debug' );
			$notice = 'Logs de debug eliminados.';
		}
	}

	$last_run       = get_option( 'bsc_followup_email_last_run_summary', array() );
	$email_log_rows = function_exists( 'bsc_get_recent_order_email_log_rows' )
		? bsc_get_recent_order_email_log_rows( 20 )
		: array();
	$smtp_settings       = bsc_get_smtp_settings();
	$smtp_password_saved = bsc_smtp_password_is_configured();
	?>
	<div class="wrap">
		<h1>Emails BSC</h1>

		<?php if ( $notice !== '' ) : ?>
			<div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<?php $debug_logs = bsc_get_preview_debug_logs(); ?>
		<?php if ( ! empty( $debug_logs ) ) : ?>
			<details class="bsc-email-debug" style="margin:12px 0;border:1px solid #ccd0d4;border-radius:4px;background:#f6f7f7;">
				<summary style="padding:10px 14px;cursor:pointer;font-weight:700;font-size:14px;color:#1d2327;">
					<?php echo count( $debug_logs ); ?> envío(s) registrado(s) en debug — clic para expandir
				</summary>
				<div style="padding:0 14px 14px;max-height:600px;overflow:auto;">
					<?php foreach ( $debug_logs as $index => $log ) : ?>
						<div style="margin-bottom:14px;padding:12px;border:1px solid #dcdcde;border-radius:4px;background:#fff;">
							<strong>#<?php echo (int) ( $index + 1 ); ?> — <?php echo esc_html( $log['time'] ?? '?' ); ?> — <?php echo esc_html( $log['slug'] ?? '?' ); ?> → <?php echo esc_html( $log['to'] ?? '?' ); ?></strong>
							<div style="margin-top:6px;">
								<strong>wp_mail result:</strong> <?php echo esc_html( $log['wp_mail_result'] ?? '?' ); ?>
							</div>
							<div style="margin-top:4px;">
								<strong>SMTP:</strong>
								<?php echo esc_html( ( $log['smtp']['enabled'] ?? false ) ? 'ACTIVO' : 'DESACTIVADO' ); ?>
								<?php if ( ! empty( $log['smtp']['host'] ) ) : ?>
									— <?php echo esc_html( $log['smtp']['host'] ); ?>:<?php echo esc_html( (string) ( $log['smtp']['port'] ?? '' ) ); ?>
									(<?php echo esc_html( $log['smtp']['secure'] ?? '?' ); ?>,
									auth: <?php echo empty( $log['smtp']['auth'] ) ? 'no' : 'si'; ?>,
									user: <?php echo esc_html( $log['smtp']['username'] ?? '?' ); ?>)
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $log['steps'] ) ) : ?>
								<div style="margin-top:6px;">
									<strong>Pasos:</strong>
									<ul style="margin:4px 0 0 16px;list-style:disc;">
										<?php foreach ( $log['steps'] as $step ) : ?>
											<li style="font-family:monospace;font-size:12px;<?php echo 0 === strpos( (string) $step, 'FAIL' ) ? 'color:#b32d2e;' : 'color:#007017;'; ?>">
												<?php echo esc_html( $step ); ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $log['errors'] ) ) : ?>
								<div style="margin-top:6px;padding:8px;background:#fcf0f1;border-left:4px solid #b32d2e;">
									<strong style="color:#b32d2e;">Errores:</strong>
									<ul style="margin:4px 0 0 16px;list-style:disc;">
										<?php foreach ( $log['errors'] as $error ) : ?>
											<li style="font-family:monospace;font-size:12px;color:#b32d2e;"><?php echo esc_html( is_array( $error ) ? wp_json_encode( $error ) : (string) $error ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $log['phpmailer_debug'] ) && is_string( $log['phpmailer_debug'] ) ) : ?>
								<details style="margin-top:6px;">
									<summary style="cursor:pointer;font-weight:600;font-size:12px;">PHPMailer/SMTP debug output</summary>
									<pre style="margin-top:4px;padding:8px;background:#1d2327;color:#a7aaad;font-size:11px;line-height:1.4;overflow:auto;max-height:300px;border-radius:3px;"><?php echo esc_html( $log['phpmailer_debug'] ); ?></pre>
								</details>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<form method="post" style="margin-top:8px;">
						<?php wp_nonce_field( 'bsc_followup_emails_action', 'bsc_followup_emails_nonce' ); ?>
						<button type="submit" name="bsc_clear_preview_debug" class="button button-small" style="color:#b32d2e;">Limpiar logs de debug</button>
					</form>
				</div>
			</details>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'bsc_followup_emails_action', 'bsc_followup_emails_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th colspan="2"><h2 class="bsc-admin-followup__section-title">Remitente y SMTP</h2></th>
				</tr>
				<tr>
					<th><label for="bsc_email_from_name">Nombre del remitente</label></th>
					<td>
						<input
							type="text"
							id="bsc_email_from_name"
							name="bsc_email_from_name"
							value="<?php echo esc_attr( bsc_get_email_from_name() ); ?>"
							class="regular-text"
						>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_email_from_address">Email del remitente</label></th>
					<td>
						<input
							type="email"
							id="bsc_email_from_address"
							name="bsc_email_from_address"
							value="<?php echo esc_attr( bsc_get_email_from_address() ); ?>"
							class="regular-text"
							placeholder="info@bubblesskincare.com"
						>
						<p class="description">Debe coincidir con una cuenta autorizada por el proveedor SMTP.</p>
					</td>
				</tr>
				<tr>
					<th>SMTP</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_smtp_enabled" value="1" <?php checked( (bool) $smtp_settings['enabled'], true ); ?>>
							Enviar correos con SMTP configurado por BSC
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_smtp_host">Servidor SMTP</label></th>
					<td>
						<input
							type="text"
							id="bsc_smtp_host"
							name="bsc_smtp_host"
							value="<?php echo esc_attr( (string) $smtp_settings['host'] ); ?>"
							class="regular-text"
							placeholder="smtppro.zoho.com"
						>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_smtp_port">Puerto y seguridad</label></th>
					<td>
						<input
							type="number"
							id="bsc_smtp_port"
							name="bsc_smtp_port"
							min="1"
							step="1"
							value="<?php echo esc_attr( (string) $smtp_settings['port'] ); ?>"
							class="small-text"
						>
						<select name="bsc_smtp_secure" aria-label="Seguridad SMTP">
							<option value="ssl" <?php selected( (string) $smtp_settings['secure'], 'ssl' ); ?>>SSL</option>
							<option value="tls" <?php selected( (string) $smtp_settings['secure'], 'tls' ); ?>>TLS</option>
							<option value="" <?php selected( (string) $smtp_settings['secure'], '' ); ?>>Sin cifrado</option>
						</select>
						<p class="description">Zoho: 465 con SSL o 587 con TLS.</p>
					</td>
				</tr>
				<tr>
					<th>Autenticacion</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_smtp_auth" value="1" <?php checked( (bool) $smtp_settings['auth'], true ); ?>>
							Requerir autenticacion SMTP
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_smtp_username">Usuario SMTP</label></th>
					<td>
						<input
							type="text"
							id="bsc_smtp_username"
							name="bsc_smtp_username"
							value="<?php echo esc_attr( (string) $smtp_settings['username'] ); ?>"
							class="regular-text"
							autocomplete="username"
							placeholder="info@bubblesskincare.com"
						>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_smtp_password">Password SMTP</label></th>
					<td>
						<input
							type="password"
							id="bsc_smtp_password"
							name="bsc_smtp_password"
							value=""
							class="regular-text"
							autocomplete="new-password"
							placeholder="<?php echo esc_attr( $smtp_password_saved ? 'Clave guardada' : 'Password SMTP' ); ?>"
						>
						<p class="description">Dejalo vacio para conservar la clave guardada.</p>
						<?php if ( $smtp_password_saved ) : ?>
							<label class="bsc-admin-followup__inline-setting">
								<input type="checkbox" name="bsc_smtp_clear_password" value="1">
								Borrar clave guardada al guardar
							</label>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th colspan="2"><h2 class="bsc-admin-followup__section-title">Módulo</h2></th>
				</tr>
				<tr>
					<th>Activación general</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_followup_emails_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_followup_emails_enabled' ), 1 ); ?>>
							Activar correos de seguimiento y branded emails BSC
						</label>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2 class="bsc-admin-followup__section-title bsc-admin-followup__section-title--spaced">Correos inmediatos</h2></th>
				</tr>
				<tr>
					<th>Usuario nuevo</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_welcome_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_welcome_email_enabled' ), 1 ); ?>>
							Enviar bienvenida al crear cuenta customer
						</label>
					</td>
				</tr>
				<tr>
					<th>Recuperar contraseña</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_password_reset_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_password_reset_email_enabled' ), 1 ); ?>>
							Usar template branded BSC para reset
						</label>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2 class="bsc-admin-followup__section-title bsc-admin-followup__section-title--spaced">Correos de seguimiento</h2></th>
				</tr>
				<tr>
					<th>Cumpleaños</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_birthday_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_birthday_email_enabled' ), 1 ); ?>>
							Enviar una vez por año según `bsc_birthday`
						</label>
					</td>
				</tr>
				<tr>
					<th>Hace mucho no compras</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_inactive_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_inactive_email_enabled' ), 1 ); ?>>
							Activar recordatorio por inactividad
						</label>
						<p class="bsc-admin-followup__inline-setting">
							<input type="number" min="1" step="1" name="bsc_inactive_email_days" value="<?php echo esc_attr( (string) bsc_get_followup_email_setting( 'bsc_inactive_email_days' ) ); ?>" class="small-text">
							días desde la última compra
						</p>
					</td>
				</tr>
				<tr>
					<th>Se te acabó el producto</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_repurchase_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_repurchase_email_enabled' ), 1 ); ?>>
							Activar recordatorio de recompra
						</label>
						<p class="bsc-admin-followup__inline-setting">
							<input type="number" min="1" step="1" name="bsc_default_repurchase_days" value="<?php echo esc_attr( (string) bsc_get_followup_email_setting( 'bsc_default_repurchase_days' ) ); ?>" class="small-text">
							días por defecto para productos sin override
						</p>
						<p class="description">Cada producto puede sobreescribir este timeout desde el editor BSC.</p>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Guardar configuración', 'primary', 'bsc_save_followup_emails' ); ?>
			<?php submit_button( 'Ejecutar seguimiento ahora', 'secondary', 'bsc_run_followup_now', false ); ?>
		</form>

		<?php if ( ! empty( $run_now_summary ) ) : ?>
			<div class="notice notice-info bsc-admin-followup__notice">
				<p>
					Ejecutado: <?php echo esc_html( $run_now_summary['ran_at'] ?? '' ); ?> |
					Cumpleaños: <?php echo esc_html( (string) ( $run_now_summary['birthday'] ?? 0 ) ); ?> |
					Inactividad: <?php echo esc_html( (string) ( $run_now_summary['inactive'] ?? 0 ) ); ?> |
					Recompra: <?php echo esc_html( (string) ( $run_now_summary['repurchase'] ?? 0 ) ); ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="bsc-admin-followup__section">
			<h2>Última ejecución registrada</h2>
			<?php if ( ! empty( $last_run ) ) : ?>
				<p>
					Fecha: <strong><?php echo esc_html( (string) ( $last_run['ran_at'] ?? '' ) ); ?></strong><br>
					Cumpleaños: <?php echo esc_html( (string) ( $last_run['birthday'] ?? 0 ) ); ?><br>
					Inactividad: <?php echo esc_html( (string) ( $last_run['inactive'] ?? 0 ) ); ?><br>
					Recompra: <?php echo esc_html( (string) ( $last_run['repurchase'] ?? 0 ) ); ?>
				</p>
			<?php else : ?>
				<p>No hay ejecuciones registradas todavía.</p>
			<?php endif; ?>
		</div>

		<div class="bsc-admin-followup__section">
			<h2>Log operativo de correos de pedidos</h2>
			<?php if ( ! empty( $email_log_rows ) ) : ?>
				<table class="widefat striped bsc-admin-followup__templates-table">
					<thead>
						<tr>
							<th>Fecha</th>
							<th>Pedido</th>
							<th>Tipo</th>
							<th>Estado</th>
							<th>Destino</th>
							<th>Asunto</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $email_log_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['sent_at'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-orders&s=' . rawurlencode( (string) $row['order_number'] ) ) ); ?>">
										#<?php echo esc_html( $row['order_number'] ); ?>
									</a>
								</td>
								<td><?php echo esc_html( $row['type'] ); ?></td>
								<td><?php echo esc_html( $row['status'] ); ?></td>
								<td><?php echo esc_html( $row['to'] ); ?></td>
								<td><?php echo esc_html( $row['subject'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No hay correos de pedidos registrados todavia.</p>
			<?php endif; ?>
		</div>

		<div class="bsc-admin-followup__section">
			<h2>Templates editables</h2>
			<table class="widefat striped bsc-admin-followup__templates-table">
				<thead>
					<tr>
						<th>Tipo</th>
						<th>Trigger</th>
						<th>Archivo</th>
						<th>Preview</th>
						<th>Enviar prueba</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $manifest as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['label'] ); ?></td>
							<td><?php echo esc_html( $row['trigger'] ); ?></td>
							<td><code><?php echo esc_html( get_template_directory() . '/emails/' . $row['file'] ); ?></code></td>
							<td>
								<?php if ( ! empty( $row['slug'] ) && function_exists( 'bsc_get_email_preview_url' ) ) : ?>
									<a class="button button-secondary" href="<?php echo esc_url( bsc_get_email_preview_url( (string) $row['slug'] ) ); ?>" target="_blank" rel="noopener noreferrer">Preview</a>
								<?php else : ?>
									<span class="description">N/D</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $row['slug'] ) ) : ?>
									<form method="post" class="bsc-admin-followup__preview-send-form">
										<?php wp_nonce_field( 'bsc_followup_emails_action', 'bsc_followup_emails_nonce' ); ?>
										<input type="hidden" name="bsc_preview_template" value="<?php echo esc_attr( (string) $row['slug'] ); ?>">
										<label class="screen-reader-text" for="bsc-preview-target-<?php echo esc_attr( (string) $row['slug'] ); ?>">
											Email destino para <?php echo esc_html( $row['label'] ); ?>
										</label>
										<input
											type="email"
											id="bsc-preview-target-<?php echo esc_attr( (string) $row['slug'] ); ?>"
											name="bsc_preview_target_email"
											value="<?php echo esc_attr( $target_email ); ?>"
											placeholder="email destino"
											required
										>
										<?php submit_button( 'Enviar prueba', 'secondary small', 'bsc_send_preview_email', false ); ?>
									</form>
								<?php else : ?>
									<span class="description">N/D</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
