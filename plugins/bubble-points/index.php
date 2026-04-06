<?php
defined('ABSPATH') || exit;

// Autoload Classes
require_once __DIR__ . '/classes/class-bsc-bubble-points.php';

// Helpers
foreach (glob(__DIR__ . '/helpers/*.php') as $helper) {
    require_once $helper;
}

// Hooks
require_once __DIR__ . '/hooks/hooks-admin.php';
require_once __DIR__ . '/hooks/hooks-front.php';

// AJAX
if (defined('DOING_AJAX') && DOING_AJAX) {
    foreach (glob(__DIR__ . '/ajax/*.php') as $ajax_file) {
        require_once $ajax_file;
    }
}

// Components (UI or shortcodes)
foreach (glob(__DIR__ . '/components/*.php') as $component) {
    require_once $component;
}


// Views (UI or shortcodes)
foreach (glob(__DIR__ . '/views/*.php') as $view) {
    //require_once $view;
}

// /plugins/bubble-points/index.php (your module loader)
require_once __DIR__ . '/includes/install.php';

// Run on theme load (safe for your theme "plugins" pattern)
add_action('after_setup_theme', 'bsc_bp_maybe_install');
// Also run after theme switch to be extra safe
add_action('after_switch_theme', 'bsc_bp_maybe_install');

require_once __DIR__ . '/includes/store.php';
require_once __DIR__ . '/includes/redeem.php';
require_once __DIR__ . '/admin/admin-menu.php';
require_once __DIR__ . '/admin/admin-actions.php';
require_once __DIR__ . '/admin/user-history.php';

if (is_admin()) {
    require_once __DIR__ . '/admin/class-bsc-bp-list-table.php';
}


// require_once __DIR__ . '/seeds/demo1.php';
// Load handlers
require_once __DIR__ . '/admin/admin-handlers.php';
