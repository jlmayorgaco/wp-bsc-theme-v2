<?php
/**
 * BSC-024 / BSC-025: Informes page with extensible tab architecture.
 *
 * Tab registry: add future tabs to bsc_get_report_tabs() only.
 * Each tab is isolated; no shared state.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_reports_admin_assets' );
function bsc_enqueue_reports_admin_assets(): void {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-reports' !== $page ) {
		return;
	}

	bsc_enqueue_admin_ui_assets();

	$css_path = get_template_directory() . '/admin/bsc-reports.css';
	$js_path  = get_template_directory() . '/js/admin/bsc-reports.js';

	wp_enqueue_style(
		'bsc-admin-reports',
		get_template_directory_uri() . '/admin/bsc-reports.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);

	wp_enqueue_script(
		'bsc-admin-reports',
		get_template_directory_uri() . '/js/admin/bsc-reports.js',
		array(),
		file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
		true
	);
}

// Tab registry (BSC-024)
/**
 * To add a new tab: append an entry here and create its callback function.
 * Keys become ?tab=<key> query arg values.
 */
function bsc_get_report_tabs(): array {
	return array(
		'ventas' => array(
			'label'    => 'Ventas',
			'callback' => 'bsc_reports_tab_ventas',
		),
		'stock'  => array(
			'label'    => 'Stock',
			'callback' => 'bsc_reports_tab_stock',
		),
		// Future: 'productos', 'clientes', 'main'...
	);
}

// CSV export only used by ventas tab
add_action(
	'admin_init',
	function () {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'bsc-reports' !== $page ) {
			return;
		}
		if ( ! isset( $_GET['bsc_export_csv'] ) ) {
			return;
		}
		if ( ! bsc_current_user_has_bsc_page_access( 'bsc-reports' ) ) {
			wp_die( 'Sin permisos.' );
		}
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce is verified only and is not persisted.
		if ( ! wp_verify_nonce( sanitize_text_field( $_GET['bsc_export_nonce'] ?? '' ), 'bsc_reports_export' ) ) {
			wp_die( 'Nonce inválido.' );
		}

		$date_start = sanitize_text_field( wp_unslash( $_GET['date_start'] ?? gmdate( 'Y-m-01' ) ) );
		$date_end   = sanitize_text_field( wp_unslash( $_GET['date_end'] ?? gmdate( 'Y-m-d' ) ) );
		$statuses   = bsc_reports_get_statuses();

		$orders_args = array(
			'status'      => $statuses,
			'date_after'  => $date_start . ' 00:00:00',
			'date_before' => $date_end . ' 23:59:59',
		);

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="bsc-informe-' . $date_start . '_' . $date_end . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputs( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array( 'Fecha', 'Pedido #', 'Cliente', 'Email', 'Productos', 'Total', 'Estado', 'Canal' ) );

		bsc_reports_for_each_order(
			$orders_args,
			function ( $order ) use ( $out ) {
				$items_str = implode( ' | ', array_map( fn( $i ) => $i->get_name() . ' x' . $i->get_quantity(), $order->get_items() ) );
				$canal     = $order->get_meta( '_bsc_is_showroom_sale' ) ? 'Showroom' : 'Web';
				fputcsv(
					$out,
					bsc_csv_safe_row(
						array(
							$order->get_date_created()?->date( 'Y-m-d H:i' ) ?? '',
							'#' . $order->get_order_number(),
							$order->get_formatted_billing_full_name(),
							$order->get_billing_email(),
							$items_str,
							$order->get_total(),
							wc_get_order_status_name( $order->get_status() ),
							$canal,
						)
					)
				);
			}
		);
		fclose( $out );
		exit;
	}
);

function bsc_reports_get_statuses(): array {
	$all = array( 'completed', 'processing', 'wc-preparing', 'wc-shipped' );
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter.
	if ( ! isset( $_GET['statuses'] ) ) {
		return $all;
	}
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter.
	$raw = array_map( 'sanitize_key', (array) wp_unslash( $_GET['statuses'] ) );
	return array_filter( $raw, fn( $s ) => in_array( $s, $all, true ) );
}

function bsc_reports_for_each_order( array $args, callable $callback, int $limit = 100 ): void {
	$page = 1;

	do {
		$orders = wc_get_orders(
			array_merge(
				$args,
				array(
					'limit' => $limit,
					'paged' => $page,
				)
			)
		);

		foreach ($orders as $order) {
			if ($order instanceof WC_Order) {
				$callback( $order );
			}
		}

		++$page;
	} while (count( $orders ) === $limit);
}

