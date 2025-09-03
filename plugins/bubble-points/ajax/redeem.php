<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_bsc_redeem_points', 'bsc_bp_ajax_redeem_points');

function bsc_bp_ajax_redeem_points() {
    check_ajax_referer('bsc_redeem_points', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Debes iniciar sesión.']);
    }

    $user_id = get_current_user_id();
    $points  = isset($_POST['points']) ? intval($_POST['points']) : 0;

    if ($points <= 0) {
        wp_send_json_error(['message' => 'Datos inválidos para redención.']);
    }

    // 1. Check user balance
    $balance = bsc_bp_get_balance($user_id);
    if ($balance < $points) {
        wp_send_json_error(['message' => 'No tienes puntos suficientes.']);
    }

    // 4. Create WooCommerce coupon
    $coupon_code = 'BSC_' . strtoupper(wp_generate_password(8, false, false));

    // 2. Deduct points and log transaction in ledger
$ledger = bsc_bp_add_ledger_entry(
    $user_id,
    -$points,
    'coupon',
    null,
    [
        'context'      => 'coupon_redeem',
        'coupon_code'  => $coupon_code,                    // unique code created
        'points_used'  => $points,                         // how many points were spent
        'coupon_value' => $coupon_value,                   // COP value of coupon
        'timestamp'    => current_time('mysql')
    ]
);

    if (!$ledger['ok']) {
        wp_send_json_error(['message' => 'Error al descontar puntos: ' . $ledger['error']]);
    }

    // 3. Convert redeemed points to coupon value
    $coupon_value = points_to_currency($points);

    $coupon_id = wp_insert_post([
        'post_title'   => $coupon_code,
        'post_content' => 'Cupón generado por redención de Bubble Points',
        'post_status'  => 'publish',
        'post_type'    => 'shop_coupon',
        'post_author'  => 1,
    ]);

    if (!$coupon_id || is_wp_error($coupon_id)) {
        wp_send_json_error(['message' => 'Error al crear el cupón.']);
    }

    update_post_meta($coupon_id, 'discount_type', 'fixed_cart');
    update_post_meta($coupon_id, 'coupon_amount', $coupon_value);
    update_post_meta($coupon_id, 'individual_use', 'yes');
    update_post_meta($coupon_id, 'usage_limit', 1);
    update_post_meta($coupon_id, 'customer_email', wp_get_current_user()->user_email);

    // 5. Return success
    wp_send_json_success([
        'message'      => 'Cupón creado correctamente.',
        'coupon_code'  => $coupon_code,
        'coupon_value' => $coupon_value,
        'points_used'  => $points,
        'balance_left' => $ledger['balance']
    ]);
}

/**
 * Convert currency (COP) to Bubble Points
 * Rule: $75,000 COP = 1,000 points
 */
function currency_to_points($currency) {
    return floor(($currency * 1000) / 75000);
}

/**
 * Convert Bubble Points to currency (COP)
 * Rule: 1,000 points = $75,000 COP
 */
function points_to_currency($points) {
    return (int) round(($points * 75000) / 1000);
}
