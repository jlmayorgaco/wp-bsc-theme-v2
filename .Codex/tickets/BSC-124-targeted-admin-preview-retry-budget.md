# BSC-124 — Targeted Admin/Preview Retry Budget

## Objective
Stabilize the remaining local Playwright flakes by increasing the retry budget only for fragile WordPress admin and email preview routes.

## Context
- The shared harness is already stable for normal storefront navigation.
- The remaining local flakes are concentrated on `wp-admin/admin.php?...` pages and `admin-post.php?action=bsc_preview_email...`.
- These routes occasionally need more than the default retry budget even though a later attempt succeeds unchanged.

## Files To Inspect
- `tests/e2e/visual/email-previews.spec.js`
- `tests/e2e/smoke/admin-orders.spec.js`
- `tests/e2e/smoke/admin-products.spec.js`
- `tests/e2e/smoke/admin-reports.spec.js`
- `tests/e2e/smoke/admin-showroom.spec.js`
- `tests/e2e/smoke/admin-bubble-points.spec.js`

## Exact Implementation Plan
1. Increase `gotoAndStabilize()` retry budget to `maxAttempts: 5` for the known fragile admin/admin-post routes.
2. Disable page priming on email previews because those pages do not benefit from scroll priming.
3. Re-run the default smoke and visual wrappers.

## Acceptance Criteria
- Default smoke remains green.
- Default visual wrapper remains green.
- No product code or snapshot changes are required.

## Manual QA
1. Run `npm run test:e2e:smoke`.
2. Run `npm run test:e2e:visual`.
3. Confirm the admin/email routes stop surfacing transient first-pass failures.

## Rollback Notes
- Revert this commit to restore the previous route-specific retry settings.
