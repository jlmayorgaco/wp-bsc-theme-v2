<?php
/**
 * BSC-031: Admin orders page render + AJAX handlers.
 * BSC-033: bsc_save_tracking -> auto status 'shipped' + shipping email.
 * BSC-034: CSV export + packing print view via bulk actions.
 */
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/admin/class-bsc-orders-table.php';
require_once get_template_directory() . '/admin/class-bsc-order-labels.php';

// Enqueue admin JS only on BSC orders page
add_action(
	'admin_enqueue_scripts',
	function ( string $hook ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'bsc-orders' !== $page ) {
			return;
		}

		$js_path  = get_template_directory() . '/js/bsc-admin-orders.js';
		$css_path = get_template_directory() . '/admin/bsc-admin-orders.css';

		bsc_enqueue_admin_ui_assets();

		wp_enqueue_style(
			'bsc-admin-orders',
			get_template_directory_uri() . '/admin/bsc-admin-orders.css',
			array( 'bsc-admin-ui' ),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
		);

		wp_enqueue_script(
			'bsc-admin-orders',
			get_template_directory_uri() . '/js/bsc-admin-orders.js',
			array( 'jquery' ),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
			true
		);
		wp_localize_script(
			'bsc-admin-orders',
			'bscOrders',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'bsc_admin_orders' ),
				'strings'  => array(
					'saved'               => 'Elemento guardado',
					'saving'              => 'Guardando...',
					'save'                => 'Guardar',
					'saveError'           => 'Error al actualizar estado',
					'trackingError'       => 'Error al guardar tracking',
					'trackingUrlInvalid'  => 'La URL de seguimiento debe empezar por https:// o http://.',
					'trackingUrlRequired' => 'Ingresa la URL completa de seguimiento antes de guardar la guia.',
					'connectionError'     => 'Error de conexion. Intenta de nuevo.',
					'selectFirst'         => 'Selecciona al menos un pedido primero.',
					'selectStatus'        => 'Selecciona el estado que quieres aplicar.',
					'bulkStatusConfirm'   => 'Vas a cambiar el estado de los pedidos seleccionados. ¿Continuar?',
				),
			)
		);
	}
);

// BSC-034: handle bulk export/packing on admin_init
// Form POSTs back to admin.php?page=bsc-orders (same page).
// admin_init fires after WooCommerce is ready but before any HTML output.
add_action( 'admin_init', 'bsc_handle_bulk_export' );
function bsc_handle_bulk_export(): void {
	// Only act on our form POST
	if ( ! isset( $_POST['bsc_bulk_action'], $_POST['bsc_export_nonce'] ) ) {
		return;
	}
	$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'bsc-orders' !== $current_page ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_export_nonce'] ) ), 'bsc_bulk_export' ) ) {
		wp_die( esc_html__( 'Nonce inválido.', 'bsc-2-0' ) );
	}
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_orders' ) ) {
		wp_die( esc_html__( 'Sin permisos.', 'bsc-2-0' ) );
	}

	$action    = sanitize_text_field( wp_unslash( $_POST['bsc_bulk_action'] ) );
	$order_ids = isset( $_POST['order_ids'] )
		? array_map( 'absint', (array) wp_unslash( $_POST['order_ids'] ) )
		: array();

	if ( empty( $order_ids ) ) {
		return; // JS already prevents this, but safety guard
	}

	if ( $action === 'export_csv' ) {
		bsc_export_orders_csv( $order_ids );
	} elseif ( $action === 'print_packing' ) {
		bsc_render_packing_view( $order_ids );
	} elseif ( $action === 'print_order_labels' ) {
		bsc_render_order_labels( $order_ids );
	} elseif ( $action === 'bulk_update_status' ) {
		$status_key = isset( $_POST['bsc_bulk_status'] )
			? sanitize_text_field( wp_unslash( $_POST['bsc_bulk_status'] ) )
			: '';

		if ( ! isset( BSC_Admin_Orders_Table::STATUS_OPTIONS[ $status_key ] ) ) {
			wp_die( esc_html__( 'Estado inválido.', 'bsc-2-0' ) );
		}

		$status_slug = bsc_normalize_order_status_slug( $status_key );
		$updated     = 0;

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			if ( bsc_orders_is_archive_status( $status_slug ) ) {
				bsc_archive_order_from_admin( $order, 'Archivado manualmente en lote desde BSC Admin.' );
				++$updated;
				continue;
			}

			bsc_unarchive_order_from_admin( $order );
			$order->update_status( $status_slug, 'Estado actualizado en lote desde BSC Admin.' );

			if ( 'shipped' === $status_slug ) {
				$email_error = bsc_maybe_send_shipping_email_for_order( $order );

				if ( $email_error ) {
					error_log( 'BSC: error al enviar email de envío — ' . $email_error );
				}
			}

			++$updated;
		}

		$redirect_url = wp_get_referer() ?: admin_url( 'admin.php?page=bsc-orders' );
		$redirect_url = remove_query_arg( array( 'bsc_bulk_updated' ), $redirect_url );
		$redirect_url = add_query_arg( 'bsc_bulk_updated', $updated, $redirect_url );

		wp_safe_redirect( $redirect_url );
		exit;
	}
}

