<?php
/**
 * BSC-031: Admin orders page render + AJAX handlers.
 * BSC-033: bsc_save_tracking → auto status 'shipped' + shipping email.
 * BSC-034: CSV export + packing print view via bulk actions.
 */
defined('ABSPATH') || exit;

require_once get_template_directory() . '/admin/class-bsc-orders-table.php';
require_once get_template_directory() . '/admin/class-bsc-order-labels.php';

// ── Enqueue admin JS only on BSC orders page ──────────────────────────
add_action( 'admin_enqueue_scripts', function ( string $hook ) {
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'bsc-orders' ) return;

    $js_path = get_template_directory() . '/js/bsc-admin-orders.js';
    wp_enqueue_script(
        'bsc-admin-orders',
        get_template_directory_uri() . '/js/bsc-admin-orders.js',
        [ 'jquery' ],
        file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
        true
    );
    wp_localize_script( 'bsc-admin-orders', 'bscOrders', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'bsc_admin_orders' ),
    ] );
} );

// ── BSC-034: handle bulk export/packing on admin_init ────────────────
// Form POSTs back to admin.php?page=bsc-orders (same page).
// admin_init fires after WooCommerce is ready but before any HTML output.
add_action( 'admin_init', 'bsc_handle_bulk_export' );
function bsc_handle_bulk_export(): void {
    // Only act on our form POST
    if ( ! isset( $_POST['bsc_bulk_action'], $_POST['bsc_export_nonce'] ) ) return;
    if ( ( sanitize_text_field( $_GET['page'] ?? '' ) ) !== 'bsc-orders' ) return;

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_export_nonce'] ) ), 'bsc_bulk_export' ) ) {
        wp_die( esc_html__( 'Nonce inválido.', 'bsc-2-0' ) );
    }
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_orders' ) ) {
        wp_die( esc_html__( 'Sin permisos.', 'bsc-2-0' ) );
    }

    $action    = sanitize_text_field( $_POST['bsc_bulk_action'] );
    $order_ids = array_map( 'absint', (array) ( $_POST['order_ids'] ?? [] ) );

    if ( empty( $order_ids ) ) return; // JS already prevents this, but safety guard

    if ( $action === 'export_csv' ) {
        bsc_export_orders_csv( $order_ids );
    } elseif ( $action === 'print_packing' ) {
        bsc_render_packing_view( $order_ids );
    } elseif ( $action === 'print_order_labels' ) {
        bsc_render_order_labels( $order_ids );
    }
}

// ── Status tabs config ─────────────────────────────────────────────────
function bsc_orders_status_tabs(): array {
    return [
        ''              => [ 'label' => 'Todos',          'statuses' => [] ],
        'wc-pending'    => [ 'label' => 'Pendiente',      'statuses' => [ 'pending', 'on-hold' ] ],
        'wc-processing' => [ 'label' => 'Recibido',       'statuses' => [ 'processing' ] ],
        'wc-preparing'  => [ 'label' => 'En preparación', 'statuses' => [ 'preparing' ] ],
        'wc-shipped'    => [ 'label' => 'Enviado',        'statuses' => [ 'shipped' ] ],
        'wc-completed'  => [ 'label' => 'Terminado',      'statuses' => [ 'completed' ] ],
        'wc-cancelled'  => [ 'label' => 'Cancelado',      'statuses' => [ 'cancelled', 'failed', 'refunded' ] ],
    ];
}

function bsc_normalize_order_status_slug( string $status ): string {
    return preg_replace( '/^wc-/', '', $status );
}

function bsc_normalize_order_statuses( array $statuses ): array {
    return array_values( array_filter( array_map( 'bsc_normalize_order_status_slug', $statuses ) ) );
}

function bsc_unarchived_orders_meta_query(): array {
    return [
        'relation' => 'OR',
        [
            'key'     => '_bsc_archived_at',
            'compare' => 'NOT EXISTS',
        ],
        [
            'key'     => '_bsc_archived_at',
            'value'   => '',
            'compare' => '=',
        ],
    ];
}

