<?php
/**
 * BSC-029 / BSC-059 / BSC-060: Admin access restrictions for operational roles.
 *
 * bsc_operator  → only bsc-orders + bsc-showroom
 * shop_manager  → full BSC access (manage_woocommerce capability allows all BSC pages)
 * administrator → unrestricted (no redirect)
 */
defined('ABSPATH') || exit;

class BSC_Permissions {

    public static function init(): void {
        add_action( 'admin_init', [ __CLASS__, 'restrict_admin_access' ] );
    }

    public static function restrict_admin_access(): void {
        // Never block AJAX requests
        if ( wp_doing_ajax() ) return;

        $user = wp_get_current_user();

        // BSC-060: bsc_operator — restricted to orders and showroom only
        if ( in_array( 'bsc_operator', (array) $user->roles, true ) ) {
            $page    = sanitize_text_field( $_GET['page'] ?? '' );
            $post_type = sanitize_text_field( $_GET['post_type'] ?? '' );
            $allowed_pages = [ 'bsc-dashboard', 'bsc-orders', 'bsc-showroom' ];

            // Allow load-scripts.php, load-styles.php, ajax
            if ( ! empty( $post_type ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-orders' ) );
                exit;
            }
            if ( $page && ! in_array( $page, $allowed_pages, true ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-orders' ) );
                exit;
            }
        }
    }
}

BSC_Permissions::init();
