# BSC-058 - Checkout shipping prices and iOS selects

## Objective
Fix checkout shipping price selection so Bogota or Cundinamarca costs 9000 COP, every other Colombian destination costs 20000 COP, and existing free shipping rules still win. Also make department and city selects styled on iOS/iPhone.

## Context
The custom checkout renders billing state and city through WooCommerce fields in `components/checkout/checkout-form.php`. `js/checkout.js` reloads the city field after department changes and triggers WooCommerce checkout recalculation. `inc/woocommerce.php` currently filters `woocommerce_package_rates`, but it depends on distinct rate labels and only treats Cundinamarca plus Bogota as the local case. If WooCommerce returns only one flat rate, the current code leaves that rate untouched, so non-local destinations can keep the 9000 COP rate.

## Files to Inspect
- `inc/woocommerce.php`
- `js/checkout.js`
- `components/checkout/checkout-form.php`
- `components/checkout/checkout-summary.php`
- `sass/components/checkout/_checkout-form.scss`
- `style.css`

## Implementation Plan
1. Keep the existing checkout field flow and WooCommerce update events.
2. Add helper functions to normalize destination values and detect Bogota/Cundinamarca.
3. Preserve free shipping when the cart reaches the configured 300000 COP threshold or has a WooCommerce free-shipping coupon.
4. Force flat-rate shipping costs to 9000 COP for Bogota/Cundinamarca and 20000 COP elsewhere.
5. If free shipping is available, remove paid flat rates so zero-cost shipping is selected.
6. Add native select styling for billing/shipping state and city fields, including iOS Safari appearance resets.
7. Compile Sass to `style.css`.

## Acceptance Criteria
- No department/city selected: shipping stays hidden with the existing guidance message.
- Bogota selected: shipping cost is 9000 COP unless free shipping applies.
- Any Cundinamarca city selected: shipping cost is 9000 COP unless free shipping applies.
- Any non-Cundinamarca destination selected: shipping cost is 20000 COP unless free shipping applies.
- Cart subtotal after discounts at or above 300000 COP keeps free shipping.
- A valid free-shipping coupon keeps free shipping.
- Department/city fields retain BSC styling on iOS/iPhone native select rendering.

## Manual QA
1. Open `/checkout` with products below 300000 COP and no coupon.
2. Select Cundinamarca + Bogota and confirm shipping is 9000 COP.
3. Select Cundinamarca + another city and confirm shipping is 9000 COP.
4. Select Antioquia + Medellin and confirm shipping is 20000 COP.
5. Add enough products to reach 300000 COP after discounts and confirm shipping is free.
6. Apply a free-shipping coupon below 300000 COP and confirm shipping is free.
7. Check iPhone Safari or iOS simulator and confirm department/city selects are visibly styled.

## Rollback Notes
Revert this ticket's changes in `inc/woocommerce.php`, `sass/components/checkout/_checkout-form.scss`, and the compiled CSS assets. WooCommerce will return to label-based flat-rate filtering.
