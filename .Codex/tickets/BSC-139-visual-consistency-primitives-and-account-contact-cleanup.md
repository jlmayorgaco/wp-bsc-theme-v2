## Objective

Align account/contact action controls with reusable button primitives, harden My Account active-state rendering, and clean up the responsive Sass structure without changing the approved UI language.

## Context

- Checkout/cart/coupon smoke is green after `BSC-138`.
- Public/account visual regression still shows drift on:
  - checkout
  - contact us
  - account addresses
- Contact/account actions still rely on bespoke CSS instead of shared button modifiers.
- My Account navigation computes active state from `$_SERVER['REQUEST_URI']`, which is brittle and harder to reason about.

## Files To Inspect

- `woocommerce/myaccount/navigation.php`
- `components/my-account/my-account-header.php`
- `woocommerce/myaccount/my-address.php`
- `woocommerce/myaccount/form-edit-account.php`
- `page-contact-us.php`
- `sass/components/primitives/_buttons.scss`
- `sass/components/profile/_my_account_header.scss`
- `sass/components/profile/_my_address.scss`
- `sass/pages/_page-contact-us.scss`

## Exact Implementation Plan

1. Introduce a compact reusable pill button modifier for form/account CTAs.
2. Apply that modifier to contact submit and account address/edit actions.
3. Pass an explicit current route into the My Account header and stop inferring active state from raw request URI string matching.
4. Refactor My Account header/address Sass so the mobile structure is easier to follow and modify.
5. Rebuild CSS and revalidate smoke/visual coverage for the affected views.

## Acceptance Criteria

- Contact/account CTAs share a reusable button modifier.
- My Account menu active state is derived from the Woo endpoint, not raw string search.
- Sass for account/contact affected areas is cleaner and easier to scale on mobile.
- Smoke remains green for storefront checkout/account flows.
- Visual baselines for affected pages are green after validation.

## Manual QA

1. Open `Mi cuenta > Envío y dirección` on mobile/tablet/desktop.
2. Confirm only the current menu item is highlighted.
3. Confirm `Editar datos` and `Guardar datos` keep the approved pill appearance.
4. Open `Contacto` and verify the submit CTA still matches the approved style and spacing.
5. Open `Checkout` and compare coupon block spacing/summary alignment.

## Rollback Notes

- Revert the markup class additions in:
  - `page-contact-us.php`
  - `woocommerce/myaccount/my-address.php`
  - `woocommerce/myaccount/form-edit-account.php`
- Revert route handling in:
  - `woocommerce/myaccount/navigation.php`
  - `components/my-account/my-account-header.php`
- Rebuild `style.css` from the previous Sass state.
