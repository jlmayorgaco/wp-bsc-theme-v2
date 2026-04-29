<?php
/**
 * BSC-082: Admin page for customer email flows and follow-up settings.
 */
defined( 'ABSPATH' ) || exit;

function bsc_render_followup_emails_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
	}

	$defaults = bsc_get_followup_email_defaults();
	$notice   = '';
	$run_now_summary = [];

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['bsc_followup_emails_nonce'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_followup_emails_nonce'] ) ), 'bsc_followup_emails_action' ) ) {
			wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
		}

		if ( isset( $_POST['bsc_save_followup_emails'] ) ) {
			update_option( 'bsc_followup_emails_enabled', isset( $_POST['bsc_followup_emails_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_welcome_email_enabled', isset( $_POST['bsc_welcome_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_password_reset_email_enabled', isset( $_POST['bsc_password_reset_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_birthday_email_enabled', isset( $_POST['bsc_birthday_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_inactive_email_enabled', isset( $_POST['bsc_inactive_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_inactive_email_days', max( 1, (int) ( $_POST['bsc_inactive_email_days'] ?? $defaults['bsc_inactive_email_days'] ) ) );
			update_option( 'bsc_repurchase_email_enabled', isset( $_POST['bsc_repurchase_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_default_repurchase_days', max( 1, (int) ( $_POST['bsc_default_repurchase_days'] ?? $defaults['bsc_default_repurchase_days'] ) ) );

			$notice = 'Configuración guardada.';
		}

		if ( isset( $_POST['bsc_run_followup_now'] ) ) {
			$run_now_summary = bsc_run_followup_email_jobs();
			$notice = 'Seguimiento ejecutado manualmente.';
		}
	}

	$last_run = get_option( 'bsc_followup_email_last_run_summary', [] );
	$manifest = bsc_get_email_template_manifest();
	?>
	<div class="wrap">
		<h1>Emails BSC</h1>

		<?php if ( $notice !== '' ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'bsc_followup_emails_action', 'bsc_followup_emails_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th colspan="2"><h2 style="margin:0">Módulo</h2></th>
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
					<th colspan="2"><h2 style="margin:16px 0 0">Correos inmediatos</h2></th>
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
					<th colspan="2"><h2 style="margin:16px 0 0">Correos de seguimiento</h2></th>
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
						<p style="margin:8px 0 0">
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
						<p style="margin:8px 0 0">
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
			<div class="notice notice-info" style="margin-top:16px">
				<p>
					Ejecutado: <?php echo esc_html( $run_now_summary['ran_at'] ?? '' ); ?> |
					Cumpleaños: <?php echo esc_html( (string) ( $run_now_summary['birthday'] ?? 0 ) ); ?> |
					Inactividad: <?php echo esc_html( (string) ( $run_now_summary['inactive'] ?? 0 ) ); ?> |
					Recompra: <?php echo esc_html( (string) ( $run_now_summary['repurchase'] ?? 0 ) ); ?>
				</p>
			</div>
		<?php endif; ?>

		<div style="margin-top:24px">
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

		<div style="margin-top:24px">
			<h2>Templates editables</h2>
			<table class="widefat striped" style="max-width:980px">
				<thead>
					<tr>
						<th>Tipo</th>
						<th>Trigger</th>
						<th>Archivo</th>
						<th>Preview</th>
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
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
