# MVP2 Release Status

Date: 2026-04-30
Branch: `MVP2`

## Gate Summary
- `npm run test:e2e:smoke`: green
  - `74 passed`
  - `10 skipped`
- `npm run test:e2e:visual`: green
  - `74 passed`
  - `4 skipped`
- Home public baselines are synced with the current approved storefront content.

## Scope Closed
- Orders / account hardening and authenticated route coverage
- Bubble Points integrity, admin cleanup, and visual coverage
- Checkout view separation, cart / coupon smoke coverage, and thank-you rendering cleanup
- Header modularization, responsive cleanup, and route normalization
- Storefront / admin inline asset extraction into the theme CSS / JS pipeline
- Playwright coverage for storefront, auth, admin, email previews, and release smoke flows
- Remaining hardcoded public template links replaced with shared URL helpers
- Admin smoke stabilization without depending on global Playwright retries

## Residual Notes
- The local WP admin remains slower and more brittle than storefront routes, but the current smoke harness now passes cleanly without top-level flaky recovery.
- `Videos/` in the working tree is unrelated local material and not part of the release scope.

## Explicit Exception
- `cicd/deploy.php` is intentionally outside the current execution scope by instruction.
