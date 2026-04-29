# BSC-090A - Header baseline and menu config extraction

## Objective
Reduce risk in the header/mobile-nav refactor by adding dedicated visual/smoke coverage and extracting the menu configuration out of `components/header.php` without changing the approved UI.

## Context
- `components/header.php` currently mixes menu data, account URLs, desktop/mobile markup, and render orchestration in one file.
- The desktop/mobile header is already covered indirectly by full-page baselines, but the mobile sidebar open state is not baseline-tested directly.
- The header JavaScript depends on fixed IDs and classes, so this slice must preserve those contracts.
- This ticket intentionally avoids layout, SCSS, or hardcoded-URL cleanup. It only prepares the header for later modular refactors.

## Files To Inspect
- `components/header.php`
- `components/header/BSC_HeaderNav.class.php`
- `components/header/BSC_MenuNav.class.php`
- `js/mobile-menu.js`
- `js/navigation.js`
- `tests/e2e/smoke/site-smoke.spec.js`
- `tests/e2e/visual/public-pages.spec.js`

## Exact Implementation Plan
1. Extract the menu data and shared account-link helpers into `components/header/header-menu-config.php`.
2. Update `components/header.php` to consume that config and keep the same desktop/mobile render structure.
3. Add smoke coverage for the header shell and mobile menu toggle.
4. Add dedicated visual baselines for:
   - desktop header shell
   - mobile header shell
   - mobile sidebar open state
5. Validate with targeted Playwright smoke + visual runs in storefront mode.

## Acceptance Criteria
- `components/header.php` no longer contains the large inline menu configuration block.
- Desktop and mobile header markup remain visually identical.
- `mobileMenuToggle` and `mobileSidebar` keep their current behavior.
- Dedicated header/mobile-nav visual baselines pass.
- Header/mobile-nav smoke tests pass in storefront mode.

## Manual QA
1. Desktop: hover menu buttons and profile dropdown, confirm they still open correctly.
2. Mobile/tablet: open and close the sidebar from the hamburger button.
3. Mobile/tablet: confirm search/profile icons remain aligned and unchanged.
4. Mobile/tablet: confirm the sidebar CTA and account links still render in the same order.

## Rollback Notes
- Revert `components/header.php`, `components/header/header-menu-config.php`, and the new E2E files together if desktop/mobile header rendering or toggle behavior drifts.
