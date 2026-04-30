# BSC-109 — Admin Reports Asset Extraction

## Objective
Move inline CSS and inline JS out of the BSC reports admin screen into dedicated assets without changing the approved admin UI.

## Context
`admin/bsc-reports-page.php` mixed page render, KPI styling, stock-table presentation, and stock search behavior in a single template. That made the screen harder to maintain and blocked the remaining inline-asset cleanup pass.

## Files to inspect
- `admin/bsc-reports-page.php`
- `admin/bsc-reports.css`
- `js/admin/bsc-reports.js`
- `tests/e2e/smoke/admin-reports.spec.js`

## Exact implementation plan
1. Add page-scoped asset enqueueing for `bsc-reports`.
2. Replace inline `style` attributes with stable admin classes.
3. Replace dynamic inline color/background logic with conditional class names.
4. Move stock search behavior into a dedicated admin script.
5. Add admin smoke coverage for both `ventas` and `stock` tabs.

## Acceptance criteria
- `admin/bsc-reports-page.php` contains no inline `style`, `<style>`, or `<script>` blocks.
- Reports CSS/JS load only on the reports screen.
- KPI cards, tables, filters, and stock search behave exactly as before.
- Admin smoke covers reports page load on both tabs.

## Manual QA
1. Open `BSC > Informes > Ventas` and verify filters, KPI cards, and CSV export button layout.
2. Open `BSC > Informes > Stock` and verify low/zero stock row highlighting.
3. Use stock search to confirm row filtering and result counter behavior.
4. Resize the admin viewport and verify the two-column summary area collapses cleanly.

## Rollback notes
- Revert the commit for `BSC-109` to restore the previous inline asset implementation.
- Remove `admin/bsc-reports.css`, `js/admin/bsc-reports.js`, and `tests/e2e/smoke/admin-reports.spec.js` if full rollback is required.
