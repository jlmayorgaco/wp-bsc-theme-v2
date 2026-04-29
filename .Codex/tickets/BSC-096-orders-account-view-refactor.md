# BSC-096 — Orders/account view refactor and account script extraction

## Objective
Refactor the `view-order` account organism into a reusable order-view class, remove inline account/order scripts, and extend authenticated visual coverage to the real `view order` route.

## Context
- `woocommerce/myaccount/view-order.php` currently mixes order lookups, brand helpers, summary math, shipping rendering, a hardcoded shop CTA, and an inline mobile scroll script.
- `woocommerce/myaccount/form-edit-account.php` still embeds an inline script to sync hidden WooCommerce name fields.
- This area is part of the release-critical `orders/account` organism and should follow the same reusable structure as `BSC_Products_Card`.

## Files To Inspect
- `woocommerce/myaccount/view-order.php`
- `woocommerce/myaccount/form-edit-account.php`
- `components/orders/class-bsc-order-view.php`
- `js/account-view-order.js`
- `js/account-edit.js`
- `inc/scripts/enqueue-scripts.php`
- `tests/e2e/fixtures/bootstrap-auth-visual-fixtures.php`
- `tests/e2e/helpers/env.js`
- `tests/e2e/visual/account-and-post-purchase.spec.js`

## Exact Implementation Plan
1. Move `view-order` rendering into a reusable `BSC_Order_View` class with dedicated render methods.
2. Replace the hardcoded shop CTA with a dynamic WooCommerce shop URL.
3. Move the mobile scroll helper for `view-order` into a dedicated script.
4. Move the hidden-name sync logic from `form-edit-account.php` into a dedicated script.
5. Expose a deterministic `viewOrder` route from the Playwright fixture and add a visual baseline for it.

## Acceptance Criteria
- `view-order.php` becomes a thin orchestrator.
- No inline `<script>` remains in `view-order.php` or `form-edit-account.php`.
- The shop CTA on `view-order` is dynamic.
- Authenticated visual regression covers `view-order`.
- No pixel drift on account/thank-you/view-order layouts.

## Manual QA
1. Open an order detail from `Mi cuenta > Pedidos`.
2. Verify overview, item list, summary, shipping block, and Bubble Points display.
3. On mobile width, confirm the order items block still scrolls into view.
4. Open `Editar cuenta` and verify the hidden name fields still sync correctly on submit.

## Rollback Notes
- Restore the original inline logic in `view-order.php` and `form-edit-account.php`.
- Remove `class-bsc-order-view.php`, `account-view-order.js`, and `account-edit.js`.
- Revert fixture/test additions for `viewOrder`.
