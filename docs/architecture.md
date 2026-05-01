# BSC Theme Architecture

## Runtime Shape

BSC is a custom WordPress theme with WooCommerce extensions implemented through theme files, hooks, AJAX handlers, templates, and local admin modules.

Request flow:

```text
WordPress bootstrap
  -> functions.php
    -> inc/setup/*
    -> inc/scripts/enqueue-scripts.php
    -> inc/woocommerce.php
    -> inc/ajax/*.php
    -> includes/class-bsc-roles.php
    -> includes/class-bsc-permissions.php
    -> includes/class-bsc-stock.php
    -> emails/bsc-emails.php
  -> selected template
  -> components/header.php
  -> page/template/component content
  -> components/footer.php
```

AJAX flow:

```text
admin-ajax.php
  -> check_ajax_referer()
  -> capability check when needed
  -> sanitize input
  -> WooCommerce/theme operation
  -> wp_send_json_success() or wp_send_json_error()
```

## Core Areas

### Storefront

- `front-page.php`: home sections, product sliders, newsletter shell.
- `components/header.php` and `components/header/*`: desktop/mobile navigation.
- `components/products/*`: product cards, sliders, category metadata, filters.
- `woocommerce/archive-product.php`: shop/category archive entry.
- `woocommerce/single-product.php`: product detail page.
- `page-cart.php`: custom cart page.

### Checkout

- `page-checkout.php`: custom checkout route.
- `components/checkout/*`: checkout form, cart sidebar, coupons, payment, summary, view router.
- `inc/ajax/cart-actions.php`: add/update/remove cart AJAX.
- `inc/ajax/checkout-actions.php`: checkout support AJAX.
- `js/cart.js`: cart UI state, quantity controls, cart fragments.
- `js/checkout.js`: checkout interactions, destination/city reload, shipping summary refresh.

High-risk behavior:

- Cart quantities should target WooCommerce `cart_item_key` when available.
- UI must reconcile from server responses, not only optimistic local state.
- Checkout destination changes must avoid stale AJAX responses.

### Admin Operations

- `admin/bsc-admin-menu.php`: BSC admin entry points and dashboard.
- `admin/bsc-orders-page.php`: orders table actions, tracking, CSV export, packing view.
- `admin/class-bsc-orders-table.php`: orders list table.
- `admin/bsc-products-page.php`: product stock list and inline stock actions.
- `admin/bsc-product-edit-page.php`: product edit screen.
- `admin/bsc-reports-page.php`: sales and stock reports.
- `admin/bsc-showroom-page.php`: showroom sale flow.
- `includes/class-bsc-permissions.php`: role/page enforcement.

### Dual Stock

- `includes/class-bsc-stock.php` owns stock helpers.
- Web order processing deducts from `_stock_bodega`.
- Showroom sales deduct from `_stock_tienda`.
- Packing view can deduct each order item from `bodega` by default or `tienda`/showroom when selected by the operator.
- Manual adjustments should go through `BSC_Stock::adjust()` so stock movement logs stay consistent.

### Emails

- `emails/bsc-emails.php`: email dispatcher and status hooks.
- `emails/bsc-email-helpers.php`: shared helpers.
- `emails/bsc-order-*.php`: order lifecycle templates.
- `emails/bsc-followup-*.php`: follow-up templates.

### Tests And Tooling

- `tools/lint-php.js`: PHP syntax lint.
- `tools/check-js-syntax.js`: JavaScript syntax lint.
- `tools/check-scss-entrypoints.js`: SCSS compile check.
- `tests/e2e/smoke`: release smoke coverage.
- `tests/e2e/visual`: visual regression coverage.

Default validation:

```bash
npm run lint
npm run test:e2e:smoke
```

Use visual tests for UI changes:

```bash
npm run test:e2e:visual
```

## Release Documents

- `MVP2_RELEASE_STATUS.md`: latest known gate status and residual notes.
- `MVP2_GO_LIVE_CHECKLIST.md`: production deployment checklist and rollback pack.
- `CHANGELOG.md`: durable history of delivered work.

## Documentation Policy

Keep active docs small and current. Completed implementation plans should not remain in active ticket folders. Historical details belong in git commits, `CHANGELOG.md`, or release notes.
