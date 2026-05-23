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
const BSC_PLAYWRIGHT_PUBLIC_VISUAL_CATEGORY_SLUG = 'qa-visual-rutina';

function bsc_playwright_fixture_email(): string {
    return getenv('PW_ACCOUNT_EMAIL') ?: 'qa.visual@bsc.local';
}

function bsc_playwright_fixture_password(): string {
    return getenv('PW_ACCOUNT_PASSWORD') ?: 'Visual#2026BSC';
}

function bsc_playwright_fixture_admin_username(): string {
    return getenv('PW_ADMIN_USERNAME') ?: 'qa_visual_admin';
}

function bsc_playwright_fixture_admin_email(): string {
    return getenv('PW_ADMIN_EMAIL') ?: 'qa.visual.admin@bsc.local';
}

function bsc_playwright_fixture_admin_password(): string {
    return getenv('PW_ADMIN_PASSWORD') ?: 'VisualAdmin#2026BSC';
}

function bsc_playwright_fixture_role_password(): string {
    return getenv('PW_ROLE_PASSWORD') ?: 'RoleSmoke#2026BSC';
}

function bsc_playwright_fixture_login_url(): string {
    $login_page = get_page_by_path('login');

    if ($login_page instanceof WP_Post) {
        return get_permalink($login_page);
    }

    return home_url('/login/');
}

function bsc_playwright_fixture_account_url(): string {
    $account_page = get_page_by_path('mi-cuenta');

    if ($account_page instanceof WP_Post) {
        return trailingslashit(get_permalink($account_page));
    }

    return home_url('/mi-cuenta/');
}

function bsc_playwright_fixture_account_endpoint_url(string $endpoint, string $value = ''): string {
    if (function_exists('wc_get_endpoint_url')) {
        return wc_get_endpoint_url($endpoint, $value, bsc_playwright_fixture_account_url());
    }

    $path = trim($endpoint . '/' . trim($value, '/'), '/');

    return trailingslashit(home_url('/mi-cuenta/' . $path));
}

function bsc_playwright_fixture_account_addresses_url(): string {
    return bsc_playwright_fixture_account_endpoint_url('edit-address');
}

function bsc_playwright_fixture_account_edit_url(): string {
    return bsc_playwright_fixture_account_endpoint_url('edit-account');
}

function bsc_playwright_fixture_account_orders_url(): string {
    return bsc_playwright_fixture_account_endpoint_url('orders');
}

function bsc_playwright_fixture_bubble_points_url(): string {
    $bubble_points_page = get_page_by_path('mi-cuenta/bubble-points');

    if ($bubble_points_page instanceof WP_Post) {
        return get_permalink($bubble_points_page);
    }

    return home_url('/mi-cuenta/bubble-points/');
}

function bsc_playwright_fixture_visual_category_description(): string {
    return 'Tu rutina coreana empieza aqui: limpiadores, esencias, serums, contornos, mascarillas y mas para una piel saludable todos los dias.';
}

function bsc_playwright_fixture_visual_group_slug(): string {
    return 'group-skin-care';
}

function bsc_playwright_fixture_visual_parent_slug(): string {
    return 'sk-rutina';
}

function bsc_playwright_fixture_visual_category_slug(): string {
    return BSC_PLAYWRIGHT_PUBLIC_VISUAL_CATEGORY_SLUG;
}

function bsc_playwright_fixture_visual_category_grid_url(): string {
    return trailingslashit(
        home_url(
            '/product-category/'
            . bsc_playwright_fixture_visual_group_slug()
            . '/'
            . bsc_playwright_fixture_visual_parent_slug()
            . '/'
            . bsc_playwright_fixture_visual_category_slug()
            . '/'
        )
    );
}