function bsc_count_orders_for_statuses( array $statuses ): int {
    $query_args = [
        'limit'    => 1,
        'paginate' => true,
        'return'   => 'ids',
        'meta_query' => bsc_unarchived_orders_meta_query(),
    ];

    if ( ! empty( $statuses ) ) {
        $query_args['status'] = bsc_normalize_order_statuses( $statuses );
    }

    $result = wc_get_orders( $query_args );

    return (int) ( $result->total ?? 0 );
}

// ── Page render ───────────────────────────────────────────────────────
function bsc_render_orders_page(): void {
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_orders' ) ) {
        wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
    }

    // Sanitize filters
    $date_start     = isset( $_GET['date_start'] )    ? sanitize_text_field( $_GET['date_start'] )    : '';
    $date_end       = isset( $_GET['date_end'] )      ? sanitize_text_field( $_GET['date_end'] )      : '';
    $search         = isset( $_GET['s'] )             ? sanitize_text_field( $_GET['s'] )             : '';
    $active_status  = isset( $_GET['order_status'] )  ? sanitize_text_field( $_GET['order_status'] )  : '';

    // Status tabs + counts
    $status_tabs = bsc_orders_status_tabs();
    $tab_counts  = [];
    $all_statuses = array_values(
        array_unique(
            array_merge(
                [ 'processing', 'on-hold', 'preparing', 'shipped', 'completed', 'cancelled', 'pending', 'failed', 'refunded' ],
                bsc_normalize_order_statuses( array_keys( BSC_Admin_Orders_Table::STATUS_OPTIONS ) )
            )
        )
    );

    foreach ( $status_tabs as $slug => $config ) {
        $statuses = $slug === '' ? $all_statuses : (array) ( $config['statuses'] ?? [] );
        $tab_counts[ $slug ] = bsc_count_orders_for_statuses( $statuses );
    }

    // Build query args
    $query_args = [];
    if ( $date_start )    $query_args['date_after']  = $date_start . ' 00:00:00';
    if ( $date_end )      $query_args['date_before'] = $date_end   . ' 23:59:59';
    if ( $search )        $query_args['s']           = $search;
    $query_args['meta_query'] = bsc_unarchived_orders_meta_query();
    if ( $active_status && isset( $status_tabs[ $active_status ] ) ) {
        $query_args['status'] = bsc_normalize_order_statuses( (array) $status_tabs[ $active_status ]['statuses'] );
    }

    $table = new BSC_Admin_Orders_Table( $query_args );
    $table->prepare_items();

    $base_url = admin_url( 'admin.php?page=bsc-orders' );
    ?>
    <div class="wrap bsc-admin-orders">
        <h1 class="wp-heading-inline">Pedidos BSC</h1>
        <hr class="wp-header-end">

        <!-- ── Status tabs ── -->
        <nav class="bsc-orders-tabs">
            <?php foreach ( $status_tabs as $slug => $config ) :
                $tab_url = $slug
                    ? add_query_arg( 'order_status', $slug, $base_url )
                    : $base_url;
                $is_active = ( $active_status === $slug );
                $count = $tab_counts[ $slug ] ?? 0;
            ?>
            <a href="<?php echo esc_url( $tab_url ); ?>"
               class="bsc-orders-tab<?php echo $is_active ? ' bsc-orders-tab--active' : ''; ?>">
                <?php echo esc_html( $config['label'] ); ?>
                <span class="bsc-tab-count"><?php echo esc_html( $count ); ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- ── Filters ── -->
        <form method="get" class="bsc-orders-filters">
            <input type="hidden" name="page" value="bsc-orders">
            <?php if ( $active_status ) : ?>
            <input type="hidden" name="order_status" value="<?php echo esc_attr( $active_status ); ?>">
            <?php endif; ?>
            <div>
                <label>Desde</label>
                <input type="date" name="date_start" value="<?php echo esc_attr( $date_start ); ?>">
            </div>
            <div>
                <label>Hasta</label>
                <input type="date" name="date_end" value="<?php echo esc_attr( $date_end ); ?>">
            </div>
            <div>
                <label>Buscar</label>
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
                       placeholder="Nombre, email o # de orden" style="width:220px">
            </div>
            <button type="submit" class="button button-primary">Filtrar</button>
            <?php if ( $date_start || $date_end || $search ) : ?>
                <a href="<?php echo esc_url( $active_status ? add_query_arg('order_status', $active_status, $base_url) : $base_url ); ?>" class="button">Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- ── Table with bulk export form ── -->
        <!-- Posts back to this same page; bsc_handle_bulk_export() intercepts in admin_init -->
        <form method="post" id="bsc-orders-form"
              action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-orders' ) ); ?>">
            <?php wp_nonce_field( 'bsc_bulk_export', 'bsc_export_nonce' ); ?>
            <div style="margin-bottom:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <button type="submit" name="bsc_bulk_action" value="export_csv" class="button" id="bsc-csv-btn">
                    ⬇ Exportar CSV
                </button>
                <button type="submit" name="bsc_bulk_action" value="print_packing" class="button" id="bsc-packing-btn">
                    🖨 Vista de empaque
                </button>
                <button type="submit" name="bsc_bulk_action" value="print_order_labels" class="button button-primary" id="bsc-labels-btn">
                    🏷 Imprimir con datos (PDF)
                </button>
                <span id="bsc-bulk-msg" style="display:none;color:#c0392b;font-size:13px;margin-left:6px">
                    Selecciona al menos un pedido primero.
                </span>
            </div>
            <?php $table->display(); ?>
        </form>

        <script>
        (function($){
            function requireSelection(e) {
                if ($('input[name="order_ids[]"]:checked').length === 0) {
                    e.preventDefault();
                    $('#bsc-bulk-msg').stop(true).fadeIn(150).delay(3000).fadeOut(400);
                    return false;
                }
                return true;
            }

            // Vista de empaque → open in new tab so orders page stays visible
            $('#bsc-packing-btn').on('click', function(e) {
                if (!requireSelection(e)) return;
                $('#bsc-orders-form').attr('target', '_blank');
                setTimeout(function() { $('#bsc-orders-form').removeAttr('target'); }, 300);
            });

            // Imprimir con datos (PDF) → open in new tab
            $('#bsc-labels-btn').on('click', function(e) {
                if (!requireSelection(e)) return;
                $('#bsc-orders-form').attr('target', '_blank');
                setTimeout(function() { $('#bsc-orders-form').removeAttr('target'); }, 300);
            });

            // CSV → same tab (browser downloads file, page stays)
            $('#bsc-csv-btn').on('click', function(e) {
                if (!requireSelection(e)) return;
                $('#bsc-orders-form').removeAttr('target');
            });
        })(jQuery);
        </script>
    </div>

    <style>
        /* ── Status tabs ── */
        .bsc-orders-tabs { display:flex; gap:2px; flex-wrap:wrap; margin:16px 0 0; border-bottom:2px solid #ddd; }
        .bsc-orders-tab {
            display:inline-flex; align-items:center; gap:6px;
            padding:8px 14px; font-size:13px; color:#555; text-decoration:none;
            border:1px solid transparent; border-bottom:none; border-radius:4px 4px 0 0;
            margin-bottom:-2px; background:#f9f9f9;
        }
        .bsc-orders-tab:hover { background:#fff; color:#333; }
        .bsc-orders-tab--active { background:#fff; color:#000; font-weight:600; border-color:#ddd; border-bottom-color:#fff; }
        .bsc-tab-count { background:#e1e1e1; color:#555; border-radius:10px; padding:1px 7px; font-size:11px; font-weight:700; }
        .bsc-orders-tab--active .bsc-tab-count { background:#2271b1; color:#fff; }

        /* ── Filters row ── */
        .bsc-orders-filters { margin:12px 0 16px; display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; }
        .bsc-orders-filters label { display:block; font-size:11px; font-weight:600; margin-bottom:3px; text-transform:uppercase; letter-spacing:.3px; color:#555; }
        .bsc-orders-filters input[type=date], .bsc-orders-filters input[type=search] { padding:5px; }

        /* ── Table ── */
        .bsc-admin-orders .wp-list-table { font-size:13px; }
        .bsc-admin-orders .column-tracking input { margin-bottom:4px; }
        .bsc-admin-orders .column-city { width:90px; }
        .bsc-admin-orders .column-status { width:170px; }
    </style>
    <?php
}

// ── BSC-031: AJAX — update order status ──────────────────────────────
add_action( 'wp_ajax_bsc_update_order_status', 'bsc_ajax_update_order_status' );
function bsc_ajax_update_order_status(): void {
    check_ajax_referer( 'bsc_admin_orders', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_orders' ) ) wp_send_json_error( ['message' => 'Sin permisos'] );

    $order_id = absint( $_POST['order_id'] ?? 0 );
    $status   = sanitize_text_field( $_POST['status'] ?? '' );
    $allowed_statuses = array_map( 'bsc_normalize_order_status_slug', array_keys( BSC_Admin_Orders_Table::STATUS_OPTIONS ) );
    $status = bsc_normalize_order_status_slug( $status );

    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( ['message' => 'Pedido no encontrado'] );
    if ( ! in_array( $status, $allowed_statuses, true ) ) wp_send_json_error( ['message' => 'Estado inválido'] );

    $order->update_status( $status, 'Estado actualizado desde BSC Admin.' );
    wp_send_json_success( ['message' => 'Estado actualizado', 'status' => $status] );
}

// ── BSC-031+033: AJAX — save tracking code/link ───────────────────────
add_action( 'wp_ajax_bsc_save_tracking', 'bsc_ajax_save_tracking' );
function bsc_ajax_save_tracking(): void {
    check_ajax_referer( 'bsc_admin_orders', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_orders' ) ) wp_send_json_error( ['message' => 'Sin permisos'] );

    $order_id      = absint( $_POST['order_id'] ?? 0 );
    $tracking_code = sanitize_text_field( $_POST['tracking_code'] ?? '' );
    $tracking_link = esc_url_raw( $_POST['tracking_link'] ?? '' );

    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( ['message' => 'Pedido no encontrado'] );

    update_post_meta( $order_id, '_bsc_tracking_code', $tracking_code );
    update_post_meta( $order_id, '_bsc_tracking_link', $tracking_link );

    // BSC-033: auto-change status to "shipped" and send email
    if ( $tracking_code && $order->get_status() !== 'shipped' ) {
        $order->update_status( 'shipped', 'Guía ingresada desde BSC Admin.' );

        $email_error = bsc_send_shipping_email( $order_id );
        if ( $email_error ) {
            error_log( 'BSC: error al enviar email de envío — ' . $email_error );
        }
    }

    wp_send_json_success( ['message' => 'Tracking guardado'] );
}

// ── BSC-033: Send shipping notification email ─────────────────────────
function bsc_send_shipping_email( int $order_id ): string {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return 'Pedido no encontrado';

    $to    = $order->get_billing_email();
    if ( ! $to ) return 'Sin email del cliente';

    $tracking_code = get_post_meta( $order_id, '_bsc_tracking_code', true );
    $tracking_link = get_post_meta( $order_id, '_bsc_tracking_link', true );

    $subject = sprintf( 'Tu pedido #%s está en camino 🚚', $order->get_order_number() );

    ob_start();
    include get_template_directory() . '/emails/bsc-order-shipped.php';
    $message = ob_get_clean();

    $headers = function_exists( 'bsc_get_email_headers' )
        ? bsc_get_email_headers()
        : [ 'Content-Type: text/html; charset=UTF-8' ];

    $sent = wp_mail( $to, $subject, $message, $headers );

    return $sent ? '' : 'wp_mail() devolvió false';
}

// ── BSC-034: CSV export ───────────────────────────────────────────────
function bsc_export_orders_csv( array $order_ids ): void {
    // Clear any WP output buffers so headers can be sent cleanly
    while ( ob_get_level() > 0 ) {
        ob_end_clean();
    }

    // UTF-8 BOM for Excel compatibility
    $bom = "\xEF\xBB\xBF";

    header( 'Content-Type: text/csv; charset=UTF-8' );
    header( 'Content-Disposition: attachment; filename="pedidos-' . gmdate('Y-m-d') . '.csv"' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );

    $out = fopen( 'php://output', 'w' );
    fwrite( $out, $bom );

    fputcsv( $out, [ 'ID', 'Fecha', 'Cliente', 'Email', 'Teléfono', 'Ciudad', 'Dirección', 'Productos', 'Total', 'Estado' ] );

    foreach ( $order_ids as $id ) {
        $order = wc_get_order( $id );
        if ( ! $order ) continue;

        $items = [];
        foreach ( $order->get_items() as $item ) {
            $items[] = $item->get_quantity() . '× ' . $item->get_name();
        }

        fputcsv( $out, [
            $order->get_order_number(),
            $order->get_date_created() ? $order->get_date_created()->date('d/m/Y H:i') : '',
            trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
            $order->get_billing_email(),
            $order->get_billing_phone(),
            $order->get_shipping_city() ?: $order->get_billing_city(),
            trim( $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() ) ?: $order->get_billing_address_1(),
            implode( ' | ', $items ),
            $order->get_total(),
            wc_get_order_status_name( $order->get_status() ),
        ] );
    }

    fclose( $out );
    exit;
}

// ── BSC-034: Packing print view ───────────────────────────────────────
function bsc_render_packing_view( array $order_ids ): void {
    // Clear any WP output buffers so we control the full response
    while ( ob_get_level() > 0 ) {
        ob_end_clean();
    }
    header( 'Content-Type: text/html; charset=UTF-8' );
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Vista de Empaque — BSC</title>
        <style>
            * { box-sizing: border-box; }
            body { font-family: Arial, sans-serif; font-size: 13px; color: #222; margin: 0; padding: 16px; }
            h1 { font-size: 18px; margin: 0 0 16px; }
            .order-card { border: 1px solid #ccc; border-radius: 8px; padding: 16px; margin-bottom: 20px; page-break-inside: avoid; }
            .order-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
            .order-number { font-size: 16px; font-weight: bold; }
            .order-status { background: #FFB6C1; color: #000; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
            .order-details { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #eee; font-size: 12px; }
            th { background: #f5f5f5; font-weight: bold; }
            .total-row td { font-weight: bold; border-top: 2px solid #ccc; }
            @media print {
                body { padding: 8px; }
                .no-print { display: none; }
                .order-card { border: 1px solid #999; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom:16px">
            <button onclick="window.print()" style="padding:8px 16px;cursor:pointer">🖨 Imprimir</button>
            <button onclick="window.close()" style="padding:8px 16px;margin-left:8px;cursor:pointer">✕ Cerrar</button>
        </div>
        <h1>Vista de Empaque — <?php echo esc_html( gmdate('d/m/Y') ); ?></h1>

        <?php foreach ( $order_ids as $id ) :
            $order = wc_get_order( $id );
            if ( ! $order ) continue;
            $name = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() )
                 ?: trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
            $city    = $order->get_shipping_city() ?: $order->get_billing_city();
            $address = trim( $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() )
                    ?: $order->get_billing_address_1();
        ?>
        <div class="order-card">
            <div class="order-header">
                <span class="order-number">#<?php echo esc_html( $order->get_order_number() ); ?></span>
                <span class="order-status"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
            </div>
            <div class="order-details">
                <div><strong>Cliente:</strong> <?php echo esc_html( $name ); ?></div>
                <div><strong>Teléfono:</strong> <?php echo esc_html( $order->get_billing_phone() ); ?></div>
                <div><strong>Ciudad:</strong> <?php echo esc_html( $city ); ?></div>
                <div><strong>Fecha:</strong> <?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date('d/m/Y') : '' ); ?></div>
                <div style="grid-column:span 2"><strong>Dirección:</strong> <?php echo esc_html( $address ); ?></div>
            </div>
            <table>
                <thead>
                    <tr><th>Producto</th><th>SKU</th><th>Qty</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach ( $order->get_items() as $item ) :
                        $product = $item->get_product();
                        $sku     = $product ? $product->get_sku() : '';
                    ?>
                    <tr>
                        <td><?php echo esc_html( $item->get_name() ); ?></td>
                        <td><?php echo esc_html( $sku ); ?></td>
                        <td><?php echo esc_html( $item->get_quantity() ); ?></td>
                        <td><?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3">Total</td>
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
