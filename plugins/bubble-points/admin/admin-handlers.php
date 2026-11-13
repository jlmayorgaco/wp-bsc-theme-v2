<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle manual adjust from admin User History table.
 * Expects the form to send:
 *   - action=bsc_bp_manual_adjust
 *   - user_id (int)
 *   - amount  (int, >0)
 *   - op      ('add' | 'reduce')
 *   - _bsc_bp_nonce = wp_nonce_field( 'bsc_bp_manual_adjust_'.$user_id, '_bsc_bp_nonce' )
 */
add_action( 'admin_post_bsc_bp_manual_adjust', 'bsc_bp_handle_manual_adjust' );

function bsc_bp_handle_manual_adjust() {

	// Capability check
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions.' );
	}

	// Inputs (get user_id BEFORE verifying nonce)
	$user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;

	// Nonce must match the form's action string
	$nonce = isset( $_POST['_bsc_bp_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_bsc_bp_nonce'] ) ) : '';
	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'bsc_bp_manual_adjust_' . $user_id ) ) {
		wp_die( 'Bad nonce' );
	}

	// Validate remaining inputs
	$amount = isset( $_POST['amount'] ) ? intval( wp_unslash( $_POST['amount'] ) ) : 0;
	$op     = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
	$note   = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';
	$request_id = sanitize_key( wp_unslash( $_POST['request_id'] ?? '' ) );
	$source = 'inline' === ( $_POST['adjust_source'] ?? '' ) ? 'admin_inline' : 'admin_user_history';

	if ( $user_id <= 0 || ! get_userdata( $user_id ) || $amount <= 0 || $amount > 2147483647
		|| ! in_array( $op, array( 'add', 'reduce' ), true ) || ! wp_is_uuid( $request_id, 4 ) ) {
		return bsc_bp_redirect_back( array( 'bp_msg' => 'invalid' ) );
	}

	$delta = 'reduce' === $op ? -$amount : $amount;
	$admin = get_current_user_id();
	$result = bsc_bp_add_ledger_entry(
		$user_id,
		$delta,
		'manual_adjustment',
		null,
		array(
			'admin_id'  => $admin,
			'note'      => $note,
			'source'    => $source,
			'event_key' => 'manual:' . $user_id . ':' . $admin . ':' . $request_id,
		)
	);
	if ( empty( $result['ok'] ) ) {
		return bsc_bp_redirect_back( array( 'bp_msg' => 'error', 'bp_err' => sanitize_key( $result['error'] ?? 'unknown' ) ) );
	}

	return bsc_bp_redirect_back( array( 'bp_msg' => 'ok' ) );
}

/**
 * Safe redirect back to the referring admin page.
 */
function bsc_bp_redirect_back( $args = array() ) {
	$ref = wp_get_referer();
	if ( ! $ref ) {
		// Fallback to your plugin admin screen; try to keep context of user_id
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Fallback redirect runs after the nonce-verified handler.
		$uid = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		$ref = admin_url( 'admin.php?page=bsc-bubble-points' . ( $uid ? '&user_id=' . $uid : '' ) );
	}
	$ref = remove_query_arg( array( 'bp_msg', 'bp_err', 'updated', 'error' ), $ref );
	wp_safe_redirect( add_query_arg( $args, $ref ) );
	exit;
}
