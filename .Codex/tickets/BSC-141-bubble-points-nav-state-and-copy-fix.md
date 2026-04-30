## Objective

Fix the Bubble Points account navigation active state and remove visible mojibake from the Bubble Points profile page without changing the approved layout.

## Context

- Full visual regression after `BSC-138/139/140` exposed a small but real drift on Bubble Points mobile and stale gated-page baselines on account.
- The Bubble Points template renders the My Account header directly, so it never sets `current_route = bubble-points`.
- The Bubble Points profile copy contains visible mojibake in the title and explanatory text.

## Files To Inspect

- `page-bubble-points.php`
- `plugins/bubble-points/views/profile-bubble-points.php`
- `tests/e2e/visual/account-and-post-purchase.spec.js`

## Exact Implementation Plan

1. Pass the explicit `bubble-points` route into the shared My Account header from the page template.
2. Rewrite the Bubble Points profile copy using entity-safe strings in UTF-8 without BOM.
3. Refresh only the gated-page baselines affected by the route-state and copy fixes.
4. Re-run the gated-page visual suite.

## Acceptance Criteria

- Bubble Points highlights `Mis puntos` as the active account item.
- Bubble Points visible copy renders correctly.
- Account and Bubble Points gated-page baselines are green.

## Manual QA

1. Open `/mi-cuenta/bubble-points/` on mobile, tablet, and desktop.
2. Confirm `Mis puntos` is the active item in the account nav.
3. Confirm the title and explanatory text render with correct accents and punctuation.
4. Re-open `/mi-cuenta/` and confirm the default account page still highlights `Mis pedidos`.

## Rollback Notes

- Revert the commit for `BSC-141`.
- Restore the previous Bubble Points page template and profile copy.