// Status tabs config
function bsc_orders_status_tabs(): array {
	return array(
		''              => array(
			'label'    => 'Todos',
			'statuses' => array(),
		),
		'wc-processing' => array(
			'label'    => 'Recibido',
			'statuses' => array( 'pending', 'on-hold', 'processing', 'preparing' ),
		),
		'wc-shipped'    => array(
			'label'    => 'Enviado',
			'statuses' => array( 'shipped', 'completed' ),
		),
		'wc-cancelled'  => array(
			'label'    => 'Cancelado',
			'statuses' => array( 'cancelled', 'failed', 'refunded' ),
		),
		'bsc-archived' => array(
			'label'    => 'Archivado',
			'statuses' => array(),
			'archived' => true,
		),
	);
}

function bsc_normalize_order_status_slug( string $status ): string {
	return preg_replace( '/^wc-/', '', $status );
}

function bsc_orders_archive_status_key(): string {
	return 'bsc-archived';
}

function bsc_orders_is_archive_status( string $status ): bool {
	return bsc_orders_archive_status_key() === bsc_normalize_order_status_slug( $status );
}

function bsc_orders_tab_is_archived( array $tab_config ): bool {
	return ! empty( $tab_config['archived'] );
}

function bsc_archive_order_from_admin( WC_Order $order, string $note = 'Archivado manualmente desde BSC Admin.' ): void {
	if ( ! $order->get_meta( '_bsc_archived_at', true ) ) {
		$order->update_meta_data( '_bsc_archived_at', current_time( 'mysql', true ) );
	}

	$order->update_meta_data( '_bsc_archive_bucket', 'manual' );
	$order->save_meta_data();
	$order->add_order_note( $note );
}

function bsc_unarchive_order_from_admin( WC_Order $order ): void {
	if ( ! $order->get_meta( '_bsc_archived_at', true ) ) {
		return;
	}

	$order->delete_meta_data( '_bsc_archived_at' );
	$order->delete_meta_data( '_bsc_archive_bucket' );
	$order->save_meta_data();
	$order->add_order_note( 'Reactivado desde BSC Admin.' );
}

function bsc_normalize_order_statuses( array $statuses ): array {
	return array_values( array_filter( array_map( 'bsc_normalize_order_status_slug', $statuses ) ) );
}

function bsc_unarchived_orders_meta_query(): array {
	return array(
		'relation' => 'OR',
		array(
			'key'     => '_bsc_archived_at',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_bsc_archived_at',
			'value'   => '',
			'compare' => '=',
		),
	);
}

function bsc_archived_orders_meta_query(): array {
	return array(
		'relation' => 'AND',
		array(
			'key'     => '_bsc_archived_at',
			'compare' => 'EXISTS',
		),
		array(
			'key'     => '_bsc_archived_at',
			'value'   => '',
			'compare' => '!=',
		),
	);
}

function bsc_count_orders_for_statuses( array $statuses ): int {
	$query_args = array(
		'limit'      => 1,
		'paginate'   => true,
		'return'     => 'ids',
		'meta_query' => bsc_unarchived_orders_meta_query(),
	);

	if ( ! empty( $statuses ) ) {
		$query_args['status'] = bsc_normalize_order_statuses( $statuses );
	}

	$result = wc_get_orders( $query_args );

	return (int) ( $result->total ?? 0 );
}

function bsc_count_archived_orders(): int {
	$result = wc_get_orders(
		array(
			'limit'      => 1,
			'paginate'   => true,
			'return'     => 'ids',
			'meta_query' => bsc_archived_orders_meta_query(),
		)
	);

	return (int) ( $result->total ?? 0 );
}

