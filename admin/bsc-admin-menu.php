<?php
/**
 * BSC-030: Custom admin menu for BSC store management.
 * Visibility is controlled by WP capability checks on each add_submenu_page() call.
 * For operational roles (bsc_operator / bsc_employee), the native WC menus are hidden.
 */
defined('ABSPATH') || exit;

require_once get_template_directory() . '/admin/bsc-admin-ui.php';

// Register BSC menu pages
add_action( 'admin_menu', 'bsc_add_admin_menu' );

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_dashboard_assets' );

function bsc_enqueue_dashboard_assets( string $hook ): void {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( ! in_array( $page, [ 'bsc-dashboard', 'bsc-settings' ], true ) ) {
        return;
    }

    $css_path = get_template_directory() . '/admin/bsc-admin-dashboard.css';

    wp_enqueue_style(
        'bsc-admin-dashboard',
        get_template_directory_uri() . '/admin/bsc-admin-dashboard.css',
        array(),
        file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
    );
}

function bsc_dashboard_count_orders( array $statuses, array $extra_args = [] ): int {
    $query_args = array_merge(
        [
            'limit'    => 1,
            'paginate' => true,
            'return'   => 'ids',
        ],
        $extra_args
    );

    if ( ! empty( $statuses ) ) {
        $query_args['status'] = $statuses;
    }

    $result = wc_get_orders( $query_args );
    return (int) ( $result->total ?? 0 );
}

function bsc_dashboard_user_can_view_financials(): bool {
    return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
}

function bsc_dashboard_range_options(): array {
    return [
        'today'  => 'Hoy',
        '7d'     => 'Ultimos 7 dias',
        '30d'    => 'Ultimos 30 dias',
        'custom' => 'Personalizado',
    ];
}

function bsc_dashboard_parse_date_input( string $value ): string {
    $value = sanitize_text_field( $value );
    return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
}

function bsc_dashboard_get_range(): array {
    $range_key = isset( $_GET['dashboard_range'] ) ? sanitize_key( wp_unslash( $_GET['dashboard_range'] ) ) : 'today';
    $options   = bsc_dashboard_range_options();

    if ( ! array_key_exists( $range_key, $options ) ) {
        $range_key = 'today';
    }

    $now        = current_time( 'timestamp' );
    $today      = wp_date( 'Y-m-d', $now );
    $start_date = $today;
    $end_date   = $today;

    if ( '7d' === $range_key ) {
        $start_date = wp_date( 'Y-m-d', strtotime( '-6 days', $now ) );
    } elseif ( '30d' === $range_key ) {
        $start_date = wp_date( 'Y-m-d', strtotime( '-29 days', $now ) );
    } elseif ( 'custom' === $range_key ) {
        $custom_start = isset( $_GET['dashboard_start'] ) ? bsc_dashboard_parse_date_input( wp_unslash( $_GET['dashboard_start'] ) ) : '';
        $custom_end   = isset( $_GET['dashboard_end'] ) ? bsc_dashboard_parse_date_input( wp_unslash( $_GET['dashboard_end'] ) ) : '';

        if ( $custom_start && $custom_end && $custom_start <= $custom_end ) {
            $start_date = $custom_start;
            $end_date   = $custom_end;
        } else {
            $range_key = 'today';
        }
    }

    return [
        'key'        => $range_key,
        'label'      => $options[ $range_key ],
        'start_date' => $start_date,
        'end_date'   => $end_date,
        'date_after' => $start_date . ' 00:00:00',
        'date_before'=> $end_date . ' 23:59:59',
        'cache_key'  => $range_key . '_' . $start_date . '_' . $end_date,
    ];
}

function bsc_dashboard_quick_links(): array {
    $links = [
        [ 'page' => 'bsc-orders', 'label' => 'Pedidos' ],
        [ 'page' => 'bsc-products', 'label' => 'Productos' ],
        [ 'page' => 'bsc-showroom', 'label' => 'Showcase' ],
        [ 'page' => 'bsc-creators', 'label' => 'Creators' ],
        [ 'page' => 'bsc-newsletter', 'label' => 'Newsletter' ],
        [ 'page' => 'bsc-monitoring', 'label' => 'Monitoreo' ],
        [ 'page' => 'bsc-reports', 'label' => 'Informes' ],
    ];

    return array_values(
        array_filter(
            $links,
            static fn( array $link ): bool => bsc_current_user_has_bsc_page_access( $link['page'] )
        )
    );
}

function bsc_dashboard_format_percent( float $value ): string {
    return number_format_i18n( $value, 1 ) . '%';
}

function bsc_dashboard_empty_metrics(): array {
    return [
        'counters'           => function_exists( 'bsc_metrics_empty_counters' ) ? bsc_metrics_empty_counters() : [],
        'top_products'       => [],
        'no_result_searches' => [],
        'abandoned_snapshot' => [
            'active'    => 0,
            'reminded'  => 0,
            'recovered' => 0,
            'converted' => 0,
        ],
    ];
}

