# BSC-123 — Playwright WordPress Error Page Retry

## Objective
Reduce local Playwright flake by treating transient WordPress `error-page` responses as retryable navigation failures inside the shared harness.

## Context
- The local `bsc.local` stack occasionally serves the WordPress `body#error-page` shell instead of the expected screen.
- This affects admin smoke and email preview routes intermittently, even when a retry succeeds immediately afterward.
- The current helper only recognizes gateway/database markers, so these transient error pages leak into the specs.

## Files To Inspect
- `tests/e2e/helpers/ui.js`

## Exact Implementation Plan
1. Expand the transient page detector to identify `body#error-page` and related WordPress fatal shells.
2. Keep the retry behavior inside `gotoAndStabilize()` so individual specs do not need custom recovery logic.
3. Re-run the default smoke and visual wrappers.

## Acceptance Criteria
- `gotoAndStabilize()` retries when the local stack serves `body#error-page`.
- Default smoke and visual wrappers remain green.
- No product code or visual baseline changes are required.

## Manual QA
1. Run `npm run test:e2e:smoke`.
2. Run `npm run test:e2e:visual`.
3. Confirm transient local error pages no longer bubble up as first-attempt spec failures.

## Rollback Notes
- Revert this commit to restore the previous detector behavior.
