# BSC-068 - Checkout shipping session sync follow-up

## Objective
Fix the remaining checkout shipping issue where the custom review summary can keep showing the stale 9000 COP rate after department/city changes.

## Context
`refreshReviewSummary()` calls the custom `bsc_get_review_summary` AJAX endpoint after WooCommerce checkout updates. That custom request previously sent only `action` and `nonce`, so the server-side summary render could calculate shipping without the selected checkout destination. WooCommerce package rates may also be cached in the session, keeping the old flat-rate cost.

## Files to Inspect
- `js/cart.js`
- `inc/ajax/review-summary-actions.php`
- `inc/woocommerce.php`

## Implementation Plan
1. Send serialized checkout form fields with the custom review-summary AJAX request.
2. On the server, sync the posted billing/shipping destination into `WC()->customer`.
3. Clear cached `shipping_for_package_*` session values after the destination changes.
4. Make the flat-rate filter prefer posted/customer destination data when WooCommerce package destination data is missing or stale.
5. Keep the previous 9000 COP local, 20000 COP national, and free-shipping behavior.

## Acceptance Criteria
- Changing department/city updates the custom review summary shipping amount.
- Cundinamarca/Bogota destinations calculate 9000 COP.
- Non-Cundinamarca destinations calculate 20000 COP.
- Free shipping by threshold or free-shipping coupon still wins.

## Manual QA
1. In `/checkout`, below 300000 COP, select Cundinamarca + Bogota and confirm 9000 COP.
2. Without refreshing, change to Antioquia + Medellin and confirm the custom summary changes to 20000 COP.
3. Change back to Cundinamarca and confirm it returns to 9000 COP.
4. Test the 300000 COP free-shipping threshold and a free-shipping coupon.

## Rollback Notes
Revert the follow-up changes in `js/cart.js`, `inc/ajax/review-summary-actions.php`, and `inc/woocommerce.php`.
