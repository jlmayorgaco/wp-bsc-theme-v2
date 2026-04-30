# BSC-121 — Stable Visual Wrapper

## Objective
Make the top-level visual regression command deterministic on the local `bsc.local` stack by replacing the concurrent visual runner with a sequential, page-scoped wrapper.

## Context
- The underlying visual suites are already green when run in isolated slices.
- `npm run test:e2e:visual` still launches all visual specs together, which causes intermittent local session and preview-route flake.
- This is a harness stability issue, not a product UI issue.

## Files To Inspect
- `package.json`
- `tests/README.md`

## Exact Implementation Plan
1. Replace the generic visual script with a sequential chain of the stable visual subcommands.
2. Force `--workers=1` on each slice.
3. Document the new execution model and why it exists.

## Acceptance Criteria
- `npm run test:e2e:visual` passes on the local storefront environment.
- The command covers public pages, header/mobile nav, authenticated pages, and email previews.
- The documentation matches the new behavior.

## Manual QA
1. Run `npm run test:e2e:visual`.
2. Confirm all four visual slices execute in sequence.
3. Confirm the command finishes green without manual environment juggling.

## Rollback Notes
- Revert this commit to restore the old concurrent visual command.
