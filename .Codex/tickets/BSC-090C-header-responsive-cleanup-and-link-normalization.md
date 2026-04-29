# BSC-090C / BSC-090D - Header responsive cleanup and internal link normalization

## Objective
Clean up the responsive SCSS around the header/mobile nav and normalize internal header URLs through shared helpers, without changing the approved UI or public DOM contracts.

## Context
- `BSC-090A` extracted the menu configuration and installed dedicated header smoke/visual coverage.
- `BSC-090B` split the header into desktop/mobile/sidebar partials and removed the remaining inline hidden states.
- The header still contains internal hardcoded links for the home logo and mobile shop CTA.
- Header SCSS still contains duplicated or stale mobile/sidebar/search rules that make future changes riskier than necessary.

## Files To Inspect
- `components/header.php`
- `components/header/header-menu-config.php`
- `components/header/partials/header-desktop.php`
- `components/header/partials/header-mobile.php`
- `components/header/partials/header-mobile-sidebar.php`
- `sass/components/header/_header.scss`
- `sass/components/header/_header-mobile--header.scss`
- `sass/components/header/_header-mobile--sidebar.scss`
- `sass/components/header/_header-search.scss`

## Exact Implementation Plan
1. Add shared header link helpers for `home` and `shop`.
2. Normalize internal header links through a single URL-normalization function.
3. Replace header-level hardcoded `/` and `/shop` paths in the partials with shared helper values.
4. Consolidate duplicated/stale header SCSS rules, especially in mobile header, mobile sidebar, and search.
5. Recompile `style.css`.
6. Validate with header smoke/visual tests plus the public/authenticated visual suite.

## Acceptance Criteria
- Header partials no longer hardcode `/` or `/shop`.
- Internal header URLs resolve through a shared helper path.
- Header/mobile/search/sidebar SCSS is cleaner with fewer duplicated rules.
- No DOM/class changes in the rendered header.
- Header-specific smoke/visual coverage stays green.
- Public/authenticated visual baselines stay green.

## Manual QA
1. Desktop: click logo and confirm it goes home.
2. Mobile: click logo and confirm it goes home.
3. Mobile: open sidebar and click `¡Ir a la tienda!`.
4. Mobile: open search and verify the panel still aligns below the header.
5. Tablet/mobile: verify the menu toggle still opens/closes cleanly.

## Rollback Notes
- Revert the touched header partials, helper functions, and header SCSS files together if the visual baselines drift or any header/mobile-nav interaction breaks.
