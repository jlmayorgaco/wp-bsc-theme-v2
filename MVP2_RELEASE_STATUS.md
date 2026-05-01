# MVP2 Release Status

Date: 2026-04-30
Branch: `MVP2`

## Gate Summary
- `npm run lint:php`: green
- `npm run lint:js`: green
- `npm run lint:scss`: green
- `npm run test:e2e:smoke`: green
  - `86 passed`
  - `25 skipped`
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
- Checkout/cart quantity hardening now targets cart item keys, disables duplicate clicks while requests are in flight, and reconciles UI state from WooCommerce responses.
- Packing view now supports stock deduction per line item from `bodega` by default or `showroom` when the operator selects it.
- Visible mojibake cleanup covered admin/operator-facing copy touched in this release pass.

## Residual Notes
- The local WP admin remains slower and more brittle than storefront routes, but the current smoke harness now passes cleanly without top-level flaky recovery.
- Full visual suite was not rerun after the cart/packing changes in this pass; run it before production deploy.
- `Videos/` in the working tree is unrelated local material and not part of the release scope.

## Explicit Exception
- `cicd/deploy.php` is intentionally outside the current execution scope by instruction.