function bsc_simplified_order_status_label( WC_Order $order ): string {
	if ( $order->get_meta( '_bsc_archived_at', true ) ) {
		return 'Archivado';
	}

	$status = $order->get_status();

	if ( in_array( $status, array( 'shipped', 'completed' ), true ) ) {
		return 'Enviado';
	}

	if ( in_array( $status, array( 'cancelled', 'failed', 'refunded' ), true ) ) {
		return 'Cancelado';
	}

	return 'Recibido';
}

// Page render
function bsc_render_orders_page(): void {
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_orders' ) ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
	}

	// Sanitize filters
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only order filters.
	$date_start    = isset( $_GET['date_start'] ) ? sanitize_text_field( wp_unslash( $_GET['date_start'] ) ) : '';
	$date_end      = isset( $_GET['date_end'] ) ? sanitize_text_field( wp_unslash( $_GET['date_end'] ) ) : '';
	$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$active_status = isset( $_GET['order_status'] ) ? sanitize_text_field( wp_unslash( $_GET['order_status'] ) ) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

	// Status tabs + counts
	$status_tabs  = bsc_orders_status_tabs();
	$tab_counts   = array();
	if ( ! isset( $status_tabs[ $active_status ] ) ) {
		$active_status = '';
	}

	$all_statuses = array();

	foreach ( $status_tabs as $tab_config ) {
		if ( bsc_orders_tab_is_archived( $tab_config ) ) {
			continue;
		}

		$all_statuses = array_merge( $all_statuses, (array) ( $tab_config['statuses'] ?? array() ) );
	}

	$all_statuses = array_values( array_unique( bsc_normalize_order_statuses( $all_statuses ) ) );

	foreach ( $status_tabs as $slug => $config ) {
		if ( bsc_orders_tab_is_archived( $config ) ) {
			$tab_counts[ $slug ] = bsc_count_archived_orders();
			continue;
		}

		$statuses            = $slug === '' ? $all_statuses : (array) ( $config['statuses'] ?? array() );
		$tab_counts[ $slug ] = bsc_count_orders_for_statuses( $statuses );
	}

	// Build query args
	$query_args = array();
	if ( $date_start ) {
		$query_args['date_after'] = $date_start . ' 00:00:00';
	}
	if ( $date_end ) {
		$query_args['date_before'] = $date_end . ' 23:59:59';
	}
	if ( $search ) {
		$query_args['s'] = $search;
	}
	$active_tab_config        = $status_tabs[ $active_status ] ?? $status_tabs[''];
	$is_archived_view        = bsc_orders_tab_is_archived( $active_tab_config );
	$query_args['meta_query'] = $is_archived_view ? bsc_archived_orders_meta_query() : bsc_unarchived_orders_meta_query();

	if ( ! $is_archived_view && '' === $active_status ) {
		$query_args['status'] = $all_statuses;
	} elseif ( ! $is_archived_view && $active_status ) {
		$query_args['status'] = bsc_normalize_order_statuses( (array) $status_tabs[ $active_status ]['statuses'] );
	}

	$table = new BSC_Admin_Orders_Table( $query_args );
	$table->prepare_items();

	$base_url = admin_url( 'admin.php?page=bsc-orders' );
	$current_tab_label = isset( $status_tabs[ $active_status ] )
		? (string) $status_tabs[ $active_status ]['label']
		: (string) $status_tabs['']['label'];
	$active_filters_count = ( $date_start ? 1 : 0 ) + ( $date_end ? 1 : 0 ) + ( $search ? 1 : 0 );
	?>
	<div class="wrap bsc-admin-orders">
		<div class="bsc-admin-page-header">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Operacion diaria</span>
				<h1 class="wp-heading-inline">Pedidos BSC</h1>
				<p class="bsc-admin-page-header__description">Filtra, prepara, exporta e imprime pedidos desde el flujo BSC.</p>
			</div>
			<div class="bsc-admin-page-header__actions">
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-dashboard' ) ); ?>">Dashboard</a>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-products' ) ); ?>">Revisar stock</a>
			</div>
		</div>

		<?php
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag.
		if ( isset( $_GET['bsc_bulk_updated'] ) ) :
			$updated_count = absint( $_GET['bsc_bulk_updated'] );
			?>
			<div class="notice notice-success is-dismissible bsc-orders-notice">
				<p><?php echo esc_html( sprintf( '%d pedidos actualizados.', $updated_count ) ); ?></p>
			</div>
		<?php endif; ?>
		<?php // phpcs:enable WordPress.Security.NonceVerification.Recommended ?>

		<div class="bsc-admin-stat-grid bsc-orders-summary">
			<div class="bsc-admin-stat-card">
				<span class="bsc-admin-stat-card__label">Vista actual</span>
				<span class="bsc-admin-stat-card__value"><?php echo esc_html( $current_tab_label ); ?></span>
				<span class="bsc-admin-stat-card__help"><?php echo esc_html( number_format_i18n( (int) ( $tab_counts[ $active_status ] ?? $tab_counts[''] ?? 0 ) ) ); ?> pedidos en esta vista.</span>
			</div>
			<div class="bsc-admin-stat-card<?php echo esc_attr( (int) ( $tab_counts['wc-processing'] ?? 0 ) > 0 ? ' bsc-admin-stat-card--warning' : '' ); ?>">
				<span class="bsc-admin-stat-card__label">Recibidos</span>
				<span class="bsc-admin-stat-card__value"><?php echo esc_html( number_format_i18n( (int) ( $tab_counts['wc-processing'] ?? 0 ) ) ); ?></span>
				<span class="bsc-admin-stat-card__help">Por revisar, empacar o enviar.</span>
			</div>
			<div class="bsc-admin-stat-card">
				<span class="bsc-admin-stat-card__label">Enviados</span>
				<span class="bsc-admin-stat-card__value"><?php echo esc_html( number_format_i18n( (int) ( $tab_counts['wc-shipped'] ?? 0 ) ) ); ?></span>
				<span class="bsc-admin-stat-card__help">Listos para archivar cuando cierre el seguimiento.</span>
			</div>
			<div class="bsc-admin-stat-card">
				<span class="bsc-admin-stat-card__label">Archivados</span>
				<span class="bsc-admin-stat-card__value"><?php echo esc_html( number_format_i18n( (int) ( $tab_counts['bsc-archived'] ?? 0 ) ) ); ?></span>
				<span class="bsc-admin-stat-card__help">Guardados fuera de las listas activas.</span>
			</div>
		</div>

		<!-- Status tabs -->
		<nav class="bsc-orders-tabs" aria-label="Estados de pedidos">
			<?php
			foreach ( $status_tabs as $slug => $config ) :
				$tab_url   = $slug
					? add_query_arg( 'order_status', $slug, $base_url )
					: $base_url;
				$is_active = ( $active_status === $slug );
				$count     = $tab_counts[ $slug ] ?? 0;
				?>
			<a href="<?php echo esc_url( $tab_url ); ?>"
								class="bsc-orders-tab<?php echo esc_attr( $is_active ? ' bsc-orders-tab--active' : '' ); ?>">
				<?php echo esc_html( $config['label'] ); ?>
				<span class="bsc-tab-count"><?php echo esc_html( $count ); ?></span>
			</a>
			<?php endforeach; ?>
		</nav>

		<!-- Filters -->
		<form method="get" class="bsc-orders-filters bsc-admin-filter-panel">
			<input type="hidden" name="page" value="bsc-orders">
			<?php if ( $active_status ) : ?>
			<input type="hidden" name="order_status" value="<?php echo esc_attr( $active_status ); ?>">
			<?php endif; ?>
			<div class="bsc-admin-field">
				<label for="bsc-orders-date-start">Desde</label>
				<input type="date" id="bsc-orders-date-start" name="date_start" value="<?php echo esc_attr( $date_start ); ?>">
			</div>
			<div class="bsc-admin-field">
				<label for="bsc-orders-date-end">Hasta</label>
				<input type="date" id="bsc-orders-date-end" name="date_end" value="<?php echo esc_attr( $date_end ); ?>">
			</div>
			<div class="bsc-admin-field bsc-admin-field--grow">
				<label for="bsc-orders-search">Buscar</label>
				<input type="search" id="bsc-orders-search" name="s" value="<?php echo esc_attr( $search ); ?>"
						placeholder="Nombre, email o # de orden" class="bsc-orders-search-input">
			</div>
			<div class="bsc-admin-filter-panel__actions">
				<button type="submit" class="button button-primary">Filtrar</button>
				<?php if ( $date_start || $date_end || $search ) : ?>
					<a href="<?php echo esc_url( $active_status ? add_query_arg( 'order_status', $active_status, $base_url ) : $base_url ); ?>" class="button">Limpiar</a>
				<?php endif; ?>
			</div>
		</form>

		<!-- Table with bulk export form -->
		<!-- Posts back to this same page; bsc_handle_bulk_export() intercepts in admin_init -->
		<form method="post" id="bsc-orders-form"
				action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-orders' ) ); ?>">
			<?php wp_nonce_field( 'bsc_bulk_export', 'bsc_export_nonce' ); ?>
			<div class="bsc-orders-bulk-actions bsc-admin-panel bsc-admin-panel--compact">
				<div class="bsc-orders-bulk-actions__copy">
					<strong>Acciones masivas</strong>
					<span id="bsc-orders-selection-count">0 pedidos seleccionados</span>
				</div>
				<div class="bsc-orders-bulk-actions__buttons">
					<button type="submit" name="bsc_bulk_action" value="export_csv" class="button" id="bsc-csv-btn">
						Descargar CSV
					</button>
					<button type="submit" name="bsc_bulk_action" value="print_packing" class="button" id="bsc-packing-btn">
						Vista de empaque
					</button>
					<button type="submit" name="bsc_bulk_action" value="print_order_labels" class="button button-primary" id="bsc-labels-btn">
						Imprimir con datos (PDF)
					</button>
				</div>
				<div class="bsc-orders-bulk-status">
					<label for="bsc-bulk-status">Cambiar estado</label>
					<select name="bsc_bulk_status" id="bsc-bulk-status">
						<option value="">Seleccionar estado</option>
						<?php foreach ( BSC_Admin_Orders_Table::STATUS_OPTIONS as $status_key => $status_label ) : ?>
							<option value="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit" name="bsc_bulk_action" value="bulk_update_status" class="button" id="bsc-bulk-status-btn">
						Aplicar
					</button>
				</div>
				<span id="bsc-bulk-msg" class="bsc-orders-bulk-message">
					Selecciona al menos un pedido primero.
				</span>
			</div>
			<div class="bsc-admin-table-wrap bsc-admin-orders__table-wrap">
				<?php $table->display(); ?>
			</div>
		</form>
	</div>
	<?php
}

