# BSC-133 - Static Page Template Dedup and Hero Fix

## Objective
Deduplicate the legal / FAQ page templates and fix the broken copy-paste state where multiple routes currently inherit the FAQ hero and fallback content.

## Context
`page-claims.php`, `page-cookies.php`, `page-copyrights.php`, `page-faq.php`, `page-policies.php`, and `page-privacy.php` repeat the same static page wrapper. Several of them currently ship with the wrong template metadata, wrong hero copy, and FAQ fallback content copied into unrelated legal pages.

## Files To Inspect
- `functions.php`
- `inc/bsc-static-pages.php`
- `page-claims.php`
- `page-cookies.php`
- `page-copyrights.php`
- `page-faq.php`
- `page-policies.php`
- `page-privacy.php`
- `tests/e2e/helpers/env.js`
- `tests/e2e/smoke/static-pages.spec.js`

## Exact Implementation Plan
1. Add a shared static-page renderer that preserves the current `bsc__static-*` wrappers.
2. Replace the duplicated legal / FAQ templates with thin config-only files.
3. Fix page-specific heroes and fallback content so empty-content pages no longer render FAQ copy.
4. Add a smoke suite that validates the key static routes and their hero titles.

## Acceptance Criteria
- The affected static page templates no longer duplicate the full wrapper markup.
- Each route renders the correct hero title and subtitle.
- FAQ keeps its accordion fallback, while legal pages no longer fall back to FAQ content.
- Static page smoke passes in storefront mode.

## Manual QA
1. Open FAQ, Policies, Privacy, Cookies, Claims, and Copyrights.
2. Confirm each hero title matches the route.
3. Confirm the body still uses the approved static-page layout.
4. If any page content is empty in WP Admin, confirm the fallback content is route-appropriate.

## Rollback Notes
- Revert the commit to restore the previous per-file templates.
