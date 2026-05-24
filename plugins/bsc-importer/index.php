<?php
/**
 * Theme-bundled BSC import tools.
 *
 * This module replaces the legacy wp-bsc-plugin-v1 admin importer so the store
 * can run from the theme bundle without installing that plugin separately.
 */

defined('ABSPATH') || exit;

if (!defined('BSC_THEME_IMPORTER_PATH')) {
    define('BSC_THEME_IMPORTER_PATH', trailingslashit(__DIR__));
}

if (!defined('BSC_THEME_IMPORTER_URL')) {
    define(
        'BSC_THEME_IMPORTER_URL',
        trailingslashit(get_template_directory_uri()) . 'plugins/bsc-importer/'
    );
}

if (!defined('BSC_THEME_IMPORTER_VERSION')) {
    define('BSC_THEME_IMPORTER_VERSION', defined('BSC_THEME_VERSION') ? BSC_THEME_VERSION : '2.1.0');
}

if (is_admin()) {
    require_once BSC_THEME_IMPORTER_PATH . 'admin/importer-page.php';

    add_action('admin_menu', 'bsc_theme_importer_register_admin_menu', 30);
    add_action('admin_menu', 'bsc_theme_importer_hide_legacy_plugin_menu', 998);
    add_action('admin_enqueue_scripts', 'bsc_theme_importer_enqueue_admin_assets_for_page');
}

function bsc_theme_importer_register_admin_menu(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    add_submenu_page(
        'bsc-dashboard',
        __('Importador BSC', 'bsc-2-0'),
        __('Importador', 'bsc-2-0'),
        'manage_options',
        'bsc-plugin',
        'bsc_theme_importer_render_admin_page'
    );
}

function bsc_theme_importer_hide_legacy_plugin_menu(): void
{
    remove_menu_page('bsc-plugin');

    if (has_action('toplevel_page_bsc-plugin', 'bsc_admin_init') !== false) {
        remove_action('toplevel_page_bsc-plugin', 'bsc_admin_init');
    }

    if (has_action('toplevel_page_bsc-plugin', 'bsc_theme_importer_render_admin_page') === false) {
        add_action('toplevel_page_bsc-plugin', 'bsc_theme_importer_render_admin_page', 1);
    }
}

function bsc_theme_importer_enqueue_admin_assets_for_page(string $hook): void
{
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : '';

    if ($page !== 'bsc-plugin') {
        return;
    }

    bsc_theme_importer_enqueue_assets();
}

function bsc_theme_importer_is_legacy_plugin_loaded(): bool
{
    return class_exists('WC_BSC_Plugin') || defined('WC_BSC_PLUGIN_PATH');
}