// BSC-031: AJAX - update order status
add_action( 'wp_ajax_bsc_update_order_status', 'bsc_ajax_update_order_status' );
function bsc_ajax_update_order_status(): void {
	check_ajax_referer( 'bsc_admin_orders', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_orders' ) ) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ) );
	}

	$order_id         = absint( wp_unslash( $_POST['order_id'] ?? 0 ) );
	$status           = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
	$allowed_statuses = array_map( 'bsc_normalize_order_status_slug', array_keys( BSC_Admin_Orders_Table::STATUS_OPTIONS ) );
	$status           = bsc_normalize_order_status_slug( $status );

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => 'Pedido no encontrado' ) );
	}
	if ( ! in_array( $status, $allowed_statuses, true ) ) {
		wp_send_json_error( array( 'message' => 'Estado inválido' ) );
	}

	if ( bsc_orders_is_archive_status( $status ) ) {
		bsc_archive_order_from_admin( $order );

		wp_send_json_success(
			array(
				'message'    => 'Pedido archivado',
				'status'     => bsc_orders_archive_status_key(),
				'status_key' => bsc_orders_archive_status_key(),
			)
		);
	}

	bsc_unarchive_order_from_admin( $order );
	$order->update_status( $status, 'Estado actualizado desde BSC Admin.' );

	if ( 'shipped' === $status ) {
		$email_error = bsc_maybe_send_shipping_email_for_order( $order );

		if ( $email_error ) {
			error_log( 'BSC: error al enviar email de envío — ' . $email_error );
		}
	}

	wp_send_json_success(
		array(
			'message'    => 'Estado actualizado',
			'status'     => $status,
			'status_key' => 'wc-' . $status,
		)
	);
}

