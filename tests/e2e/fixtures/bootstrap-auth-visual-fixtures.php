<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This fixture bootstrap must run from CLI.\n");
    exit(1);
}

$wp_load_path = realpath(__DIR__ . '/../../../../../../wp-load.php');

if (!$wp_load_path || !file_exists($wp_load_path)) {
    fwrite(STDERR, "wp-load.php was not found.\n");
    exit(1);
}

require_once $wp_load_path;

if (!function_exists('wc_get_orders')) {
    fwrite(STDERR, "WooCommerce runtime is not available.\n");
    exit(1);
}

if (!function_exists('wc_create_order')) {
    fwrite(STDERR, "WooCommerce order factory is not available.\n");
    exit(1);
}

const BSC_PLAYWRIGHT_FIXTURE_META_KEY = '_bsc_playwright_fixture';
const BSC_PLAYWRIGHT_FIXTURE_META_VALUE = 'auth_visual';

function bsc_playwright_fixture_email(): string {
    return getenv('PW_ACCOUNT_EMAIL') ?: 'qa.visual@bsc.local';
}

function bsc_playwright_fixture_password(): string {
    return getenv('PW_ACCOUNT_PASSWORD') ?: 'Visual#2026BSC';
}

function bsc_playwright_fixture_account_url(): string {
    return wc_get_page_permalink('myaccount') ?: home_url('/mi-cuenta/');
}

function bsc_playwright_fixture_bubble_points_url(): string {
    $bubble_points_page = get_page_by_path('mi-cuenta/bubble-points');

    if ($bubble_points_page instanceof WP_Post) {
        return get_permalink($bubble_points_page);
    }

    return home_url('/mi-cuenta/bubble-points/');
}

function bsc_playwright_fixture_get_or_create_customer(string $email, string $password): WP_User {
    $user = get_user_by('email', $email);

    if (!$user) {
        $username_parts = explode('@', $email);
        $username = sanitize_user((string) reset($username_parts));
        $user_id = wc_create_new_customer($email, $username, $password);

        if (is_wp_error($user_id)) {
            fwrite(STDERR, "Failed to create fixture customer: {$user_id->get_error_message()}\n");
            exit(1);
        }

        $user = get_user_by('id', $user_id);
    }

    wp_update_user([
        'ID'           => $user->ID,
        'user_pass'    => $password,
        'role'         => 'customer',
        'first_name'   => 'QA',
        'last_name'    => 'Visual',
        'display_name' => 'QA Visual',
    ]);

    update_user_meta($user->ID, 'billing_first_name', 'QA');
    update_user_meta($user->ID, 'billing_last_name', 'Visual');
    update_user_meta($user->ID, 'billing_phone', '3000000000');
    update_user_meta($user->ID, 'billing_city', 'Bogota');
    update_user_meta($user->ID, 'billing_address_1', 'Calle 123 #45-67');
    update_user_meta($user->ID, '_billing_cedula', '1234567890');
    update_user_meta($user->ID, 'shipping_first_name', 'QA');
    update_user_meta($user->ID, 'shipping_last_name', 'Visual');
    update_user_meta($user->ID, 'shipping_city', 'Bogota');
    update_user_meta($user->ID, 'shipping_address_1', 'Calle 123 #45-67');
    update_user_meta($user->ID, 'bsc_bubble_points', 250);

    return $user;
}

function bsc_playwright_fixture_pick_product(): WC_Product {
    $products = wc_get_products([
        'status'  => 'publish',
        'limit'   => 1,
        'orderby' => 'date',
        'order'   => 'ASC',
        'return'  => 'objects',
    ]);

    if (empty($products) || !$products[0] instanceof WC_Product) {
        fwrite(STDERR, "No published WooCommerce product was found for the fixture order.\n");
        exit(1);
    }

    return $products[0];
}

function bsc_playwright_fixture_existing_order(int $user_id): ?WC_Order {
    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit'       => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'meta_key'    => BSC_PLAYWRIGHT_FIXTURE_META_KEY,
        'meta_value'  => BSC_PLAYWRIGHT_FIXTURE_META_VALUE,
        'return'      => 'objects',
        'status'      => array_keys(wc_get_order_statuses()),
    ]);

    return !empty($orders) && $orders[0] instanceof WC_Order ? $orders[0] : null;
}

function bsc_playwright_fixture_seed_order_meta(WC_Order $order): void {
    $billing = [
        'first_name' => 'QA',
        'last_name'  => 'Visual',
        'company'    => '',
        'email'      => bsc_playwright_fixture_email(),
        'phone'      => '3000000000',
        'address_1'  => 'Calle 123 #45-67',
        'address_2'  => 'Apto 301',
        'city'       => 'Bogota',
        'state'      => 'Bogota',
        'postcode'   => '110111',
        'country'    => 'CO',
    ];

    $shipping = [
        'first_name' => 'QA',
        'last_name'  => 'Visual',
        'company'    => '',
        'address_1'  => 'Calle 123 #45-67',
        'address_2'  => 'Apto 301',
        'city'       => 'Bogota',
        'state'      => 'Bogota',
        'postcode'   => '110111',
        'country'    => 'CO',
    ];

    $order->set_address($billing, 'billing');
    $order->set_address($shipping, 'shipping');
    $order->set_payment_method('bacs');
    $order->set_payment_method_title('Transferencia bancaria');
    $order->update_meta_data(BSC_PLAYWRIGHT_FIXTURE_META_KEY, BSC_PLAYWRIGHT_FIXTURE_META_VALUE);
    $order->update_meta_data('_billing_cedula', '1234567890');
}

function bsc_playwright_fixture_create_order(int $user_id): WC_Order {
    $product = bsc_playwright_fixture_pick_product();
    $order = wc_create_order([
        'customer_id' => $user_id,
        'created_via' => 'playwright-fixture',
    ]);

    $order->add_product($product, 1);
    bsc_playwright_fixture_seed_order_meta($order);
    $order->calculate_totals();
    $order->save();

    $fixed_date = new WC_DateTime('2026-01-15 10:00:00', new DateTimeZone('America/Bogota'));
    $order->set_date_created($fixed_date);
    $order->save();

    return $order;
}

function bsc_playwright_fixture_get_or_create_order(int $user_id): WC_Order {
    $existing_order = bsc_playwright_fixture_existing_order($user_id);

    if ($existing_order) {
        return $existing_order;
    }

    return bsc_playwright_fixture_create_order($user_id);
}

$email = bsc_playwright_fixture_email();
$password = bsc_playwright_fixture_password();
$user = bsc_playwright_fixture_get_or_create_customer($email, $password);
$order = bsc_playwright_fixture_get_or_create_order((int) $user->ID);

$payload = [
    'auth' => [
        'email'    => $email,
        'password' => $password,
    ],
    'routes' => [
        'account'      => bsc_playwright_fixture_account_url(),
        'bubblePoints' => bsc_playwright_fixture_bubble_points_url(),
        'thankYou'     => $order->get_checkout_order_received_url(),
    ],
    'order' => [
        'id'     => $order->get_id(),
        'number' => $order->get_order_number(),
        'status' => $order->get_status(),
    ],
];

echo wp_json_encode($payload, JSON_UNESCAPED_SLASHES);
