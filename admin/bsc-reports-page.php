<?php
/**
 * BSC-035: Analytics/reports page.
 * Metrics: total sales, order count, average ticket, top 5 products.
 * Results cached in transients (1 hour per date range).
 */
defined('ABSPATH') || exit;

function bsc_render_reports_page(): void {
    if ( ! current_user_can('manage_woocommerce') ) {
        wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
    }

    // ── Date range defaults: current month ─────────────────────────
    $date_start = isset( $_GET['date_start'] ) && $_GET['date_start']
        ? sanitize_text_field( $_GET['date_start'] )
        : gmdate('Y-m-01');
    $date_end = isset( $_GET['date_end'] ) && $_GET['date_end']
        ? sanitize_text_field( $_GET['date_end'] )
        : gmdate('Y-m-d');

    // ── Transient cache key ────────────────────────────────────────
    $cache_key = 'bsc_report_' . md5( $date_start . '_' . $date_end );
    $data      = get_transient( $cache_key );

    if ( false === $data ) {
        $orders = wc_get_orders([
            'status'      => [ 'completed', 'processing' ],
            'date_after'  => $date_start . ' 00:00:00',
            'date_before' => $date_end   . ' 23:59:59',
            'limit'       => -1,
        ]);

        $total_sales   = 0;
        $order_count   = count( $orders );
        $product_sales = [];

        foreach ( $orders as $order ) {
            $total_sales += (float) $order->get_total();

            foreach ( $order->get_items() as $item ) {
                $pid = $item->get_product_id();
                if ( ! isset( $product_sales[ $pid ] ) ) {
                    $product_sales[ $pid ] = [
                        'name' => $item->get_name(),
                        'qty'  => 0,
                        'total' => 0,
                    ];
                }
                $product_sales[ $pid ]['qty']   += $item->get_quantity();
                $product_sales[ $pid ]['total'] += (float) $item->get_total();
            }
        }

        // Sort by qty DESC, take top 5
        uasort( $product_sales, fn($a, $b) => $b['qty'] <=> $a['qty'] );
        $top_products = array_slice( $product_sales, 0, 5, true );

        $avg_ticket = $order_count > 0 ? $total_sales / $order_count : 0;

        $data = compact( 'total_sales', 'order_count', 'avg_ticket', 'top_products' );
        set_transient( $cache_key, $data, HOUR_IN_SECONDS );
    }

    [
        'total_sales'  => $total_sales,
        'order_count'  => $order_count,
        'avg_ticket'   => $avg_ticket,
        'top_products' => $top_products,
    ] = $data;
    ?>
    <div class="wrap bsc-admin-reports">
        <h1>Informes BSC</h1>

        <!-- ── Date filter ── -->
        <form method="get" style="margin:16px 0;display:flex;gap:10px;align-items:flex-end">
            <input type="hidden" name="page" value="bsc-reports">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:3px">Desde</label>
                <input type="date" name="date_start" value="<?php echo esc_attr($date_start); ?>" style="padding:5px">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:3px">Hasta</label>
                <input type="date" name="date_end" value="<?php echo esc_attr($date_end); ?>" style="padding:5px">
            </div>
            <button type="submit" class="button button-primary">Actualizar</button>
        </form>

        <!-- ── KPI Cards ── -->
        <div class="bsc-kpi-grid">
            <div class="bsc-kpi-card">
                <div class="bsc-kpi-value"><?php echo wp_kses_post( wc_price($total_sales) ); ?></div>
                <div class="bsc-kpi-label">Ventas totales</div>
            </div>
            <div class="bsc-kpi-card">
                <div class="bsc-kpi-value"><?php echo esc_html( $order_count ); ?></div>
                <div class="bsc-kpi-label">Pedidos</div>
            </div>
            <div class="bsc-kpi-card">
                <div class="bsc-kpi-value"><?php echo wp_kses_post( wc_price($avg_ticket) ); ?></div>
                <div class="bsc-kpi-label">Ticket promedio</div>
            </div>
        </div>

        <!-- ── Top products ── -->
        <h2 style="margin-top:2rem">Top 5 productos vendidos</h2>
        <?php if ( $top_products ) : ?>
        <table class="wp-list-table widefat fixed striped" style="max-width:640px">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Unidades</th>
                    <th>Total vendido</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ( $top_products as $pid => $p ) : ?>
                <tr>
                    <td><?php echo esc_html($rank++); ?></td>
                    <td>
                        <a href="<?php echo esc_url( get_edit_post_link($pid) ); ?>" target="_blank">
                            <?php echo esc_html($p['name']); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($p['qty']); ?></td>
                    <td><?php echo wp_kses_post( wc_price($p['total']) ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p>No hay ventas en el rango seleccionado.</p>
        <?php endif; ?>

        <p style="margin-top:1.5rem;color:#888;font-size:12px">
            * Solo pedidos con estado "Procesando" o "Completado". Resultados cacheados por 1 hora.
        </p>
    </div>

    <style>
        .bsc-kpi-grid { display:flex; gap:16px; flex-wrap:wrap; margin:16px 0; }
        .bsc-kpi-card { background:#fff; border:1px solid #ddd; border-radius:10px; padding:20px 28px; min-width:160px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
        .bsc-kpi-value { font-size:1.8rem; font-weight:800; color:#222; line-height:1.2; }
        .bsc-kpi-label { font-size:0.82rem; color:#888; margin-top:4px; font-weight:600; letter-spacing:0.5px; }
    </style>
    <?php
}
