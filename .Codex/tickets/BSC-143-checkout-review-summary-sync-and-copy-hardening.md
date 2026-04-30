# BSC-143 — Checkout review summary sync and copy hardening

## Objective
Unify the checkout review-summary source of truth so coupons, shipping, and totals stay synchronized across AJAX flows, while cleaning visible checkout copy without changing the approved layout.

## Context
- The checkout summary duplicated shipping/totals logic in multiple places.
- `coupons.js` only updated DOM totals when payload values were truthy, which could leave stale values during transitory checkout states.
- The review-summary renderer still contained mojibake in visible labels.

## Files to inspect
- `inc/checkout-review-summary-helpers.php`
- `components/checkout/checkout-summary.php`
- `inc/ajax/review-summary-actions.php`
- `inc/ajax/coupons-actions.php`
- `js/coupons.js`
- `tests/e2e/smoke/site-smoke.spec.js`

## Exact implementation plan
1. Extract shared checkout summary/shipping helpers into one reusable PHP helper file.
2. Make both the review-summary renderer and coupon AJAX payload consume that shared source.
3. Fix visible checkout summary labels and separators.
4. Make coupon DOM syncing update fields by payload presence, not truthiness.
5. Extend smoke coverage for the shipping pending state before destination selection.

## Acceptance criteria
- Coupon apply/remove and review-summary rendering use the same shipping/total payload source.
- Checkout summary labels display clean Spanish copy.
- Coupon updates do not leave stale shipping/subtotal/total values in the DOM.
- Smoke covers the shipping pending state and the transition after selecting destination.

## Manual QA
1. Open checkout with a product in cart.
2. Confirm shipping is hidden until department is selected and the pending notice is visible.
3. Select department/city and confirm the shipping row appears.
4. Apply/remove a normal coupon and verify subtotal/total stay in sync.
5. Apply/remove a free-shipping coupon and verify the shipping row toggles to `Gratis`.

## Rollback notes
- Revert the helper extraction and restore the previous inline summary logic in `checkout-summary.php` and `coupons-actions.php`.