function bsc_reports_get_stock_row_class( object $row, int $threshold ): string {
	$b = (int) $row->stock_bodega;
	$t = (int) $row->stock_tienda;

	$classes = array( 'bsc-stock-row', 'bsc-admin-reports__stock-row' );

	if ( 0 === $b && 0 === $t ) {
		$classes[] = 'bsc-admin-reports__stock-row--zero';
	} elseif ( 0 === $b || 0 === $t ) {
		$classes[] = 'bsc-admin-reports__stock-row--partial-zero';
	} elseif ( $b < $threshold || $t < $threshold ) {
		$classes[] = 'bsc-admin-reports__stock-row--low';
	}

	return implode( ' ', $classes );
}

function bsc_reports_get_stock_value_class( int $stock, int $threshold ): string {
	$classes = array(
		'bsc-admin-reports__table-cell',
		'bsc-admin-reports__table-cell--center',
		'bsc-admin-reports__stock-value',
	);

	if ( 0 === $stock ) {
		$classes[] = 'bsc-admin-reports__stock-value--danger';
	} elseif ( $stock < $threshold ) {
		$classes[] = 'bsc-admin-reports__stock-value--warning';
	}

	return implode( ' ', $classes );
}

// Main dispatcher (BSC-024)
function bsc_render_reports_page(): void {
	if ( ! bsc_current_user_has_bsc_page_access( 'bsc-reports' ) ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
	}

	$tabs = bsc_get_report_tabs();
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report tab filter.
	$active_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'ventas' ) );
	if ( ! isset( $tabs[ $active_tab ] ) ) {
		$active_tab = 'ventas';
	}

	echo '<div class="wrap bsc-admin-reports">';
	echo '<div class="bsc-admin-page-header bsc-admin-page-header--compact">';
	echo '<div><span class="bsc-admin-page-header__eyebrow">Analitica</span><h1>Informes BSC</h1><p class="bsc-admin-page-header__description">Revisa ventas, stock y senales operativas con filtros accionables.</p></div>';
	echo '<div class="bsc-admin-page-header__actions"><a class="button" href="' . esc_url( admin_url( 'admin.php?page=bsc-dashboard' ) ) . '">Dashboard</a></div>';
	echo '</div>';

	// WP-native nav tabs
	echo '<nav class="nav-tab-wrapper bsc-admin-reports__tabs bsc-admin-screen-tabs">';
	foreach ( $tabs as $slug => $tab ) {
		$url   = add_query_arg(
			array(
				'page' => 'bsc-reports',
				'tab'  => $slug,
			),
			admin_url( 'admin.php' )
		);
		$class = $slug === $active_tab ? 'nav-tab nav-tab-active' : 'nav-tab';
		printf( '<a href="%s" class="%s">%s</a>', esc_url( $url ), esc_attr( $class ), esc_html( $tab['label'] ) );
	}
	echo '</nav>';

	echo '<div class="bsc-tab-content bsc-admin-reports__tab-content bsc-admin-panel">';

	if ( is_callable( $tabs[ $active_tab ]['callback'] ) ) {
		call_user_func( $tabs[ $active_tab ]['callback'] );
	} else {
		echo '<p class="bsc-admin-reports__empty-state">Tab no disponible.</p>';
	}

	echo '</div></div>';
}