function bsc_playwright_fixture_visual_products(): array {
    return [
        [
            'slug'              => 'qa-visual-deep-vita-c-pad',
            'name'              => 'Deep Vita C Pad',
            'regular_price'     => '149000',
            'menu_order'        => 1,
            'short_description' => '<p>Almohadillas iluminadoras infundidas en vitamina C que ayuda a desvanecer las manchas oscuras de la piel, ilumina el tono de la piel opaca y proporciona una exfoliacion suave y refrescante. Tambien contiene 500,000 ppm de agua vitaminica y agua de arbol de te que ayudan a la cicatrizacion de heridas y refuerzan la accion de la vitamina c.</p><p>Contenido: 150g - 70pcs</p>',
        ],
        [
            'slug'              => 'qa-visual-collagen-jelly-cream',
            'name'              => 'Collagen Jelly Cream',
            'regular_price'     => '146000',
            'menu_order'        => 2,
            'short_description' => '<p>Crema antienvejecimiento formulada con un 98% de colageno hidrolizado y elastina para mejorar la elasticidad de la piel y reducir las arrugas. Contiene niacinamida y extracto de arandano que ayudan a promover una tez mas brillante y acido hialuronico y escualano que fortalecen la barrera de humedad de la piel.</p><p>Contenido: 110ml</p>',
        ],
        [
            'slug'              => 'qa-visual-super-cica-toner',
            'name'              => 'Super Cica Toner',
            'regular_price'     => '89000',
            'menu_order'        => 3,
            'short_description' => '<p>Toner calmante enfocado en piel sensible con textura ligera, ideal para refrescar y equilibrar la piel despues de la limpieza.</p><p>Contenido: 210ml</p>',
        ],
        [
            'slug'              => 'qa-visual-red-serum-plus',
            'name'              => 'Red Serum Plus',
            'regular_price'     => '164000',
            'menu_order'        => 4,
            'short_description' => '<p>Serum intensivo para aportar elasticidad y luminosidad con una rutina enfocada en firmeza y humectacion.</p><p>Contenido: 30ml</p>',
        ],
        [
            'slug'              => 'qa-visual-red-toner-plus',
            'name'              => 'Red Toner Plus',
            'regular_price'     => '89000',
            'menu_order'        => 5,
            'short_description' => '<p>Toner de uso diario con acabado ligero para complementar rutinas de hidratacion y equilibrio.</p><p>Contenido: 200ml</p>',
        ],
        [
            'slug'              => 'qa-visual-triple-collagen-toner',
            'name'              => 'Triple Collagen Toner',
            'regular_price'     => '142000',
            'menu_order'        => 6,
            'short_description' => '<p>Toner nutritivo con colageno para aportar sensacion de piel suave, flexible y mas confortable.</p><p>Contenido: 140ml</p>',
        ],
    ];
}

function bsc_playwright_fixture_visual_parent_term(): WP_Term {
    $parent = get_term_by('slug', bsc_playwright_fixture_visual_parent_slug(), 'product_cat');

    if (!$parent instanceof WP_Term) {
        fwrite(STDERR, "Expected fixture parent product category was not found.\n");
        exit(1);
    }

    return $parent;
}

function bsc_playwright_fixture_get_or_create_visual_category(): WP_Term {
    $parent = bsc_playwright_fixture_visual_parent_term();
    $existing = get_term_by('slug', BSC_PLAYWRIGHT_PUBLIC_VISUAL_CATEGORY_SLUG, 'product_cat');

    if ($existing instanceof WP_Term) {
        wp_update_term($existing->term_id, 'product_cat', [
            'name'        => 'QA Visual Rutina',
            'description' => bsc_playwright_fixture_visual_category_description(),
            'parent'      => $parent->term_id,
        ]);

        return get_term($existing->term_id, 'product_cat');
    }

    $inserted = wp_insert_term('QA Visual Rutina', 'product_cat', [
        'slug'        => BSC_PLAYWRIGHT_PUBLIC_VISUAL_CATEGORY_SLUG,
        'description' => bsc_playwright_fixture_visual_category_description(),
        'parent'      => $parent->term_id,
    ]);

    if (is_wp_error($inserted)) {
        fwrite(STDERR, "Failed to create public visual category: {$inserted->get_error_message()}\n");
        exit(1);
    }

    return get_term((int) $inserted['term_id'], 'product_cat');
}

