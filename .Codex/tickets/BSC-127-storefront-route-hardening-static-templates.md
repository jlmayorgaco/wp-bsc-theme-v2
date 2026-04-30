# BSC-127 - Storefront Route Hardening for Static Templates

## Objective
Replace the remaining hardcoded storefront/account links in public templates with shared dynamic helpers, without changing the approved copy, DOM, or visual layout.

## Context
A final release sweep found residual hardcoded links in public templates: `/shipping-returns/`, `/mi-cuenta/orders/`, and a commented `/shop` link in `front-page.php`. These worked in the current environment but remained fragile if slugs or WooCommerce permalinks changed.

## Files To Inspect
- `inc/bsc-url-helpers.php`
- `functions.php`
- `front-page.php`
- `page-claims.php`
- `page-cookies.php`
- `page-copyrights.php`
- `page-faq.php`
- `page-policies.php`

## Exact Implementation Plan
1. Add shared helpers for `shop`, `shipping-returns`, and `my account orders` URLs with safe fallbacks.
2. Load the helper file from `functions.php`.
3. Replace the remaining hardcoded public template links with the helper calls.
4. Re-run PHP lint, smoke, and public visual checks.

## Acceptance Criteria
- The targeted public templates no longer contain hardcoded `href="/..."` links for those routes.
- Route helpers resolve through WooCommerce/WordPress first, with safe fallback behavior.
- Public smoke and visual regression stay green.

## Manual QA
1. Open Home and verify the shop CTA target.
2. Open FAQ / legal pages and click the shipping-returns and My Orders links.
3. Confirm account orders still lands on the WooCommerce orders endpoint.

## Rollback Notes
- Revert this commit to restore the previous hardcoded links.
- Remove `inc/bsc-url-helpers.php` and its require from `functions.php` if a full rollback is needed.
