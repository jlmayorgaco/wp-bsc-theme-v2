# BSC-135 - Local Tooling and Lint Normalization

## Objective
Normalize the local lint toolchain so PHP, JS, and SCSS checks are runnable from npm scripts in the Local + Windows environment used by this project.

## Context
The repo currently has release-ready runtime behavior, but local quality checks are inconsistent: `php` is not in `PATH`, and `npm run lint-js` / `npm run lint-style` do not work because the scripts and glob handling are not aligned with this environment.

## Files To Inspect
- `package.json`
- `tests/README.md`
- `tools/lint-php.js`
- `tools/check-js-syntax.js`
- `tools/check-scss-entrypoints.js`

## Exact Implementation Plan
1. Add a PHP lint runner that auto-discovers the Local PHP binary.
2. Add a JS syntax check runner that validates theme JS, E2E JS, and tooling JS.
3. Add a SCSS entrypoint check runner that compiles the main entrypoints in memory.
4. Normalize npm scripts so `lint-js`, `lint-style`, and `lint:php` are usable locally.
5. Document the commands in `tests/README.md`.

## Acceptance Criteria
- `npm run lint-js` runs successfully.
- `npm run lint-style` runs successfully.
- `npm run lint:php` runs successfully without requiring `php` in `PATH`.
- Documentation reflects the working commands.

## Manual QA
1. Run `npm run lint-js`.
2. Run `npm run lint-style`.
3. Run `npm run lint:php`.
4. Confirm the commands work from a normal Local / PowerShell session.

## Rollback Notes
- Revert the commit to restore the prior npm script definitions.