// BSC-031+033: AJAX - save tracking code/link
add_action( 'wp_ajax_bsc_save_tracking', 'bsc_ajax_save_tracking' );
function bsc_ajax_save_tracking(): void {
	check_ajax_referer( 'bsc_admin_orders', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_orders' ) ) {
		wp_send_json_error( array( 'message' => 'Sin permisos' ) );
	}

	$order_id            = absint( wp_unslash( $_POST['order_id'] ?? 0 ) );
	$tracking_code       = sanitize_text_field( wp_unslash( $_POST['tracking_code'] ?? '' ) );
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw URL is validated before esc_url_raw() so scheme-less URLs stay invalid.
	$tracking_link_input = trim( (string) wp_unslash( $_POST['tracking_link'] ?? '' ) );

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => 'Pedido no encontrado' ) );
	}

	$tracking_link = bsc_resolve_tracking_link( $tracking_code, $tracking_link_input );

	if ( '' !== $tracking_link_input && '' === $tracking_link ) {
		wp_send_json_error( array( 'message' => 'Ingresa una URL completa de seguimiento que empiece por https:// o http://.' ) );
	}

	if ( $tracking_code && '' === $tracking_link ) {
		wp_send_json_error( array( 'message' => 'Ingresa la URL completa de seguimiento antes de guardar la guia.' ) );
	}

	update_post_meta( $order_id, '_bsc_tracking_code', $tracking_code );
	update_post_meta( $order_id, '_bsc_tracking_link', $tracking_link );

	// BSC-033: auto-change status to "shipped" and send email
	if ( $tracking_code ) {
		if ( $order->get_status() !== 'shipped' ) {
			$order->update_status( 'shipped', 'Guía ingresada desde BSC Admin.' );
		}

		$email_error = bsc_maybe_send_shipping_email_for_order( $order );
		if ( $email_error ) {
			error_log( 'BSC: error al enviar email de envío — ' . $email_error );
		}
	}

	wp_send_json_success(
		array(
			'message'       => 'Tracking guardado',
			'tracking_link' => $tracking_link,
		)
	);
}

