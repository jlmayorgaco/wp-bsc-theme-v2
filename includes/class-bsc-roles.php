<?php
/**
 * BSC-029: Custom user roles for store operations.
 * Roles are created once (guarded by get_role) and removed on theme deactivation.
 */
defined('ABSPATH') || exit;

class BSC_Roles {

    /**
     * Create bsc_operator role if it doesn't already exist.
     * BSC-060: bsc_employee removed — use native shop_manager instead.
     * Safe to call on every after_switch_theme.
     */
    public static function create(): void {
        // BSC-029 / BSC-060: Operador — solo pedidos BSC
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

        // BSC-060: Migrate any remaining bsc_employee users to shop_manager
        self::migrate_employees_to_shop_manager();

        // BSC-060: Remove obsolete bsc_employee role
        if ( get_role('bsc_employee') ) {
            remove_role('bsc_employee');
        }
    }

    /**
     * BSC-060: Move any user with bsc_employee role to shop_manager.
     * Runs once; safe to call repeatedly.
     */
    private static function migrate_employees_to_shop_manager(): void {
        $employees = get_users( [ 'role' => 'bsc_employee', 'fields' => [ 'ID' ] ] );
        foreach ( $employees as $user ) {
            $u = new WP_User( $user->ID );
            $u->remove_role('bsc_employee');
            $u->add_role('shop_manager');
        }
    }

    /**
     * Remove BSC roles — call on theme deactivation if desired.
     */
    public static function remove(): void {
        remove_role('bsc_operator');
    }
}

// BSC-060: Default role for new registrations is 'customer' (WooCommerce customer, not subscriber)
add_filter( 'pre_option_default_role', function( $role ) {
    // Only override if not already set to something meaningful
    return 'customer';
});
