# BSC-129 - Email Preview Recovery Loop Hardening

## Objective
Make the email preview visual suite resilient to transient `admin-post.php` gateway pages so the intended retry loop actually runs instead of aborting on the first navigation failure.

## Context
The release sweep showed that `email-previews.spec.js` already had a retry loop, but `gotoAndStabilize()` could throw before the loop reached its fallback logic. That made the suite fail on transient tablet preview errors even though retryable admin-post instability was already expected.

## Files To Inspect
- `tests/e2e/visual/email-previews.spec.js`

## Exact Implementation Plan
1. Extract preview loading into a helper that wraps login + preview navigation in `try/catch`.
2. Increase the preview retry budget slightly.
3. Reset the page between attempts.
4. Re-run the visual wrapper.

## Acceptance Criteria
- Transient gateway failures inside preview navigation are retried inside the spec.
- `npm run test:e2e:visual` returns green.
- No snapshot changes are introduced by this hardening.

## Manual QA
1. Run `npm run test:e2e:visual`.
2. Confirm email previews complete across all three projects.

## Rollback Notes
- Revert this commit to restore the previous email preview retry logic.