function bsc_playwright_fixture_get_or_create_visual_product(array $definition, int $category_term_id): WC_Product {
    $existing_post = get_page_by_path((string) $definition['slug'], OBJECT, 'product');
    $product = $existing_post instanceof WP_Post ? wc_get_product($existing_post->ID) : null;

    if (!$product instanceof WC_Product_Simple) {
        $product = new WC_Product_Simple();
    }

    $product->set_name((string) $definition['name']);
    $product->set_slug((string) $definition['slug']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_regular_price((string) $definition['regular_price']);
    $product->set_price((string) $definition['regular_price']);
    $product->set_stock_status('instock');
    $product->set_manage_stock(false);
    $product->set_sold_individually(false);
    $product->set_short_description((string) $definition['short_description']);
    $product->set_category_ids([$category_term_id]);
    $product->set_menu_order((int) $definition['menu_order']);
    $product->save();

    update_post_meta($product->get_id(), BSC_PLAYWRIGHT_FIXTURE_META_KEY, 'public_visual');

    return wc_get_product($product->get_id());
}

function bsc_playwright_fixture_seed_public_visual_catalog(): array {
    $category = bsc_playwright_fixture_get_or_create_visual_category();
    $products = [];

    foreach (bsc_playwright_fixture_visual_products() as $definition) {
        $product = bsc_playwright_fixture_get_or_create_visual_product($definition, (int) $category->term_id);

        if (!$product instanceof WC_Product) {
            fwrite(STDERR, "Failed to load public visual fixture product.\n");
            exit(1);
        }

        $products[] = $product;
    }

    usort($products, static function (WC_Product $left, WC_Product $right): int {
        return $left->get_menu_order() <=> $right->get_menu_order();
    });

    return [
        'category' => $category,
        'product'  => $products[0],
    ];
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

function bsc_playwright_fixture_get_or_create_admin(string $username, string $email, string $password): WP_User {
    $user = get_user_by('login', $username);

    if (!$user) {
        $user = get_user_by('email', $email);
    }

    if (!$user) {
        $user_id = wp_insert_user([
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'role'         => 'administrator',
            'first_name'   => 'QA',
            'last_name'    => 'Admin',
            'display_name' => 'QA Admin',
        ]);

        if (is_wp_error($user_id)) {
            fwrite(STDERR, "Failed to create fixture admin: {$user_id->get_error_message()}\n");
            exit(1);
        }

        $user = get_user_by('id', $user_id);
    }

    wp_update_user([
        'ID'           => $user->ID,
        'user_pass'    => $password,
        'role'         => 'administrator',
        'first_name'   => 'QA',
        'last_name'    => 'Admin',
        'display_name' => 'QA Admin',
    ]);

    return $user;
}

function bsc_playwright_fixture_get_or_create_role_user(string $role, string $username, string $email, string $password): WP_User {
    $user = get_user_by('login', $username);

    if (!$user) {
        $user = get_user_by('email', $email);
    }

    if (!$user) {
        $user_id = wp_insert_user([
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'role'         => $role,
            'first_name'   => 'QA',
            'last_name'    => ucfirst(str_replace('_', ' ', $role)),
            'display_name' => 'QA ' . ucfirst(str_replace('_', ' ', $role)),
        ]);

        if (is_wp_error($user_id)) {
            fwrite(STDERR, "Failed to create fixture {$role}: {$user_id->get_error_message()}\n");
            exit(1);
        }

        $user = get_user_by('id', $user_id);
    }

    wp_update_user([
        'ID'           => $user->ID,
        'user_pass'    => $password,
        'role'         => $role,
        'first_name'   => 'QA',
        'last_name'    => ucfirst(str_replace('_', ' ', $role)),
        'display_name' => 'QA ' . ucfirst(str_replace('_', ' ', $role)),
    ]);

    return $user;
}

function bsc_playwright_fixture_admin_role_users(string $password): array {
    if (class_exists('BSC_Roles')) {
        BSC_Roles::create();
        BSC_Roles::grant_admin_wc_caps();
    }

    $roles = [
        'operator' => [
            'role'     => 'bsc_operator',
            'username' => 'qa_bsc_operator',
            'email'    => 'qa.bsc.operator@bsc.local',
        ],
        'employee' => [
            'role'     => 'bsc_employee',
            'username' => 'qa_bsc_employee',
            'email'    => 'qa.bsc.employee@bsc.local',
        ],
        'shopManager' => [
            'role'     => 'shop_manager',
            'username' => 'qa_bsc_shop_manager',
            'email'    => 'qa.bsc.shop-manager@bsc.local',
        ],
    ];

    $payload = [];

    foreach ($roles as $key => $definition) {
        if (!get_role($definition['role'])) {
            continue;
        }

        $user = bsc_playwright_fixture_get_or_create_role_user(
            $definition['role'],
            $definition['username'],
            $definition['email'],
            $password
        );

        $payload[$key] = [
            'username' => $user->user_login,
            'email'    => $user->user_email,
            'password' => $password,
            'role'     => $definition['role'],
        ];
    }

    return $payload;
}

function bsc_playwright_fixture_admin_variants(string $password): array {
    $variants = [];

    foreach (['mobile', 'tablet', 'desktop'] as $project_name) {
        $username = 'qa_visual_admin_' . $project_name;
        $email = 'qa.visual.admin+' . $project_name . '@bsc.local';
        $user = bsc_playwright_fixture_get_or_create_admin($username, $email, $password);

        $variants[$project_name] = [
            'username' => $user->user_login,
            'email'    => $user->user_email,
            'password' => $password,
        ];
    }

    return $variants;
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

function bsc_playwright_fixture_get_or_create_coupon( array $definition ): WC_Coupon {
    $code = wc_format_coupon_code( (string) $definition['code'] );
    $coupon = new WC_Coupon( $code );

    if ( ! $coupon->get_id() ) {
        $coupon = new WC_Coupon();
    }

    $coupon->set_code( $code );
    $coupon->set_description( (string) ( $definition['description'] ?? 'Playwright fixture coupon' ) );
    $coupon->set_discount_type( (string) $definition['discount_type'] );
    $coupon->set_amount( (string) $definition['amount'] );
    $coupon->set_individual_use( false );
    $coupon->set_free_shipping( ! empty( $definition['free_shipping'] ) );
    $coupon->set_date_expires( new WC_DateTime( '2027-12-31 23:59:59', new DateTimeZone( 'America/Bogota' ) ) );
    $coupon->save();

    if ( $coupon->get_id() ) {
        update_post_meta( $coupon->get_id(), BSC_PLAYWRIGHT_FIXTURE_META_KEY, 'coupon_fixture' );
    }

    return new WC_Coupon( $coupon->get_id() );
}

function bsc_playwright_fixture_seed_coupons(): array {
    $fixed_coupon = bsc_playwright_fixture_get_or_create_coupon( [
        'code'          => 'QA10OFF',
        'discount_type' => 'fixed_cart',
        'amount'        => '10000',
        'description'   => 'Fixture fixed-cart coupon for checkout smoke.',
    ] );

    $free_shipping_coupon = bsc_playwright_fixture_get_or_create_coupon( [
        'code'          => 'QAFREESHIP',
        'discount_type' => 'fixed_cart',
        'amount'        => '1',
        'free_shipping' => true,
        'description'   => 'Fixture free-shipping coupon for checkout smoke.',
    ] );

    return [
        'fixed'        => $fixed_coupon->get_code(),
        'freeShipping' => $free_shipping_coupon->get_code(),
    ];
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

function bsc_playwright_fixture_email_preview_routes(): array {
    $routes = [];

    if (!function_exists('bsc_get_email_preview_definitions') || !function_exists('bsc_get_email_preview_url')) {
        return $routes;
    }

    foreach (array_keys((array) bsc_get_email_preview_definitions()) as $slug) {
        $routes[$slug] = bsc_get_email_preview_url((string) $slug);
    }

    return $routes;
}

$email = bsc_playwright_fixture_email();
$password = bsc_playwright_fixture_password();
$admin_username = bsc_playwright_fixture_admin_username();
$admin_email = bsc_playwright_fixture_admin_email();
$admin_password = bsc_playwright_fixture_admin_password();
$role_password = bsc_playwright_fixture_role_password();
$user = bsc_playwright_fixture_get_or_create_customer($email, $password);
$admin_user = bsc_playwright_fixture_get_or_create_admin($admin_username, $admin_email, $admin_password);
$order = bsc_playwright_fixture_get_or_create_order((int) $user->ID);
$public_catalog = bsc_playwright_fixture_seed_public_visual_catalog();
$public_category = $public_catalog['category'];
$public_product = $public_catalog['product'];
$coupons = bsc_playwright_fixture_seed_coupons();

$payload = [
    'auth' => [
        'email'    => $email,
        'username' => $user->user_login,
        'password' => $password,
    ],
    'routes' => [
        'groupCategory'    => trailingslashit(home_url('/product-category/' . bsc_playwright_fixture_visual_group_slug() . '/')),
        'categoryGrid'     => bsc_playwright_fixture_visual_category_grid_url(),
        'login'            => bsc_playwright_fixture_login_url(),
        'account'          => bsc_playwright_fixture_account_url(),
        'accountAddresses' => bsc_playwright_fixture_account_addresses_url(),
        'accountEdit'      => bsc_playwright_fixture_account_edit_url(),
        'accountOrders'    => bsc_playwright_fixture_account_orders_url(),
        'bubblePoints'     => bsc_playwright_fixture_bubble_points_url(),
        'category'         => get_term_link($public_category),
        'product'          => get_permalink($public_product->get_id()),
        'thankYou'         => $order->get_checkout_order_received_url(),
        'viewOrder'        => bsc_playwright_fixture_account_endpoint_url('view-order', (string) $order->get_id()),
    ],
    'admin' => [
        'username' => $admin_user->user_login,
        'email'    => $admin_user->user_email,
        'password' => $admin_password,
    ],
    'adminVariants' => bsc_playwright_fixture_admin_variants($admin_password),
    'adminRoles' => bsc_playwright_fixture_admin_role_users($role_password),
    'previewRoutes' => bsc_playwright_fixture_email_preview_routes(),
    'order' => [
        'id'     => $order->get_id(),
        'number' => $order->get_order_number(),
        'status' => $order->get_status(),
    ],
    'coupons' => $coupons,
];

echo wp_json_encode($payload, JSON_UNESCAPED_SLASHES);
