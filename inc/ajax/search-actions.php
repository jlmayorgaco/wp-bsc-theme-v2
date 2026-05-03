<?php
defined('ABSPATH') || exit;

/**
 * BSC-038: Custom Product Search — optimized with transient cache.
 * Searches by: title/description, SKU, brand (product category taxonomy).
 * Results cached 15 min per unique query; client cache handled in search.js.
 */

add_action('wp_ajax_bsc_search_products', 'bsc_search_products');
add_action('wp_ajax_nopriv_bsc_search_products', 'bsc_search_products');

function bsc_search_products() {
    check_ajax_referer('bsc_ajax_action', 'nonce');

    if ( ! bsc_search_rate_limit_passed() ) {
        wp_send_json_error(['message' => 'Demasiadas busquedas. Intenta de nuevo en un momento.'], 429);
    }

    $query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

    if ( strlen($query) < 2 ) {
        wp_send_json_success(['products' => []]);
    }

    // ── Transient cache (15 min per unique search term) ────────────────────
    $cache_key = 'bsc_search_' . md5($query);
    $cached    = get_transient($cache_key);
    if ( $cached !== false ) {
        wp_send_json_success(['products' => $cached]);
    }

    $collected_ids = [];

    // ── 1. Title + description ─────────────────────────────────────────────
    $q1 = new WP_Query([
        'post_type'              => 'product',
        'post_status'            => 'publish',
        's'                      => $query,
        'fields'                 => 'ids',
        'posts_per_page'         => 8,
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);
    if ( ! empty($q1->posts) ) {
        $collected_ids = array_merge($collected_ids, $q1->posts);
    }

    // ── 2. SKU (product meta _sku) ─────────────────────────────────────────
    if ( count($collected_ids) < 8 ) {
        $q2 = new WP_Query([
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'fields'                 => 'ids',
            'posts_per_page'         => 8,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => [[
                'key'     => '_sku',
                'value'   => $query,
                'compare' => 'LIKE',
            ]],
        ]);
        if ( ! empty($q2->posts) ) {
            $collected_ids = array_merge($collected_ids, $q2->posts);
        }
    }

    // ── 3. Brand / category name ───────────────────────────────────────────
    if ( count($collected_ids) < 8 ) {
        $matching_terms = get_terms([
            'taxonomy'   => 'product_cat',
            'name__like' => $query,
            'fields'     => 'ids',
            'hide_empty' => true,
        ]);

        if ( ! empty($matching_terms) && ! is_wp_error($matching_terms) ) {
            $q3 = new WP_Query([
                'post_type'              => 'product',
                'post_status'            => 'publish',
                'fields'                 => 'ids',
                'posts_per_page'         => 8,
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'tax_query'              => [[
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $matching_terms,
                ]],
            ]);
            if ( ! empty($q3->posts) ) {
                $collected_ids = array_merge($collected_ids, $q3->posts);
            }
        }
    }

    // ── Deduplicate and limit to 8 ─────────────────────────────────────────
    $product_ids = array_slice(array_unique($collected_ids), 0, 8);

    if ( empty($product_ids) ) {
        set_transient($cache_key, [], 15 * MINUTE_IN_SECONDS);
        wp_send_json_success(['products' => []]);
    }

    // ── Build response ─────────────────────────────────────────────────────
    $results = [];

    foreach ( $product_ids as $pid ) {
        $product = wc_get_product($pid);
        if ( ! $product ) continue;

        // Brand: first category with -marca in slug
        $brand = '';
        $terms = get_the_terms($pid, 'product_cat');
        if ( $terms && ! is_wp_error($terms) ) {
            foreach ( $terms as $term ) {
                if ( strpos($term->slug, '-marca') !== false ) {
                    $brand = $term->name;
                    break;
                }
            }
        }

        // Image
        $img_url = '';
        $img_id  = $product->get_image_id();
        if ( $img_id ) {
            $img_src = wp_get_attachment_image_src($img_id, 'woocommerce_thumbnail');
            $img_url = $img_src ? $img_src[0] : '';
        }

        $results[] = [
            'id'        => $pid,
            'name'      => $product->get_name(),
            'permalink' => get_permalink($pid),
            'image'     => $img_url,
            'brand'     => $brand,
            'price'     => strip_tags($product->get_price_html()),
            'sku'       => $product->get_sku(),
        ];
    }

    // ── Cache and return ───────────────────────────────────────────────────
    set_transient($cache_key, $results, 15 * MINUTE_IN_SECONDS);
    wp_send_json_success(['products' => $results]);
}

function bsc_search_rate_limit_passed(): bool {
    $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $key = 'bsc_search_rate_' . md5($ip);
    $hits = (int) get_transient($key);

    if ($hits >= 60) {
        return false;
    }

    set_transient($key, $hits + 1, MINUTE_IN_SECONDS);
    return true;
}