add_action( 'wp_ajax_bsc_pack_order_item_stock', 'bsc_ajax_pack_order_item_stock' );
function bsc_ajax_pack_order_item_stock(): void {
	check_ajax_referer( 'bsc_packing_stock', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_orders' ) ) {
		wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
	}

	if ( ! class_exists( 'BSC_Stock' ) ) {
		wp_send_json_error( array( 'message' => 'Modulo de stock no disponible.' ), 500 );
	}

	$order_id = absint( $_POST['order_id'] ?? 0 );
	$item_id  = absint( $_POST['item_id'] ?? 0 );
	$order    = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => 'Pedido no encontrado.' ), 404 );
	}

	$item = $order->get_item( $item_id );
	if ( ! $item || ! is_a( $item, 'WC_Order_Item_Product' ) ) {
		wp_send_json_error( array( 'message' => 'Producto del pedido no encontrado.' ), 404 );
	}

	$product_id = (int) $item->get_product_id();
	$quantity   = max( 1, (int) $item->get_quantity() );
	$label      = BSC_Stock::get_order_item_source_label( $item, $product_id );

	$item->update_meta_data( '_bsc_packed_stock_at', current_time( 'mysql' ) );
	$item->update_meta_data( '_bsc_packed_stock_user_id', get_current_user_id() );
	$item->save();

	$order->add_order_note(
		sprintf(
			'Empaque confirmado para %1$s x %2$d. Origen: %3$s.',
			$item->get_name(),
			$quantity,
			$label,
		)
	);

	wp_send_json_success(
		array(
			'message' => 'Empaque confirmado.',
			'label'   => $label,
		)
	);
}

/**
 * Sanitize a user-provided full tracking URL.
 *
 * @param string $tracking_code Tracking code kept for backward-compatible callers.
 * @param string $tracking_link Submitted tracking URL.
 * @return string Sanitized URL or empty string.
 */
function bsc_resolve_tracking_link( string $tracking_code, string $tracking_link = '' ): string {
	$tracking_link = trim( $tracking_link );

	if ( '' === $tracking_link ) {
		return '';
	}

	$scheme = wp_parse_url( $tracking_link, PHP_URL_SCHEME );
	$host   = wp_parse_url( $tracking_link, PHP_URL_HOST );

	if (
		! is_string( $scheme )
		|| ! is_string( $host )
		|| '' === $host
		|| ! in_array( strtolower( $scheme ), array( 'http', 'https' ), true )
	) {
		return '';
	}

	return esc_url_raw( $tracking_link );
}

// BSC-033: Send shipping notification email
function bsc_send_shipping_email( int $order_id ): string {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return 'Pedido no encontrado';
	}

	$to = $order->get_billing_email();
	if ( ! $to ) {
		return 'Sin email del cliente';
	}

	$tracking_code = get_post_meta( $order_id, '_bsc_tracking_code', true );
	$tracking_link = get_post_meta( $order_id, '_bsc_tracking_link', true );
	$tracking_link = bsc_resolve_tracking_link( (string) $tracking_code, (string) $tracking_link );

	if ( function_exists( 'bsc_send_order_email' ) ) {
		$sent = bsc_send_order_email(
			$order_id,
			'shipped',
			array(
				'tracking_code' => $tracking_code,
				'tracking_link' => $tracking_link,
			)
		);

		return $sent ? '' : 'wp_mail() devolvio false';
	}

	$subject = sprintf( 'Tu pedido #%s está en camino 🚚', $order->get_order_number() );

	ob_start();
	include get_template_directory() . '/emails/bsc-order-shipped.php';
	$message = ob_get_clean();

	$headers = function_exists( 'bsc_get_email_headers' )
		? bsc_get_email_headers()
		: array( 'Content-Type: text/html; charset=UTF-8' );

	$sent = wp_mail( $to, $subject, $message, $headers );

	return $sent ? '' : 'wp_mail() devolvió false';
}

