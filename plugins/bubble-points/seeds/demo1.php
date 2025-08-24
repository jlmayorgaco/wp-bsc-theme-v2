<?php
/**
 * Bubble Points – Demo Seeder with Cleaner
 *
 * Run:
 *   wp eval-file wp-content/themes/wp-bsc-theme-v2/plugins/bubble-points/seeds/demo1.php
 */

if ( ! defined('ABSPATH') ) { exit; }

global $wpdb;

/** ----------------------------------------------------------------
 * Sanity checks
 * -------------------------------------------------------------- */
if ( ! class_exists('WooCommerce') ) {
  echo "WooCommerce is not active.\n";
  return;
}

$ledger_table = $wpdb->prefix . 'bsc_points_ledger';
$has_table = (int) $wpdb->get_var( $wpdb->prepare(
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s",
  $ledger_table
) );

if ( $has_table === 0 ) {
  echo "Ledger table {$ledger_table} does not exist. Aborting.\n";
  echo "Tip: ensure your Bubble Points installer (dbDelta) has run.\n";
  return;
}

/** ----------------------------------------------------------------
 * Helpers
 * -------------------------------------------------------------- */
function seed_log($msg) {
  if ( defined('WP_CLI') && WP_CLI ) { WP_CLI::log($msg); } else { echo $msg . "\n"; }
}

function get_product_id_by_sku_safe($sku) {
  if ( function_exists('wc_get_product_id_by_sku') ) {
    $id = wc_get_product_id_by_sku($sku);
    if ( $id ) return $id;
  }
  global $wpdb;
  return (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
    $sku
  ));
}

/** ----------------------------------------------------------------
 * CLEAN PREVIOUS SEEDS
 * - Deletes demo orders (by meta _bsc_demo_seed)
 * - Deletes demo users (user_login LIKE 'bsc_demo_%')
 * - Deletes their ledger rows
 * - Deletes demo product (SKU BSC-DEMO-PROD)
 * -------------------------------------------------------------- */
seed_log("== Cleaning previous demo data ==");

// 1) Delete orders marked by _bsc_demo_seed
$order_ids = $wpdb->get_col( $wpdb->prepare(
  "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s",
  '_bsc_demo_seed'
));
if ($order_ids) {
  foreach ($order_ids as $oid) {
    // Remove ledger rows tied to these orders (if any)
    $wpdb->query( $wpdb->prepare(
      "DELETE FROM $ledger_table WHERE order_id = %d",
      (int)$oid
    ));
    // Delete order permanently
    wp_delete_post((int)$oid, true);
  }
  seed_log("Deleted ".count($order_ids)." demo orders and related ledger rows.");
} else {
  seed_log("No prior demo orders found.");
}

// 2) Find demo users and clean their ledger + delete users
$demo_users = get_users([
  'search'         => 'bsc_demo_*',
  'search_columns' => ['user_login'],
  'fields'         => ['ID','user_login']
]);

if ($demo_users) {
  require_once ABSPATH . 'wp-admin/includes/user.php';
  foreach ($demo_users as $u) {
    // delete ledger rows for this user
    $wpdb->query( $wpdb->prepare(
      "DELETE FROM $ledger_table WHERE user_id = %d",
      (int)$u->ID
    ));
    // delete user (reassign = null)
    wp_delete_user((int)$u->ID);
  }
  seed_log("Deleted ".count($demo_users)." demo users and their ledger rows.");
} else {
  seed_log("No prior demo users found.");
}

// 3) Delete demo product by SKU
$sku = 'BSC-DEMO-PROD';
$prod_id = get_product_id_by_sku_safe($sku);
if ($prod_id) {
  wp_delete_post($prod_id, true);
  seed_log("Deleted demo product #$prod_id (SKU $sku).");
} else {
  seed_log("No demo product found to delete (SKU $sku).");
}

seed_log("== Clean done ==\n");

/** ----------------------------------------------------------------
 * SEED NEW DATA
 * -------------------------------------------------------------- */

// ---- Create (or fetch) a hidden demo product ----
$sku = 'BSC-DEMO-PROD';
$product_id = get_product_id_by_sku_safe($sku);

if ( ! $product_id ) {
  $price = rand(15, 49);
  $product = new WC_Product_Simple();
  $product->set_name('BSC Demo Product');
  $product->set_regular_price($price);
  $product->set_sku($sku);
  $product->set_catalog_visibility('hidden');
  $product->set_status('publish');
  $product->save();
  $product_id = $product->get_id();
  seed_log("Created demo product #$product_id (SKU $sku, $$price)");
} else {
  seed_log("Using existing product #$product_id (SKU $sku)");
}

