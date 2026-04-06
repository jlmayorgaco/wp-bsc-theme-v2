<?php
/**
 * BSC-035 / BSC-063: Analytics/reports page.
 * Enhanced: date range, status filters, showroom vs web, category breakdown, CSV export.
 * Results cached in transients (1 hour per cache key).
 */
defined('ABSPATH') || exit;

// ── CSV Export ────────────────────────────────────────────────────────
add_action('admin_init', function() {
    if ( ! isset($_GET['page']) || $_GET['page'] !== 'bsc-reports' ) return;
    if ( ! isset($_GET['bsc_export_csv']) ) return;
    if ( ! current_user_can('manage_options') && ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');
    if ( ! wp_verify_nonce( sanitize_text_field($_GET['bsc_export_nonce'] ?? ''), 'bsc_reports_export' ) ) wp_die('Nonce inválido.');

    $date_start  = sanitize_text_field( $_GET['date_start'] ?? gmdate('Y-m-01') );
    $date_end    = sanitize_text_field( $_GET['date_end']   ?? gmdate('Y-m-d') );
    $statuses    = bsc_reports_get_statuses();

    $orders = wc_get_orders([
        'status'      => $statuses,
        'date_after'  => $date_start . ' 00:00:00',
        'date_before' => $date_end   . ' 23:59:59',
        'limit'       => -1,
    ]);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="bsc-informe-' . $date_start . '_' . $date_end . '.csv"');
    $out = fopen('php://output', 'w');
    // BOM UTF-8
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

function bsc_render_reports_page(): void {
    if ( ! current_user_can('manage_options') && ! current_user_can('manage_woocommerce') ) {
        wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
    }

    // ── Date range defaults ────────────────────────────────────────
    $date_start = sanitize_text_field( $_GET['date_start'] ?? gmdate('Y-m-01') );
    $date_end   = sanitize_text_field( $_GET['date_end']   ?? gmdate('Y-m-d') );
    $statuses   = bsc_reports_get_statuses();

    // Quick preset handler
    if ( isset($_GET['preset']) ) {
        switch ($_GET['preset']) {
            case 'hoy':    $date_start = $date_end = gmdate('Y-m-d'); break;
            case 'semana': $date_start = gmdate('Y-m-d', strtotime('monday this week')); $date_end = gmdate('Y-m-d'); break;
            case 'mes':    $date_start = gmdate('Y-m-01'); $date_end = gmdate('Y-m-d'); break;
            case 'mes_ant':$date_start = gmdate('Y-m-01', strtotime('first day of last month')); $date_end = gmdate('Y-m-t', strtotime('last month')); break;
            case 'anno':   $date_start = gmdate('Y-01-01'); $date_end = gmdate('Y-m-d'); break;
        }
    }

    // ── Cache ──────────────────────────────────────────────────────
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

        $total_sales   = 0;
        $order_count   = count($orders);
        $total_items   = 0;
        $web_sales     = 0;
        $showroom_sales = 0;
        $product_sales = [];
        $category_sales = [];

        foreach ($orders as $order) {
            $total = (float) $order->get_total();
            $total_sales += $total;
            $is_showroom  = (bool) $order->get_meta('_bsc_is_showroom_sale');
            if ($is_showroom) $showroom_sales += $total;
            else $web_sales += $total;

            foreach ($order->get_items() as $item) {
                $pid = $item->get_product_id();
                $qty = $item->get_quantity();
                $total_items += $qty;

                // Product aggregation
                if (!isset($product_sales[$pid])) {
                    $product_sales[$pid] = ['name' => $item->get_name(), 'qty' => 0, 'total' => 0];
                }
                $product_sales[$pid]['qty']   += $qty;
                $product_sales[$pid]['total'] += (float)$item->get_total();

                // Category aggregation
                $cats = get_the_terms($pid, 'product_cat');
                if ($cats && !is_wp_error($cats)) {
                    foreach ($cats as $cat) {
                        if (!isset($category_sales[$cat->term_id])) {
                            $category_sales[$cat->term_id] = ['name' => $cat->name, 'orders' => 0, 'total' => 0];
                        }
                        $category_sales[$cat->term_id]['total'] += (float)$item->get_total();
                    }
                }
            }

            // Category order count
            $seen_cats_this_order = [];
            foreach ($order->get_items() as $item) {
                $cats = get_the_terms($item->get_product_id(), 'product_cat');
                if ($cats && !is_wp_error($cats)) {
                    foreach ($cats as $cat) {
                        if (!in_array($cat->term_id, $seen_cats_this_order, true)) {
                            $category_sales[$cat->term_id]['orders'] = ($category_sales[$cat->term_id]['orders'] ?? 0) + 1;
                            $seen_cats_this_order[] = $cat->term_id;
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
        'page'             => 'bsc-reports',
        'bsc_export_csv'   => 1,
        'bsc_export_nonce' => $export_nonce,
        'date_start'       => $date_start,
        'date_end'         => $date_end,
        'statuses'         => $statuses,
    ], admin_url('admin.php'));
    ?>
    <div class="wrap bsc-admin-reports">
        <h1>Informes BSC</h1>

        <!-- Filters -->
        <form method="get" style="background:#f9f9f9;border:1px solid #ddd;border-radius:8px;padding:16px;margin:12px 0">
            <input type="hidden" name="page" value="bsc-reports">
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
                    <a href="<?php echo esc_url(add_query_arg(['page'=>'bsc-reports','preset'=>$k])); ?>" class="button"><?php echo esc_html($l); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <span style="color:#666;font-size:13px">
                <?php echo esc_html($date_start); ?> → <?php echo esc_html($date_end); ?>
                &nbsp;|&nbsp;
                <a href="<?php echo esc_url(add_query_arg('bsc_clear_report_cache','1')); ?>">↺ Limpiar caché</a>
            </span>
            <a href="<?php echo esc_url($export_url); ?>" class="button">⬇ Exportar CSV</a>
        </div>

        <!-- KPI Cards -->
        <div class="bsc-kpi-grid">
            <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post(wc_price($total_sales)); ?></div><div class="bsc-kpi-label">Ventas totales</div></div>
            <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo esc_html($order_count); ?></div><div class="bsc-kpi-label">Pedidos</div></div>
            <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post(wc_price($avg_ticket)); ?></div><div class="bsc-kpi-label">Ticket promedio</div></div>
            <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo esc_html($avg_items); ?></div><div class="bsc-kpi-label">Ítems / pedido</div></div>
            <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post(wc_price($web_sales)); ?></div><div class="bsc-kpi-label">Ventas Web</div></div>
            <div class="bsc-kpi-card"><div class="bsc-kpi-value"><?php echo wp_kses_post(wc_price($showroom_sales)); ?></div><div class="bsc-kpi-label">Ventas Showroom</div></div>
        </div>

        <!-- Tables -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px">

            <!-- Top products -->
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

            <!-- Category breakdown -->
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
    </div>

    <style>
        .bsc-kpi-grid { display:flex; gap:12px; flex-wrap:wrap; margin:16px 0; }
        .bsc-kpi-card { background:#fff; border:1px solid #ddd; border-radius:10px; padding:16px 20px; min-width:130px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
        .bsc-kpi-value { font-size:1.5rem; font-weight:800; color:#222; line-height:1.2; }
        .bsc-kpi-label { font-size:0.78rem; color:#888; margin-top:4px; font-weight:600; letter-spacing:0.5px; text-transform:uppercase; }
    </style>
    <?php
}