// Tab: Ventas
function bsc_reports_tab_ventas(): void {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only sales report filters.
	$date_start = sanitize_text_field( wp_unslash( $_GET['date_start'] ?? gmdate( 'Y-m-01' ) ) );
	$date_end   = sanitize_text_field( wp_unslash( $_GET['date_end'] ?? gmdate( 'Y-m-d' ) ) );
	$statuses   = bsc_reports_get_statuses();

	if ( isset( $_GET['preset'] ) ) {
		switch ( sanitize_key( wp_unslash( $_GET['preset'] ) ) ) {
			case 'hoy':
				$date_start = $date_end = gmdate( 'Y-m-d' );
				break;
			case 'semana':
				$date_start = gmdate( 'Y-m-d', strtotime( 'monday this week' ) );
				$date_end   = gmdate( 'Y-m-d' );
				break;
			case 'mes':
				$date_start = gmdate( 'Y-m-01' );
				$date_end   = gmdate( 'Y-m-d' );
				break;
			case 'mes_ant':
				$date_start = gmdate( 'Y-m-01', strtotime( 'first day of last month' ) );
				$date_end   = gmdate( 'Y-m-t', strtotime( 'last month' ) );
				break;
			case 'anno':
				$date_start = gmdate( 'Y-01-01' );
				$date_end   = gmdate( 'Y-m-d' );
				break;
		}
	}
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

	$cache_key = 'bsc_report_' . md5( $date_start . '_' . $date_end . implode( ',', $statuses ) );
	$data      = get_transient( $cache_key );

	$clear_cache_nonce = isset( $_GET['bsc_clear_report_cache_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['bsc_clear_report_cache_nonce'] ) ) : '';
	if ( isset( $_GET['bsc_clear_report_cache'] ) && wp_verify_nonce( $clear_cache_nonce, 'bsc_reports_clear_cache' ) ) {
		delete_transient( $cache_key );
		$data = false;
	}

	if ( false === $data ) {
		$orders_args = array(
			'status'      => $statuses,
			'date_after'  => $date_start . ' 00:00:00',
			'date_before' => $date_end . ' 23:59:59',
		);

		$total_sales    = 0;
		$order_count    = 0;
		$total_items    = 0;
		$web_sales      = 0;
		$showroom_sales = 0;
		$product_sales  = array();
		$category_sales = array();

		bsc_reports_for_each_order(
			$orders_args,
			function ( $order ) use ( &$total_sales, &$order_count, &$total_items, &$web_sales, &$showroom_sales, &$product_sales, &$category_sales ) {
				$order_count++;
				$total        = (float) $order->get_total();
				$total_sales += $total;
				$is_showroom  = (bool) $order->get_meta( '_bsc_is_showroom_sale' );
				if ($is_showroom) {
					$showroom_sales += $total;
				} else {
					$web_sales += $total;
				}

				foreach ($order->get_items() as $item) {
					$pid          = $item->get_product_id();
					$qty          = $item->get_quantity();
					$total_items += $qty;

					if (!isset( $product_sales[ $pid ] )) {
						$product_sales[ $pid ] = array(
							'name'  =>$item->get_name(),
							'qty'   =>0,
							'total' =>0,
						);
					}
					$product_sales[ $pid ]['qty']   += $qty;
					$product_sales[ $pid ]['total'] += (float) $item->get_total();

					$cats = get_the_terms( $pid, 'product_cat' );
					if ($cats && !is_wp_error( $cats )) {
						foreach ($cats as $cat) {
							if (!isset( $category_sales[ $cat->term_id ] )) {
								$category_sales[ $cat->term_id ] = array(
									'name'   =>$cat->name,
									'orders' =>0,
									'total'  =>0,
								);
							}
							$category_sales[ $cat->term_id ]['total'] += (float) $item->get_total();
						}
					}
				}

				$seen_cats = array();
				foreach ($order->get_items() as $item) {
					$cats = get_the_terms( $item->get_product_id(), 'product_cat' );
					if ($cats && !is_wp_error( $cats )) {
						foreach ($cats as $cat) {
							if (!in_array( $cat->term_id, $seen_cats, true )) {
								$category_sales[ $cat->term_id ]['orders'] = ( $category_sales[ $cat->term_id ]['orders'] ?? 0 ) + 1;
								$seen_cats[]                               = $cat->term_id;
							}
						}
					}
				}
			}
		);

		uasort( $product_sales, fn( $a, $b ) => $b['qty'] <=> $a['qty'] );
		$top_products = array_slice( $product_sales, 0, 10, true );
		uasort( $category_sales, fn( $a, $b ) => $b['total'] <=> $a['total'] );
		$avg_ticket = $order_count > 0 ? $total_sales / $order_count : 0;
		$avg_items  = $order_count > 0 ? round( $total_items / $order_count, 1 ) : 0;

		$data = compact( 'total_sales', 'order_count', 'avg_ticket', 'avg_items', 'web_sales', 'showroom_sales', 'top_products', 'category_sales' );
		set_transient( $cache_key, $data, HOUR_IN_SECONDS );
	}

	extract( $data );
	$export_nonce    = wp_create_nonce( 'bsc_reports_export' );
	$export_url      = add_query_arg(
		array(
			'page'             =>'bsc-reports',
			'tab'              =>'ventas',
			'bsc_export_csv'   =>1,
			'bsc_export_nonce' =>$export_nonce,
			'date_start'       =>$date_start,
			'date_end'         =>$date_end,
			'statuses'         =>$statuses,
		),
		admin_url( 'admin.php' )
	);
	$clear_cache_url = add_query_arg(
		array(
			'tab'                          => 'ventas',
			'bsc_clear_report_cache'       => '1',
			'bsc_clear_report_cache_nonce' => wp_create_nonce( 'bsc_reports_clear_cache' ),
		)
	);
	?>

	<!-- Filters -->
	<form method="get" class="bsc-admin-reports__sales-filters bsc-admin-filter-panel">
		<input type="hidden" name="page"  value="bsc-reports">
		<input type="hidden" name="tab"   value="ventas">
		<div class="bsc-admin-reports__filter-row">
			<div class="bsc-admin-reports__filter-field">
				<label class="bsc-admin-reports__field-label">Desde</label>
				<input type="date" name="date_start" value="<?php echo esc_attr( $date_start ); ?>" class="bsc-admin-reports__date-input">
			</div>
			<div class="bsc-admin-reports__filter-field">
				<label class="bsc-admin-reports__field-label">Hasta</label>
				<input type="date" name="date_end" value="<?php echo esc_attr( $date_end ); ?>" class="bsc-admin-reports__date-input">
			</div>
			<div class="bsc-admin-reports__filter-field">
				<label class="bsc-admin-reports__field-label">Estado</label>
				<div class="bsc-admin-reports__checkbox-group">
					<?php
					foreach (array(
						'completed'    =>'Completado',
						'processing'   =>'Procesando',
						'wc-preparing' =>'Preparando',
						'wc-shipped'   =>'Enviado',
					) as $slug => $label) :
						?>
					<label class="bsc-admin-reports__checkbox-label">
						<input type="checkbox" name="statuses[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $statuses ) ); ?>>
																<?php echo esc_html( $label ); ?>
					</label>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="bsc-admin-reports__filter-actions bsc-admin-actions">
				<button type="submit" class="button button-primary">Aplicar</button>
				<?php
				foreach (array(
					'hoy'     =>'Hoy',
					'semana'  =>'Semana',
					'mes'     =>'Este mes',
					'mes_ant' =>'Mes anterior',
					'anno'    =>'Anual',
				) as $k=>$l) :
					?>
				<a href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'page'   =>'bsc-reports',
								'tab'    =>'ventas',
								'preset' =>$k,
							)
						)
					);
					?>
							" class="button"><?php echo esc_html( $l ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</form>

	<div class="bsc-admin-reports__summary-bar bsc-admin-toolbar">
		<span class="bsc-admin-reports__summary-text">
			<?php echo esc_html( $date_start ); ?> al <?php echo esc_html( $date_end ); ?>
			&nbsp;|&nbsp;
			<a href="<?php echo esc_url( $clear_cache_url ); ?>">Limpiar cache</a>
		</span>
		<a href="<?php echo esc_url( $export_url ); ?>" class="button">Exportar CSV</a>
	</div>

	<div class="bsc-kpi-grid">
		<div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post( wc_price( $total_sales ) ); ?></div><div class="bsc-kpi-label">Ventas totales</div></div>
		<div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo esc_html( $order_count ); ?></div><div class="bsc-kpi-label">Pedidos</div></div>
		<div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post( wc_price( $avg_ticket ) ); ?></div><div class="bsc-kpi-label">Ticket promedio</div></div>
		<div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo esc_html( $avg_items ); ?></div><div class="bsc-kpi-label">Items / pedido</div></div>
		<div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post( wc_price( $web_sales ) ); ?></div><div class="bsc-kpi-label">Ventas Web</div></div>
		<div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post( wc_price( $showroom_sales ) ); ?></div><div class="bsc-kpi-label">Ventas Showroom</div></div>
	</div>

	<div class="bsc-admin-reports__split-grid">
		<div class="bsc-admin-reports__panel">
			<h2 class="bsc-admin-reports__section-title">Top 10 productos vendidos</h2>
			<?php if ($top_products) : ?>
			<div class="bsc-admin-table-wrap bsc-admin-table-wrap--flush"><table class="wp-list-table widefat fixed striped">
				<thead><tr><th>#</th><th>Producto</th><th>Uds.</th><th>Total</th></tr></thead>
				<tbody>
				<?php $rank =1; foreach ($top_products as $pid => $p) : ?>
				<tr>
					<td><?php echo esc_html( $rank++ ); ?></td>
					<td><a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>" target="_blank"><?php echo esc_html( $p['name'] ); ?></a></td>
					<td><?php echo esc_html( $p['qty'] ); ?></td>
					<td><?php echo wp_kses_post( wc_price( $p['total'] ) ); ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
				<?php
			else :
				?>
				<p class="bsc-admin-reports__empty-state">Sin ventas en el periodo.</p><?php endif; ?>
		</div>
		<div class="bsc-admin-reports__panel">
			<h2 class="bsc-admin-reports__section-title">Desglose por categoria</h2>
			<?php if ($category_sales) : ?>
			<div class="bsc-admin-table-wrap bsc-admin-table-wrap--flush"><table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Categoria</th><th>Pedidos</th><th>Total</th></tr></thead>
				<tbody>
				<?php foreach (array_slice( $category_sales, 0, 10, true ) as $tid => $cat) : ?>
				<tr>
					<td><?php echo esc_html( $cat['name'] ); ?></td>
					<td><?php echo esc_html( $cat['orders'] ); ?></td>
					<td><?php echo wp_kses_post( wc_price( $cat['total'] ) ); ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
				<?php
			else :
				?>
				<p class="bsc-admin-reports__empty-state">Sin datos de categorias.</p><?php endif; ?>
		</div>
	</div>

	<p class="bsc-admin-reports__footnote">Resultados cacheados por 1 hora. Usa Limpiar cache para actualizar.</p>
	<?php
}

