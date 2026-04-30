# BSC-138 - Checkout Coupon, Shipping Integration, and Reusable Primitives

## Objective
Close the remaining checkout coupon and shipping integration gaps without changing the approved UI, and align the coupon block with reusable button and floating-label primitives.

## Context
The coupon flow still had working AJAX endpoints, but the coupon renderer itself became empty during refactor work. That left the review summary updates alive while the applied-coupon shell was missing from the DOM. The checkout summary refresh also replaced the content of `#bsc-review-summary` with another wrapper carrying the same id, which could accumulate invalid nested markup after repeated updates.

## Files to inspect
- `components/checkout/checkout-coupons.php`
- `inc/ajax/coupons-actions.php`
- `js/cart.js`
- `js/coupons.js`
- `sass/components/checkout/_checkout-coupons.scss`
- `sass/components/forms/_forms.scss`
- `tests/e2e/helpers/env.js`
- `tests/e2e/helpers/ui.js`
- `tests/e2e/fixtures/bootstrap-auth-visual-fixtures.php`
- `tests/e2e/smoke/site-smoke.spec.js`

## Exact implementation plan
1. Restore the checkout coupon renderer with the same class contract used by the checkout view.
2. Always render the applied-coupon shell so the AJAX layer has a stable DOM target.
3. Move the coupon CTA and input to reusable floating-field and capsule-button primitives.
4. Normalize coupon and shipping payload data so the summary fallback text matches the visible review summary.
5. Replace the review-summary wrapper instead of nesting duplicate `#bsc-review-summary` nodes.
6. Add smoke coverage for apply/remove coupon and shipping plus free-shipping coupon interaction.

## Acceptance criteria
- `components/checkout/checkout-coupons.php` renders a stable coupon block and is not empty.
- Applying and removing coupons updates both the applied-coupon list and the review summary.
- Shipping text reacts correctly to destination changes and free-shipping coupons.
- The checkout coupon UI uses reusable button and field primitives without visual drift.
- Checkout smoke passes for coupon apply/remove and shipping-coupon interaction.

## Manual QA
1. Open checkout with a seeded cart and verify the coupon input, label, and CTA layout on desktop and mobile.
2. Apply the fixed coupon and verify the total changes and the applied-coupon card appears.
3. Remove the coupon and verify the original total returns.
4. Select a billing destination, verify shipping appears, then apply the free-shipping coupon and confirm shipping changes to `Gratis`.
5. Remove the free-shipping coupon and confirm the previous shipping text returns.

## Rollback notes
- Revert the commit for `BSC-138`.
- Restore the previous versions of the checkout coupon renderer, coupon AJAX payload, and review-summary refresh behavior.