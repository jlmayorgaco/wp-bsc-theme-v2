<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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



    // DEBUG LOGGING – remove after testing
if ( defined('WP_DEBUG') && WP_DEBUG ) {
    error_log('BSC manual adjust HIT');
    error_log('POST: ' . wp_json_encode( $_POST ));
    error_log('REF: ' . ( wp_get_referer() ?: 'no-referer' ));
}

    // Capability check
    if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Insufficient permissions.' );
    }

    // Inputs (get user_id BEFORE verifying nonce)
    $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

    // Nonce must match the form's action string
    if ( empty( $_POST['_bsc_bp_nonce'] ) || ! wp_verify_nonce( $_POST['_bsc_bp_nonce'], 'bsc_bp_manual_adjust_' . $user_id ) ) {
        wp_die( 'Bad nonce' );
    }

    // Validate remaining inputs
    $amount = isset( $_POST['amount'] ) ? intval( $_POST['amount'] ) : 0;
    $op     = isset( $_POST['op'] ) ? sanitize_key( $_POST['op'] ) : '';
    $note   = isset( $_POST['note'] ) ? sanitize_text_field( $_POST['note'] ) : '';

    if ( $user_id <= 0 || $amount <= 0 || ! in_array( $op, array( 'add', 'reduce' ), true ) ) {
        return bsc_bp_redirect_back( array( 'bp_msg' => 'invalid' ) );
    }

    // If you use the meta-based balance:
    // $current_points = (int) get_user_meta( $user_id, '_bsc_bp_points', true );
    // $delta          = ( 'add' === $op ) ? $amount : -$amount;
    // $new_points     = max( 0, $current_points + $delta );
    // update_user_meta( $user_id, '_bsc_bp_points', $new_points );

    // If you use the ledger system (recommended)
    $delta = ( 'reduce' === $op ) ? -abs( $amount ) : abs( $amount );
    if ( function_exists( 'bsc_bp_add_ledger_entry' ) ) {
        $res = bsc_bp_add_ledger_entry( $user_id, $delta, 'manual_adjustment', null, array(
            'admin_id' => get_current_user_id(),
            'note'     => $note,
            'source'   => 'admin_user_history',
        ) );

        if ( empty( $res['ok'] ) ) {
            return bsc_bp_redirect_back( array( 'bp_msg' => 'error', 'bp_err' => rawurlencode( $res['error'] ?? 'unknown' ) ) );
        }
    } else {
        // Fallback to simple meta if ledger function is missing
        $current_points = (int) get_user_meta( $user_id, '_bsc_bp_points', true );
        $new_points     = max( 0, $current_points + $delta );
        update_user_meta( $user_id, '_bsc_bp_points', $new_points );

        // Append minimal log for visibility
        $log = get_user_meta( $user_id, '_bsc_bp_log', true );
        $log = is_array( $log ) ? $log : array();
        $log[] = array(
            'delta' => (int) $delta,
            'meta'  => array(
                'reason' => 'manual_adjust',
                'by'     => get_current_user_id(),
                'time'   => current_time( 'mysql' ),
                'note'   => $note,
            ),
        );
        update_user_meta( $user_id, '_bsc_bp_log', $log );
    }

    // Success
    return bsc_bp_redirect_back( array( 'bp_msg' => 'ok' ) );
}

/**
 * Safe redirect back to the referring admin page.
 */
function bsc_bp_redirect_back( $args = array() ) {
    $ref = wp_get_referer();
    if ( ! $ref ) {
        // Fallback to your plugin admin screen; try to keep context of user_id
        $uid = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        $ref = admin_url( 'admin.php?page=bsc-bubble-points' . ( $uid ? '&user_id=' . $uid : '' ) );
    }
    wp_safe_redirect( add_query_arg( $args, $ref ) );
    exit;
}
