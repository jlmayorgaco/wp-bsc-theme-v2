<?php
// plugins/bubble-points/includes/store.php
if (!defined('ABSPATH')) exit;

/**
 * Helpers to read/write Bubble Points and record ledger entries.
 * Single source of truth for balance + history.
 */

if (!function_exists('bsc_bp_table')) {
    function bsc_bp_table() {
        global $wpdb;
        return $wpdb->prefix . 'bsc_points_ledger';
    }
}

if (!function_exists('bsc_bp_get_balance')) {
    function bsc_bp_get_balance($user_id) {
        return (int) get_user_meta($user_id, 'bsc_bubble_points', true);
    }
}

if (!function_exists('bsc_bp_set_balance')) {
    function bsc_bp_set_balance($user_id, $points) {
        return update_user_meta($user_id, 'bsc_bubble_points', max(0, (int)$points));
    }
}

if (!function_exists('bsc_bp_add_ledger_entry')) {
    /**
     * Add a ledger row and update the user's balance.
     * Best-effort atomicity; avoids going below zero.
     *
     * @param int         $user_id
     * @param int         $delta       Positive to credit, negative to debit
     * @param string      $reason      'order_complete'|'redeem'|'manual_adjustment'|'refund'|'promo'...
     * @param int|null    $order_id
     * @param array       $extra       Arbitrary metadata (will be JSON encoded)
     * @param bool        $allow_negative If false, floor at 0 on debit
     * @return array { 'ok'=>bool, 'balance'=>int, 'error'=>string|null, 'insert_id'=>int|null }
     */
    function bsc_bp_add_ledger_entry($user_id, $delta, $reason, $order_id = null, array $extra = [], $allow_negative = false) {
        global $wpdb;

        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return ['ok' => false, 'balance' => 0, 'error' => 'invalid_user', 'insert_id' => null];
        }

        $delta    = (int) $delta;
        $reason   = sanitize_key($reason);
        $order_id = $order_id ? (int) $order_id : null;

        // Current balance
        $current = bsc_bp_get_balance($user_id);

        // Compute new balance
        $new = $current + $delta;
        if (!$allow_negative && $new < 0) {
            return ['ok' => false, 'balance' => $current, 'error' => 'insufficient_points', 'insert_id' => null];
        }
        $new = max(0, $new);

        // Persist balance first (so UI reflects latest fast)
        bsc_bp_set_balance($user_id, $new);

        // Insert ledger row
        $inserted = $wpdb->insert(
            bsc_bp_table(),
            [
                'user_id'       => $user_id,
                'delta'         => $delta,
                'balance_after' => $new,
                'reason'        => $reason,
                'order_id'      => $order_id,
                'meta'          => $extra ? wp_json_encode($extra) : null,
                'created_at'    => current_time('mysql'),
            ],
            ['%d','%d','%d','%s','%d','%s','%s']
        );

        if ($inserted === false) {
            // Ledger failed; try to revert balance to previous to keep consistency
            bsc_bp_set_balance($user_id, $current);
            return ['ok' => false, 'balance' => $current, 'error' => 'ledger_insert_failed', 'insert_id' => null];
        }

        return ['ok' => true, 'balance' => $new, 'error' => null, 'insert_id' => (int)$wpdb->insert_id];
    }
}

if (!function_exists('bsc_bp_recalc_balance_from_ledger')) {
    /**
     * Recalculate balance from ledger SUM(delta) and store it in user meta.
     */
    function bsc_bp_recalc_balance_from_ledger($user_id) {
        global $wpdb;
        $sum = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COALESCE(SUM(delta),0) FROM ' . bsc_bp_table() . ' WHERE user_id = %d',
                $user_id
            )
        );
        bsc_bp_set_balance($user_id, $sum);
        return $sum;
    }
}

if (!function_exists('bsc_bp_get_user_ledger')) {
    /**
     * Fetch paginated ledger for a user.
     * @return array { 'rows'=>array, 'total'=>int }
     */
    function bsc_bp_get_user_ledger($user_id, $per_page = 25, $paged = 1) {
        global $wpdb;
        $user_id = (int)$user_id;
        $per_page = max(1, (int)$per_page);
        $offset = max(0, ($paged - 1) * $per_page);

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id, delta, balance_after, reason, order_id, meta, created_at
                 FROM ' . bsc_bp_table() . '
                 WHERE user_id = %d
                 ORDER BY created_at DESC, id DESC
                 LIMIT %d OFFSET %d',
                $user_id, $per_page, $offset
            )
        );

        $total = (int)$wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . bsc_bp_table() . ' WHERE user_id = %d',
                $user_id
            )
        );

        return ['rows' => $rows, 'total' => $total];
    }
}
