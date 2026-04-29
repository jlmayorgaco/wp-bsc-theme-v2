# BSC-095 — Storefront inline hidden-state cleanup

## Objective
Remove the remaining low-risk inline `display:none` states from storefront PHP views and replace them with explicit SCSS modifiers.

## Context
- `components/products/card.php` still hides the add-to-cart button inline when the product is already in cart.
- `plugins/bubble-points/views/coupons-bubble-points.php` still hides coupon actions inline.
- `Product Card` is a reusable primitive reference for the rest of the refactor, so its UI state should be class-driven instead of inline-style driven.

## Files To Inspect
- `components/products/card.php`
- `js/cart.js`
- `plugins/bubble-points/views/coupons-bubble-points.php`
- `sass/components/products/_card.scss`
- `sass/components/bubble-points/_bubble-points-coupons-list.scss`

## Exact Implementation Plan
1. Replace inline hidden button rendering in `Product Card` with a modifier class.
2. Update `cart.js` to toggle the hidden modifier instead of using `.hide()` / `.show()` for the product-card CTA.
3. Replace the hidden coupon-wallet actions inline style with a modifier class.
4. Recompile CSS and validate with product-card smoke and Bubble Points visual coverage.

## Acceptance Criteria
- No inline `display:none` remains in the targeted storefront views.
- Product-card add-to-cart still swaps to quantity controls and restores the CTA at zero.
- Bubble Points wallet keeps the same pixel output.
- No visual drift in public category/PDP or Bubble Points baselines.

## Manual QA
1. Add a product to cart from the category page.
2. Reduce quantity to zero and confirm the CTA returns exactly in place.
3. Open Bubble Points and confirm coupon cards look identical.

## Rollback Notes
- Restore the inline hidden styles in the two PHP files.
- Revert the modifier classes and `cart.js` state toggles.
