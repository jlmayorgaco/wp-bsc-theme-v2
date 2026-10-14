<?php
/** Enforce the configured BSC page policy on the corresponding AJAX operations. */
defined( 'ABSPATH' ) || exit;

function bsc_admin_action_page( string $action ): string {
	$groups = array(
		'bsc-products' => array( 'bsc_update_product_status', 'bsc_update_product_stock', 'bsc_update_product_stocks', 'bsc_apply_product_discount', 'bsc_trash_product', 'bsc_adjust_stock', 'bsc_get_stock_log', 'bsc_get_product_variant_matrix', 'bsc_save_product_variant_matrix' ),
		'bsc-showroom' => array( 'bsc_showroom_search', 'bsc_register_showroom_sale' ),
		'bsc-orders' => array( 'bsc_update_order_status', 'bsc_save_tracking', 'bsc_pack_order_item_stock' ),
	);
	foreach ( $groups as $page => $actions ) {
		if ( in_array( $action, $actions, true ) ) {
			return $page;
		}
	}
	return '';
}

function bsc_admin_action_allowed( string $action, int $product_id = 0 ): bool {
	$page = bsc_admin_action_page( $action );
	if ( '' === $page ) {
		return true;
	}
	if ( ! function_exists( 'bsc_current_user_has_bsc_page_access' ) || ! bsc_current_user_has_bsc_page_access( $page ) ) {
		return false;
	}
	if ( 'bsc-products' === $page && $product_id > 0 ) {
		return current_user_can( 'bsc_trash_product' === $action ? 'delete_post' : 'edit_post', $product_id );
	}
	return true;
}

function bsc_guard_admin_ajax_permissions(): void {
	if ( ! wp_doing_ajax() ) {
		return;
	}
	// This is authorization only. Every handler must still verify its own action nonce.
	$action = isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
	$product_id = isset( $_POST['product_id'] ) && is_scalar( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
	if ( ! $product_id && isset( $_GET['product_id'] ) && is_scalar( $_GET['product_id'] ) ) {
		$product_id = absint( wp_unslash( $_GET['product_id'] ) );
	}
	if ( ! bsc_admin_action_allowed( $action, $product_id ) ) {
		wp_send_json_error( array( 'message' => 'No tienes permiso para realizar esta operación.' ), 403 );
	}
	if ( 'bsc_apply_product_discount' === $action ) {
		foreach ( wp_parse_id_list( (array) ( $_POST['product_ids'] ?? array() ) ) as $id ) {
			if ( ! current_user_can( 'edit_post', $id ) ) { wp_send_json_error( array( 'message' => 'No tienes permiso sobre uno de los productos.' ), 403 ); }
		}
	}
	if ( $product_id && in_array( $action, array( 'bsc_update_product_stock', 'bsc_update_product_stocks' ), true ) ) {
		if ( ! BSC_DB_Lock::acquire( 'stock-product:' . $product_id ) ) {
			wp_send_json_error( array( 'message' => 'Hay una operación de inventario en curso.' ), 409 );
		}
		wp_cache_delete( $product_id, 'post_meta' );
		register_shutdown_function( static function () use ( $product_id ) { BSC_DB_Lock::release( 'stock-product:' . $product_id ); } );
	}
}
add_action( 'admin_init', 'bsc_guard_admin_ajax_permissions', 1 );