function bsc_add_admin_menu(): void {
    if ( ! bsc_current_user_has_any_bsc_page_access() ) {
        return;
    }

    // Top-level BSC entry for authorized BSC admin users.
    add_menu_page(
        __( 'BSC Dashboard', 'bsc-2-0' ),
        'BSC',
        'read',
        'bsc-dashboard',
        'bsc_render_dashboard',
        'dashicons-store',
        3
    );

    // BSC-021: Ordered submenu list

    // 1. Pedidos - operator + employee + admin
    if ( bsc_current_user_has_bsc_page_access( 'bsc-orders' ) ) {
        add_submenu_page(
            'bsc-dashboard',
            __( 'Pedidos BSC', 'bsc-2-0' ),
            __( 'Pedidos', 'bsc-2-0' ),
            'read',
            'bsc-orders',
            'bsc_render_orders_page'
        );
    }

    // 2. Productos - admin + employee
    if ( bsc_current_user_has_bsc_page_access( 'bsc-products' ) ) {
        add_submenu_page(
            'bsc-dashboard',
            __( 'Productos BSC', 'bsc-2-0' ),
            __( 'Productos', 'bsc-2-0' ),
            'read',
            'bsc-products',
            'bsc_render_products_page'
        );
    }

    // 3. Informes - admin only
    if ( bsc_current_user_has_bsc_page_access( 'bsc-reports' ) ) {
        add_submenu_page(
            'bsc-dashboard',
            __( 'Informes BSC', 'bsc-2-0' ),
            __( 'Informes', 'bsc-2-0' ),
            'read',
            'bsc-reports',
            'bsc_render_reports_page'
        );
    }

    // 4. Showcase (Venta Presencial) - operator + employee + admin
    if ( bsc_current_user_has_bsc_page_access( 'bsc-showroom' ) ) {
        add_submenu_page(
            'bsc-dashboard',
            __( 'Venta Presencial', 'bsc-2-0' ),
            __( 'Showcase', 'bsc-2-0' ),
            'read',
            'bsc-showroom',
            'bsc_render_showroom_page'
        );
    }

    // 5. Home Favorites - admin only
    add_submenu_page(
        'bsc-dashboard',
        __( 'Home Favorites', 'bsc-2-0' ),
        __( 'Home Favorites', 'bsc-2-0' ),
        'manage_options',
        'bsc-home-favorites',
        'bsc_home_favorites_settings_page'  // defined in scripts/script_custom_types.php
    );

    // 6. Hero Slides - links to CPT list (show_in_menu=false on the CPT keeps this clean)
    add_submenu_page(
        'bsc-dashboard',
        __( 'Hero Slides', 'bsc-2-0' ),
        __( 'Hero Slides', 'bsc-2-0' ),
        'manage_options',
        'edit.php?post_type=home_slide',
        ''
    );

    // 7. Bubble Points - callbacks defined in plugins/bubble-points/admin/admin-menu.php
    add_submenu_page(
        'bsc-dashboard',
        __( 'Bubble Points', 'bsc-2-0' ),
        __( 'Bubble Points', 'bsc-2-0' ),
        'manage_woocommerce',
        'bsc-bubble-points',
        'bsc_bp_render_admin_screen'
    );

    // 8. Cupones - WC coupons management
    if ( bsc_current_user_has_bsc_page_access( 'bsc-coupons' ) ) {
        add_submenu_page(
            'bsc-dashboard',
            __( 'Cupones BSC', 'bsc-2-0' ),
            __( 'Cupones', 'bsc-2-0' ),
            'read',
            'bsc-coupons',
            'bsc_render_coupons_page'
        );
    }

    // 9. Bubble Creators - admin / shop manager
    add_submenu_page(
        'bsc-dashboard',
        __( 'Bubble Creators', 'bsc-2-0' ),
        __( 'Creators', 'bsc-2-0' ),
        'manage_woocommerce',
        'bsc-creators',
        'bsc_render_creators_page'
    );

    // 10. Newsletter leads - admin / shop manager
    add_submenu_page(
        'bsc-dashboard',
        __( 'Newsletter BSC', 'bsc-2-0' ),
        __( 'Newsletter', 'bsc-2-0' ),
        'manage_woocommerce',
        'bsc-newsletter',
        'bsc_render_newsletter_page'
    );

    // 11. Emails - admin only
    add_submenu_page(
        'bsc-dashboard',
        __( 'Emails BSC', 'bsc-2-0' ),
        __( 'Emails', 'bsc-2-0' ),
        'manage_options',
        'bsc-followup-emails',
        'bsc_render_followup_emails_page'
    );

    // 12. Monitoreo post-launch - admin only
    add_submenu_page(
        'bsc-dashboard',
        __( 'Monitoreo BSC', 'bsc-2-0' ),
        __( 'Monitoreo', 'bsc-2-0' ),
        'manage_options',
        'bsc-monitoring',
        'bsc_render_monitoring_page'
    );

    // 13. Control de Acceso - role x page matrix
    add_submenu_page(
        'bsc-dashboard',
        __( 'Control de Acceso', 'bsc-2-0' ),
        __( 'Acceso', 'bsc-2-0' ),
        'manage_options',
        'bsc-access',
        'bsc_render_access_page'
    );

    // 14. Configuracion - admin only (always last)
    add_submenu_page(
        'bsc-dashboard',
        __( 'Configuración BSC', 'bsc-2-0' ),
        __( 'Configuración', 'bsc-2-0' ),
        'manage_options',
        'bsc-settings',
        'bsc_render_settings_page'
    );

    // Hidden pages (no sidebar entry, accessible via direct URL)
    // Product editor - reachable from Productos table
    if ( bsc_current_user_has_bsc_page_access( 'bsc-product-edit' ) ) {
        add_submenu_page(
            null,
            __( 'Editar Producto — BSC', 'bsc-2-0' ),
            __( 'Editar Producto', 'bsc-2-0' ),
            'read',
            'bsc-product-edit',
            'bsc_render_product_edit_page'
        );
    }

    // Bubble Points settings stub
    add_submenu_page(
        null,
        __( 'Bubble Points — Ajustes', 'bsc-2-0' ),
        __( 'Bubble Points Ajustes', 'bsc-2-0' ),
        'manage_woocommerce',
        'bsc-bp-settings',
        'bsc_bp_render_settings_screen'
    );
}

