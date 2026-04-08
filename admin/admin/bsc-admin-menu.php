<?php
/**
 * BSC-030: Custom admin menu for BSC store management.
 * Visibility is controlled by WP capability checks on each add_submenu_page() call.
 * For operational roles (bsc_operator / bsc_employee), the native WC menus are hidden.
 */
defined('ABSPATH') || exit;

// ── Register BSC menu pages ────────────────────────────────────────────
add_action( 'admin_menu', 'bsc_add_admin_menu' );

function bsc_add_admin_menu(): void {
    // Top-level BSC entry (visible to any logged-in admin user)
    add_menu_page(
        __( 'BSC Dashboard', 'bsc-2-0' ),
        'BSC',
        'read',
        'bsc-dashboard',
        'bsc_render_dashboard',
        'dashicons-store',
        3
    );

    // ── BSC-021: Ordered submenu list ────────────────────────────────────

    // 1. Pedidos — operator + employee + admin
    add_submenu_page(
        'bsc-dashboard',
        __( 'Pedidos BSC', 'bsc-2-0' ),
        __( 'Pedidos', 'bsc-2-0' ),
        'read',
        'bsc-orders',
        'bsc_render_orders_page'
    );

    // 2. Productos — admin + employee
    add_submenu_page(
        'bsc-dashboard',
        __( 'Productos BSC', 'bsc-2-0' ),
        __( 'Productos', 'bsc-2-0' ),
        'read',
        'bsc-products',
        'bsc_render_products_page'
    );

    // 3. Informes — admin only
    add_submenu_page(
        'bsc-dashboard',
        __( 'Informes BSC', 'bsc-2-0' ),
        __( 'Informes', 'bsc-2-0' ),
        'read',
        'bsc-reports',
        'bsc_render_reports_page'
    );

    // 4. Showcase (Venta Presencial) — operator + employee + admin
    add_submenu_page(
        'bsc-dashboard',
        __( 'Venta Presencial', 'bsc-2-0' ),
        __( 'Showcase', 'bsc-2-0' ),
        'read',
        'bsc-showroom',
        'bsc_render_showroom_page'
    );

    // 5. Home Favorites — admin only
    add_submenu_page(
        'bsc-dashboard',
        __( 'Home Favorites', 'bsc-2-0' ),
        __( 'Home Favorites', 'bsc-2-0' ),
        'manage_options',
        'bsc-home-favorites',
        'bsc_home_favorites_settings_page'  // defined in scripts/script_custom_types.php
    );

    // 6. Hero Slides — links to CPT list (show_in_menu=false on the CPT keeps this clean)
    add_submenu_page(
        'bsc-dashboard',
        __( 'Hero Slides', 'bsc-2-0' ),
        __( 'Hero Slides', 'bsc-2-0' ),
        'manage_options',
        'edit.php?post_type=home_slide',
        ''
    );

    // 7. Bubble Points — callbacks defined in plugins/bubble-points/admin/admin-menu.php
    add_submenu_page(
        'bsc-dashboard',
        __( 'Bubble Points', 'bsc-2-0' ),
        __( 'Bubble Points', 'bsc-2-0' ),
        'manage_options',
        'bsc-bubble-points',
        'bsc_bp_render_admin_screen'
    );

    // 8. Cupones — WC coupons management
    add_submenu_page(
        'bsc-dashboard',
        __( 'Cupones BSC', 'bsc-2-0' ),
        __( 'Cupones', 'bsc-2-0' ),
        'manage_options',
        'bsc-coupons',
        'bsc_render_coupons_page'
    );

    // 9. Control de Acceso — role × page matrix
    add_submenu_page(
        'bsc-dashboard',
        __( 'Control de Acceso', 'bsc-2-0' ),
        __( 'Acceso', 'bsc-2-0' ),
        'manage_options',
        'bsc-access',
        'bsc_render_access_page'
    );

    // 10. Configuración — admin only (always last)
    add_submenu_page(
        'bsc-dashboard',
        __( 'Configuración BSC', 'bsc-2-0' ),
        __( 'Configuración', 'bsc-2-0' ),
        'manage_options',
        'bsc-settings',
        'bsc_render_settings_page'
    );

    // ── Hidden pages (no sidebar entry, accessible via direct URL) ────────
    // Product editor — reachable from Productos table
    add_submenu_page(
        null,
        __( 'Editar Producto — BSC', 'bsc-2-0' ),
        __( 'Editar Producto', 'bsc-2-0' ),
        'read',
        'bsc-product-edit',
        'bsc_render_product_edit_page'
    );

    // Bubble Points settings stub
    add_submenu_page(
        null,
        __( 'Bubble Points — Ajustes', 'bsc-2-0' ),
        __( 'Bubble Points Ajustes', 'bsc-2-0' ),
        'manage_options',
        'bsc-bp-settings',
        'bsc_bp_render_settings_screen'
    );
}

// ── Hide native WP/WC menus for operational roles ─────────────────────
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

