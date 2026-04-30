# MVP2 Release Status

Date: 2026-04-29
Branch: `MVP2`

## Gate Summary
- `npm run test:e2e:smoke`: green
  - `69 passed`
  - `10 skipped`
  - `5 flaky` recovered by Playwright retries on transient local WP admin routes
- `npm run test:e2e:visual`: green
  - `74 passed`
  - `4 skipped`
- Home public baselines were refreshed in this release sweep to match the current approved storefront content.

## Scope Closed
- Orders / account hardening
- Bubble Points integrity, admin cleanup, and visual coverage
- Checkout view separation and thank-you rendering cleanup
- Header modularization, responsive cleanup, and route normalization
- Storefront / admin inline asset extraction
- Playwright storefront, auth, admin, and email coverage
- Remaining hardcoded public template links replaced with shared helpers

## Residual Notes
- The local stack still shows transient instability on some WP admin routes; the smoke suite now passes through built-in retries, but those routes remain more fragile than storefront pages.
- `Videos/` in the working tree is unrelated local material and not part of the release scope.

## Explicit Exception
- `cicd/deploy.php` was intentionally left untouched by instruction and remains outside release scope for this branch.
- This branch is feature-complete for tomorrow's release preparation, but the deploy hook is still a real production security blocker if public exposure remains unchanged.
