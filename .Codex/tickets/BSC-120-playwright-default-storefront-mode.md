# BSC-120 — Playwright Default Storefront Mode

## Objective
Make the default Playwright smoke/visual harness match the current local QA environment by defaulting public mode to `storefront`, while preserving an explicit `coming-soon` override.

## Context
- The local `bsc.local` environment is currently validated in storefront mode.
- Public smoke and visual suites already pass when `PW_PUBLIC_MODE=storefront`.
- The default helper still falls back to `coming-soon`, which makes `npm run test:e2e:smoke` fail even though the theme is healthy.

## Files To Inspect
- `tests/e2e/helpers/env.js`
- `tests/README.md`

## Exact Implementation Plan
1. Change the default public mode fallback from `coming-soon` to `storefront`.
2. Keep `PW_PUBLIC_MODE` and `PW_EXPECT_STOREFRONT` overrides intact.
3. Update test documentation so `coming-soon` becomes the explicit opt-in mode, not the default.

## Acceptance Criteria
- `npm run test:e2e:smoke` runs against the current storefront by default.
- The suite still supports `PW_PUBLIC_MODE=coming-soon`.
- The README documents the new default and the explicit override.

## Manual QA
1. Run `npm run test:e2e:smoke`.
2. Run `PW_PUBLIC_MODE=coming-soon npm run test:e2e:smoke`.
3. Confirm the first command targets storefront and the second targets the maintenance shell.

## Rollback Notes
- Revert this commit to restore `coming-soon` as the default harness mode.