function bsc_reports_get_stock_request_args(): array {
	$valid_sorts = array( 'title', 'sku', 'bodega', 'tienda', 'total' );
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only stock report filters.
	$sort_col = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'title';
	if ( ! in_array( $sort_col, $valid_sorts, true ) ) {
		$sort_col = 'title';
	}

	$sort_dir = isset( $_GET['dir'] ) ? sanitize_key( wp_unslash( $_GET['dir'] ) ) : 'asc';
	$sort_dir = 'desc' === $sort_dir ? 'DESC' : 'ASC';

	$valid_filters = array( 'all', 'low_bodega', 'low_tienda', 'zero_bodega', 'zero_tienda' );
	$filter        = isset( $_GET['stock_filter'] ) ? sanitize_key( wp_unslash( $_GET['stock_filter'] ) ) : 'all';
	if ( ! in_array( $filter, $valid_filters, true ) ) {
		$filter = 'all';
	}

	$per_page = isset( $_GET['per_page'] ) ? absint( wp_unslash( $_GET['per_page'] ) ) : 50;
	if ( ! in_array( $per_page, array( 25, 50, 100 ), true ) ) {
		$per_page = 50;
	}

	return array(
		'sort'     => $sort_col,
		'dir'      => $sort_dir,
		'filter'   => $filter,
		'search'   => isset( $_GET['stock_search'] ) ? sanitize_text_field( wp_unslash( $_GET['stock_search'] ) ) : '',
		'per_page' => $per_page,
		'paged'    => max( 1, isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1 ),
	);
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
}

