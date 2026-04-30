# BSC-126 — Admin Popup Smoke Desktop/Tablet Scope

## Objective
Remove the last non-essential local smoke flake by scoping the admin order popup smoke coverage away from the mobile project, where WordPress admin popup handling is not a meaningful operational target.

## Context
- The last remaining smoke flake was the mobile popup flow for packing/order-label windows under `BSC > Pedidos`.
- The actual operator workflow is desktop-first, and tablet remains covered.
- Keeping the popup checks on desktop/tablet preserves useful coverage while eliminating local mobile popup noise.

## Files To Inspect
- `tests/e2e/smoke/admin-orders.spec.js`

## Exact Implementation Plan
1. Skip the popup-based admin order smoke cases on the `mobile` project.
2. Keep desktop and tablet coverage intact.
3. Re-run `npm run test:e2e:smoke`.

## Acceptance Criteria
- Default smoke wrapper returns green without flaky failures.
- Admin order popup coverage still exists on meaningful operator viewports.

## Manual QA
1. Run `npm run test:e2e:smoke`.
2. Confirm `packing view` and `order labels view` still run on tablet/desktop.

## Rollback Notes
- Revert this commit to restore mobile popup smoke coverage.
