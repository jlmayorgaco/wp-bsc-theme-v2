# BSC-085 - Playwright baseline and visual regression gate

## Objective
Create the first automated safety net for `MVP2`: Playwright smoke flows, fixed test data assumptions, and screenshot baselines for the approved pixel-perfect UI.

## Context
- `MVP2` already has active refactor work in checkout, account, PDP gallery, category mobile filters, admin labels, and Bubble Points.
- The branch now needs a stable visual/functional baseline before broader SCSS cleanup and reusable UI refactors continue.
- The current roadmap already defines this as the practical next gate after the latest Phase 1 and Phase 2 fixes.
- This ticket should not redesign anything; it only creates protection against regressions.

## Files To Inspect
- `package.json`
- any existing JS tooling config in the repo root
- `ROADMAP.md`
- `CHANGELOG.md`
- critical templates and routes:
  - `front-page.php`
  - `components/header.php`
  - `components/products/card.php`
  - `components/product-category.php`
  - `components/checkout/*`
  - `woocommerce/checkout/*`
  - `woocommerce/myaccount/*`
  - `page-bubble-points.php`

## Exact Implementation Plan
1. Add Playwright as the E2E/visual runner for the theme workspace.
2. Create a config with fixed viewports:
   - `390x844`
   - `768x1024`
   - `1440x900`
3. Define a base URL strategy for local/staging execution.
4. Add a `tests/e2e/` structure with:
   - shared helpers
   - auth/session helpers if needed
   - smoke specs
   - visual specs
5. Freeze the first approved baseline for these screens:
   - home
   - header desktop/mobile
   - PLP/category
   - PDP
   - cart
   - checkout
   - thank you
   - my account
   - Bubble Points
6. Disable non-essential motion in test mode without changing layout.
7. Document the dataset assumptions required to keep snapshots deterministic.

## Acceptance Criteria
- The repo has a Playwright config and runnable test scripts.
- At least one smoke spec exists for:
  - home
  - PDP
  - cart/checkout
  - my account
- At least one visual baseline spec exists for the approved critical screens.
- Viewports are fixed and documented.
- The test setup is explicit about what data the environment must contain.

## Manual QA
1. Install dependencies and run the Playwright smoke suite locally.
2. Run the visual suite on the approved environment.
3. Confirm screenshots are created for the official mobile/tablet/desktop viewports.
4. Change a safe spacing or color locally and confirm the visual suite detects the drift.
5. Restore the change and confirm the suite passes again.

## Rollback Notes
- Remove Playwright config, scripts, and baseline assets if the setup proves unstable.
- Keep the ticket documentation even if the first implementation is reverted, so the next attempt starts from the same contract.
