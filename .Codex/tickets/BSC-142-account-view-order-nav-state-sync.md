## Objective

Map the `view-order` account endpoint to the `Mis pedidos` active state so the shared My Account navigation stays visually consistent across order-detail screens.

## Context

- After fixing Bubble Points gated-page baselines, the remaining visual drift was isolated to `view-order`.
- The screenshot review showed the shared account navigation rendered with no active item on the order-detail page.
- `woocommerce/myaccount/navigation.php` only maps `orders`, `edit-address`, `edit-account`, and Bubble Points, so `view-order` falls through with an empty route.

## Files To Inspect

- `woocommerce/myaccount/navigation.php`
- `tests/e2e/visual/account-and-post-purchase.spec.js`

## Exact Implementation Plan

1. Treat `view-order` as part of the `orders` navigation route.
2. Refresh the `view-order` visual baselines across mobile, tablet, and desktop.
3. Re-run the gated-page visual suite to confirm the active-state contract is consistent.

## Acceptance Criteria

- `view-order` highlights `Mis pedidos` in the shared account nav.
- `view-order` visual baselines are green.
- The gated-page visual suite passes.

## Manual QA

1. Open a valid account order-detail route.
2. Confirm `Mis pedidos` is the highlighted nav item.
3. Compare mobile, tablet, and desktop spacing around the nav and order summary.

## Rollback Notes

- Revert the commit for `BSC-142`.
- Restore the previous endpoint-route mapping in `woocommerce/myaccount/navigation.php`.