// Hide native WP/WC menus for operational roles
add_action( 'admin_menu', 'bsc_restrict_admin_menus', 999 );

function bsc_restrict_admin_menus(): void {
    $user  = wp_get_current_user();
    $roles = (array) $user->roles;

    // BSC-066: applies to both bsc_operator and bsc_employee
    $is_restricted = in_array( 'bsc_operator', $roles, true )
                  || in_array( 'bsc_employee',  $roles, true );

    if ( ! $is_restricted ) return;

    // Native WP/WC menus to hide for operational roles
    $hide = [
        'index.php',                    // Dashboard WP
        'edit.php',                     // Posts
        'upload.php',                   // Media
        'edit.php?post_type=page',      // Pages
        'edit-comments.php',            // Comments
        'themes.php',                   // Appearance
        'plugins.php',                  // Plugins
        'users.php',                    // Users
        'tools.php',                    // Tools
        'options-general.php',          // Settings
        'woocommerce',                     // WooCommerce native
        'edit.php?post_type=product',      // WC Products native
        'edit.php?post_type=shop_order',   // WC Orders native
        'edit.php?post_type=home_slide',   // BSC CPT (now under BSC menu)
    ];

    foreach ( $hide as $slug ) {
        remove_menu_page( $slug );
    }
}

// Include page-specific implementations
require_once get_template_directory() . '/admin/bsc-orders-page.php';
require_once get_template_directory() . '/admin/bsc-reports-page.php';
require_once get_template_directory() . '/admin/bsc-showroom-page.php';
require_once get_template_directory() . '/admin/bsc-products-page.php';      // BSC-062
require_once get_template_directory() . '/admin/bsc-product-edit-page.php';  // BSC-065
require_once get_template_directory() . '/admin/bsc-coupons-page.php';       // BSC-066
require_once get_template_directory() . '/admin/bsc-creators-page.php';
require_once get_template_directory() . '/admin/bsc-newsletter-page.php';
require_once get_template_directory() . '/admin/bsc-followup-emails-page.php'; // BSC-082
require_once get_template_directory() . '/admin/bsc-monitoring-page.php';
require_once get_template_directory() . '/admin/bsc-access-page.php';        // BSC-066

// Page render functions

