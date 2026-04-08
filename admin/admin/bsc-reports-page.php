<?php
/**
 * BSC-024 / BSC-025: Informes page with extensible tab architecture.
 *
 * Tab registry: add future tabs to bsc_get_report_tabs() only.
 * Each tab is an isolated callback — no shared state.
 */
defined('ABSPATH') || exit;

// ── Tab registry (BSC-024) ─────────────────────────────────────────────
/**
 * To add a new tab: append an entry here and create its callback function.
 * Keys become ?tab=<key> query arg values.
 */
function bsc_get_report_tabs(): array {
    return [
        'ventas' => ['label' => 'Ventas',  'callback' => 'bsc_reports_tab_ventas'],
        'stock'  => ['label' => 'Stock',   'callback' => 'bsc_reports_tab_stock'],
        // Future: 'productos', 'clientes', 'main'...
    ];
}

// ── CSV export — only used by ventas tab ──────────────────────────────
add_action('admin_init', function() {
    if ( ! isset($_GET['page']) || $_GET['page'] !== 'bsc-reports' ) return;
    if ( ! isset($_GET['bsc_export_csv']) ) return;
    if ( ! current_user_can('manage_options') && ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');
    if ( ! wp_verify_nonce( sanitize_text_field($_GET['bsc_export_nonce'] ?? ''), 'bsc_reports_export' ) ) wp_die('Nonce inválido.');

    $date_start = sanitize_text_field( $_GET['date_start'] ?? gmdate('Y-m-01') );
    $date_end   = sanitize_text_field( $_GET['date_end']   ?? gmdate('Y-m-d') );
    $statuses   = bsc_reports_get_statuses();

    $orders = wc_get_orders([
        'status'      => $statuses,
        'date_after'  => $date_start . ' 00:00:00',
        'date_before' => $date_end   . ' 23:59:59',
        'limit'       => -1,
    ]);

    while ( ob_get_level() > 0 ) ob_end_clean();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="bsc-informe-' . $date_start . '_' . $date_end . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Fecha', 'Pedido #', 'Cliente', 'Email', 'Productos', 'Total', 'Estado', 'Canal']);

    foreach ($orders as $order) {
        $items_str = implode(' | ', array_map(fn($i) => $i->get_name() . ' x' . $i->get_quantity(), $order->get_items()));
        $canal     = $order->get_meta('_bsc_is_showroom_sale') ? 'Showroom' : 'Web';
        fputcsv($out, [
            $order->get_date_created()?->date('Y-m-d H:i') ?? '',
            '#' . $order->get_order_number(),
            $order->get_formatted_billing_full_name(),
            $order->get_billing_email(),
            $items_str,
            $order->get_total(),
            wc_get_order_status_name($order->get_status()),
            $canal,
        ]);
    }
    fclose($out);
    exit;
});

function bsc_reports_get_statuses(): array {
    $all = [ 'completed', 'processing', 'wc-preparing', 'wc-shipped' ];
    if ( ! isset($_GET['statuses']) ) return $all;
    $raw = (array) $_GET['statuses'];
    return array_filter($raw, fn($s) => in_array($s, $all, true));
}

// ── Main dispatcher (BSC-024) ──────────────────────────────────────────
function bsc_render_reports_page(): void {
    if ( ! current_user_can('manage_options') && ! current_user_can('manage_woocommerce') ) {
        wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
    }

    $tabs       = bsc_get_report_tabs();
    $active_tab = sanitize_key( $_GET['tab'] ?? 'ventas' );
    if ( ! isset($tabs[$active_tab]) ) $active_tab = 'ventas';

    echo '<div class="wrap bsc-admin-reports">';
    echo '<h1>Informes BSC</h1>';

    // WP-native nav tabs
    echo '<nav class="nav-tab-wrapper" style="margin-bottom:0">';
    foreach ( $tabs as $slug => $tab ) {
        $url   = esc_url( add_query_arg(['page' => 'bsc-reports', 'tab' => $slug], admin_url('admin.php')) );
        $class = $slug === $active_tab ? 'nav-tab nav-tab-active' : 'nav-tab';
        printf('<a href="%s" class="%s">%s</a>', $url, esc_attr($class), esc_html($tab['label']));
    }
    echo '</nav>';

    echo '<div class="bsc-tab-content" style="background:#fff;border:1px solid #c3c4c7;border-top:none;padding:20px 24px;margin-bottom:20px">';

    if ( is_callable($tabs[$active_tab]['callback']) ) {
        call_user_func( $tabs[$active_tab]['callback'] );
    } else {
        echo '<p style="color:#888">Tab no disponible.</p>';
    }

    echo '</div></div>';

    echo '<style>
        .bsc-kpi-grid  { display:flex; gap:12px; flex-wrap:wrap; margin:16px 0; }
        .bsc-kpi-card  { background:#f9f9f9; border:1px solid #ddd; border-radius:8px; padding:16px 20px; min-width:130px; text-align:center; }
        .bsc-kpi-value { font-size:1.5rem; font-weight:800; color:#222; line-height:1.2; }
        .bsc-kpi-label { font-size:0.75rem; color:#888; margin-top:4px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; }
    </style>';
}

// ── Tab: Ventas ────────────────────────────────────────────────────────
function bsc_reports_tab_ventas(): void {
    $date_start = sanitize_text_field( $_GET['date_start'] ?? gmdate('Y-m-01') );
    $date_end   = sanitize_text_field( $_GET['date_end']   ?? gmdate('Y-m-d') );
    $statuses   = bsc_reports_get_statuses();

    if ( isset($_GET['preset']) ) {
        switch ($_GET['preset']) {
            case 'hoy':     $date_start = $date_end = gmdate('Y-m-d'); break;
            case 'semana':  $date_start = gmdate('Y-m-d', strtotime('monday this week')); $date_end = gmdate('Y-m-d'); break;
            case 'mes':     $date_start = gmdate('Y-m-01'); $date_end = gmdate('Y-m-d'); break;
            case 'mes_ant': $date_start = gmdate('Y-m-01', strtotime('first day of last month')); $date_end = gmdate('Y-m-t', strtotime('last month')); break;
            case 'anno':    $date_start = gmdate('Y-01-01'); $date_end = gmdate('Y-m-d'); break;
        }
    }

    $cache_key = 'bsc_report_' . md5($date_start . '_' . $date_end . implode(',', $statuses));
    $data = get_transient($cache_key);

    if ( isset($_GET['bsc_clear_report_cache']) ) {
        delete_transient($cache_key);
        $data = false;
    }

    if ( false === $data ) {
        $orders = wc_get_orders([
            'status'      => $statuses,
            'date_after'  => $date_start . ' 00:00:00',
            'date_before' => $date_end   . ' 23:59:59',
            'limit'       => -1,
        ]);

        $total_sales    = 0; $order_count = count($orders);
        $total_items    = 0; $web_sales   = 0; $showroom_sales = 0;
        $product_sales  = []; $category_sales = [];

        foreach ($orders as $order) {
            $total = (float) $order->get_total();
            $total_sales += $total;
            $is_showroom  = (bool) $order->get_meta('_bsc_is_showroom_sale');
            if ($is_showroom) $showroom_sales += $total; else $web_sales += $total;

            foreach ($order->get_items() as $item) {
                $pid = $item->get_product_id();
                $qty = $item->get_quantity();
                $total_items += $qty;

                if (!isset($product_sales[$pid])) $product_sales[$pid] = ['name'=>$item->get_name(),'qty'=>0,'total'=>0];
                $product_sales[$pid]['qty']   += $qty;
                $product_sales[$pid]['total'] += (float) $item->get_total();

                $cats = get_the_terms($pid, 'product_cat');
                if ($cats && !is_wp_error($cats)) {
                    foreach ($cats as $cat) {
                        if (!isset($category_sales[$cat->term_id])) $category_sales[$cat->term_id] = ['name'=>$cat->name,'orders'=>0,'total'=>0];
                        $category_sales[$cat->term_id]['total'] += (float) $item->get_total();
                    }
                }
            }

            $seen_cats = [];
            foreach ($order->get_items() as $item) {
                $cats = get_the_terms($item->get_product_id(), 'product_cat');
                if ($cats && !is_wp_error($cats)) {
                    foreach ($cats as $cat) {
                        if (!in_array($cat->term_id, $seen_cats, true)) {
                            $category_sales[$cat->term_id]['orders'] = ($category_sales[$cat->term_id]['orders'] ?? 0) + 1;
                            $seen_cats[] = $cat->term_id;
                        }
                    }
                }
            }
        }

        uasort($product_sales, fn($a,$b) => $b['qty'] <=> $a['qty']);
        $top_products = array_slice($product_sales, 0, 10, true);
        uasort($category_sales, fn($a,$b) => $b['total'] <=> $a['total']);
        $avg_ticket = $order_count > 0 ? $total_sales / $order_count : 0;
        $avg_items  = $order_count > 0 ? round($total_items / $order_count, 1) : 0;

        $data = compact('total_sales','order_count','avg_ticket','avg_items','web_sales','showroom_sales','top_products','category_sales');
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
    }

    extract($data);
    $export_nonce = wp_create_nonce('bsc_reports_export');
    $export_url   = add_query_arg([
        'page'=>'bsc-reports','tab'=>'ventas','bsc_export_csv'=>1,
        'bsc_export_nonce'=>$export_nonce,'date_start'=>$date_start,
        'date_end'=>$date_end,'statuses'=>$statuses,
    ], admin_url('admin.php'));
    ?>

    <!-- Filters -->
    <form method="get" style="background:#f9f9f9;border:1px solid #ddd;border-radius:6px;padding:14px 16px;margin-bottom:16px">
        <input type="hidden" name="page"  value="bsc-reports">
        <input type="hidden" name="tab"   value="ventas">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:3px">Desde</label>
                <input type="date" name="date_start" value="<?php echo esc_attr($date_start); ?>" style="padding:5px">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:3px">Hasta</label>
                <input type="date" name="date_end" value="<?php echo esc_attr($date_end); ?>" style="padding:5px">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:3px">Estado</label>
                <div style="display:flex;gap:8px">
                    <?php foreach (['completed'=>'Completado','processing'=>'Procesando','wc-preparing'=>'Preparando','wc-shipped'=>'Enviado'] as $slug => $label): ?>
                    <label style="font-size:12px">
                        <input type="checkbox" name="statuses[]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug,$statuses)); ?>>
                        <?php echo esc_html($label); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <button type="submit" class="button button-primary">Aplicar</button>
                <?php foreach (['hoy'=>'Hoy','semana'=>'Semana','mes'=>'Este mes','mes_ant'=>'Mes ant.','anno'=>'Este año'] as $k=>$l): ?>
                <a href="<?php echo esc_url(add_query_arg(['page'=>'bsc-reports','tab'=>'ventas','preset'=>$k])); ?>" class="button"><?php echo esc_html($l); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </form>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="color:#666;font-size:13px">
            <?php echo esc_html($date_start); ?> → <?php echo esc_html($date_end); ?>
            &nbsp;|&nbsp;
            <a href="<?php echo esc_url(add_query_arg(['tab'=>'ventas','bsc_clear_report_cache'=>'1'])); ?>">↺ Limpiar caché</a>
        </span>
        <a href="<?php echo esc_url($export_url); ?>" class="button">⬇ Exportar CSV</a>
    </div>

    <div class="bsc-kpi-grid">
        <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post(wc_price($total_sales)); ?></div><div class="bsc-kpi-label">Ventas totales</div></div>
        <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo esc_html($order_count); ?></div><div class="bsc-kpi-label">Pedidos</div></div>
        <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post(wc_price($avg_ticket)); ?></div><div class="bsc-kpi-label">Ticket promedio</div></div>
        <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo esc_html($avg_items); ?></div><div class="bsc-kpi-label">Ítems / pedido</div></div>
        <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post(wc_price($web_sales)); ?></div><div class="bsc-kpi-label">Ventas Web</div></div>
        <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post(wc_price($showroom_sales)); ?></div><div class="bsc-kpi-label">Ventas Showroom</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px">
        <div>
            <h2 style="font-size:1rem;margin-bottom:8px">Top 10 productos vendidos</h2>
            <?php if ($top_products): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>#</th><th>Producto</th><th>Uds.</th><th>Total</th></tr></thead>
                <tbody>
                <?php $rank=1; foreach ($top_products as $pid => $p): ?>
                <tr>
                    <td><?php echo esc_html($rank++); ?></td>
                    <td><a href="<?php echo esc_url(get_edit_post_link($pid)); ?>" target="_blank"><?php echo esc_html($p['name']); ?></a></td>
                    <td><?php echo esc_html($p['qty']); ?></td>
                    <td><?php echo wp_kses_post(wc_price($p['total'])); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><p style="color:#888">Sin ventas en el período.</p><?php endif; ?>
        </div>
        <div>
            <h2 style="font-size:1rem;margin-bottom:8px">Desglose por categoría</h2>
            <?php if ($category_sales): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Categoría</th><th>Pedidos</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($category_sales, 0, 10, true) as $tid => $cat): ?>
                <tr>
                    <td><?php echo esc_html($cat['name']); ?></td>
                    <td><?php echo esc_html($cat['orders']); ?></td>
                    <td><?php echo wp_kses_post(wc_price($cat['total'])); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><p style="color:#888">Sin datos de categorías.</p><?php endif; ?>
        </div>
    </div>

    <p style="margin-top:1.5rem;color:#888;font-size:12px">Resultados cacheados por 1 hora. Use ↺ Limpiar caché para actualizar.</p>
    <?php
}

// ── Tab: Stock (BSC-025) ───────────────────────────────────────────────
function bsc_reports_tab_stock(): void {
    global $wpdb;

    $threshold = (int) get_option('bsc_low_stock_threshold', 3);

    // ── Sort params ──────────────────────────────────────────────────
    $valid_sorts = ['title', 'sku', 'bodega', 'tienda', 'total'];
    $sort_col    = in_array($_GET['sort'] ?? '', $valid_sorts, true)
        ? sanitize_key($_GET['sort']) : 'title';
    $sort_dir    = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
    $flip_dir    = $sort_dir === 'ASC' ? 'desc' : 'asc';

    $order_map = [
        'title'  => 'p.post_title',
        'sku'    => 'sku_m.meta_value',
        'bodega' => 'CAST(IFNULL(bodega_m.meta_value,0) AS UNSIGNED)',
        'tienda' => 'CAST(IFNULL(tienda_m.meta_value,0) AS UNSIGNED)',
        'total'  => '(CAST(IFNULL(bodega_m.meta_value,0) AS UNSIGNED) + CAST(IFNULL(tienda_m.meta_value,0) AS UNSIGNED))',
    ];
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared — validated above
    $order_sql = $order_map[$sort_col] . ' ' . $sort_dir;

    // ── Filter by status ─────────────────────────────────────────────
    $filter = sanitize_key($_GET['stock_filter'] ?? 'all');
    $valid_filters = ['all', 'low_bodega', 'low_tienda', 'zero_bodega', 'zero_tienda'];
    if (!in_array($filter, $valid_filters, true)) $filter = 'all';

    $having = '';
    switch ($filter) {
        case 'low_bodega':  $having = $wpdb->prepare("HAVING stock_bodega < %d", $threshold); break;
        case 'low_tienda':  $having = $wpdb->prepare("HAVING stock_tienda < %d", $threshold); break;
        case 'zero_bodega': $having = "HAVING stock_bodega = 0"; break;
        case 'zero_tienda': $having = "HAVING stock_tienda = 0"; break;
    }

    // ── Single query: all products + 4 meta keys ─────────────────────
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared — $order_sql and $having are validated above
    $products = $wpdb->get_results(
        "SELECT
            p.ID,
            p.post_title,
            IFNULL(sku_m.meta_value, '')                                              AS sku,
            CAST(IFNULL(bodega_m.meta_value, 0) AS UNSIGNED)                          AS stock_bodega,
            CAST(IFNULL(tienda_m.meta_value, 0) AS UNSIGNED)                          AS stock_tienda,
            CAST(IFNULL(bodega_m.meta_value, 0) AS UNSIGNED)
            + CAST(IFNULL(tienda_m.meta_value, 0) AS UNSIGNED)                        AS stock_total,
            IFNULL(envio_m.meta_value, 'bodega')                                      AS envio_tipo
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} sku_m    ON p.ID = sku_m.post_id    AND sku_m.meta_key    = '_sku'
         LEFT JOIN {$wpdb->postmeta} bodega_m ON p.ID = bodega_m.post_id AND bodega_m.meta_key = '_stock_bodega'
         LEFT JOIN {$wpdb->postmeta} tienda_m ON p.ID = tienda_m.post_id AND tienda_m.meta_key = '_stock_tienda'
         LEFT JOIN {$wpdb->postmeta} envio_m  ON p.ID = envio_m.post_id  AND envio_m.meta_key  = '_envio_tipo'
         WHERE p.post_type = 'product' AND p.post_status = 'publish'
         {$having}
         ORDER BY {$order_sql}, p.post_title ASC"
    );

    // ── KPI totals (computed from full unfiltered set for accuracy) ──
    $totals = $wpdb->get_row( $wpdb->prepare(
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
    ), ARRAY_A );

    // Helper: sort link URL
    $sort_link = function(string $col) use ($sort_col, $flip_dir, $sort_dir): string {
        $dir = ($col === $sort_col) ? $flip_dir : 'asc';
        return esc_url(add_query_arg(['page'=>'bsc-reports','tab'=>'stock','sort'=>$col,'dir'=>$dir,'stock_filter'=>$_GET['stock_filter']??'all'], admin_url('admin.php')));
    };
    $sort_arrow = fn(string $col): string => $col === $sort_col ? ($sort_dir==='ASC'?'↑':'↓') : '';

    // Helper: row background for low/zero stock
    $row_style = function(object $row) use ($threshold): string {
        $b = (int) $row->stock_bodega;
        $t = (int) $row->stock_tienda;
        if ($b === 0 && $t === 0) return 'background:#fff5f5;';
        if ($b === 0 || $t === 0) return 'background:#fffbeb;';
        if ($b < $threshold || $t < $threshold) return 'background:#fffff0;';
        return '';
    };

    // Filter quick-links
    $filter_links = [
        'all'          => 'Todos',
        'low_bodega'   => "Bodega < {$threshold}",
        'low_tienda'   => "Showcase < {$threshold}",
        'zero_bodega'  => 'Sin stock bodega',
        'zero_tienda'  => 'Sin stock showcase',
    ];
    ?>

    <!-- KPI summary -->
    <div class="bsc-kpi-grid">
        <div class="bsc-kpi-card">
            <div class="bsc-kpi-value"><?php echo esc_html($totals['total_products'] ?? 0); ?></div>
            <div class="bsc-kpi-label">Productos</div>
        </div>
        <div class="bsc-kpi-card">
            <div class="bsc-kpi-value"><?php echo esc_html($totals['sum_bodega'] ?? 0); ?></div>
            <div class="bsc-kpi-label">Total Bodega</div>
        </div>
        <div class="bsc-kpi-card">
            <div class="bsc-kpi-value"><?php echo esc_html($totals['sum_tienda'] ?? 0); ?></div>
            <div class="bsc-kpi-label">Total Showcase</div>
        </div>
        <div class="bsc-kpi-card" style="<?php echo ($totals['low_bodega_count'] ?? 0) > 0 ? 'border-color:#f6ad55;background:#fffaf0' : ''; ?>">
            <div class="bsc-kpi-value" style="<?php echo ($totals['low_bodega_count'] ?? 0) > 0 ? 'color:#c05621' : ''; ?>">
                <?php echo esc_html($totals['low_bodega_count'] ?? 0); ?>
            </div>
            <div class="bsc-kpi-label">Stock bodega bajo</div>
        </div>
        <div class="bsc-kpi-card" style="<?php echo ($totals['zero_bodega_count'] ?? 0) > 0 ? 'border-color:#fc8181;background:#fff5f5' : ''; ?>">
            <div class="bsc-kpi-value" style="<?php echo ($totals['zero_bodega_count'] ?? 0) > 0 ? 'color:#c53030' : ''; ?>">
                <?php echo esc_html($totals['zero_bodega_count'] ?? 0); ?>
            </div>
            <div class="bsc-kpi-label">Sin stock bodega</div>
        </div>
        <div class="bsc-kpi-card" style="color:#666">
            <div class="bsc-kpi-value" style="font-size:1rem;color:#555"><?php echo esc_html($threshold); ?></div>
            <div class="bsc-kpi-label">Umbral alerta</div>
        </div>
    </div>

    <!-- Filter + search bar -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin:12px 0 10px;flex-wrap:wrap;gap:8px">
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php foreach ($filter_links as $fkey => $flabel): ?>
            <?php $furl = esc_url(add_query_arg(['page'=>'bsc-reports','tab'=>'stock','sort'=>$sort_col,'dir'=>strtolower($sort_dir),'stock_filter'=>$fkey], admin_url('admin.php'))); ?>
            <a href="<?php echo $furl; ?>"
               class="button<?php echo $filter === $fkey ? ' button-primary' : ''; ?>"
               style="font-size:12px">
                <?php echo esc_html($flabel); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;align-items:center;gap:6px">
            <input type="text" id="bsc-stock-search" placeholder="Buscar producto o SKU…"
                   style="padding:5px 8px;border:1px solid #ccc;border-radius:3px;width:220px;font-size:13px">
            <span id="bsc-stock-count" style="font-size:12px;color:#888"></span>
        </div>
    </div>

    <!-- Legend -->
    <p style="font-size:11px;color:#888;margin-bottom:8px">
        <span style="background:#fff5f5;padding:2px 6px;border-radius:3px;margin-right:6px">Sin stock</span>
        <span style="background:#fffbeb;padding:2px 6px;border-radius:3px;margin-right:6px">Un tipo vacío</span>
        <span style="background:#fffff0;padding:2px 6px;border-radius:3px;margin-right:6px">Stock bajo (< <?php echo esc_html($threshold); ?>)</span>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-settings')); ?>" style="font-size:11px;color:#888">Cambiar umbral ↗</a>
    </p>

    <?php if (empty($products)): ?>
    <p style="color:#888;font-style:italic">No hay productos que coincidan con el filtro.</p>
    <?php else: ?>
    <table id="bsc-stock-table" class="wp-list-table widefat fixed striped" style="font-size:13px">
        <thead>
            <tr>
                <th style="width:35%">
                    <a href="<?php echo $sort_link('title'); ?>">Producto <?php echo $sort_arrow('title'); ?></a>
                </th>
                <th style="width:13%">
                    <a href="<?php echo $sort_link('sku'); ?>">SKU <?php echo $sort_arrow('sku'); ?></a>
                </th>
                <th style="width:13%;text-align:center">
                    <a href="<?php echo $sort_link('bodega'); ?>">Bodega <?php echo $sort_arrow('bodega'); ?></a>
                </th>
                <th style="width:13%;text-align:center">
                    <a href="<?php echo $sort_link('tienda'); ?>">Showcase <?php echo $sort_arrow('tienda'); ?></a>
                </th>
                <th style="width:10%;text-align:center">
                    <a href="<?php echo $sort_link('total'); ?>">Total <?php echo $sort_arrow('total'); ?></a>
                </th>
                <th style="width:10%;text-align:center">Despacho</th>
                <th style="width:6%"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $row):
            $b       = (int) $row->stock_bodega;
            $t       = (int) $row->stock_tienda;
            $edit_url = admin_url('admin.php?page=bsc-product-edit&id=' . $row->ID);
            $b_style = $b === 0 ? 'color:#c53030;font-weight:700' : ($b < $threshold ? 'color:#c05621;font-weight:600' : '');
            $t_style = $t === 0 ? 'color:#c53030;font-weight:700' : ($t < $threshold ? 'color:#c05621;font-weight:600' : '');
            $envio_labels = ['bodega'=>'Bodega','tienda'=>'Showcase','ambos'=>'Ambos'];
            ?>
            <tr style="<?php echo esc_attr($row_style($row)); ?>" class="bsc-stock-row">
                <td>
                    <a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($row->post_title); ?></a>
                </td>
                <td style="color:#666;font-size:12px"><?php echo esc_html($row->sku); ?></td>
                <td style="text-align:center;<?php echo esc_attr($b_style); ?>"><?php echo esc_html($b); ?></td>
                <td style="text-align:center;<?php echo esc_attr($t_style); ?>"><?php echo esc_html($t); ?></td>
                <td style="text-align:center;font-weight:600"><?php echo esc_html((int)$row->stock_total); ?></td>
                <td style="text-align:center;font-size:12px;color:#555">
                    <?php echo esc_html($envio_labels[$row->envio_tipo] ?? $row->envio_tipo); ?>
                </td>
                <td>
                    <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Editar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p style="color:#888;font-size:12px;margin-top:8px">
        <?php echo esc_html(count($products)); ?> producto(s) mostrados.
    </p>
    <?php endif; ?>

    <script>
    (function() {
        var searchInput = document.getElementById('bsc-stock-search');
        var countEl     = document.getElementById('bsc-stock-count');
        if (!searchInput) return;

        searchInput.addEventListener('input', function() {
            var q    = this.value.toLowerCase().trim();
            var rows = document.querySelectorAll('#bsc-stock-table .bsc-stock-row');
            var vis  = 0;
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                var show = !q || text.indexOf(q) !== -1;
                row.style.display = show ? '' : 'none';
                if (show) vis++;
            });
            countEl.textContent = q ? vis + ' resultado(s)' : '';
        });
    })();
    </script>
    <?php
}
