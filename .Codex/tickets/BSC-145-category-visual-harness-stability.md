# BSC-145 — Category visual harness stability

## Objective
Reduce category-page mobile visual flakiness by making the Playwright category helper wait for a fully populated product grid before taking screenshots.

## Context
- Category mobile visual checks were passing in retry but still showing intermittent full-page height drift.
- The diff pattern pointed to an unstable page height rather than a deterministic UI regression.

## Files to inspect
- `tests/e2e/helpers/ui.js`

## Exact implementation plan
1. Add a helper that waits until document height stops changing.
2. Make `gotoProductGridCategory()` wait for the first product-card images to be fully loaded.
3. Re-run the targeted category visual suite to confirm stability.

## Acceptance criteria
- Category visual verification passes without a structural diff caused by page-height jitter.
- No storefront markup or CSS changes are required for the stabilization.

## Manual QA
1. Run `PW_PUBLIC_MODE=storefront npx playwright test tests/e2e/visual/public-pages.spec.js --grep "category" --workers=1`.
2. Confirm category snapshots are stable across repeated local runs.

## Rollback notes
- Revert the helper changes in `tests/e2e/helpers/ui.js`.
