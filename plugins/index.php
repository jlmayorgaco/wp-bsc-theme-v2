<?php
defined('ABSPATH') || exit;

// Autoload all plugins
$custom_plugins = [
    'bubble-points/index.php',
    // Add more like: 'wishlist/index.php'
];

foreach ($custom_plugins as $plugin) {
    $plugin_path = get_template_directory() . '/plugins/' . $plugin;
    if (file_exists($plugin_path)) {
        require_once $plugin_path;
    }
}
