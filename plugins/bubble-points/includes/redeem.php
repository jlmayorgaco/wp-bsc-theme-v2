<?php
add_action('woocommerce_order_status_completed', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    $user_id = (int)$order->get_user_id();
    if ($user_id <= 0) return;

    // Example rule: 1 point per $1,000 COP
    $points = (int) floor($order->get_total() / 1000);
    if ($points <= 0) return;

    bsc_bp_add_ledger_entry($user_id, +$points, 'order_complete', $order_id, [
        'order_total' => $order->get_total(),
        'currency'    => $order->get_currency(),
    ]);
});


?>