function bsc_maybe_send_shipping_email_for_order( WC_Order $order ): string {
	$order_id = $order->get_id();
	$tracking_code = (string) get_post_meta( $order_id, '_bsc_tracking_code', true );

	if ( ! $tracking_code ) {
		return '';
	}

	$tracking_link = bsc_resolve_tracking_link(
		$tracking_code,
		(string) get_post_meta( $order_id, '_bsc_tracking_link', true )
	);

	if ( ! $tracking_link ) {
		return '';
	}

	update_post_meta( $order_id, '_bsc_tracking_link', $tracking_link );

	$last_email_tracking_code = (string) get_post_meta( $order_id, '_bsc_shipping_email_tracking_code', true );
	$last_email_tracking_link = (string) get_post_meta( $order_id, '_bsc_shipping_email_tracking_link', true );

	if (
		get_post_meta( $order_id, '_bsc_shipping_email_sent_at', true )
		&& $last_email_tracking_code === $tracking_code
		&& $last_email_tracking_link === $tracking_link
	) {
		return '';
	}

	$email_error = bsc_send_shipping_email( $order_id );

	if ( ! $email_error ) {
		update_post_meta( $order_id, '_bsc_shipping_email_sent_at', current_time( 'mysql' ) );
		update_post_meta( $order_id, '_bsc_shipping_email_tracking_code', $tracking_code );
		update_post_meta( $order_id, '_bsc_shipping_email_tracking_link', $tracking_link );
	}

	return $email_error;
}

// BSC-034: CSV export
function bsc_export_orders_csv( array $order_ids ): void {
	// Clear any WP output buffers so headers can be sent cleanly
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	// UTF-8 BOM for Excel compatibility
	$bom = "\xEF\xBB\xBF";

	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="pedidos-' . gmdate( 'Y-m-d' ) . '.csv"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, $bom );

	fputcsv( $out, array( 'ID', 'Fecha', 'Cliente', 'Email', 'Teléfono', 'Ciudad', 'Dirección', 'Productos', 'Total', 'Estado' ) );

	foreach ( $order_ids as $id ) {
		$order = wc_get_order( $id );
		if ( ! $order ) {
			continue;
		}

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$item_name = $item instanceof WC_Order_Item_Product && function_exists( 'bsc_format_order_item_name_with_variant' )
				? bsc_format_order_item_name_with_variant( $item )
				: $item->get_name();
			$items[]   = $item->get_quantity() . '× ' . $item_name;
		}

		fputcsv(
			$out,
			bsc_csv_safe_row(
				array(
					$order->get_order_number(),
					$order->get_date_created() ? $order->get_date_created()->date( 'd/m/Y H:i' ) : '',
					trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
					$order->get_billing_email(),
					$order->get_billing_phone(),
					$order->get_shipping_city() ?: $order->get_billing_city(),
					trim( $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() ) ?: $order->get_billing_address_1(),
					implode( ' | ', $items ),
					$order->get_total(),
					bsc_simplified_order_status_label( $order ),
				)
			)
		);
	}

	fclose( $out );
	exit;
}

if ( ! function_exists( 'bsc_csv_safe_value' ) ) {
	function bsc_csv_safe_value( $value ) {
		if ( is_string( $value ) && preg_match( '/^[=+\-@]/', ltrim( $value ) ) ) {
			return "'" . $value;
		}

		return $value;
	}
}

if ( ! function_exists( 'bsc_csv_safe_row' ) ) {
	function bsc_csv_safe_row( array $row ): array {
		return array_map( 'bsc_csv_safe_value', $row );
	}
}

