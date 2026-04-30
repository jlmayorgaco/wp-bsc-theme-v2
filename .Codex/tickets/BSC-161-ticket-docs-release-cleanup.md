# BSC-161 - Ticket, Docs, and Release Cleanup

## Objective
Separate active tickets from completed delivery history, normalize local tooling metadata, and update release documentation so the repository is easier to ship safely.

## Context
The `.Codex/tickets` folder contains many completed implementation tickets, while the active work is a smaller set. `package.json` still has inherited Underscores metadata and inconsistent lint command names. Release docs need to reflect the current hardening work.

## Files To Inspect
- `.Codex/tickets/`
- `package.json`
- `README.md`
- `MVP2_RELEASE_STATUS.md`
- `MVP2_GO_LIVE_CHECKLIST.md`

## Exact Implementation Plan
1. Add an active ticket index for the currently open work.
2. Move or mark completed tickets out of the active queue without losing history.
3. Normalize package metadata and npm scripts.
4. Update release docs with the new gates and residual risks.
5. Keep cleanup reversible.

## Acceptance Criteria
- Active tickets are clearly listed.
- Completed tickets are not mixed into the active queue.
- `npm run lint:js`, `npm run lint:scss`, and `npm run lint:php` are available.
- Release docs mention checkout/cart and packing stock QA.

## Manual QA
1. Review `.Codex/tickets/README.md`.
2. Run the lint commands.
3. Review release checklist for the new manual QA items.

## Rollback Notes
- Revert ticket index, package metadata, and documentation changes.
