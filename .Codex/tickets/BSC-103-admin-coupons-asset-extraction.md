# BSC-103 - Admin coupons asset extraction

## Objective
Move inline styles and inline delete-confirm logic out of the BSC coupons admin page into dedicated admin assets.

## Context
- `admin/bsc-coupons-page.php` still mixes page layout, inline width styles, expired-state text coloring, and inline `onclick` confirmation.
- This is presentation and admin interaction debt with low functional risk.

## Files To Inspect
- `admin/bsc-coupons-page.php`
- `admin/bsc-coupons.css`
- `js/bsc-admin-coupons.js`

## Exact Implementation Plan
1. Enqueue page-specific admin CSS/JS only on `page=bsc-coupons`.
2. Replace inline styles with semantic classes.
3. Replace `onclick="return confirm(...)"` with a delegated JS handler based on `data-bsc-confirm`.

## Acceptance Criteria
- No inline `onclick` remains in the coupons page.
- Create form and coupons table styling come from CSS assets.
- Expired rows and delete action keep the same visual intent.

## Manual QA
1. Open `BSC > Cupones`.
2. Compare create panel spacing and widths.
3. Click `Eliminar` and confirm the browser confirm still appears.

## Rollback Notes
- Remove `admin/bsc-coupons.css` and `js/bsc-admin-coupons.js`.
- Restore the original inline styles and `onclick` confirmation.
