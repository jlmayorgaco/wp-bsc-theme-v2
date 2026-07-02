<?php
/**
 * Admin workflow for Contact messages stored by the public form.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_contact_admin_assets' );
add_action( 'admin_init', 'bsc_contact_handle_admin_actions' );

function bsc_enqueue_contact_admin_assets(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-contact' !== $page ) {
		return;
	}

	if ( function_exists( 'bsc_enqueue_admin_ui_assets' ) ) {
		bsc_enqueue_admin_ui_assets();
	}

	$css_path = get_template_directory() . '/admin/bsc-contact.css';

	wp_enqueue_style(
		'bsc-admin-contact',
		get_template_directory_uri() . '/admin/bsc-contact.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);
}

function bsc_contact_user_can_manage(): bool {
	return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
}

function bsc_contact_handle_admin_actions(): void {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-contact' !== $page || ! bsc_contact_user_can_manage() ) {
		return;
	}

	if ( isset( $_POST['bsc_contact_action'] ) ) {
		if (
			! isset( $_POST['bsc_contact_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_contact_nonce'] ) ), 'bsc_contact_action' )
		) {
			wp_die( esc_html__( 'Solicitud no valida.', 'bsc-2-0' ) );
		}

		$action = sanitize_key( wp_unslash( $_POST['bsc_contact_action'] ) );

		if ( 'update_message' === $action ) {
			$index    = isset( $_POST['message_id'] ) ? max( 0, (int) wp_unslash( $_POST['message_id'] ) ) : -1;
			$status   = isset( $_POST['message_status'] ) ? sanitize_key( wp_unslash( $_POST['message_status'] ) ) : 'new';
			$notes    = isset( $_POST['message_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message_notes'] ) ) : '';
			$messages = bsc_contact_get_messages();

			if ( isset( $messages[ $index ] ) ) {
				$messages[ $index ]['status']     = bsc_contact_normalize_status( $status );
				$messages[ $index ]['notes']      = $notes;
				$messages[ $index ]['updated_at'] = current_time( 'mysql' );
				$messages[ $index ]['updated_by'] = get_current_user_id();
				bsc_contact_save_messages( $messages );
			}

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'       => 'bsc-contact',
						'bsc_notice' => 'updated',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		if ( 'delete_message' === $action ) {
			$index    = isset( $_POST['message_id'] ) ? max( 0, (int) wp_unslash( $_POST['message_id'] ) ) : -1;
			$messages = function_exists( 'bsc_privacy_delete_indexed_row' )
				? bsc_privacy_delete_indexed_row( bsc_contact_get_messages(), $index )
				: array_values( array_diff_key( bsc_contact_get_messages(), array( $index => true ) ) );

			bsc_contact_save_messages( $messages );

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'       => 'bsc-contact',
						'bsc_notice' => 'deleted',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}

	if ( isset( $_GET['bsc_export'], $_GET['_wpnonce'] ) && 'contact' === sanitize_key( wp_unslash( $_GET['bsc_export'] ) ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bsc_contact_export' ) ) {
			wp_die( esc_html__( 'Solicitud no valida.', 'bsc-2-0' ) );
		}

		bsc_contact_export_csv();
	}
}

function bsc_contact_export_csv(): void {
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="contact-messages-' . gmdate( 'Y-m-d' ) . '.csv"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$out = fopen( 'php://output', 'w' );

	if ( false === $out ) {
		exit;
	}

	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'Fecha', 'Estado', 'Nombre', 'Email', 'Origen', 'Mensaje', 'Notas', 'Notificacion email', 'Consentimiento', 'IP hash', 'Retencion dias' ) );

	$statuses = bsc_contact_status_options();

	foreach ( bsc_contact_get_messages() as $message ) {
		$status = bsc_contact_normalize_status( (string) ( $message['status'] ?? 'new' ) );

		fputcsv(
			$out,
			array(
				$message['date'] ?? '',
				$statuses[ $status ],
				$message['name'] ?? '',
				$message['email'] ?? '',
				$message['source'] ?? '',
				$message['message'] ?? '',
				$message['notes'] ?? '',
				! empty( $message['mail_sent'] ) ? 'enviada' : 'fallo',
				! empty( $message['consent'] ) ? 'si' : 'no',
				$message['ip_hash'] ?? '',
				$message['retention_days'] ?? '',
			)
		);
	}

	fclose( $out );
	exit;
}

function bsc_render_contact_page(): void {
	if ( ! bsc_contact_user_can_manage() ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
	}

	$messages = bsc_contact_get_messages();
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin filters.
	$status_filter = isset( $_GET['message_status'] ) ? sanitize_key( wp_unslash( $_GET['message_status'] ) ) : '';
	$query_filter  = isset( $_GET['message_query'] ) ? sanitize_text_field( wp_unslash( $_GET['message_query'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$statuses       = bsc_contact_status_options();
	$status_filter  = array_key_exists( $status_filter, $statuses ) ? $status_filter : '';
	$notice_message = '';

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag after redirect.
	if ( isset( $_GET['bsc_notice'] ) ) {
		$notice_message = 'deleted' === sanitize_key( wp_unslash( $_GET['bsc_notice'] ) ) ? 'Mensaje eliminado.' : 'Mensaje actualizado.';
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$export_url = wp_nonce_url(
		add_query_arg(
			array(
				'page'       => 'bsc-contact',
				'bsc_export' => 'contact',
			),
			admin_url( 'admin.php' )
		),
		'bsc_contact_export'
	);
	?>
	<div class="wrap bsc-admin-contact">
		<div class="bsc-admin-page-header bsc-admin-page-header--compact">
			<div>
				<span class="bsc-admin-page-header__eyebrow">CRM</span>
				<h1>Contacto</h1>
				<p class="bsc-admin-page-header__description">Gestiona mensajes entrantes, estado de respuesta, privacidad y notas internas.</p>
			</div>
			<div class="bsc-admin-page-header__actions">
				<a href="<?php echo esc_url( $export_url ); ?>" class="button button-primary">Exportar CSV</a>
			</div>
		</div>

		<?php if ( '' !== $notice_message ) : ?>
			<div class="bsc-admin-note bsc-admin-note--success">
				<?php echo esc_html( $notice_message ); ?>
			</div>
		<?php endif; ?>

		<form method="get" class="bsc-admin-contact__filters bsc-admin-filter-panel">
			<input type="hidden" name="page" value="bsc-contact">
			<div class="bsc-admin-field">
				<label for="message_status">Estado</label>
				<select id="message_status" name="message_status">
					<option value="">Todos</option>
					<?php foreach ( $statuses as $status_key => $status_label ) : ?>
						<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status_filter, $status_key ); ?>>
							<?php echo esc_html( $status_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="bsc-admin-field bsc-admin-field--grow">
				<label for="message_query">Buscar</label>
				<input
					type="search"
					id="message_query"
					name="message_query"
					value="<?php echo esc_attr( $query_filter ); ?>"
					placeholder="nombre, email o mensaje"
				>
			</div>
			<div class="bsc-admin-filter-panel__actions">
				<button type="submit" class="button button-primary">Filtrar</button>
			</div>
		</form>

		<div class="bsc-admin-table-wrap bsc-admin-table-wrap--flush bsc-admin-table-wrap--spacious">
		<table class="wp-list-table widefat fixed striped bsc-admin-contact__table">
			<thead>
				<tr>
					<th>Fecha</th>
					<th>Estado</th>
					<th>Nombre</th>
					<th>Email</th>
					<th>Origen</th>
					<th>Mensaje</th>
					<th>Notificacion</th>
					<th>Notas</th>
					<th>Privacidad</th>
					<th>Acciones</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$visible_rows = 0;
			foreach ( $messages as $index => $message ) :
				$status   = bsc_contact_normalize_status( (string) ( $message['status'] ?? 'new' ) );
				$email    = (string) ( $message['email'] ?? '' );
				$name     = (string) ( $message['name'] ?? '' );
				$content  = (string) ( $message['message'] ?? '' );
				$haystack = $name . ' ' . $email . ' ' . $content;

				if ( $status_filter && $status !== $status_filter ) {
					continue;
				}

				if ( $query_filter && false === stripos( $haystack, $query_filter ) ) {
					continue;
				}

				++$visible_rows;
				?>
				<tr>
					<td><?php echo esc_html( $message['date'] ?? '' ); ?></td>
					<td><?php echo esc_html( $statuses[ $status ] ); ?></td>
					<td><?php echo esc_html( $name ); ?></td>
					<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
					<td><?php echo esc_html( $message['source'] ?? 'contact-form' ); ?></td>
					<td class="bsc-admin-contact__message"><?php echo esc_html( wp_trim_words( $content, 20 ) ); ?></td>
					<td>
						<span class="bsc-admin-badge <?php echo esc_attr( ! empty( $message['mail_sent'] ) ? 'bsc-admin-badge--success' : 'bsc-admin-badge--warning' ); ?>">
							<?php echo ! empty( $message['mail_sent'] ) ? esc_html__( 'Enviada', 'bsc-2-0' ) : esc_html__( 'Fallo', 'bsc-2-0' ); ?>
						</span>
					</td>
					<td><?php echo esc_html( wp_trim_words( (string) ( $message['notes'] ?? '' ), 14 ) ); ?></td>
					<td>
						<?php
						$retention_days = absint( $message['retention_days'] ?? 0 );
						echo esc_html( $retention_days > 0 ? 'Retencion: ' . $retention_days . ' dias' : 'Sin retencion definida' );
						?>
					</td>
					<td>
						<form method="post" class="bsc-admin-contact__status-form">
							<?php wp_nonce_field( 'bsc_contact_action', 'bsc_contact_nonce' ); ?>
							<input type="hidden" name="bsc_contact_action" value="update_message">
							<input type="hidden" name="message_id" value="<?php echo esc_attr( $index ); ?>">
							<select name="message_status">
								<?php foreach ( $statuses as $status_key => $status_label ) : ?>
									<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status, $status_key ); ?>>
										<?php echo esc_html( $status_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<textarea name="message_notes" rows="2" placeholder="Notas internas"><?php echo esc_textarea( $message['notes'] ?? '' ); ?></textarea>
							<button type="submit" class="button button-small">Guardar</button>
						</form>
						<div class="bsc-admin-contact__row-actions">
							<a class="button button-small" href="mailto:<?php echo esc_attr( $email ); ?>">Responder</a>
							<form method="post" class="bsc-admin-contact__delete-form">
								<?php wp_nonce_field( 'bsc_contact_action', 'bsc_contact_nonce' ); ?>
								<input type="hidden" name="bsc_contact_action" value="delete_message">
								<input type="hidden" name="message_id" value="<?php echo esc_attr( $index ); ?>">
								<button type="submit" class="button button-small bsc-admin-button-danger" onclick="return confirm('Eliminar este mensaje y sus datos personales?');">Eliminar datos</button>
							</form>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( 0 === $visible_rows ) : ?>
				<tr><td colspan="10" class="bsc-admin-empty-state"><?php echo esc_html( ( $status_filter || $query_filter ) ? 'No hay mensajes con este filtro.' : 'No hay mensajes de contacto todavia.' ); ?></td></tr>
			<?php endif; ?>
			</tbody>
		</table>
		</div>
	</div>
	<?php
}
