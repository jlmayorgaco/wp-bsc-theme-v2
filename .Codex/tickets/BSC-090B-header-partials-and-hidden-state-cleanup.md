# BSC-090B - Header partials and hidden-state cleanup

## Objective
Split the header render into focused partials and move the remaining safe inline hidden states out of `components/header.php` into the SCSS pipeline without changing the approved UI.

## Context
- `BSC-090A` already extracted the header menu configuration and added dedicated smoke/visual coverage.
- `components/header.php` still renders desktop header, mobile header, and mobile sidebar in one file.
- Two frontend inline hidden states remain in the header:
  - hidden account dropdown entry
  - hidden desktop checkout icon
- The header JavaScript still depends on the current IDs/classes, so this slice must preserve DOM contracts.

## Files To Inspect
- `components/header.php`
- `components/header/partials/*`
- `sass/components/header/_header.scss`
- `sass/components/header/_header-profile.scss`
- `tests/e2e/smoke/site-smoke.spec.js`
- `tests/e2e/visual/header-and-mobile-nav.spec.js`

## Exact Implementation Plan
1. Split `components/header.php` into:
   - desktop header partial
   - mobile header partial
   - mobile sidebar partial
2. Keep the same render order, IDs, classes, and markup structure.
3. Replace the two inline `display:none` styles with explicit classes and SCSS rules.
4. Recompile `style.css`.
5. Validate with header-specific smoke/visual tests and the public/authenticated visual suite.

## Acceptance Criteria
- `components/header.php` becomes a thin orchestrator file.
- Header/mobile sidebar markup remains visually identical.
- Inline hidden states are no longer in `components/header.php`.
- Header/mobile-nav tests pass.
- Public/authenticated visual baselines remain green.

## Manual QA
1. Desktop: open profile dropdown and confirm item order remains unchanged.
2. Desktop: confirm hidden checkout icon is still not visible.
3. Mobile/tablet: open and close the sidebar.
4. Mobile/tablet: verify account links and CTA still render in the same order.

## Rollback Notes
- Revert `components/header.php`, the new partials, and the touched header SCSS files together if any desktop/mobile header snapshot drifts.
