<?php
if (!defined('ABSPATH')) exit;

/**
 * BSC-021: Bubble Points is registered as a submenu of BSC (bsc-dashboard)
 * in admin/bsc-admin-menu.php. This file only defines the callback functions.
 * The top-level add_menu_page() has been removed to avoid a duplicate menu entry.
 */

/**
 * Router / main screen: shows either the users list
 * or the per-user history if ?user_id= is present.
 */
add_action( 'admin_enqueue_scripts', 'bsc_bp_enqueue_admin_assets' );
function bsc_bp_enqueue_admin_assets(): void {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( 'bsc-bubble-points' !== $page ) {
        return;
    }

    $css_path = get_template_directory() . '/plugins/bubble-points/admin/bsc-bp-admin.css';

    wp_enqueue_style(
        'bsc-bubble-points-admin',
        get_template_directory_uri() . '/plugins/bubble-points/admin/bsc-bp-admin.css',
        array(),
        file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
    );
}

function bsc_bp_render_admin_screen() {
    if ( ! current_user_can('manage_woocommerce') && ! current_user_can('manage_options') ) {
        wp_die(__('You do not have sufficient permissions.', 'bsc'));
    }

    echo '<div class="wrap bsc-bp-admin"><h1 class="wp-heading-inline">'.esc_html__('Bubble Points', 'bsc').'</h1><hr class="wp-header-end" />';

    $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

    if ($user_id > 0) {
        require_once __DIR__ . '/user-history.php';
        bsc_bp_render_user_history_screen($user_id);
    } else {
        require_once __DIR__ . '/class-bsc-bp-list-table.php';
        $table = new BSC_BP_List_Table();
        $table->prepare_items();

        echo '<form method="get">';
        // preserve routing params
        foreach (['page','post_type'] as $keep) {
            if (isset($_GET[$keep])) {
                printf('<input type="hidden" name="%s" value="%s" />', esc_attr($keep), esc_attr(sanitize_text_field(wp_unslash($_GET[$keep]))));
            }
        }
        $table->search_box(__('Search users', 'bsc'), 'bsc-bp');
        $table->display();
        echo '</form>';
    }

    echo '</div>';
}

/** Optional settings stub */
function bsc_bp_render_settings_screen() {
    if ( ! current_user_can('manage_woocommerce') && ! current_user_can('manage_options') ) {
        wp_die(__('You do not have sufficient permissions.', 'bsc'));
    }
    echo '<div class="wrap"><h1>'.esc_html__('Bubble Points – Settings', 'bsc').'</h1>';
    echo '<p>'.esc_html__('Settings coming soon (earn rules, expiry, UI toggles).', 'bsc').'</p></div>';
}
