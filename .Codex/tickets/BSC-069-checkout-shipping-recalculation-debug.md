# BSC-069 Checkout shipping recalculation debug

## Objective

Fix checkout shipping so Bogotá/Cundinamarca stays at 9,000 COP, other Colombian destinations such as Boyacá/Chiquinquirá update to 20,000 COP, and free shipping by coupon or the 300,000 COP threshold still takes priority.

## Context

The previous shipping override still showed 9,000 COP after selecting Boyacá and Chiquinquirá. Debugging found two separate issues:

- The custom checkout form did not include WooCommerce's required `checkout` class, so WooCommerce's `update_checkout` JavaScript exited before recalculating rates.
- The Colombia departments/cities plugin can send city values as codes, labels, or combined JavaScript strings, so the theme shipping override needs to resolve those formats before deciding whether the destination is local or national.

## Files To Inspect

- `components/checkout/views/checkout-view-main.php`
- `components/checkout/checkout-summary.php`
- `inc/woocommerce.php`
- `inc/ajax/checkout-actions.php`
- `inc/ajax/review-summary-actions.php`
- `js/checkout.js`
- `js/cart.js`
- `wp-content/plugins/wc-departamentos-y-ciudades-colombia/assets/places/CO-cities.php`
- `wp-content/plugins/wc-departamentos-y-ciudades-colombia/assets/js/place-select.js`

## Implementation Plan

1. Restore WooCommerce's standard checkout form classes so native checkout AJAX can run.
2. Refresh the custom BSC order summary directly when billing department/city changes, instead of depending only on WooCommerce's `updated_checkout` event.
3. Force WooCommerce shipping package destinations from posted checkout fields before rate hashes and cached package rates are used.
4. Parse WooCommerce normalized AJAX keys (`state`, `city`, `s_state`, `s_city`) and serialized `post_data`.
5. Resolve Colombia plugin city codes/labels so city code `15176000` maps to Boyacá and code `11001000` maps to Cundinamarca/Bogotá.
6. Keep free shipping coupons and the 300,000 COP threshold above location pricing.

## Acceptance Criteria

- Selecting Bogotá or any Cundinamarca city shows 9,000 COP unless free shipping applies.
- Selecting Boyacá / Chiquinquirá shows 20,000 COP without a page refresh.
- Changing from a local destination to a national destination clears stale cached shipping package rates.
- Free shipping coupons still show free shipping.
- Carts at or above 300,000 COP after discount still show free shipping.
- Checkout form submission and payment method updates still use WooCommerce's normal checkout JavaScript.

## Manual QA

1. Add a shippable product under 300,000 COP to the cart.
2. Open `/checkout`.
3. Select Cundinamarca / Bogotá and confirm shipping is 9,000 COP.
4. Change department to Boyacá and city to Chiquinquirá; confirm shipping updates to 20,000 COP without refreshing.
5. Change back to Cundinamarca / Bogotá; confirm shipping returns to 9,000 COP.
6. Apply a free-shipping coupon and confirm the shipping row is free.
7. Increase the cart subtotal above 300,000 COP after discounts and confirm shipping is free.

## Rollback Notes

Revert this ticket's commit to restore the previous checkout AJAX behavior and previous location-rate detection. If only the frontend behavior causes trouble, the lowest-risk rollback is to remove the direct summary refresh in `js/checkout.js` while keeping the PHP destination parsing.
