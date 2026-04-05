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

    // Pedidos — operator + employee + admin
    add_submenu_page(
        'bsc-dashboard',
        __( 'Pedidos BSC', 'bsc-2-0' ),
        __( 'Pedidos', 'bsc-2-0' ),
        'edit_orders',
        'bsc-orders',
        'bsc_render_orders_page'
    );

    // Productos — employee + admin
    add_submenu_page(
        'bsc-dashboard',
        __( 'Productos BSC', 'bsc-2-0' ),
        __( 'Productos', 'bsc-2-0' ),
        'edit_products',
        'bsc-products',
        'bsc_render_products_page'
    );

    // Informes — admin only
    add_submenu_page(
        'bsc-dashboard',
        __( 'Informes BSC', 'bsc-2-0' ),
        __( 'Informes', 'bsc-2-0' ),
        'manage_woocommerce',
        'bsc-reports',
        'bsc_render_reports_page'
    );

    // Configuración — admin only
    add_submenu_page(
        'bsc-dashboard',
        __( 'Configuración BSC', 'bsc-2-0' ),
        __( 'Configuración', 'bsc-2-0' ),
        'manage_options',
        'bsc-settings',
        'bsc_render_settings_page'
    );
}

// ── Hide native WP/WC menus for operational roles ─────────────────────
add_action( 'admin_menu', 'bsc_restrict_admin_menus', 999 );

function bsc_restrict_admin_menus(): void {
    $user = wp_get_current_user();
    $operational_roles = [ 'bsc_operator', 'bsc_employee' ];

    $is_operational = ! empty( array_intersect( $operational_roles, (array) $user->roles ) );
    if ( ! $is_operational ) return;

    // Menus to hide for operational users
    $hide = [
        'index.php',           // Dashboard WP
        'edit.php',            // Posts
        'upload.php',          // Media
        'edit.php?post_type=page',  // Pages
        'edit-comments.php',   // Comments
        'themes.php',          // Appearance
        'plugins.php',         // Plugins
        'users.php',           // Users
        'tools.php',           // Tools
        'options-general.php', // Settings
        'woocommerce',         // WooCommerce native
        'edit.php?post_type=product', // WC Products native
    ];

    foreach ( $hide as $slug ) {
        remove_menu_page( $slug );
    }
}

// ── Include page-specific implementations ─────────────────────────────
require_once get_template_directory() . '/admin/bsc-orders-page.php';
require_once get_template_directory() . '/admin/bsc-reports-page.php';

// ── Page render functions ──────────────────────────────────────────────

function bsc_render_dashboard(): void {
    if ( ! current_user_can('read') ) {
        wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
    }

    // Basic today stats
    $today_orders = wc_get_orders([
        'date_created' => '>' . ( strtotime('today midnight') ),
        'limit'        => -1,
        'return'       => 'ids',
    ]);
    $pending_orders = wc_get_orders([
        'status' => [ 'pending', 'on-hold', 'processing' ],
        'limit'  => -1,
        'return' => 'ids',
    ]);
    ?>
    <div class="wrap bsc-admin-dashboard">
        <h1>BSC Dashboard</h1>
        <div class="bsc-admin-widgets">
            <div class="bsc-admin-widget">
                <h2><?php echo count( $today_orders ); ?></h2>
                <p>Pedidos hoy</p>
            </div>
            <div class="bsc-admin-widget">
                <h2><?php echo count( $pending_orders ); ?></h2>
                <p>Pedidos pendientes</p>
            </div>
        </div>
        <p><a href="<?php echo esc_url( admin_url('admin.php?page=bsc-orders') ); ?>" class="button button-primary">Ver pedidos</a></p>
    </div>
    <style>
        .bsc-admin-widgets { display:flex; gap:20px; margin:20px 0; }
        .bsc-admin-widget  { background:#fff; border:1px solid #ddd; border-radius:8px; padding:24px 32px; text-align:center; min-width:140px; }
        .bsc-admin-widget h2 { font-size:2.5rem; margin:0 0 6px; color:#2c3e50; }
        .bsc-admin-widget p  { margin:0; color:#777; font-size:0.9rem; }
    </style>
    <?php
}

// bsc_render_orders_page() is defined in admin/bsc-orders-page.php

function bsc_render_products_page(): void {
    if ( ! current_user_can('edit_products') ) {
        wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
    }
    ?>
    <div class="wrap">
        <h1>Productos BSC</h1>
        <p>Vista de productos — <em>En construcción (BSC-034)</em>.</p>
        <p><a href="<?php echo esc_url( admin_url('edit.php?post_type=product') ); ?>" class="button">Ver en WooCommerce</a></p>
    </div>
    <?php
}

// bsc_render_reports_page() is defined in admin/bsc-reports-page.php

function bsc_render_settings_page(): void {
    if ( ! current_user_can('manage_options') ) {
        wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
    }
    ?>
    <div class="wrap">
        <h1>Configuración BSC</h1>
        <p>Opciones de configuración — <em>En construcción</em>.</p>
    </div>
    <?php
}