function bsc_render_dashboard(): void {
    if ( ! bsc_current_user_has_bsc_page_access( 'bsc-dashboard' ) ) {
        wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
    }

    $range               = bsc_dashboard_get_range();
    $can_view_financials = bsc_dashboard_user_can_view_financials();
    $can_view_metrics    = current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
    $cache_key           = 'bsc_dashboard_kpis_' . md5( $range['cache_key'] . '|' . ( $can_view_financials ? 'finance' : 'ops' ) . '|metrics_v1' );

    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['bsc_dashboard_action'] ) ) {
        if ( ! isset( $_POST['bsc_dashboard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_dashboard_nonce'] ) ), 'bsc_dashboard_action' ) ) {
            wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
        }

        $dashboard_action = sanitize_key( wp_unslash( $_POST['bsc_dashboard_action'] ) );

        if ( 'clear_cache' === $dashboard_action ) {
            delete_transient( $cache_key );
            add_settings_error( 'bsc_dashboard', 'cache_cleared', __( 'Cache del dashboard limpiada.', 'bsc-2-0' ), 'updated' );
        }
    }

    $kpis = get_transient( $cache_key );

    if ( false === $kpis ) {
        $range_order_count = bsc_dashboard_count_orders([], [
            'date_after'  => $range['date_after'],
            'date_before' => $range['date_before'],
        ]);

        $range_revenue = null;

        if ( $can_view_financials ) {
            $range_revenue = 0;
            bsc_reports_for_each_order([
                'date_after'  => $range['date_after'],
                'date_before' => $range['date_before'],
            ], function($o) use (&$range_revenue) {
                if (in_array($o->get_status(), ['processing','completed','preparing','shipped'], true)) {
                    $range_revenue += (float) $o->get_total();
                }
            });
        }

        $pending_count   = bsc_dashboard_count_orders(['pending', 'on-hold']);
        $preparing_count = bsc_dashboard_count_orders(['processing', 'preparing']);
        $shipped_count   = bsc_dashboard_count_orders([
            'shipped',
        ], [
            'meta_query' => [
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
            ],
        ]);

        $recent_orders = wc_get_orders(['limit' => 5, 'orderby' => 'date', 'order' => 'DESC']);

        $low_threshold = (int) get_option('bsc_low_stock_threshold', 3);
        $low_stock_ids = class_exists('BSC_Stock') ? BSC_Stock::get_low_stock_products($low_threshold) : [];

        $kpis = [
            'ventas_periodo' => $range_revenue,
            'pedidos_periodo'=> $range_order_count,
            'pendientes'     => $pending_count,
            'preparando'     => $preparing_count,
            'enviados'       => $shipped_count,
            'recent_orders'  => array_map(fn($o) => [
                'id'     => $o->get_id(),
                'number' => $o->get_order_number(),
                'name'   => $o->get_formatted_billing_full_name(),
                'total'  => (float) $o->get_total(),
                'status' => wc_get_order_status_name($o->get_status()),
                'url'    => $o->get_edit_order_url(),
            ], $recent_orders),
            'low_stock_ids' => $low_stock_ids,
            'low_threshold' => $low_threshold,
            'quick_links'   => bsc_dashboard_quick_links(),
            'metrics'       => $can_view_metrics && function_exists( 'bsc_metrics_get_summary' )
                ? bsc_metrics_get_summary( $range['start_date'], $range['end_date'] )
                : bsc_dashboard_empty_metrics(),
        ];
        set_transient($cache_key, $kpis, 30 * MINUTE_IN_SECONDS);
    }

    $metrics              = is_array( $kpis['metrics'] ?? null ) ? $kpis['metrics'] : bsc_dashboard_empty_metrics();
    $metric_counters      = array_merge( bsc_dashboard_empty_metrics()['counters'], is_array( $metrics['counters'] ?? null ) ? $metrics['counters'] : [] );
    $abandoned_snapshot   = is_array( $metrics['abandoned_snapshot'] ?? null ) ? $metrics['abandoned_snapshot'] : bsc_dashboard_empty_metrics()['abandoned_snapshot'];
    $add_to_cart_rate     = function_exists( 'bsc_metrics_rate' ) ? bsc_metrics_rate( (float) $metric_counters['add_to_cart'], (float) $metric_counters['view_item'] ) : 0.0;
    $checkout_conversion  = function_exists( 'bsc_metrics_rate' ) ? bsc_metrics_rate( (float) $metric_counters['purchase'], (float) $metric_counters['begin_checkout'] ) : 0.0;
    $cart_recovery_rate   = function_exists( 'bsc_metrics_rate' ) ? bsc_metrics_rate( (float) $metric_counters['abandoned_cart_converted'], (float) $metric_counters['abandoned_cart_capture'] ) : 0.0;
    ?>
    <div class="wrap bsc-admin-dashboard">
        <h1 class="bsc-admin-dashboard__title">
            BSC Dashboard
            <form method="post" class="bsc-admin-dashboard__refresh-form">
                <?php wp_nonce_field( 'bsc_dashboard_action', 'bsc_dashboard_nonce' ); ?>
                <input type="hidden" name="bsc_dashboard_action" value="clear_cache">
                <button type="submit" class="page-title-action">Actualizar</button>
            </form>
        </h1>
        <?php settings_errors( 'bsc_dashboard' ); ?>

        <form method="get" class="bsc-admin-dashboard__filters">
            <input type="hidden" name="page" value="bsc-dashboard">
            <label for="dashboard_range">Periodo</label>
            <select id="dashboard_range" name="dashboard_range">
                <?php foreach ( bsc_dashboard_range_options() as $range_key => $range_label ) : ?>
                    <option value="<?php echo esc_attr( $range_key ); ?>" <?php selected( $range['key'], $range_key ); ?>>
                        <?php echo esc_html( $range_label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="dashboard_start">Desde</label>
            <input type="date" id="dashboard_start" name="dashboard_start" value="<?php echo esc_attr( $range['start_date'] ); ?>">
            <label for="dashboard_end">Hasta</label>
            <input type="date" id="dashboard_end" name="dashboard_end" value="<?php echo esc_attr( $range['end_date'] ); ?>">
            <button type="submit" class="button">Aplicar</button>
        </form>

        <!-- KPI Cards -->
        <div class="bsc-admin-dashboard__grid">
            <?php if ( $can_view_financials ) : ?>
            <div class="bsc-admin-dashboard__card">
                <div class="bsc-admin-dashboard__value"><?php echo wp_kses_post(wc_price((float) $kpis['ventas_periodo'])); ?></div>
                <div class="bsc-admin-dashboard__label">Ventas <?php echo esc_html($range['label']); ?></div>
            </div>
            <?php endif; ?>
            <div class="bsc-admin-dashboard__card">
                <div class="bsc-admin-dashboard__value"><?php echo esc_html($kpis['pedidos_periodo']); ?></div>
                <div class="bsc-admin-dashboard__label">Pedidos <?php echo esc_html($range['label']); ?></div>
            </div>
            <div class="bsc-admin-dashboard__card<?php echo esc_attr($kpis['pendientes'] > 0 ? ' bsc-admin-dashboard__card--warning' : ''); ?>">
                <div class="bsc-admin-dashboard__value"><?php echo esc_html($kpis['pendientes']); ?></div>
                <div class="bsc-admin-dashboard__label">Pendientes</div>
            </div>
            <div class="bsc-admin-dashboard__card">
                <div class="bsc-admin-dashboard__value"><?php echo esc_html($kpis['preparando']); ?></div>
                <div class="bsc-admin-dashboard__label">En preparación</div>
            </div>
            <div class="bsc-admin-dashboard__card">
                <div class="bsc-admin-dashboard__value"><?php echo esc_html($kpis['enviados']); ?></div>
                <div class="bsc-admin-dashboard__label">Enviados</div>
            </div>
        </div>

        <?php if ( $can_view_metrics ) : ?>
        <section class="bsc-admin-dashboard__metrics" aria-labelledby="bsc-dashboard-ecommerce-metrics">
            <h2 id="bsc-dashboard-ecommerce-metrics" class="bsc-admin-dashboard__section-title">Metricas ecommerce</h2>
            <div class="bsc-admin-dashboard__grid bsc-admin-dashboard__grid--metrics">
                <div class="bsc-admin-dashboard__card">
                    <div class="bsc-admin-dashboard__value"><?php echo esc_html( number_format_i18n( (int) $metric_counters['view_item'] ) ); ?></div>
                    <div class="bsc-admin-dashboard__label">Vistas de producto</div>
                </div>
                <div class="bsc-admin-dashboard__card">
                    <div class="bsc-admin-dashboard__value"><?php echo esc_html( bsc_dashboard_format_percent( $add_to_cart_rate ) ); ?></div>
                    <div class="bsc-admin-dashboard__label">Add-to-cart rate</div>
                </div>
                <div class="bsc-admin-dashboard__card">
                    <div class="bsc-admin-dashboard__value"><?php echo esc_html( bsc_dashboard_format_percent( $checkout_conversion ) ); ?></div>
                    <div class="bsc-admin-dashboard__label">Conversion checkout</div>
                </div>
                <div class="bsc-admin-dashboard__card<?php echo (int) $metric_counters['search_no_results'] > 0 ? ' bsc-admin-dashboard__card--warning' : ''; ?>">
                    <div class="bsc-admin-dashboard__value"><?php echo esc_html( number_format_i18n( (int) $metric_counters['search_no_results'] ) ); ?></div>
                    <div class="bsc-admin-dashboard__label">Busquedas sin resultado</div>
                </div>
                <div class="bsc-admin-dashboard__card">
                    <div class="bsc-admin-dashboard__value"><?php echo esc_html( bsc_dashboard_format_percent( $cart_recovery_rate ) ); ?></div>
                    <div class="bsc-admin-dashboard__label">Recuperacion carrito</div>
                </div>
                <div class="bsc-admin-dashboard__card">
                    <div class="bsc-admin-dashboard__value"><?php echo esc_html( number_format_i18n( (int) $abandoned_snapshot['active'] ) ); ?></div>
                    <div class="bsc-admin-dashboard__label">Carritos activos</div>
                </div>
            </div>

            <div class="bsc-admin-dashboard__insight-panels">
                <div>
                    <h3 class="bsc-admin-dashboard__panel-title">Productos mas vistos</h3>
                    <table class="wp-list-table widefat striped">
                        <thead><tr><th>Producto</th><th>Vistas</th><th>Adds</th><th>Compras</th></tr></thead>
                        <tbody>
                        <?php foreach ( (array) ( $metrics['top_products'] ?? [] ) as $product_row ) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( (string) ( $product_row['edit_url'] ?? '' ) ); ?>">
                                        <?php echo esc_html( (string) ( $product_row['title'] ?? 'Producto' ) ); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html( number_format_i18n( (int) ( $product_row['views'] ?? 0 ) ) ); ?></td>
                                <td><?php echo esc_html( number_format_i18n( (int) ( $product_row['add_to_cart'] ?? 0 ) ) ); ?></td>
                                <td><?php echo esc_html( number_format_i18n( (int) ( $product_row['purchases'] ?? 0 ) ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ( empty( $metrics['top_products'] ) ) : ?>
                            <tr><td colspan="4" class="bsc-admin-dashboard__empty-row">Sin datos de productos para este periodo.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h3 class="bsc-admin-dashboard__panel-title">Busquedas sin resultado</h3>
                    <table class="wp-list-table widefat striped">
                        <thead><tr><th>Busqueda</th><th>Veces</th><th>Ultima vez</th></tr></thead>
                        <tbody>
                        <?php foreach ( (array) ( $metrics['no_result_searches'] ?? [] ) as $search_row ) : ?>
                            <tr>
                                <td><?php echo esc_html( (string) ( $search_row['query'] ?? '' ) ); ?></td>
                                <td><?php echo esc_html( number_format_i18n( (int) ( $search_row['no_results'] ?? 0 ) ) ); ?></td>
                                <td><?php echo esc_html( (string) ( $search_row['last_at'] ?? '' ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ( empty( $metrics['no_result_searches'] ) ) : ?>
                            <tr><td colspan="3" class="bsc-admin-dashboard__empty-row">No hay busquedas sin resultado en este periodo.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Bottom panels -->
        <div class="bsc-admin-dashboard__panels">

            <!-- Recent orders -->
            <div>
                <h2 class="bsc-admin-dashboard__panel-title">Últimos 5 pedidos</h2>
                <table class="wp-list-table widefat striped">
                    <thead><tr><th>#</th><th>Cliente</th><?php if ( $can_view_financials ) : ?><th>Total</th><?php endif; ?><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($kpis['recent_orders'] as $ro): ?>
                    <tr>
                        <td><a href="<?php echo esc_url($ro['url']); ?>">#<?php echo esc_html($ro['number']); ?></a></td>
                        <td><?php echo esc_html($ro['name']); ?></td>
                        <?php if ( $can_view_financials ) : ?>
                        <td><?php echo wp_kses_post(wc_price($ro['total'])); ?></td>
                        <?php endif; ?>
                        <td><?php echo esc_html($ro['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($kpis['recent_orders'])): ?><tr><td colspan="<?php echo esc_attr( $can_view_financials ? 4 : 3 ); ?>" class="bsc-admin-dashboard__empty-row">Sin pedidos.</td></tr><?php endif; ?>
                    </tbody>
                </table>
                <p class="bsc-admin-dashboard__panel-action"><a href="<?php echo esc_url(admin_url('admin.php?page=bsc-orders')); ?>" class="button button-primary">Ver todos los pedidos</a></p>
            </div>

            <!-- Low stock alerts -->
            <div>
                <h2 class="bsc-admin-dashboard__panel-title">Stock bodega bajo (< <?php echo esc_html($kpis['low_threshold']); ?>)</h2>
                <?php if ( ! empty($kpis['low_stock_ids']) ): ?>
                <table class="wp-list-table widefat striped bsc-admin-dashboard__low-stock-table">
                    <thead><tr><th>Producto</th><th>Stock bodega</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($kpis['low_stock_ids'] as $pid):
                        $stock_b = (int) get_post_meta($pid, '_stock_bodega', true);
                    ?>
                    <tr>
                        <td><?php echo esc_html(get_the_title($pid)); ?></td>
                        <td class="bsc-admin-dashboard__low-stock-value"><?php echo esc_html($stock_b); ?></td>
                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=bsc-product-edit&id='.$pid)); ?>" class="button button-small">Editar</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="bsc-admin-dashboard__low-stock-ok">Todos los productos tienen stock suficiente.</p>
                <?php endif; ?>
            </div>

            <div>
                <h2 class="bsc-admin-dashboard__panel-title">Accesos rapidos</h2>
                <?php if ( ! empty($kpis['quick_links']) ): ?>
                    <div class="bsc-admin-dashboard__quick-links">
                        <?php foreach ($kpis['quick_links'] as $quick_link): ?>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=' . $quick_link['page'])); ?>">
                                <?php echo esc_html($quick_link['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="bsc-admin-dashboard__empty-row">No hay accesos disponibles para este rol.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

// bsc_render_orders_page()  is defined in admin/bsc-orders-page.php
// bsc_render_products_page() is defined in admin/bsc-products-page.php  (BSC-062)
// bsc_render_reports_page() is defined in admin/bsc-reports-page.php
// bsc_render_product_edit_page() is defined in admin/bsc-product-edit-page.php (BSC-065)

// BSC-064: Settings page
function bsc_render_settings_page(): void {
    if ( ! current_user_can('manage_options') ) {
        wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
    }

    // Handle save
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['bsc_settings_nonce']) ) {
        if ( ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['bsc_settings_nonce'])), 'bsc_settings_action' ) ) {
            wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
        }

        update_option('bsc_whatsapp_number',          preg_replace('/[^0-9]/', '', wp_unslash($_POST['bsc_whatsapp_number'] ?? '573156922859')));
        update_option('bsc_contact_email',             sanitize_email(wp_unslash($_POST['bsc_contact_email'] ?? '')));
        update_option('bsc_creator_email',             sanitize_email(wp_unslash($_POST['bsc_creator_email'] ?? '')));
        update_option('bsc_free_shipping_threshold',   max(0, intval(wp_unslash($_POST['bsc_free_shipping_threshold'] ?? 300000))));
        update_option('bsc_bogota_shipping_price',     max(0, intval(wp_unslash($_POST['bsc_bogota_shipping_price'] ?? 10000))));
        update_option('bsc_other_shipping_price',      max(0, intval(wp_unslash($_POST['bsc_other_shipping_price'] ?? 17000))));
        update_option('bsc_bogota_shipping_label',     sanitize_text_field(wp_unslash($_POST['bsc_bogota_shipping_label'] ?? 'Bogotá')));
        update_option('bsc_default_max_products_slider', max(1, intval(wp_unslash($_POST['bsc_default_max_products_slider'] ?? 5))));
        update_option('bsc_form_data_retention_days', max(30, intval(wp_unslash($_POST['bsc_form_data_retention_days'] ?? 730))));
        update_option('bsc_email_from_name',           sanitize_text_field(wp_unslash($_POST['bsc_email_from_name'] ?? 'Bubble Skin Care')));
        update_option('bsc_email_from_address',        sanitize_email(wp_unslash($_POST['bsc_email_from_address'] ?? '')));
        update_option('bsc_low_stock_threshold',       max(0, intval(wp_unslash($_POST['bsc_low_stock_threshold'] ?? 3))));
        $ga4_measurement_id = strtoupper(sanitize_text_field(wp_unslash($_POST['bsc_ga4_measurement_id'] ?? '')));
        update_option('bsc_ga4_measurement_id', preg_match('/^G-[A-Z0-9]+$/', $ga4_measurement_id) ? $ga4_measurement_id : '');
        update_option('bsc_merchant_feed_enabled', isset($_POST['bsc_merchant_feed_enabled']) ? 1 : 0);
        update_option('bsc_merchant_feed_default_brand', sanitize_text_field(wp_unslash($_POST['bsc_merchant_feed_default_brand'] ?? get_bloginfo('name'))));
        update_option('bsc_metrics_retention_days', max(30, min(365, intval(wp_unslash($_POST['bsc_metrics_retention_days'] ?? 120)))));
        update_option('bsc_abandoned_cart_enabled', isset($_POST['bsc_abandoned_cart_enabled']) ? 1 : 0);
        update_option('bsc_abandoned_cart_delay_hours', max(1, intval(wp_unslash($_POST['bsc_abandoned_cart_delay_hours'] ?? 4))));

        echo '<div class="notice notice-success is-dismissible"><p>✓ Configuración guardada.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Configuración BSC</h1>
        <form method="post">
            <?php wp_nonce_field('bsc_settings_action', 'bsc_settings_nonce'); ?>
            <table class="form-table">
                <tr><th colspan="2"><h2 class="bsc-admin-settings__section-title">General</h2></th></tr>
                <tr>
                    <th><label for="bsc_whatsapp_number">Número de WhatsApp</label></th>
                    <td>
                        <input type="text" id="bsc_whatsapp_number" name="bsc_whatsapp_number"
                            value="<?php echo esc_attr(get_option('bsc_whatsapp_number','573156922859')); ?>"
                            class="regular-text" placeholder="573156922859">
                        <p class="description">Solo números, con código de país. Ej: 573156922859</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_contact_email">Email de contacto</label></th>
                    <td>
                        <input type="email" id="bsc_contact_email" name="bsc_contact_email"
                            value="<?php echo esc_attr(get_option('bsc_contact_email', defined('BSC_CONTACT_EMAIL') ? BSC_CONTACT_EMAIL : '')); ?>"
                            class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_creator_email">Email Bubble Creators</label></th>
                    <td>
                        <input type="email" id="bsc_creator_email" name="bsc_creator_email"
                            value="<?php echo esc_attr(get_option('bsc_creator_email', defined('BSC_CREATOR_EMAIL') ? BSC_CREATOR_EMAIL : '')); ?>"
                            class="regular-text">
                        <p class="description">Destino exclusivo para solicitudes de Bubble Creators.</p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 class="bsc-admin-settings__section-title bsc-admin-settings__section-title--spaced">Tienda</h2></th></tr>
                <tr>
                    <th><label for="bsc_free_shipping_threshold">Umbral de envío gratis (COP)</label></th>
                    <td>
                        <input type="number" id="bsc_free_shipping_threshold" name="bsc_free_shipping_threshold"
                            value="<?php echo esc_attr(get_option('bsc_free_shipping_threshold',300000)); ?>"
                            class="regular-text" min="0" step="1000">
                        <p class="description">Se oculta el envío gratis si el subtotal (después de descuento) es menor a este valor.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_bogota_shipping_label">Label tarifa Bogotá</label></th>
                    <td>
                        <input type="text" id="bsc_bogota_shipping_label" name="bsc_bogota_shipping_label"
                            value="<?php echo esc_attr(get_option('bsc_bogota_shipping_label','Bogotá')); ?>"
                            class="regular-text">
                        <p class="description">Texto que identifica la tarifa de Bogotá en WooCommerce Envíos (debe coincidir con el label de la zona).</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_bogota_shipping_price">Tarifa Bogotá/Cundinamarca (COP)</label></th>
                    <td>
                        <input type="number" id="bsc_bogota_shipping_price" name="bsc_bogota_shipping_price"
                            value="<?php echo esc_attr(get_option('bsc_bogota_shipping_price',10000)); ?>"
                            class="regular-text" min="0" step="1000">
                        <p class="description">Valor aplicado en checkout para pedidos de Bogotá y Cundinamarca.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_other_shipping_price">Tarifa resto del país (COP)</label></th>
                    <td>
                        <input type="number" id="bsc_other_shipping_price" name="bsc_other_shipping_price"
                            value="<?php echo esc_attr(get_option('bsc_other_shipping_price',17000)); ?>"
                            class="regular-text" min="0" step="1000">
                        <p class="description">Valor aplicado en checkout para destinos fuera de Bogotá y Cundinamarca.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_default_max_products_slider">Productos por slider</label></th>
                    <td>
                        <input type="number" id="bsc_default_max_products_slider" name="bsc_default_max_products_slider"
                            value="<?php echo esc_attr(get_option('bsc_default_max_products_slider',5)); ?>"
                            class="small-text" min="1" max="20">
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_form_data_retention_days">Retencion datos formularios (dias)</label></th>
                    <td>
                        <input type="number" id="bsc_form_data_retention_days" name="bsc_form_data_retention_days"
                            value="<?php echo esc_attr(function_exists('bsc_privacy_get_form_retention_days') ? bsc_privacy_get_form_retention_days() : 730); ?>"
                            class="small-text" min="30" step="30">
                        <p class="description">Aplica a leads de Newsletter y Bubble Creators guardados en el panel.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_low_stock_threshold">Umbral de stock bajo</label></th>
                    <td>
                        <input type="number" id="bsc_low_stock_threshold" name="bsc_low_stock_threshold"
                            value="<?php echo esc_attr(get_option('bsc_low_stock_threshold',3)); ?>"
                            class="small-text" min="0">
                        <p class="description">Productos con stock bodega menor a este valor aparecen como alerta en el Dashboard.</p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 class="bsc-admin-settings__section-title bsc-admin-settings__section-title--spaced">Conversion y analitica</h2></th></tr>
                <tr>
                    <th><label for="bsc_ga4_measurement_id">GA4 Measurement ID</label></th>
                    <td>
                        <input type="text" id="bsc_ga4_measurement_id" name="bsc_ga4_measurement_id"
                            value="<?php echo esc_attr(get_option('bsc_ga4_measurement_id','')); ?>"
                            class="regular-text" placeholder="G-XXXXXXXXXX">
                        <p class="description">Si se deja vacio, el sitio sigue empujando eventos a dataLayer para GTM.</p>
                    </td>
                </tr>
                <tr>
                    <th>Google Merchant Center</th>
                    <td>
                        <label>
                            <input type="checkbox" name="bsc_merchant_feed_enabled" value="1" <?php checked((int) get_option('bsc_merchant_feed_enabled', 1), 1); ?>>
                            Publicar feed XML de productos para Merchant Center.
                        </label>
                        <p class="description">
                            URL del feed:
                            <code><?php echo esc_html(function_exists('bsc_merchant_center_feed_url') ? bsc_merchant_center_feed_url() : home_url('/?feed=bsc-google-merchant')); ?></code>
                        </p>
                        <p class="bsc-admin-settings__inline-fields">
                            <label for="bsc_merchant_feed_default_brand">Marca fallback</label>
                            <input type="text" id="bsc_merchant_feed_default_brand" name="bsc_merchant_feed_default_brand"
                                value="<?php echo esc_attr(get_option('bsc_merchant_feed_default_brand', get_bloginfo('name'))); ?>"
                                class="regular-text">
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_metrics_retention_days">Retencion metricas ecommerce</label></th>
                    <td>
                        <input type="number" id="bsc_metrics_retention_days" name="bsc_metrics_retention_days"
                            value="<?php echo esc_attr((string) get_option('bsc_metrics_retention_days', 120)); ?>"
                            class="small-text" min="30" max="365" step="30">
                        <p class="description">Dias que se conservan vistas, add-to-cart y busquedas internas para el dashboard.</p>
                    </td>
                </tr>
                <tr>
                    <th>Carrito abandonado</th>
                    <td>
                        <label>
                            <input type="checkbox" name="bsc_abandoned_cart_enabled" value="1" <?php checked((int) get_option('bsc_abandoned_cart_enabled', 1), 1); ?>>
                            Capturar carritos con email y enviar recordatorio.
                        </label>
                        <p class="bsc-admin-followup__inline-setting">
                            <input type="number" min="1" step="1" name="bsc_abandoned_cart_delay_hours"
                                value="<?php echo esc_attr((string) get_option('bsc_abandoned_cart_delay_hours', 4)); ?>"
                                class="small-text">
                            horas despues de la ultima actividad
                        </p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 class="bsc-admin-settings__section-title bsc-admin-settings__section-title--spaced">Emails BSC</h2></th></tr>
                <tr>
                    <th><label for="bsc_email_from_name">Nombre del remitente</label></th>
                    <td>
                        <input type="text" id="bsc_email_from_name" name="bsc_email_from_name"
                            value="<?php echo esc_attr(get_option('bsc_email_from_name','Bubble Skin Care')); ?>"
                            class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="bsc_email_from_address">Email del remitente</label></th>
                    <td>
                        <input type="email" id="bsc_email_from_address" name="bsc_email_from_address"
                            value="<?php echo esc_attr(get_option('bsc_email_from_address','')); ?>"
                            class="regular-text">
                    </td>
                </tr>
            </table>

            <?php submit_button('Guardar configuración'); ?>
        </form>
    </div>
    <?php
}
