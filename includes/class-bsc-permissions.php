<?php
/**
 * BSC-029 / BSC-059 / BSC-060 / BSC-066: Admin access restrictions for operational roles.
 *
 * bsc_operator  → orders + showroom only (configurable via BSC Access Control page)
 * bsc_employee  → orders + products + showroom (configurable via BSC Access Control page)
 * shop_manager  → full BSC access (manage_woocommerce capability allows all BSC pages)
 * administrator → unrestricted (no redirect)
 */
defined( 'ABSPATH' ) || exit;

class BSC_Permissions {

	/** Defaults applied when no saved config exists */
	private const DEFAULTS = array(
		'bsc_operator' => array( 'bsc-dashboard', 'bsc-orders', 'bsc-showroom' ),
		'bsc_employee' => array( 'bsc-dashboard', 'bsc-orders', 'bsc-products', 'bsc-product-edit', 'bsc-showroom', 'bsc-coupons' ),
	);

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'restrict_admin_access' ) );
	}

	/**
	 * Get allowed BSC pages for a role, falling back to defaults.
	 */
	public static function get_allowed_pages( string $role ): array {
		if ( function_exists( 'bsc_access_get_config' ) ) {
			$config = bsc_access_get_config();

			return $config[ $role ] ?? self::DEFAULTS[ $role ] ?? array();
		}

		$saved = get_option( 'bsc_access_control', array() );
		return $saved[ $role ] ?? self::DEFAULTS[ $role ] ?? array();
	}

	public static function restrict_admin_access(): void {
		// Never block AJAX requests
		if ( wp_doing_ajax() ) {
			return;
		}

		$user  = wp_get_current_user();
		$roles = (array) $user->roles;

		$restricted_roles = array( 'bsc_operator', 'bsc_employee' );

		foreach ( $restricted_roles as $role ) {
			if ( ! in_array( $role, $roles, true ) ) {
				continue;
			}

            // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin routing for role access control.
			$page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
			$pagenow = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
			$allowed = self::get_allowed_pages( $role );

			if ( '' === $page && 'admin.php' !== $pagenow ) {
				wp_safe_redirect( admin_url( 'admin.php?page=bsc-orders' ) );
				exit;
			}

			// Redirect native post-type screens (WC orders, products, etc.)
			if ( ! empty( $post_type ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=bsc-orders' ) );
				exit;
			}

			if ( $page && ! in_array( $page, $allowed, true ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=bsc-orders' ) );
				exit;
			}

			break; // Only apply the first matching role
		}
	}
}

BSC_Permissions::init();
