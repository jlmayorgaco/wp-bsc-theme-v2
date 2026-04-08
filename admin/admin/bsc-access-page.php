<?php
/**
 * BSC-066: BSC Access Control System
 *
 * Provides role-based access control for BSC admin pages.
 * Implements SOLID principles, KISS, DRY, and clean code best practices.
 */

defined('ABSPATH') || exit;

// Constants
define('BSC_ACCESS_CONTROL_OPTION', 'bsc_access_control');
define('BSC_ACCESS_SAVE_NONCE', 'bsc_access_save');
define('BSC_ADMIN_ROLES', ['bsc_employee', 'bsc_operator']);
define('ADMIN_ROLE_ACCESS_ALL', true);
define('MANDATORY_PAGE_SLUG', 'bsc-orders');

// Configuration
$config = [
    'roles' => [
        'bsc_employee' => 'BSC Empleado',
        'bsc_operator' => 'BSC Operador',
    ],
    'pages' => [
        'bsc-dashboard'      => 'Dashboard',
        'bsc-orders'         => 'Pedidos',
        'bsc-products'       => 'Productos',
        'bsc-product-edit'   => 'Editar Producto', // Added missing page
        'bsc-reports'        => 'Informes',
        'bsc-showroom'       => 'Venta Presencial',
        'bsc-coupons'        => 'Cupones',
        'bsc-settings'       => 'Configuración',
        'bsc-home-favorites' => 'Home Favorites',
        'bsc-bubble-points'  => 'Bubble Points',
    ],
    'defaults' => [
        'bsc_employee' => ['bsc-dashboard', 'bsc-orders', 'bsc-products', 'bsc-product-edit', 'bsc-showroom', 'bsc-coupons'],
        'bsc_operator' => ['bsc-dashboard', 'bsc-orders', 'bsc-showroom'],
    ],
];

// Security check
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('No tienes permisos.', 'bsc-2-0'));
}

// Initialize classes
require_once BSC_ACCESS_CONTROL_PATH . '/classes/AccessControlManager.php';
require_once BSC_ACCESS_CONTROL_PATH . '/classes/AdminPageRenderer.php';

$access_manager = new AccessControlManager($config);
$page_renderer = new AdminPageRenderer($access_manager);

// Render the admin page
$page_renderer->render();
