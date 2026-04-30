# BSC-125 — Gated Route and Email Preview Recovery

## Objective
Stabilize the final authenticated visual slices by adding explicit relogin/retry recovery to gated account routes and email preview pages.

## Context
- The remaining failures are not UI regressions; they happen when the local stack occasionally serves the login shell or a transient admin preview error instead of the expected page.
- Snapshot failures on `account-addresses` and `view-order` were caused by the wrong page shell being captured.
- Email preview failures were caused by transient admin-post responses even after the shared harness retried navigation.

## Files To Inspect
- `tests/e2e/visual/account-and-post-purchase.spec.js`
- `tests/e2e/visual/email-previews.spec.js`

## Exact Implementation Plan
1. Add a small spec-local helper for gated account routes that re-logins and retries when the route lands on login instead of the target view.
2. Add a preview helper that re-logins and retries when the email preview content does not match the expected template text.
3. Re-run the top-level visual wrapper.

## Acceptance Criteria
- `npm run test:e2e:visual` returns green on the local stack.
- No UI snapshots need to be changed.
- Recovery logic stays local to the fragile specs.

## Manual QA
1. Run `npm run test:e2e:visual`.
2. Confirm `account-addresses`, `view-order`, and email previews stop failing by landing on the wrong shell.

## Rollback Notes
- Revert this commit to remove the spec-local recovery logic.
