<?php
if (!defined('ABSPATH')) exit;

/**
 * Top-level Bubble Points menu at position 20,
 * with a default "All Users" screen (your list table)
 * and an optional "Settings" stub you can fill later.
 */
add_action('admin_menu', 'bsc_bp_register_top_menu');
function bsc_bp_register_top_menu() {
    // Capability: use manage_woocommerce (or manage_options for super-admin only)
    $cap = current_user_can('manage_woocommerce') ? 'manage_woocommerce' : 'manage_options';

    // Top-level menu
    $hook = add_menu_page(
        __('BSC Points', 'bsc'),
        __('BSC Points', 'bsc'),
        $cap,
        'bsc-bubble-points',                 // parent slug
        'bsc_bp_render_admin_screen',       // callback (users table / router)
        'dashicons-awards',                 // menu icon
        21                                  // menu_position
    );

    // Submenu: “All users” (points overview) – same slug to make it the default
    add_submenu_page(
        'bsc-bubble-points',
        __('All Users', 'bsc'),
        __('All Users', 'bsc'),
        $cap,
        'bsc-bubble-points',
        'bsc_bp_render_admin_screen'
    );

    // Submenu: “Settings” (optional stub)
    add_submenu_page(
        'bsc-bubble-points',
        __('Settings', 'bsc'),
        __('Settings', 'bsc'),
        $cap,
        'bsc-bp-settings',
        'bsc_bp_render_settings_screen'
    );
}

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

