# BSC-099 — Admin orders asset extraction

## Objective
Remove inline script and inline styling from the main BSC admin orders screen and move them to dedicated assets without changing the current operational UI.

## Context
`admin/bsc-orders-page.php` and `admin/class-bsc-orders-table.php` still mixed render logic with inline JS/CSS. That makes the page harder to maintain, harder to test, and inconsistent with the rest of the MVP2 refactor.

## Files to Inspect
- `admin/bsc-orders-page.php`
- `admin/class-bsc-orders-table.php`
- `js/bsc-admin-orders.js`
- `admin/bsc-admin-orders.css`
- `tests/e2e/smoke/admin-orders.spec.js`

## Minimum Viable Implementation
1. Enqueue a dedicated admin orders stylesheet.
2. Move the bulk-action guard logic into `js/bsc-admin-orders.js`.
3. Replace table-level inline styles with semantic classes.
4. Add an admin smoke test for login + bulk-action guard.

## Risks
- The bulk-action buttons depend on JS preventing invalid submits.
- The tracking/status cells rely on existing selectors used by admin JS.
- Admin coverage depends on the seeded QA admin user.

## Acceptance Criteria
- The main BSC orders admin screen no longer contains inline JS or page-level inline CSS.
- The orders table no longer uses inline presentation styles for status/tracking/customer meta.
- The admin smoke test passes.

## Manual QA
1. Login as the QA admin and open `wp-admin/admin.php?page=bsc-orders`.
2. Click each bulk action with no orders selected and confirm the warning appears.
3. Select an order, use CSV/packing/PDF actions, and confirm behavior matches the previous UI.
4. Edit one status and one tracking field and confirm the saved indicator still appears.

## Rollback Notes
Revert `admin/bsc-orders-page.php`, `admin/class-bsc-orders-table.php`, `js/bsc-admin-orders.js`, `admin/bsc-admin-orders.css`, and `tests/e2e/smoke/admin-orders.spec.js`.
