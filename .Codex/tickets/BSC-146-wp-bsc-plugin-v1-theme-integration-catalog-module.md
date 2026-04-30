# BSC-146 — Integrate `wp-bsc-plugin-v1` catalog domain into the theme

## Objective

Absorb the useful catalog/filtering logic and legacy seed contracts from `wp-bsc-plugin-v1` into the custom theme as an internal module, without importing the plugin's duplicate WooCommerce runtime.

## Context

- The theme already owns storefront, cart, checkout, account, orders, emails, and Bubble Points.
- `wp-bsc-plugin-v1` contains overlapping catalog/filter logic plus a second WooCommerce override tree.
- Loading both runtimes would create conflicting contracts for templates, hooks, assets, and product rendering.

## Files To Inspect

- [functions.php](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\functions.php)
- [components/products/filters.php](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\components\products\filters.php)
- [inc/ajax/filters-actions.php](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\inc\ajax\filters-actions.php)
- [wp-bsc-plugin-v1/wcbscapf.php](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\wp-bsc-plugin-v1\wcbscapf.php)
- [wp-bsc-plugin-v1/includes/class-wc-bsc-template.php](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\wp-bsc-plugin-v1\includes\class-wc-bsc-template.php)
- [wp-bsc-plugin-v1/seed](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\wp-bsc-plugin-v1\seed)
- [plugins/bsc-catalog](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\plugins\bsc-catalog)

## Implementation Plan

1. Create `plugins/bsc-catalog` as the internal theme module for catalog/filter logic.
2. Move filter configuration, request parsing, sidebar rendering, AJAX query building, and product card rendering into dedicated classes.
3. Preserve the existing public contracts:
   - `bsc_render_custom_filters_sidebar()`
   - `bsc_filter_products()`
4. Copy the plugin seed JSON into the theme module so category/product seed contracts live inside the theme project.
5. Leave the plugin folder as migration source only; do not load its runtime.

## Acceptance Criteria

- Theme runtime uses `plugins/bsc-catalog` for catalog filters.
- Existing product-category templates continue to call the same compatibility functions.
- AJAX action `bsc_filter_products` remains stable.
- Legacy plugin seed data is preserved inside the theme module.
- No WooCommerce override from `wp-bsc-plugin-v1/woocommerce` is loaded.

## Manual QA

1. Open a level-3 product category route and verify the filter sidebar renders unchanged.
2. Apply radio and checkbox filters and confirm AJAX updates `#bscProductsContainer`.
3. Apply a price range and verify product results still filter correctly.
4. Open a PDP and verify product category meta/details still render as before.

## Rollback Notes

- Remove `plugins/bsc-catalog`.
- Restore the original procedural logic in:
  - `components/products/filters.php`
  - `inc/ajax/filters-actions.php`
- Re-add the direct filter AJAX include in `functions.php` if needed.
