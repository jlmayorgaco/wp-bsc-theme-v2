# BSC-112 — Checkout View Separation

## Objective
Separate the custom checkout page into a view router, a main layout view, and a form view without changing the approved checkout DOM or visual output.

## Context
The custom checkout implementation was functional but spread across executable templates. The page router lived directly in `page-checkout.php`, the main checkout view mixed orchestration and content config, and the form template combined field preparation with rendering. That made future checkout changes harder to reason about and harder to test in bounded slices.

## Files to inspect
- `page-checkout.php`
- `components/checkout/class-bsc-checkout-view-router.php`
- `components/checkout/class-bsc-checkout-main-view.php`
- `components/checkout/class-bsc-checkout-form-view.php`
- `components/checkout/checkout-form.php`
- `components/checkout/views/checkout-view-main.php`

## Exact implementation plan
1. Move checkout view routing out of `page-checkout.php` into a dedicated router class.
2. Move the main checkout page orchestration and trust signals config into a dedicated main view class.
3. Move checkout field preparation and locked-country rendering into a dedicated form view class.
4. Keep the existing wrapper files so include paths and DOM remain stable.
5. Validate with checkout smoke and checkout visual regression only.

## Acceptance criteria
- `page-checkout.php` becomes a thin template wrapper.
- Checkout main and form rendering live in dedicated classes.
- The visible checkout DOM and layout remain unchanged.
- Checkout smoke and checkout visual baseline stay green.

## Manual QA
1. Open checkout with a seeded cart and verify the two-column layout, logo, trust signals, and summary.
2. Verify the Colombia locked country field still renders correctly.
3. Change department/city and confirm shipping summary still updates.
4. Confirm no visual drift on desktop/tablet/mobile.

## Rollback notes
- Revert the commit for `BSC-112` to restore the previous template-driven checkout flow.
- Remove the three new class files if a full rollback is required.