function bsc_reports_get_stock_from_sql(): string {
	global $wpdb;

	return "FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} sku_m    ON p.ID = sku_m.post_id    AND sku_m.meta_key    = '_sku'
        LEFT JOIN {$wpdb->postmeta} bodega_m ON p.ID = bodega_m.post_id AND bodega_m.meta_key = '_stock_bodega'
        LEFT JOIN {$wpdb->postmeta} tienda_m ON p.ID = tienda_m.post_id AND tienda_m.meta_key = '_stock_tienda'
        LEFT JOIN {$wpdb->postmeta} envio_m  ON p.ID = envio_m.post_id  AND envio_m.meta_key  = '_envio_tipo'";
}

function bsc_reports_get_stock_where_sql( array $args, int $threshold ): string {
	global $wpdb;

	$where  = array( "p.post_type = 'product'", "p.post_status = 'publish'" );
	$params = array();

	if ( '' !== $args['search'] ) {
		$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		$where[]  = '(p.post_title LIKE %s OR sku_m.meta_value LIKE %s)';
		$params[] = $like;
		$params[] = $like;
	}

	switch ( $args['filter'] ) {
		case 'low_bodega':
			$where[]  = 'CAST(IFNULL(bodega_m.meta_value,0) AS UNSIGNED) < %d';
			$params[] = $threshold;
			break;
		case 'low_tienda':
			$where[]  = 'CAST(IFNULL(tienda_m.meta_value,0) AS UNSIGNED) < %d';
			$params[] = $threshold;
			break;
		case 'zero_bodega':
			$where[] = 'CAST(IFNULL(bodega_m.meta_value,0) AS UNSIGNED) = 0';
			break;
		case 'zero_tienda':
			$where[] = 'CAST(IFNULL(tienda_m.meta_value,0) AS UNSIGNED) = 0';
			break;
	}

	$where_sql = 'WHERE ' . implode( ' AND ', $where );
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- WHERE clauses are hardcoded above and values are passed separately.
	return $params ? $wpdb->prepare( $where_sql, $params ) : $where_sql;
}