// ---- Seeding params ----
$count_users      = 5;
$orders_per_user  = 5;
$suffix           = time();               // unique per run for usernames
$default_password = wp_generate_password(12);

// ---- Main loop ----
$users_created = [];

for ( $i = 1; $i <= $count_users; $i++ ) {

  // Create user
  $username = "bsc_demo_{$i}_{$suffix}";
  $email    = "{$username}@example.com";

  $user_id = username_exists($username);
  if ( ! $user_id ) {
    $user_id = wp_create_user($username, $default_password, $email);
    if ( is_wp_error($user_id) ) {
      seed_log("Failed to create user $username: " . $user_id->get_error_message());
      continue;
    }
    wp_update_user(['ID' => $user_id, 'display_name' => "Demo User {$i}"]);
    seed_log("Created user #$user_id ($username)");
  } else {
    seed_log("User exists #$user_id ($username)");
  }

  // Ensure starting points meta exists
  $current_points_raw = get_user_meta($user_id, 'bsc_bubble_points', true);
  if ($current_points_raw === '') {
    update_user_meta($user_id, 'bsc_bubble_points', 0);
  }

  // Create orders + award points per order
  for ( $k = 1; $k <= $orders_per_user; $k++ ) {
    $order = wc_create_order(['customer_id' => $user_id]);

    $product  = wc_get_product($product_id);
    $quantity = rand(1, 3);
    $order->add_product($product, $quantity);

    // minimal billing
    $order->set_address([
      'first_name' => 'Demo',
      'last_name'  => "User {$i}",
      'email'      => $email,
      'phone'      => '0000000000',
      'address_1'  => '123 Demo St',
      'city'       => 'Demoville',
      'country'    => 'US',
    ], 'billing');

    // mark as seed for cleanup
    $order->update_meta_data('_bsc_demo_seed', $suffix);
    $order->calculate_totals();
    $order->update_status('completed', 'Demo seed order');
    $order->save();

    $oid = $order->get_id();
    seed_log("  • Order #{$oid} for user #$user_id (qty $quantity)");

    // Award points: 1 point per 1000 (COP) of order total (adjust if needed)
    $award_points = (int) floor($order->get_total() / 1000);
    if ($award_points > 0) {
      $current = (int) get_user_meta($user_id, 'bsc_bubble_points', true);
      $new_bal = $current + $award_points;
      update_user_meta($user_id, 'bsc_bubble_points', $new_bal);

      $wpdb->insert(
        $ledger_table,
        [
          'user_id'       => $user_id,
          'delta'         => $award_points,
          'balance_after' => $new_bal,
          'reason'        => 'order_complete',
          'order_id'      => $oid,
          'meta'          => wp_json_encode([
            'order_total' => $order->get_total(),
            'currency'    => $order->get_currency()
          ]),
          'created_at'    => current_time('mysql'),
        ],
        ['%d','%d','%d','%s','%d','%s','%s']
      );
      seed_log("    → +{$award_points} pts for order #{$oid} (new bal {$new_bal})");
    }
  }

  // Manual points transaction in ledger (no order_id)
  $manual_points   = rand(10, 100);
  $reason          = 'manual_seed';
  $created_at_gmt  = gmdate('Y-m-d H:i:s', time() - rand(0, 86400 * 14)); // within last 14 days

  $current_meta = (int) get_user_meta($user_id, 'bsc_bubble_points', true);
  $new_total    = $current_meta + $manual_points;

  $ok = $wpdb->insert(
    $ledger_table,
    [
      'user_id'       => $user_id,
      'delta'         => $manual_points,           // correct column
      'balance_after' => $new_total,
      'reason'        => $reason,
      'order_id'      => null,
      'meta'          => wp_json_encode(['note' => 'seed demo manual']),
      'created_at'    => $created_at_gmt,
    ],
    ['%d','%d','%d','%s','%d','%s','%s']
  );

  if ($ok === false) {
    seed_log("  ! Failed to insert ledger row for user #$user_id: " . $wpdb->last_error);
  } else {
    update_user_meta($user_id, 'bsc_bubble_points', $new_total);
    seed_log("  • Ledger +$manual_points pts (manual), new meta total = $new_total");
  }

  $users_created[] = $user_id;
}

// Summary
seed_log("Done. Users affected: " . implode(', ', $users_created));
seed_log("You can now test the Bubble Points admin: all users listed, balances shown (0 if none), and per-user history with order links or Manual + notes.");
