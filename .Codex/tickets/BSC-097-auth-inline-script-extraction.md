# BSC-097 - Extract auth/account inline scripts

## Objective
Remove the remaining inline JavaScript from the custom `login`, `register`, and `mi-cuenta` templates and load those behaviors through the theme script pipeline.

## Context
- `page-login.php` still embeds custom form validation inline.
- `page-register.php` still embeds custom form validation inline.
- `page-mi-cuenta.php` still embeds the account sub-page autoscroll behavior inline.
- These pages already have visual coverage, so this is a safe cleanup slice with low UI risk.

## Files To Inspect
- `page-login.php`
- `page-register.php`
- `page-mi-cuenta.php`
- `inc/scripts/enqueue-scripts.php`
- `js/login.js`
- `js/register.js`
- `js/account-page.js`

## Exact Implementation Plan
1. Move login form validation into `js/login.js`.
2. Move register form validation into `js/register.js`.
3. Move my-account sub-page autoscroll into `js/account-page.js`.
4. Enqueue each script only on its corresponding page template.
5. Re-run the public auth snapshots and the authenticated account gate.

## Acceptance Criteria
- No inline `<script>` remains in `page-login.php`, `page-register.php`, or `page-mi-cuenta.php`.
- The login and register validation behavior remains unchanged.
- My account sub-pages still auto-scroll on mobile/tablet as before.
- Visual baselines for login/register/account remain green.

## Manual QA
1. Open `/login/` and submit empty fields.
2. Open `/register/` and submit empty fields / invalid email.
3. Open `/mi-cuenta/orders/` and confirm the page still scrolls to account content on navigation.

## Rollback Notes
- Restore the original inline scripts in the three templates.
- Remove `js/login.js`, `js/register.js`, and `js/account-page.js`.
- Remove the new conditional enqueues.