// ── Include page-specific implementations ─────────────────────────────
require_once get_template_directory() . '/admin/bsc-orders-page.php';
require_once get_template_directory() . '/admin/bsc-reports-page.php';
require_once get_template_directory() . '/admin/bsc-showroom-page.php';
require_once get_template_directory() . '/admin/bsc-products-page.php';      // BSC-062
require_once get_template_directory() . '/admin/bsc-product-edit-page.php';  // BSC-065
require_once get_template_directory() . '/admin/bsc-coupons-page.php';       // BSC-066
require_once get_template_directory() . '/admin/bsc-access-page.php';        // BSC-066

// ── Page render functions ──────────────────────────────────────────────

function bsc_render_dashboard(): void {
    if ( ! current_user_can('read') ) {
        wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
    }

    // BSC-061: Full KPI dashboard with transient cache (30min)
    $cache_key = 'bsc_dashboard_kpis';
    $kpis = get_transient( $cache_key );

    if ( false === $kpis ) {
        $today_start = gmdate('Y-m-d') . ' 00:00:00';
        $today_end   = gmdate('Y-m-d') . ' 23:59:59';

        $today_orders = wc_get_orders([
            'date_after'  => $today_start,
            'date_before' => $today_end,
            'limit'       => -1,
            'return'      => 'objects',
        ]);
        $ventas_hoy = array_reduce($today_orders, function($carry, $o) {
            return $carry + (in_array($o->get_status(), ['processing','completed','preparing','shipped']) ? (float)$o->get_total() : 0);
        }, 0);

        $pending_ids    = wc_get_orders(['status' => ['pending','on-hold'], 'limit' => -1, 'return' => 'ids']);
        $preparing_ids  = wc_get_orders(['status' => ['processing','wc-preparing'], 'limit' => -1, 'return' => 'ids']);
        $shipped_ids    = wc_get_orders(['status' => ['wc-shipped'], 'limit' => -1, 'return' => 'ids']);

        $recent_orders = wc_get_orders(['limit' => 5, 'orderby' => 'date', 'order' => 'DESC']);

        $low_threshold = (int) get_option('bsc_low_stock_threshold', 3);
        $low_stock_ids = class_exists('BSC_Stock') ? BSC_Stock::get_low_stock_products($low_threshold) : [];

        $kpis = [
            'ventas_hoy'     => $ventas_hoy,
            'pedidos_hoy'    => count($today_orders),
            'pendientes'     => count($pending_ids),
            'preparando'     => count($preparing_ids),
            'enviados'       => count($shipped_ids),
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
        ];
        set_transient($cache_key, $kpis, 30 * MINUTE_IN_SECONDS);
    }
    ?>
    <div class="wrap bsc-admin-dashboard">
        <h1 style="display:flex;align-items:center;gap:12px">
            BSC Dashboard
            <a href="<?php echo esc_url(add_query_arg('bsc_clear_cache','dashboard')); ?>" class="page-title-action">↺ Actualizar</a>
        </h1>
        <?php
        // Handle cache clear
        if ( isset($_GET['bsc_clear_cache']) ) {
            delete_transient('bsc_dashboard_kpis');
            echo '<div class="notice notice-success is-dismissible"><p>Caché del dashboard limpiada.</p></div>';
        }
        ?>

        <!-- KPI Cards -->
        <div class="bsc-kpi-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:16px 0">
            <div class="bsc-kpi-card">
                <div class="bsc-kpi-value"><?php echo wp_kses_post(wc_price($kpis['ventas_hoy'])); ?></div>
                <div class="bsc-kpi-label">Ventas hoy</div>
            </div>
            <div class="bsc-kpi-card">
                <div class="bsc-kpi-value"><?php echo esc_html($kpis['pedidos_hoy']); ?></div>
                <div class="bsc-kpi-label">Pedidos hoy</div>
            </div>
            <div class="bsc-kpi-card" style="<?php echo $kpis['pendientes'] > 0 ? 'border-color:#f6ad55;background:#fffaf0' : ''; ?>">
                <div class="bsc-kpi-value"><?php echo esc_html($kpis['pendientes']); ?></div>
                <div class="bsc-kpi-label">Pendientes</div>
            </div>
            <div class="bsc-kpi-card">
                <div class="bsc-kpi-value"><?php echo esc_html($kpis['preparando']); ?></div>
                <div class="bsc-kpi-label">En preparación</div>
            </div>
            <div class="bsc-kpi-card">
                <div class="bsc-kpi-value"><?php echo esc_html($kpis['enviados']); ?></div>
                <div class="bsc-kpi-label">Enviados</div>
            </div>
        </div>

        <!-- Bottom panels -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:8px">

            <!-- Recent orders -->
            <div>
                <h2 style="font-size:1rem;margin-bottom:8px">Últimos 5 pedidos</h2>
                <table class="wp-list-table widefat striped">
                    <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($kpis['recent_orders'] as $ro): ?>
                    <tr>
                        <td><a href="<?php echo esc_url($ro['url']); ?>">#<?php echo esc_html($ro['number']); ?></a></td>
                        <td><?php echo esc_html($ro['name']); ?></td>
                        <td><?php echo wp_kses_post(wc_price($ro['total'])); ?></td>
                        <td><?php echo esc_html($ro['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($kpis['recent_orders'])): ?><tr><td colspan="4" style="text-align:center;color:#888">Sin pedidos.</td></tr><?php endif; ?>
                    </tbody>
                </table>
                <p style="margin-top:8px"><a href="<?php echo esc_url(admin_url('admin.php?page=bsc-orders')); ?>" class="button button-primary">Ver todos los pedidos</a></p>
            </div>

            <!-- Low stock alerts -->
            <div>
                <h2 style="font-size:1rem;margin-bottom:8px">⚠️ Stock bodega bajo (< <?php echo esc_html($kpis['low_threshold']); ?>)</h2>
                <?php if ( ! empty($kpis['low_stock_ids']) ): ?>
                <table class="wp-list-table widefat striped" style="background:#fff5f5;border:1px solid #fc8181">
                    <thead><tr><th>Producto</th><th>Stock bodega</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($kpis['low_stock_ids'] as $pid):
                        $stock_b = (int) get_post_meta($pid, '_stock_bodega', true);
                    ?>
                    <tr>
                        <td><?php echo esc_html(get_the_title($pid)); ?></td>
                        <td style="font-weight:700;color:#c53030"><?php echo esc_html($stock_b); ?></td>
                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=bsc-product-edit&id='.$pid)); ?>" class="button button-small">Editar</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color:#276749;background:#f0fff4;border:1px solid #9ae6b4;padding:10px 16px;border-radius:6px">✓ Todos los productos tienen stock suficiente.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <style>
        .bsc-kpi-card { background:#fff; border:1px solid #ddd; border-radius:10px; padding:18px 20px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
        .bsc-kpi-value { font-size:1.8rem; font-weight:800; color:#222; line-height:1.2; }
        .bsc-kpi-label { font-size:0.78rem; color:#888; margin-top:4px; font-weight:600; letter-spacing:0.5px; text-transform:uppercase; }
    </style>
    <?php
}

// bsc_render_orders_page()  is defined in admin/bsc-orders-page.php
// bsc_render_products_page() is defined in admin/bsc-products-page.php  (BSC-062)
// bsc_render_reports_page() is defined in admin/bsc-reports-page.php
// bsc_render_product_edit_page() is defined in admin/bsc-product-edit-page.php (BSC-065)

// ── BSC-064: Settings page ────────────────────────────────────────────
function bsc_render_settings_page(): void {
    if ( ! current_user_can('manage_options') ) {
        wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
    }

    // Handle save
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['bsc_settings_nonce']) ) {
        if ( ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['bsc_settings_nonce'])), 'bsc_settings_action' ) ) {
            wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
        }

        update_option('bsc_whatsapp_number',          preg_replace('/[^0-9]/', '', $_POST['bsc_whatsapp_number'] ?? '573156922859'));
        update_option('bsc_contact_email',             sanitize_email($_POST['bsc_contact_email'] ?? ''));
        update_option('bsc_free_shipping_threshold',   max(0, intval($_POST['bsc_free_shipping_threshold'] ?? 300000)));
        update_option('bsc_bogota_shipping_label',     sanitize_text_field($_POST['bsc_bogota_shipping_label'] ?? 'Bogotá'));
        update_option('bsc_default_max_products_slider', max(1, intval($_POST['bsc_default_max_products_slider'] ?? 5)));
        update_option('bsc_email_from_name',           sanitize_text_field($_POST['bsc_email_from_name'] ?? 'Bubble Skin Care'));
        update_option('bsc_email_from_address',        sanitize_email($_POST['bsc_email_from_address'] ?? ''));
        update_option('bsc_low_stock_threshold',       max(0, intval($_POST['bsc_low_stock_threshold'] ?? 3)));

        echo '<div class="notice notice-success is-dismissible"><p>✓ Configuración guardada.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Configuración BSC</h1>
        <form method="post">
            <?php wp_nonce_field('bsc_settings_action', 'bsc_settings_nonce'); ?>
            <table class="form-table">
                <tr><th colspan="2"><h2 style="margin:0">General</h2></th></tr>
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

                <tr><th colspan="2"><h2 style="margin:16px 0 0">Tienda</h2></th></tr>
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
                    <th><label for="bsc_default_max_products_slider">Productos por slider</label></th>
                    <td>
                        <input type="number" id="bsc_default_max_products_slider" name="bsc_default_max_products_slider"
                            value="<?php echo esc_attr(get_option('bsc_default_max_products_slider',5)); ?>"
                            class="small-text" min="1" max="20">
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

                <tr><th colspan="2"><h2 style="margin:16px 0 0">Emails BSC</h2></th></tr>
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
