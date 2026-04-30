## Objective

Fix the corrupted visible copy on the thank-you page and clean up the checkout form-state Sass so responsive/mobile maintenance is simpler without changing the approved UI.

## Context

- `BSC-138` and `BSC-139` left checkout, coupon, shipping, contact, and account-address flows green in smoke and visual regression.
- A follow-up audit found the thank-you heading in `checkout-view-thankyou.php` contains mojibake text.
- The checkout form Sass still contains duplicated error-state rules, dead selectors, and an encoding-corrupted comment, which makes future mobile changes harder to reason about.

## Files To Inspect

- `components/checkout/views/checkout-view-thankyou.php`
- `sass/components/checkout/_checkout-form.scss`
- `tests/e2e/visual/account-and-post-purchase.spec.js`

## Exact Implementation Plan

1. Replace the thank-you heading text with entity-safe copy so it renders correctly regardless of file encoding.
2. Remove duplicated and dead checkout field-state Sass rules while preserving the current visual result.
3. Keep the current class contract and DOM structure intact.
4. Rebuild CSS and refresh only the thank-you baseline if the corrected text changes the screenshot.

## Acceptance Criteria

- The thank-you heading renders correctly in storefront.
- Checkout field error/valid state Sass is cleaner and easier to maintain.
- No visual drift is introduced in checkout or thank-you beyond the corrected text.
- Relevant visual and smoke tests pass.

## Manual QA

1. Open checkout and intentionally trigger required-field validation on mobile and desktop.
2. Confirm error borders and wiggle behavior still match the current UI.
3. Open a valid thank-you route and verify the heading text reads correctly.
4. Re-check checkout coupon apply/remove and shipping update after the Sass cleanup.

## Rollback Notes

- Revert the commit for `BSC-140`.
- Restore the previous thank-you copy and checkout form Sass state rules.