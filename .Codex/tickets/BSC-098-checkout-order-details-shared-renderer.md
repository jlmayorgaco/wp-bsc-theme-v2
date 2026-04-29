# BSC-098 — Checkout order details shared renderer

## Objective
Share the order-details rendering between `view-order` and `thank-you` without changing the approved visual output of either page.

## Context
The first shared-renderer pass introduced visual drift on `view-order` and `thank-you`. This ticket keeps the reuse, but restores each page's exact variant-specific markup, classes, labels, and points logic.

## Files to Inspect
- `components/orders/class-bsc-order-view.php`
- `woocommerce/myaccount/view-order.php`
- `components/checkout/views/checkout-view-thankyou.php`
- `tests/e2e/visual/account-and-post-purchase.spec.js`

## Minimum Viable Implementation
1. Keep `view-order` as the default renderer variant.
2. Add a `thankyou` renderer variant with the exact classes and content rules used by the approved template.
3. Keep the page wrappers thin and declarative.
4. Validate against the authenticated visual suite before commit.

## Risks
- Small markup differences in order overview and shipping rows can change spacing.
- Points text/helper differences can change both content and layout.
- The thank-you page depends on a valid order key, so regression coverage must stay fixture-driven.

## Acceptance Criteria
- `view-order` matches its committed baseline.
- `thank-you` matches its committed baseline.
- Shared rendering remains centralized in `BSC_Order_View`.

## Manual QA
1. Open `/mi-cuenta/view-order/{id}` with the QA account and compare summary, shipping, and action CTA.
2. Open the real thank-you URL from the fixture order and compare logo, heading, progress bar, points block, and actions.
3. Confirm both pages still navigate to shop/account routes correctly.

## Rollback Notes
Revert `components/orders/class-bsc-order-view.php`, `components/checkout/views/checkout-view-thankyou.php`, and `woocommerce/myaccount/view-order.php` to the previous commit.
