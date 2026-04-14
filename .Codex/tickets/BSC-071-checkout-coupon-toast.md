# BSC-071 Checkout coupon toast feedback

## Objective

Add clear visual feedback when a customer applies or removes a coupon on checkout/cart, covering success, empty input, invalid coupon, expired coupon, and generic coupon failures on desktop and mobile.

## Context

The current coupon JavaScript uses a native `alert()` before showing an inline notice. That creates a rough browser-dependent experience and can be especially awkward on mobile. The AJAX endpoint also treated some WooCommerce coupon failures as success because `WC()->cart->apply_coupon()` returns `false`, not a `WP_Error`, when WooCommerce rejects a coupon.

## Files To Inspect

- `js/coupons.js`
- `inc/ajax/coupons-actions.php`
- `sass/components/checkout/_checkout-coupons.scss`
- `components/checkout/checkout-coupons.php`

## Implementation Plan

1. Replace native alerts with a fixed coupon toast.
2. Add accessible toast markup with `role="status"` and `aria-live="polite"`.
3. Return structured AJAX statuses for success, warning, and error.
4. Detect empty coupon codes, invalid coupons, already applied coupons, and expired coupons before applying.
5. Treat `WC()->cart->apply_coupon()` returning `false` as an error and surface the WooCommerce notice text when available.
6. Style the toast for desktop and mobile, including safe-area support on phones.

## Acceptance Criteria

- Empty coupon input shows a visible message asking for a coupon code.
- Valid coupon shows "Cupón agregado exitosamente."
- Invalid coupon shows that the coupon does not exist or is not valid.
- Expired coupon shows that the coupon expired.
- A coupon rejected by WooCommerce does not show success.
- Removing a coupon shows a success toast.
- Toast works on desktop and mobile without blocking checkout.

## Manual QA

1. Open checkout or cart with a product.
2. Click `Aplicar` with an empty coupon field and confirm the warning toast.
3. Enter a non-existing coupon and confirm the invalid toast.
4. Enter an expired coupon and confirm the expired toast.
5. Enter a valid coupon and confirm the success toast plus updated totals.
6. Remove the coupon and confirm the removal toast.
7. Repeat on a mobile viewport.

## Rollback Notes

Revert this ticket's commit to restore the previous alert/inline notice behavior and previous coupon endpoint response shape.
