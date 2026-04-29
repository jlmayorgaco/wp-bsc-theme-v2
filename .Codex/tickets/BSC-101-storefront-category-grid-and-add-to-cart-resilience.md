# BSC-101 - Storefront category-grid routing and add-to-cart resilience

## Objective
Recover the storefront smoke chain by ensuring the Playwright fixture targets a real product-grid route and by making product-card add-to-cart independent from the optional `a.cart-contents` fragment.

## Context
- Storefront smoke prepares checkout from `routes.category`.
- The fixture can return a taxonomy route that resolves to a category depth where `BSCShopPage` does not render `BSC_Products_Card`.
- When no cards render, add-to-cart, checkout smoke, and coupon smoke all fail in cascade.
- `js/cart.js` also blocks quantity-controls injection when Woo does not include `a.cart-contents` in the fragments payload.

## Files To Inspect
- `tests/e2e/fixtures/bootstrap-auth-visual-fixtures.php`
- `tests/e2e/helpers/env.js`
- `tests/e2e/helpers/ui.js`
- `tests/e2e/smoke/site-smoke.spec.js`
- `tests/e2e/visual/public-pages.spec.js`
- `js/cart.js`

## Exact Implementation Plan
1. Expose a dedicated fixture route for the real product-grid page.
2. Add a helper that tries the deterministic grid route first and falls back safely when needed.
3. Make public smoke/visual checkout and PDP flows consume the grid-route helper instead of assuming a single category URL.
4. Remove the hard dependency on `fragments['a.cart-contents']` before showing quantity controls on the product card.

## Acceptance Criteria
- Storefront tests that need product cards no longer depend on a depth-1 category route.
- Product-card add-to-cart can show quantity controls even when Woo omits `a.cart-contents`.
- No public DOM/class changes in storefront templates.
- The suite is ready to rerun as soon as the local storefront host is reachable again.

## Manual QA
1. Open the deterministic product-grid route and confirm cards render.
2. Add one product to cart from the card and confirm quantity controls appear.
3. Reduce quantity to zero and confirm the CTA comes back.
4. Open checkout and confirm the form loads with the seeded cart.

## Rollback Notes
- Remove `categoryGrid` fixture/env wiring.
- Restore the previous direct category route usage in smoke/visual tests.
- Restore the original `cart.js` `a.cart-contents` guard.
