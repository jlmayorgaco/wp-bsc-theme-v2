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
function bsc_bp_render_admin_screen() {
    if ( ! current_user_can('manage_woocommerce') && ! current_user_can('manage_options') ) {
        wp_die(__('You do not have sufficient permissions.', 'bsc'));
    }

    echo '<div class="wrap"><h1 class="wp-heading-inline">'.esc_html__('Bubble Points', 'bsc').'</h1><hr class="wp-header-end" />';

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
                printf('<input type="hidden" name="%s" value="%s" />', esc_attr($keep), esc_attr($_GET[$keep]));
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

