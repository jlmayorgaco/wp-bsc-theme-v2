# BSC-128 - Home Baseline Sync and Release Sweep

## Objective
Refresh the Home visual baseline to match the current approved storefront content, then rerun the full smoke and visual gates to produce a release-ready status sweep.

## Context
After closing the remaining route hardening, the only failing gate was the Home visual snapshot. The diff was content-driven across all three viewports, while category, PDP, checkout, auth, admin, and email baselines remained green. This ticket is intentionally limited to snapshot sync plus final validation.

## Files To Inspect
- `tests/e2e/visual/public-pages.spec.js-snapshots/home-mobile-win32.png`
- `tests/e2e/visual/public-pages.spec.js-snapshots/home-tablet-win32.png`
- `tests/e2e/visual/public-pages.spec.js-snapshots/home-desktop-win32.png`
- `MVP2_RELEASE_STATUS.md`

## Exact Implementation Plan
1. Refresh only the Home snapshots under `public-pages.spec.js-snapshots`.
2. Re-run `npm run test:e2e:smoke`.
3. Re-run `npm run test:e2e:visual`.
4. Record the final gate status and the explicit `deploy.php` exception in a release status document.

## Acceptance Criteria
- The Home snapshots reflect the current approved storefront state.
- Full smoke returns green.
- Full visual returns green.
- A release status document records the final test state and the remaining `deploy.php` exception.

## Manual QA
1. Open Home on mobile/tablet/desktop and compare hero, launches, favorites, featured, benefits, and newsletter sections.
2. Run `npm run test:e2e:smoke`.
3. Run `npm run test:e2e:visual`.

## Rollback Notes
- Revert this commit to restore the previous Home snapshots and release status document.
