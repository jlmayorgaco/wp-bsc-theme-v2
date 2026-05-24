<?php
/**
 * Admin workflow for Newsletter leads stored by the public form.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_newsletter_admin_assets' );
add_action( 'admin_init', 'bsc_newsletter_handle_admin_actions' );

function bsc_enqueue_newsletter_admin_assets(): void {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-newsletter' !== $page ) {
		return;
	}

	if ( function_exists( 'bsc_enqueue_admin_ui_assets' ) ) {
		bsc_enqueue_admin_ui_assets();
	}

	$css_path = get_template_directory() . '/admin/bsc-newsletter.css';

	wp_enqueue_style(
		'bsc-admin-newsletter',
		get_template_directory_uri() . '/admin/bsc-newsletter.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);
}

function bsc_newsletter_user_can_manage(): bool {
	return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
}

function bsc_newsletter_handle_admin_actions(): void {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-newsletter' !== $page || ! bsc_newsletter_user_can_manage() ) {
		return;
	}

	if ( isset( $_POST['bsc_newsletter_action'] ) ) {
		if (
			! isset( $_POST['bsc_newsletter_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_newsletter_nonce'] ) ), 'bsc_newsletter_action' )
		) {
			wp_die( esc_html__( 'Solicitud no valida.', 'bsc-2-0' ) );
		}

		$action = sanitize_key( wp_unslash( $_POST['bsc_newsletter_action'] ) );

		if ( 'update_lead' === $action ) {
			$index       = isset( $_POST['subscriber_id'] ) ? absint( wp_unslash( $_POST['subscriber_id'] ) ) : -1;
			$status      = isset( $_POST['subscriber_status'] ) ? sanitize_key( wp_unslash( $_POST['subscriber_status'] ) ) : 'new';
			$notes       = isset( $_POST['subscriber_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['subscriber_notes'] ) ) : '';
			$subscribers = bsc_newsletter_get_subscribers();

			if ( isset( $subscribers[ $index ] ) ) {
				$subscribers[ $index ]['status']     = bsc_newsletter_normalize_status( $status );
				$subscribers[ $index ]['notes']      = $notes;
				$subscribers[ $index ]['updated_at'] = current_time( 'mysql' );
				$subscribers[ $index ]['updated_by'] = get_current_user_id();
				bsc_newsletter_save_subscribers( $subscribers );
			}

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'       => 'bsc-newsletter',
						'bsc_notice' => 'updated',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		if ( 'delete_lead' === $action ) {
			$index       = isset( $_POST['subscriber_id'] ) ? absint( wp_unslash( $_POST['subscriber_id'] ) ) : -1;
			$subscribers = function_exists( 'bsc_privacy_delete_indexed_row' )
				? bsc_privacy_delete_indexed_row( bsc_newsletter_get_subscribers(), $index )
				: array_values( array_diff_key( bsc_newsletter_get_subscribers(), array( $index => true ) ) );

			bsc_newsletter_save_subscribers( $subscribers );

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'       => 'bsc-newsletter',
						'bsc_notice' => 'deleted',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}

	if ( isset( $_GET['bsc_export'], $_GET['_wpnonce'] ) && 'newsletter' === sanitize_key( wp_unslash( $_GET['bsc_export'] ) ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bsc_newsletter_export' ) ) {
			wp_die( esc_html__( 'Solicitud no valida.', 'bsc-2-0' ) );
		}

		bsc_newsletter_export_csv();
	}
}

function bsc_newsletter_export_csv(): void {
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="newsletter-leads-' . gmdate( 'Y-m-d' ) . '.csv"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$out = fopen( 'php://output', 'w' );

	if ( false === $out ) {
		exit;
	}

	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'Fecha', 'Estado', 'Email', 'Origen', 'Notas', 'Consentimiento', 'IP hash', 'Retencion dias' ) );

	$statuses = bsc_newsletter_status_options();

	foreach ( bsc_newsletter_get_subscribers() as $subscriber ) {
		$status = bsc_newsletter_normalize_status( (string) ( $subscriber['status'] ?? 'new' ) );

		fputcsv(
			$out,
			array(
				$subscriber['date'] ?? '',
				$statuses[ $status ],
				$subscriber['email'] ?? '',
				$subscriber['source'] ?? '',
				$subscriber['notes'] ?? '',
				! empty( $subscriber['consent'] ) ? 'si' : 'no',
				$subscriber['ip_hash'] ?? '',
				$subscriber['retention_days'] ?? '',
			)
		);
	}

	fclose( $out );
	exit;
}

function bsc_render_newsletter_page(): void {
	if ( ! bsc_newsletter_user_can_manage() ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
	}

	$subscribers = bsc_newsletter_get_subscribers();
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin filters.
	$status_filter = isset( $_GET['subscriber_status'] ) ? sanitize_key( wp_unslash( $_GET['subscriber_status'] ) ) : '';
	$query_filter  = isset( $_GET['subscriber_query'] ) ? sanitize_text_field( wp_unslash( $_GET['subscriber_query'] ) ) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
	$statuses       = bsc_newsletter_status_options();
	$status_filter  = array_key_exists( $status_filter, $statuses ) ? $status_filter : '';
	$notice_message = '';
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag after redirect.
	if ( isset( $_GET['bsc_notice'] ) ) {
		$notice_message = 'deleted' === sanitize_key( wp_unslash( $_GET['bsc_notice'] ) ) ? 'Lead eliminado.' : 'Lead actualizado.';
	}
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
	$export_url = wp_nonce_url(
		add_query_arg(
			array(
				'page'       => 'bsc-newsletter',
				'bsc_export' => 'newsletter',
			),
			admin_url( 'admin.php' )
		),
		'bsc_newsletter_export'
	);
	?>
	<div class="wrap bsc-admin-newsletter">
		<div class="bsc-admin-newsletter__header">
			<h1 class="wp-heading-inline">Newsletter</h1>
			<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action">Exportar CSV</a>
		</div>
		<hr class="wp-header-end">

		<?php if ( '' !== $notice_message ) : ?>
			<div class="bsc-admin-note bsc-admin-note--success">
				<?php echo esc_html( $notice_message ); ?>
			</div>
		<?php endif; ?>

		<form method="get" class="bsc-admin-newsletter__filters">
			<input type="hidden" name="page" value="bsc-newsletter">
			<label for="subscriber_status">Estado</label>
			<select id="subscriber_status" name="subscriber_status">
				<option value="">Todos</option>
				<?php foreach ( $statuses as $status_key => $status_label ) : ?>
					<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status_filter, $status_key ); ?>>
						<?php echo esc_html( $status_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<label for="subscriber_query">Email</label>
			<input
				type="search"
				id="subscriber_query"
				name="subscriber_query"
				value="<?php echo esc_attr( $query_filter ); ?>"
				placeholder="buscar@correo.com"
			>
			<button type="submit" class="button">Filtrar</button>
		</form>

		<table class="wp-list-table widefat fixed striped bsc-admin-newsletter__table">
			<thead>
				<tr>
					<th>Fecha</th>
					<th>Estado</th>
					<th>Email</th>
					<th>Origen</th>
					<th>Notas</th>
					<th>Privacidad</th>
					<th>Acciones</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$visible_rows = 0;
			foreach ( $subscribers as $index => $subscriber ) :
				$status = bsc_newsletter_normalize_status( (string) ( $subscriber['status'] ?? 'new' ) );
				$email  = (string) ( $subscriber['email'] ?? '' );

				if ( $status_filter && $status !== $status_filter ) {
					continue;
				}

				if ( $query_filter && false === stripos( $email, $query_filter ) ) {
					continue;
				}

				++$visible_rows;
				?>
				<tr>
					<td><?php echo esc_html( $subscriber['date'] ?? '' ); ?></td>
					<td><?php echo esc_html( $statuses[ $status ] ); ?></td>
					<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
					<td><?php echo esc_html( $subscriber['source'] ?? 'newsletter-form' ); ?></td>
					<td><?php echo esc_html( wp_trim_words( (string) ( $subscriber['notes'] ?? '' ), 14 ) ); ?></td>
					<td>
						<?php
						$retention_days = absint( $subscriber['retention_days'] ?? 0 );
						echo esc_html( $retention_days > 0 ? 'Retencion: ' . $retention_days . ' dias' : 'Sin retencion definida' );
						?>
					</td>
					<td>
						<form method="post" class="bsc-admin-newsletter__status-form">
							<?php wp_nonce_field( 'bsc_newsletter_action', 'bsc_newsletter_nonce' ); ?>
							<input type="hidden" name="bsc_newsletter_action" value="update_lead">
							<input type="hidden" name="subscriber_id" value="<?php echo esc_attr( $index ); ?>">
							<select name="subscriber_status">
								<?php foreach ( $statuses as $status_key => $status_label ) : ?>
									<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status, $status_key ); ?>>
										<?php echo esc_html( $status_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<textarea name="subscriber_notes" rows="2" placeholder="Notas internas"><?php echo esc_textarea( $subscriber['notes'] ?? '' ); ?></textarea>
							<button type="submit" class="button button-small">Guardar</button>
						</form>
						<form method="post" class="bsc-admin-newsletter__delete-form">
							<?php wp_nonce_field( 'bsc_newsletter_action', 'bsc_newsletter_nonce' ); ?>
							<input type="hidden" name="bsc_newsletter_action" value="delete_lead">
							<input type="hidden" name="subscriber_id" value="<?php echo esc_attr( $index ); ?>">
							<button type="submit" class="button button-small" onclick="return confirm('Eliminar este lead y sus datos personales?');">Eliminar datos</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( 0 === $visible_rows ) : ?>
				<tr><td colspan="7"><?php echo esc_html( ( $status_filter || $query_filter ) ? 'No hay leads con este filtro.' : 'No hay suscriptores todavia.' ); ?></td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
