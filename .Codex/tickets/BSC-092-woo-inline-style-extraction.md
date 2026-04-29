# BSC-092 - Extract remaining Woo/frontend inline styles

## Objective
Move the next safe batch of inline CSS out of Woo/frontend templates into the SCSS pipeline without changing approved UI or WooCommerce behavior.

## Context
- `BSC-091` already cleaned inline styles from storefront/account landing templates.
- The remaining inline styles are now mostly concentrated in Woo-related components and shared frontend modules.
- This ticket keeps the scope narrow and only targets styles that are:
  - fixed and non-dynamic, or
  - dynamic but expressed through a discrete status map that can become CSS classes.
- This ticket explicitly excludes:
  - admin files
  - email templates
  - `payment-method.php` and `form-add-payment-method.php`
  - `header.php` and `components/shop.php`
  - inline SVG `<style>` markup

## Files To Inspect
- `components/checkout/checkout-cart.php`
- `components/checkout/checkout-coupons.php`
- `components/checkout/views/checkout-view-thankyou.php`
- `components/orders/order-progress-bar.php`
- `woocommerce/cart/shipping-calculator.php`
- `woocommerce/checkout/form-coupon.php`
- `sass/components/checkout/_checkout-cart.scss`
- `sass/components/checkout/_checkout-coupons.scss`
- `sass/components/orders/_progress_bar.scss`
- `sass/pages/_page-checkout.scss`

## Exact Implementation Plan
1. Replace checkout cart inline positioning/hidden quantity styles with classes and SCSS.
2. Replace applied-coupon inline margin with SCSS.
3. Replace thank-you fixed-width inline style with an explicit modifier class.
4. Replace order progress inline width styles with discrete CSS width classes based on order state.
5. Replace Woo shipping calculator and checkout coupon hidden inline states with theme classes while preserving Woo selectors.
6. Recompile `style.css`.
7. Run the visual regression suite for public pages plus authenticated/post-purchase pages.

## Acceptance Criteria
- No targeted inline styles remain in the files listed above.
- Checkout page looks identical to the approved baseline.
- Thank-you / order progress renders identically.
- Woo shipping calculator and checkout coupon form remain hidden by default and still rely on the original Woo selectors.
- Public and authenticated visual suites pass.

## Manual QA
1. Open checkout and verify the approved layout is unchanged.
2. Open thank-you and confirm the order progress block width and bar fill match the approved UI.
3. Trigger the checkout coupon toggle and verify the coupon form still opens.
4. Trigger the cart shipping calculator and verify the form still opens.

## Rollback Notes
- Revert the touched templates and SCSS files together if the Woo toggles or the thank-you progress bar drift visually.
