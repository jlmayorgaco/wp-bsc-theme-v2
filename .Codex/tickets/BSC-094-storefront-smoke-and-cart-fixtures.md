# BSC-094 — Storefront smoke stabilization and cart fixture coverage

## Objective
Stabilize the storefront smoke suite so it reflects the actual frontend contracts, and add deterministic cart coverage for add-to-cart, quantity controls, and checkout readiness.

## Context
- The storefront smoke suite currently has false negatives caused by outdated selectors and mismatched fixture routes.
- The category fixture was seeded into a taxonomy path that `BSCShopPage` does not render as a product grid.
- Checkout smoke depends on an implicit non-empty cart, which makes it flaky.
- `BSC-003` and `BSC-005` need release-grade E2E coverage around product cards and touch/add-to-cart behavior.

## Files To Inspect
- `tests/e2e/fixtures/bootstrap-auth-visual-fixtures.php`
- `tests/e2e/helpers/env.js`
- `tests/e2e/helpers/ui.js`
- `tests/e2e/smoke/site-smoke.spec.js`
- `tests/e2e/visual/public-pages.spec.js`

## Exact Implementation Plan
1. Seed a deterministic level-3 QA category under the existing `sk-rutina` tree.
2. Expose both the deterministic listing route and the grouped route from the fixture payload.
3. Add shared helpers to interact with product-card add-to-cart and prepare checkout deterministically.
4. Fix smoke expectations for viewport-specific header rendering.
5. Add smoke coverage for add-to-cart, qty controls, and qty-zero CTA restoration.
6. Make checkout visual/smoke tests prepare a cart before asserting the checkout page.

## Acceptance Criteria
- Storefront smoke passes in `storefront` mode.
- Category and PDP smoke use a deterministic listing route with visible product cards.
- Checkout smoke reaches checkout from a seeded cart instead of relying on ambient session state.
- Product card smoke covers add-to-cart and quantity rollback to zero.
- Category and checkout visual baselines remain green after deterministic setup.

## Manual QA
1. Open the QA category route and confirm product cards render.
2. Add the first product to cart from the category page.
3. Decrease quantity to zero and verify the CTA returns.
4. Add the product again and confirm `/checkout/` loads checkout instead of redirecting to cart.

## Rollback Notes
- Restore the previous fixture route structure.
- Remove the new shared cart helpers and smoke cases.
- Revert checkout/category visual expectations to the prior state.
