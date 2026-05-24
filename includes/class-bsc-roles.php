<?php
/**
 * BSC-029: Custom user roles for store operations.
 * Roles are created once (guarded by get_role) and removed on theme deactivation.
 *
 * Role hierarchy (admin access):
 *   administrator   → everything
 *   shop_manager    → all BSC pages
 *   bsc_employee    → orders + products only (no reports, no settings)
 *   bsc_operator    → orders + showroom only
 *   customer        → no admin
 */
defined( 'ABSPATH' ) || exit;

class BSC_Roles {

	/**
	 * Create custom BSC roles if they don't already exist.
	 * Safe to call on every after_switch_theme.
	 */
	public static function create(): void {
		// BSC-029: Operador — solo pedidos BSC
		if ( ! get_role( 'bsc_operator' ) ) {
			add_role(
				'bsc_operator',
				'BSC Operador',
				array(
					'read'        => true,
					'edit_posts'  => true,
					'edit_orders' => true,
				)
			);
		}

		// BSC-066: Empleado — pedidos + productos, sin informes ni configuración
		if ( ! get_role( 'bsc_employee' ) ) {
			add_role(
				'bsc_employee',
				'BSC Empleado',
				array(
					'read'                    => true,
					'edit_posts'              => true,
					'edit_orders'             => true,
					'edit_products'           => true,
					'read_private_products'   => true,
					'publish_products'        => true,
					'edit_published_products' => true,
					'upload_files'            => true,
				)
			);
		}

		self::sync_operational_caps();
	}

	private static function sync_operational_caps(): void {
		$caps_by_role = array(
			'bsc_operator' => array(
				'read',
				'edit_posts',
				'edit_orders',
			),
			'bsc_employee' => array(
				'read',
				'edit_posts',
				'edit_orders',
				'edit_products',
				'read_private_products',
				'publish_products',
				'edit_published_products',
				'upload_files',
			),
		);

		foreach ( $caps_by_role as $role_name => $caps ) {
			$role = get_role( $role_name );

			if ( ! $role ) {
				continue;
			}

			foreach ( $caps as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap, true );
				}
			}
		}
	}

	/**
	 * BSC-066: Ensure the administrator role has all WooCommerce capabilities.
	 *
	 * WooCommerce 7.x stopped auto-granting WC-specific caps to administrators.
	 * This writes to the DB once per cap (guarded by has_cap) so it is safe to
	 * call on every init without causing unnecessary DB writes.
	 */
	public static function grant_admin_wc_caps(): void {
		$caps = array(
			'edit_orders',
			'edit_others_orders',
			'publish_orders',
			'read_private_orders',
			'delete_orders',
			'manage_woocommerce',
			'edit_products',
			'edit_others_products',
			'publish_products',
			'read_private_products',
			'delete_products',
			'manage_product_terms',
			'edit_product_terms',
			'assign_product_terms',
		);

		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}

		foreach ( $caps as $cap ) {
			if ( ! $admin->has_cap( $cap ) ) {
				$admin->add_cap( $cap, true );
			}
		}

		$shop_manager = get_role( 'shop_manager' );
		if ( ! $shop_manager ) {
			return;
		}

		foreach ( array( 'edit_orders', 'edit_products' ) as $cap ) {
			if ( ! $shop_manager->has_cap( $cap ) ) {
				$shop_manager->add_cap( $cap, true );
			}
		}
	}

	/**
	 * Remove BSC roles — call on theme deactivation if desired.
	 */
	public static function remove(): void {
		remove_role( 'bsc_operator' );
		remove_role( 'bsc_employee' );
	}
}

// BSC-060: Default role for new registrations is 'customer' (WooCommerce customer, not subscriber)
add_filter(
	'pre_option_default_role',
	function ( $role ) {
		return 'customer';
	}
);

/**
 * BSC-066: Grant WooCommerce capabilities to administrators.
 *
 * WooCommerce 7.x no longer auto-inherits edit_orders / edit_products /
 * manage_woocommerce for the administrator role. This filter ensures
 * admins and shop managers always pass BSC/WC capability checks without modifying the
 * stored role in the database.
 */
add_filter(
	'user_has_cap',
	function ( array $allcaps, array $caps, array $args, WP_User $user ): array {
		$roles = (array) $user->roles;
		if ( ! in_array( 'administrator', $roles, true ) && ! in_array( 'shop_manager', $roles, true ) ) {
			return $allcaps;
		}
		$wc_caps = array(
			'edit_orders',
			'edit_others_orders',
			'publish_orders',
			'read_private_orders',
			'delete_orders',
			'manage_woocommerce',
			'edit_products',
			'edit_others_products',
			'publish_products',
			'read_private_products',
			'delete_products',
			'manage_product_terms',
			'edit_product_terms',
		);
		foreach ( $wc_caps as $cap ) {
			$allcaps[ $cap ] = true;
		}
		return $allcaps;
	},
	10,
	4
);
