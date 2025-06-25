<?php
function get_related_product_skus(int $product_id, int $limit = 8): array {
    $skus = [];

    // Step 1: Get initial related products
    $related_ids = wc_get_related_products($product_id, $limit);

    // Convert to SKUs
    foreach ($related_ids as $id) {
        if ($id === $product_id) continue;
        $product = wc_get_product($id);
        if ($product && $product->get_sku()) {
            $skus[] = $product->get_sku();
        }
    }

    // Step 2: Fallback if needed
    $remaining = $limit - count($skus);
    if ($remaining > 0) {
        // Get same categories
        $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);

        if (!empty($terms)) {
            // Fetch random products from same categories
            $fallback_products = wc_get_products([
                'limit' => $remaining * 2, // ask for more, we'll filter later
                'status' => 'publish',
                'exclude' => array_merge([$product_id], $related_ids),
                'category' => $terms,
                'orderby' => 'rand',
                'return' => 'ids',
            ]);

            foreach ($fallback_products as $fid) {
                if (count($skus) >= $limit) break;
                $product = wc_get_product($fid);
                $sku = $product ? $product->get_sku() : '';
                if ($sku && !in_array($sku, $skus)) {
                    $skus[] = $sku;
                }
            }
        }
    }

    return array_slice($skus, 0, $limit);
}

function get_cart_recommendation_skus(int $limit = 8): array {
    $cart = WC()->cart;
    if (!$cart || $cart->is_empty()) {
        return [];
    }

    $cart_product_ids = array_map(function($item) {
        return $item['product_id'];
    }, $cart->get_cart());

    $related_ids = [];

    foreach ($cart_product_ids as $product_id) {
        $related = wc_get_related_products($product_id, $limit);
        $related_ids = array_merge($related_ids, $related);
    }

    $related_ids = array_unique($related_ids);
    shuffle($related_ids); // Optional: randomize order

    $skus = [];
    foreach (array_slice($related_ids, 0, $limit) as $id) {
        $product = wc_get_product($id);
        if ($product && $product->get_sku()) {
            $skus[] = $product->get_sku();
        }
    }

    return $skus;
}

?>
