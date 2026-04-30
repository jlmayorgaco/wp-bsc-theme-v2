## Objective

Harden the Playwright harness so storefront/auth/admin/email suites remain deterministic on the local `bsc.local` stack, without changing the approved UI or application behavior.

## Context

The remaining red/flaky suites were no longer pointing to theme regressions. The failures were transient:

- `502 Bad Gateway` pages under concurrent load
- account login helpers falling back to `/login/` without recovering
- admin smoke navigating directly into pages that occasionally returned transient gateway responses
- email preview admin-post routes failing under cross-project concurrency

The correct fix is to stabilize navigation and session helpers, and to run the heavy WordPress suites with a deterministic worker count.

## Files To Inspect

- `tests/e2e/helpers/ui.js`
- `tests/e2e/smoke/admin-products.spec.js`
- `tests/e2e/smoke/admin-orders.spec.js`
- `package.json`
- `tests/README.md`

## Exact Implementation Plan

1. Add transient 5xx detection and bounded retries to `gotoAndStabilize()`.
2. Add a shared login form submit helper with redirect support.
3. Add a fallback path in `loginFromAccount()` that retries through the custom `/login/` page when the WP login redirect does not leave an authenticated account session.
4. Reuse `gotoAndStabilize()` in admin smoke page entry points.
5. Force `--workers=1` for `smoke` and `visual:emails`.
6. Document why those suites intentionally run serially.

## Acceptance Criteria

- `npm run test:e2e:smoke` runs deterministically enough for local gating.
- `npm run test:e2e:visual:emails` no longer fails due to project concurrency overload.
- `loginFromAccount()` can recover when the session drops into the custom `/login/` page.
- Admin smoke no longer hard-fails on a single transient `502` page response.

## Manual QA

1. Run `npm run test:e2e:smoke`.
2. Run `PW_PUBLIC_MODE=storefront npm run test:e2e:visual:emails`.
3. Run `PW_PUBLIC_MODE=storefront npx playwright test tests/e2e/visual/account-and-post-purchase.spec.js --workers=1`.
4. Confirm the suites pass without changing the frontend/admin render.

## Rollback Notes

- Revert this ticket commit.
- Restore the previous package scripts if parallelism is preferred over deterministic local reliability.