// BSC-034: Packing print view
function bsc_render_packing_view( array $order_ids ): void {
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	header( 'Content-Type: text/html; charset=UTF-8' );

	$packing_view_css = trailingslashit( get_template_directory_uri() ) . 'admin/bsc-packing-view.css';
	$packing_view_js  = trailingslashit( get_template_directory_uri() ) . 'admin/bsc-packing-view.js';
	$packing_nonce    = wp_create_nonce( 'bsc_packing_stock' );
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="UTF-8">
		<title>Vista de Empaque - BSC</title>
		<link rel="stylesheet" href="<?php echo esc_url( $packing_view_css ); ?>">
		<script src="<?php echo esc_url( $packing_view_js ); ?>" defer></script>
	</head>
	<body class="bsc-packing-view" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( $packing_nonce ); ?>">
		<div class="bsc-packing-view__toolbar">
			<button type="button" id="bsc-packing-print">Imprimir</button>
			<button type="button" id="bsc-packing-close">Cerrar</button>
		</div>
		<h1>Vista de Empaque - <?php echo esc_html( gmdate( 'd/m/Y' ) ); ?></h1>

		<?php
		foreach ( $order_ids as $id ) :
			$order = wc_get_order( $id );
			if ( ! $order ) {
				continue;
			}
			$name    = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() )
				?: trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			$city    = $order->get_shipping_city() ?: $order->get_billing_city();
			$address = trim( $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() )
					?: $order->get_billing_address_1();
			?>
		<div class="bsc-packing-view__order-card">
			<div class="bsc-packing-view__order-header">
				<span class="bsc-packing-view__order-number">#<?php echo esc_html( $order->get_order_number() ); ?></span>
				<span class="bsc-packing-view__order-status"><?php echo esc_html( bsc_simplified_order_status_label( $order ) ); ?></span>
			</div>
			<div class="bsc-packing-view__details">
				<div><strong>Cliente:</strong> <?php echo esc_html( $name ); ?></div>
				<div><strong>Teléfono:</strong> <?php echo esc_html( $order->get_billing_phone() ); ?></div>
				<div><strong>Ciudad:</strong> <?php echo esc_html( $city ); ?></div>
				<div><strong>Fecha:</strong> <?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date( 'd/m/Y' ) : '' ); ?></div>
				<div class="bsc-packing-view__details-row--full"><strong>Dirección:</strong> <?php echo esc_html( $address ); ?></div>
			</div>
			<table>
				<thead>
					<tr><th>Producto</th><th>SKU</th><th>Qty</th><th>Subtotal</th><th>Stock</th><th>Origen para empaque</th></tr>
				</thead>
				<tbody>
					<?php
					foreach ( $order->get_items() as $item ) :
						$product      = $item->get_product();
						$product_id   = (int) $item->get_product_id();
						$sku          = $product ? $product->get_sku() : '';
						$variant_data = $item instanceof WC_Order_Item_Product && function_exists( 'bsc_get_order_item_product_variant_data' )
							? bsc_get_order_item_product_variant_data( $item )
							: array(
								'variant_key' => '',
								'parts'       => array(),
							);

						$variant_key = sanitize_key( (string) ( $variant_data['variant_key'] ?? '' ) );
						$stock       = array(
							'bodega' => 0,
							'tienda' => 0,
						);

						if ( class_exists( 'BSC_Stock' ) ) {
							$stock = '' !== $variant_key
								? BSC_Stock::get_variant_stock( $product_id, $variant_key )
								: BSC_Stock::get_stock( $product_id );
						}

						$variant_label = ! empty( $variant_data['parts'] ) ? implode( ' / ', $variant_data['parts'] ) : '';
						$source_label  = class_exists( 'BSC_Stock' ) && $item instanceof WC_Order_Item_Product
							? BSC_Stock::get_order_item_source_label( $item, $product_id )
							: 'Bodega';
						?>
					<tr data-order-id="<?php echo esc_attr( $order->get_id() ); ?>" data-item-id="<?php echo esc_attr( $item->get_id() ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>">
						<td>
							<?php echo esc_html( $item->get_name() ); ?>
							<?php if ( '' !== $variant_label ) : ?>
								<br><small>Variante: <?php echo esc_html( $variant_label ); ?></small>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $sku ); ?></td>
						<td><?php echo esc_html( $item->get_quantity() ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?></td>
						<td class="bsc-packing-stock">
							Bodega: <?php echo esc_html( (string) ( $stock['bodega'] ?? 0 ) ); ?><br>
							Showroom: <?php echo esc_html( (string) ( $stock['tienda'] ?? 0 ) ); ?>
						</td>
						<td class="bsc-packing-action">
							<span class="bsc-packing-source-label"><?php echo esc_html( $source_label ); ?></span>
						</td>
					</tr>
					<?php endforeach; ?>
					<tr class="total-row">
						<td colspan="5">Total</td>
						<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php endforeach; ?>
	</body>
	</html>
	<?php
	exit;
}
