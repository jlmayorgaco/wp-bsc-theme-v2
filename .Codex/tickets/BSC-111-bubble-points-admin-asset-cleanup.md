# BSC-111 — Bubble Points Admin Asset Cleanup

## Objective
Remove the remaining inline admin styles from the Bubble Points back office screens without changing balance logic, ledger behavior, or the approved admin UI.

## Context
The Bubble Points admin list and per-user history screens still mixed behavior and inline presentation inside the PHP renderers. The feature already worked, so the safe move is page-scoped CSS only, with the existing forms and actions preserved.

## Files to inspect
- `plugins/bubble-points/admin/admin-menu.php`
- `plugins/bubble-points/admin/class-bsc-bp-list-table.php`
- `plugins/bubble-points/admin/user-history.php`
- `plugins/bubble-points/admin/bsc-bp-admin.css`
- `tests/e2e/smoke/admin-bubble-points.spec.js`

## Exact implementation plan
1. Enqueue a Bubble Points admin stylesheet only on `page=bsc-bubble-points`.
2. Replace inline form spacing and width styles with dedicated classes.
3. Replace inline positive/negative delta colors with semantic classes.
4. Add smoke coverage for list view and per-user history navigation.

## Acceptance criteria
- The targeted Bubble Points admin files contain no inline `style` attributes.
- Bubble Points admin CSS loads only on the Bubble Points admin screen.
- The list view and history view retain their current layout and actions.
- Admin smoke covers list load and history navigation.

## Manual QA
1. Open `BSC > Bubble Points` and verify the user table plus inline add/reduce controls.
2. Open a user history page and verify the back button, manual adjustment form, and ledger table.
3. Confirm positive and negative deltas still use the correct visual emphasis.

## Rollback notes
- Revert the commit for `BSC-111` to restore inline styling.
- Remove `plugins/bubble-points/admin/bsc-bp-admin.css` and `tests/e2e/smoke/admin-bubble-points.spec.js` if full rollback is required.
