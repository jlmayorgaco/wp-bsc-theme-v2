# BSC-131 - Stabilize Admin Smoke Without Relying on Global Retries

## Objective
Reduce admin smoke fragility by moving navigation and popup recovery into reusable helpers instead of depending on Playwright's top-level test retries.

## Context
Admin smoke already passed overall, but several screens only recovered through full-test retries after transient WordPress admin failures. The common failure mode was duplicated login + navigation code with one-shot assertions against admin pages and popup windows.

## Files To Inspect
- `tests/e2e/helpers/ui.js`
- `tests/e2e/smoke/admin-orders.spec.js`
- `tests/e2e/smoke/admin-products.spec.js`
- `tests/e2e/smoke/admin-showroom.spec.js`
- `tests/e2e/smoke/admin-reports.spec.js`
- `tests/e2e/smoke/admin-bubble-points.spec.js`

## Exact Implementation Plan
1. Add a reusable helper for opening WP admin pages with internal recovery.
2. Add a reusable helper for popup-based WP admin actions with internal recovery.
3. Replace duplicated login + navigation code in the targeted specs.
4. Re-run the admin smoke slice to confirm stability.

## Acceptance Criteria
- Targeted admin smoke specs use shared recovery helpers.
- Admin page and popup recovery happens inside the spec flow, not only through global retries.
- Admin smoke slice returns green.

## Manual QA
1. Run `npx playwright test tests/e2e/smoke/admin-*.spec.js --workers=1`.
2. Open `BSC > Pedidos`, `Productos`, `Informes`, `Showroom`, and `Bubble Points` manually.
3. Verify packing/labels popup flows still open correctly.

## Rollback Notes
- Revert this commit to restore the previous per-spec login/navigation logic.
