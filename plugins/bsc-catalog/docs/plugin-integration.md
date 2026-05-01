# BSC Catalog Integration

## Objective

Absorb the useful catalog/filtering domain from `wp-bsc-plugin-v1` into the theme runtime without importing its duplicate WooCommerce stack.

## Absorbed Into Theme Runtime

- Filter group contract by product group
- Product-category sidebar rendering
- AJAX product filtering endpoint
- Legacy plugin seed data snapshots:
  - `seed/legacy-plugin/bsc_categories_json.json`
  - `seed/legacy-plugin/bsc_products_json.json`

## Already Superseded By Theme

- Product meta/category details renderer
- Product card rendering
- Category page rendering
- Checkout/cart/account/email WooCommerce overrides

## Explicitly Excluded From Runtime

- Plugin bootstrap in `wp-bsc-plugin-v1/wcbscapf.php`
- Legacy WooCommerce template overrides in `wp-bsc-plugin-v1/woocommerce`
- Debug/template experiments in `class-wc-bsc-template.php`
- Generic widget/asset stack (`select2`, `nouislider`, plugin-wide CSS)
- Nested `.git` history

## Integration Rule

`wp-bsc-plugin-v1` is treated as a migration source, not as a second runtime. Once its useful contracts are copied into the theme, the theme runtime should only load `plugins/bsc-catalog`.

## Directory Mapping

| Legacy plugin area | Theme target | Status |
| --- | --- | --- |
| `includes/class-wc-bsc-template.php` product-category filter/sidebar behavior | `plugins/bsc-catalog/*` + `components/product-category.php` | Absorbed |
| `widgets/widget-bsc-wc-shop-product-filters/*` | `plugins/bsc-catalog/*` | Absorbed by contract |
| `seed/bsc_categories_json.json` | `plugins/bsc-catalog/seed/legacy-plugin/bsc_categories_json.json` | Copied |
| `seed/bsc_products_json.json` | `plugins/bsc-catalog/seed/legacy-plugin/bsc_products_json.json` | Copied |
| `woocommerce/*` overrides | Theme `woocommerce/*` | Excluded from runtime |
| `assets/*` generic plugin CSS/JS | Theme asset pipeline | Excluded from runtime |
| `utilities.php`, `seed/categories.php` procedural importers | Future data/import tooling | Deferred |
| `shortcuts/*`, `blocks/*`, `apps/*` | No active runtime dependency found | Deferred / review later |

## Source Removal

After the seed contracts were copied into `plugins/bsc-catalog/seed/legacy-plugin`, the physical `wp-bsc-plugin-v1` folder can be removed from the theme workspace without affecting the active runtime.
