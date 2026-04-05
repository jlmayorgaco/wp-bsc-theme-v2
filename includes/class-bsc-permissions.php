<?php
/**
 * BSC-029: Admin access restrictions for operational roles.
 * bsc_operator  → only bsc-orders page
 * bsc_employee  → bsc-orders + bsc-products pages
 * administrator → unrestricted (no redirect)
 */
defined('ABSPATH') || exit;

class BSC_Permissions {

    public static function init(): void {
        add_action( 'admin_init', [ __CLASS__, 'restrict_admin_access' ] );
    }

    public static function restrict_admin_access(): void {
        // Never block AJAX requests or the admin itself
        if ( wp_doing_ajax() ) return;

        $user = wp_get_current_user();

        if ( in_array( 'bsc_operator', (array) $user->roles, true ) ) {
            $page    = sanitize_text_field( $_GET['page'] ?? '' );
            $allowed = [ 'bsc-orders' ];
            if ( ! in_array( $page, $allowed, true ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-orders' ) );
                exit;
            }
        }

        if ( in_array( 'bsc_employee', (array) $user->roles, true ) ) {
            $page    = sanitize_text_field( $_GET['page'] ?? '' );
            $allowed = [ 'bsc-orders', 'bsc-products' ];
            if ( ! in_array( $page, $allowed, true ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-orders' ) );
                exit;
            }
        }
    }
}

BSC_Permissions::init();