// Tab: Stock (BSC-025)
function bsc_reports_tab_stock(): void {
	global $wpdb;

	$threshold = (int) get_option( 'bsc_low_stock_threshold', 3 );
	$args      = bsc_reports_get_stock_request_args();

	$order_map = array(
		'title'  => 'p.post_title',
		'sku'    => 'sku_m.meta_value',
		'bodega' => 'CAST(IFNULL(bodega_m.meta_value,0) AS UNSIGNED)',
		'tienda' => 'CAST(IFNULL(tienda_m.meta_value,0) AS UNSIGNED)',
		'total'  => '(CAST(IFNULL(bodega_m.meta_value,0) AS UNSIGNED) + CAST(IFNULL(tienda_m.meta_value,0) AS UNSIGNED))',
	);

	$sort_col  = $args['sort'];
	$sort_dir  = $args['dir'];
	$flip_dir  = $sort_dir === 'ASC' ? 'desc' : 'asc';
	$filter    = $args['filter'];
	$from_sql  = bsc_reports_get_stock_from_sql();
	$where_sql = bsc_reports_get_stock_where_sql( $args, $threshold );
	$order_sql = $order_map[ $sort_col ] . ' ' . $sort_dir;

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL fragments are allowlisted/prepared and this admin report must read fresh stock.
	$filtered_total = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT p.ID) {$from_sql} {$where_sql}" );
	$total_pages    = max( 1, (int) ceil( $filtered_total / $args['per_page'] ) );
	$args['paged']  = min( $args['paged'], $total_pages );
	$offset         = max( 0, ( $args['paged'] - 1 ) * $args['per_page'] );

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL fragments are allowlisted/prepared and this admin report must read fresh stock.
	$products = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT
                p.ID,
                p.post_title,
                IFNULL(sku_m.meta_value, '') AS sku,
                CAST(IFNULL(bodega_m.meta_value, 0) AS UNSIGNED) AS stock_bodega,
                CAST(IFNULL(tienda_m.meta_value, 0) AS UNSIGNED) AS stock_tienda,
                CAST(IFNULL(bodega_m.meta_value, 0) AS UNSIGNED)
                    + CAST(IFNULL(tienda_m.meta_value, 0) AS UNSIGNED) AS stock_total,
                IFNULL(envio_m.meta_value, 'bodega') AS envio_tipo
             {$from_sql}
             {$where_sql}
             ORDER BY {$order_sql}, p.ID ASC
             LIMIT %d OFFSET %d",
			$args['per_page'],
			$offset
		)
	);
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// KPI totals (computed from full unfiltered set for accuracy)
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin report totals must reflect current product stock.
	$totals = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT
            COUNT(p.ID)                                                                AS total_products,
            SUM(CAST(IFNULL(bodega_m.meta_value,0) AS UNSIGNED))                      AS sum_bodega,
            SUM(CAST(IFNULL(tienda_m.meta_value,0) AS UNSIGNED))                      AS sum_tienda,
            SUM(CASE WHEN CAST(IFNULL(bodega_m.meta_value,0) AS UNSIGNED) < %d THEN 1 ELSE 0 END) AS low_bodega_count,
            SUM(CASE WHEN CAST(IFNULL(bodega_m.meta_value,0) AS UNSIGNED) = 0 THEN 1 ELSE 0 END) AS zero_bodega_count
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} bodega_m ON p.ID = bodega_m.post_id AND bodega_m.meta_key = '_stock_bodega'
         LEFT JOIN {$wpdb->postmeta} tienda_m ON p.ID = tienda_m.post_id AND tienda_m.meta_key = '_stock_tienda'
         WHERE p.post_type = 'product' AND p.post_status = 'publish'",
			$threshold
		),
		ARRAY_A
	);
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$stock_url  = function ( array $overrides = array() ) use ( $args ): string {
		$query = array_merge(
			array(
				'page'         => 'bsc-reports',
				'tab'          => 'stock',
				'sort'         => $args['sort'],
				'dir'          => strtolower( $args['dir'] ),
				'stock_filter' => $args['filter'],
				'stock_search' => $args['search'],
				'per_page'     => $args['per_page'],
				'paged'        => $args['paged'],
			),
			$overrides
		);

		if ( empty( $query['stock_search'] ) ) {
			unset( $query['stock_search'] );
		}

		return add_query_arg( $query, admin_url( 'admin.php' ) );
	};
	$sort_link  = function ( string $col ) use ( $sort_col, $flip_dir, $stock_url ): string {
		$dir = ( $col === $sort_col ) ? $flip_dir : 'asc';
		return $stock_url(
			array(
				'sort'  => $col,
				'dir'   => $dir,
				'paged' => 1,
			)
		);
	};
	$sort_arrow = fn( string $col ): string => $col === $sort_col ? ( $sort_dir === 'ASC' ? '&uarr;' : '&darr;' ) : '';

	// Filter quick-links
	$filter_links      = array(
		'all'         => 'Todos',
		'low_bodega'  => "Bodega < {$threshold}",
		'low_tienda'  => "Showcase < {$threshold}",
		'zero_bodega' => 'Sin stock bodega',
		'zero_tienda' => 'Sin stock showcase',
	);
	$low_bodega_alert  = (int) ( $totals['low_bodega_count'] ?? 0 ) > 0;
	$zero_bodega_alert = (int) ( $totals['zero_bodega_count'] ?? 0 ) > 0;
	?>

	<!-- KPI summary -->
	<div class="bsc-kpi-grid">
		<div class="bsc-kpi-card">
			<div class="bsc-kpi-value"><?php echo esc_html( $totals['total_products'] ?? 0 ); ?></div>
			<div class="bsc-kpi-label">Productos</div>
		</div>
		<div class="bsc-kpi-card">
			<div class="bsc-kpi-value"><?php echo esc_html( $totals['sum_bodega'] ?? 0 ); ?></div>
			<div class="bsc-kpi-label">Total Bodega</div>
		</div>
		<div class="bsc-kpi-card">
			<div class="bsc-kpi-value"><?php echo esc_html( $totals['sum_tienda'] ?? 0 ); ?></div>
			<div class="bsc-kpi-label">Total Showcase</div>
		</div>
		<div class="bsc-kpi-card<?php echo esc_attr( $low_bodega_alert ? ' bsc-kpi-card--warning' : '' ); ?>">
			<div class="bsc-kpi-value<?php echo esc_attr( $low_bodega_alert ? ' bsc-kpi-value--warning' : '' ); ?>">
				<?php echo esc_html( $totals['low_bodega_count'] ?? 0 ); ?>
			</div>
			<div class="bsc-kpi-label">Stock bodega bajo</div>
		</div>
		<div class="bsc-kpi-card<?php echo esc_attr( $zero_bodega_alert ? ' bsc-kpi-card--danger' : '' ); ?>">
			<div class="bsc-kpi-value<?php echo esc_attr( $zero_bodega_alert ? ' bsc-kpi-value--danger' : '' ); ?>">
				<?php echo esc_html( $totals['zero_bodega_count'] ?? 0 ); ?>
			</div>
			<div class="bsc-kpi-label">Sin stock bodega</div>
		</div>
		<div class="bsc-kpi-card bsc-kpi-card--muted">
			<div class="bsc-kpi-value bsc-kpi-value--compact bsc-kpi-value--muted"><?php echo esc_html( $threshold ); ?></div>
			<div class="bsc-kpi-label">Umbral alerta</div>
		</div>
	</div>

	<!-- Filter + search bar -->
	<div class="bsc-admin-reports__stock-toolbar">
		<div class="bsc-admin-reports__stock-filter-links">
			<?php foreach ($filter_links as $fkey => $flabel) : ?>
				<?php
				$furl = $stock_url(
					array(
						'stock_filter' => $fkey,
						'paged'        => 1,
					)
				);
				?>
			<a href="<?php echo esc_url( $furl ); ?>"
				class="button bsc-admin-reports__stock-filter-button<?php echo esc_attr( $filter === $fkey ? ' button-primary' : '' ); ?>">
				<?php echo esc_html( $flabel ); ?>
			</a>
			<?php endforeach; ?>
		</div>
		<form method="get" class="bsc-admin-reports__stock-search-wrap" data-bsc-stock-search-form>
			<input type="hidden" name="page" value="bsc-reports">
			<input type="hidden" name="tab" value="stock">
			<input type="hidden" name="sort" value="<?php echo esc_attr( $sort_col ); ?>">
			<input type="hidden" name="dir" value="<?php echo esc_attr( strtolower( $sort_dir ) ); ?>">
			<input type="hidden" name="stock_filter" value="<?php echo esc_attr( $filter ); ?>">
			<label class="screen-reader-text" for="bsc-stock-search">Buscar producto o SKU</label>
			<input type="search" id="bsc-stock-search" name="stock_search" value="<?php echo esc_attr( $args['search'] ); ?>" placeholder="Buscar producto o SKU..." class="bsc-admin-reports__stock-search-input">
			<label class="screen-reader-text" for="bsc-stock-per-page">Productos por pagina</label>
			<select id="bsc-stock-per-page" name="per_page" class="bsc-admin-reports__stock-per-page">
				<?php foreach ( array( 25, 50, 100 ) as $per_page_option ) : ?>
					<option value="<?php echo esc_attr( $per_page_option ); ?>" <?php selected( $args['per_page'], $per_page_option ); ?>>
						<?php echo esc_html( $per_page_option ); ?> por pagina
					</option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button">Buscar</button>
			<?php if ( '' !== $args['search'] ) : ?>
				<a href="
				<?php
				echo esc_url(
					$stock_url(
						array(
							'stock_search' => '',
							'paged'        => 1,
						)
					)
				);
				?>
							" class="button">Limpiar</a>
			<?php endif; ?>
			<span id="bsc-stock-count" class="bsc-admin-reports__stock-count">
				<?php echo esc_html( sprintf( '%d resultado(s)', $filtered_total ) ); ?>
			</span>
		</form>
	</div>

	<!-- Legend -->
	<p class="bsc-admin-reports__legend">
		<span class="bsc-admin-reports__legend-chip bsc-admin-reports__legend-chip--danger">Sin stock</span>
		<span class="bsc-admin-reports__legend-chip bsc-admin-reports__legend-chip--warning">Un tipo vacío</span>
		<span class="bsc-admin-reports__legend-chip bsc-admin-reports__legend-chip--low">Stock bajo (< <?php echo esc_html( $threshold ); ?>)</span>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-settings' ) ); ?>" class="bsc-admin-reports__legend-link">Cambiar umbral →</a>
	</p>

	<?php if (empty( $products )) : ?>
	<p class="bsc-admin-reports__empty-state bsc-admin-reports__empty-state--stock">No hay productos que coincidan con el filtro.</p>
	<?php else : ?>
	<div class="bsc-admin-table-wrap bsc-admin-table-wrap--flush"><table id="bsc-stock-table" class="wp-list-table widefat fixed striped bsc-admin-reports__stock-table">
		<thead>
			<tr>
				<th class="bsc-admin-reports__stock-col-product">
					<a href="<?php echo esc_url( $sort_link( 'title' ) ); ?>">Producto <?php echo wp_kses_post( $sort_arrow( 'title' ) ); ?></a>
				</th>
				<th class="bsc-admin-reports__stock-col-sku">
					<a href="<?php echo esc_url( $sort_link( 'sku' ) ); ?>">SKU <?php echo wp_kses_post( $sort_arrow( 'sku' ) ); ?></a>
				</th>
				<th class="bsc-admin-reports__stock-col-center">
					<a href="<?php echo esc_url( $sort_link( 'bodega' ) ); ?>">Bodega <?php echo wp_kses_post( $sort_arrow( 'bodega' ) ); ?></a>
				</th>
				<th class="bsc-admin-reports__stock-col-center">
					<a href="<?php echo esc_url( $sort_link( 'tienda' ) ); ?>">Showcase <?php echo wp_kses_post( $sort_arrow( 'tienda' ) ); ?></a>
				</th>
				<th class="bsc-admin-reports__stock-col-total">
					<a href="<?php echo esc_url( $sort_link( 'total' ) ); ?>">Total <?php echo wp_kses_post( $sort_arrow( 'total' ) ); ?></a>
				</th>
				<th class="bsc-admin-reports__stock-col-dispatch">Despacho</th>
				<th class="bsc-admin-reports__stock-col-actions"></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ($products as $row) :
			$b            = (int) $row->stock_bodega;
			$t            = (int) $row->stock_tienda;
			$edit_url     = admin_url( 'admin.php?page=bsc-product-edit&id=' . $row->ID );
			$envio_labels = array(
				'bodega' =>'Bodega',
				'tienda' =>'Showcase',
				'ambos'  =>'Ambos',
			);
			?>
			<tr class="<?php echo esc_attr( bsc_reports_get_stock_row_class( $row, $threshold ) ); ?>">
				<td>
					<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $row->post_title ); ?></a>
				</td>
				<td class="bsc-admin-reports__table-cell bsc-admin-reports__table-cell--muted bsc-admin-reports__table-cell--small"><?php echo esc_html( $row->sku ); ?></td>
				<td class="<?php echo esc_attr( bsc_reports_get_stock_value_class( $b, $threshold ) ); ?>"><?php echo esc_html( $b ); ?></td>
				<td class="<?php echo esc_attr( bsc_reports_get_stock_value_class( $t, $threshold ) ); ?>"><?php echo esc_html( $t ); ?></td>
				<td class="bsc-admin-reports__table-cell bsc-admin-reports__table-cell--center bsc-admin-reports__table-cell--strong"><?php echo esc_html( (int) $row->stock_total ); ?></td>
				<td class="bsc-admin-reports__table-cell bsc-admin-reports__table-cell--center bsc-admin-reports__table-cell--small bsc-admin-reports__table-cell--dispatch">
					<?php echo esc_html( $envio_labels[ $row->envio_tipo ] ?? $row->envio_tipo ); ?>
				</td>
				<td>
					<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">Editar</a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<div class="bsc-admin-reports__pagination">
		<p class="bsc-admin-reports__footnote">
			<?php
			$first_item = $filtered_total > 0 ? $offset + 1 : 0;
			$last_item  = min( $filtered_total, $offset + count( $products ) );
			echo esc_html( sprintf( 'Mostrando %1$d-%2$d de %3$d producto(s).', $first_item, $last_item, $filtered_total ) );
			?>
		</p>
		<?php
		if ( $total_pages > 1 ) {
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%', $stock_url() ),
						'format'    => '',
						'current'   => $args['paged'],
						'total'     => $total_pages,
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
					)
				)
			);
		}
		?>
	</div>
	<?php endif; ?>
	<?php
}
