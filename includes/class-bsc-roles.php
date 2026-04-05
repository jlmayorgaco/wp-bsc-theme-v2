<?php
/**
 * BSC-029: Custom user roles for store operations.
 * Roles are created once (guarded by get_role) and removed on theme deactivation.
 */
defined('ABSPATH') || exit;

class BSC_Roles {

    /**
     * Create bsc_operator and bsc_employee roles if they don't already exist.
     * Safe to call on every after_switch_theme — get_role() guard prevents duplicate creation.
     */
    public static function create(): void {
        if ( ! get_role('bsc_operator') ) {
            add_role(
                'bsc_operator',
                'BSC Operador',
                [
                    'read'        => true,
                    'edit_orders' => true,
                ]
            );
        }

        if ( ! get_role('bsc_employee') ) {
            add_role(
                'bsc_employee',
                'BSC Empleado',
                [
                    'read'           => true,
                    'edit_orders'    => true,
                    'edit_products'  => true,
                    'read_products'  => true,
                ]
            );
        }
    }

    /**
     * Remove BSC roles — call on theme deactivation if desired.
     */
    public static function remove(): void {
        remove_role('bsc_operator');
        remove_role('bsc_employee');
    }
}
