<?php
/**
 * BSC Custom Product Search
 * Searches by: title, description, SKU, brand (product category taxonomy)
 */

add_action('wp_ajax_bsc_search_products', 'bsc_search_products');
add_action('wp_ajax_nopriv_bsc_search_products', 'bsc_search_products');

function bsc_search_products() {
    $query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

    if ( strlen($query) < 3 ) {
        wp_send_json_success(['products' => []]);
    }

    $collected_ids = [];

    // ── 1. Title + description (WP native search: post_title + post_content) ──
    $q1 = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        's'              => $query,
        'fields'         => 'ids',
        'posts_per_page' => 20,
        'no_found_rows'  => true,
    ]);
    if ( ! empty($q1->posts) ) {
        $collected_ids = array_merge($collected_ids, $q1->posts);
    }

    // ── 2. SKU (product meta _sku) ──────────────────────────────────────────
    $q2 = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => 20,
        'no_found_rows'  => true,
        'meta_query'     => [[
            'key'     => '_sku',
            'value'   => $query,
            'compare' => 'LIKE',
        ]],
    ]);
    if ( ! empty($q2->posts) ) {
        $collected_ids = array_merge($collected_ids, $q2->posts);
    }

    // ── 3. Brand / category name matching the query ─────────────────────────
    $matching_terms = get_terms([
        'taxonomy'   => 'product_cat',
        'name__like' => $query,
        'fields'     => 'ids',
        'hide_empty' => true,
    ]);

    if ( ! empty($matching_terms) && ! is_wp_error($matching_terms) ) {
        $q3 = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => 20,
            'no_found_rows'  => true,
            'tax_query'      => [[
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $matching_terms,
            ]],
        ]);
        if ( ! empty($q3->posts) ) {
            $collected_ids = array_merge($collected_ids, $q3->posts);
        }
    }

    // ── Deduplicate and limit ───────────────────────────────────────────────
    $product_ids = array_slice(array_unique($collected_ids), 0, 12);

    if ( empty($product_ids) ) {
        wp_send_json_success(['products' => []]);
    }

    // ── Build response ──────────────────────────────────────────────────────
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

    wp_send_json_success(['products' => $results]);
}
