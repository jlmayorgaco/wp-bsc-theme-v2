# Bubble Skin Care (BSC) - Agent Guide

## Project Identity

Bubble Skin Care is a production-oriented WordPress + WooCommerce ecommerce site implemented primarily through this custom theme.

Business priorities:

1. Launch safely without regressions.
2. Preserve checkout, cart, account, admin operations, navigation, and SEO.
3. Fix visible client-reported issues before speculative refactors.
4. Keep changes minimal, traceable, and reversible.
5. Improve mobile UX, performance, and operational admin workflows.

## Current Release State

- Active branch: `MVP2`.
- Current release references:
  - `MVP2_RELEASE_STATUS.md`
  - `MVP2_GO_LIVE_CHECKLIST.md`
  - `CHANGELOG.md`
- Completed ticket files are intentionally removed from active ticket queues after delivery commits are accepted.
- Historical implementation evidence lives in git history and release notes, not in active ticket folders.

## Ticket Workflow

All new work must start from a fresh ticket under:

```text
.Codex/tickets/
```

Use one ticket per delivery. A ticket must include:

- objective
- context
- files to inspect
- implementation plan
- acceptance criteria
- manual QA
- rollback notes

After the ticket is implemented, tested, committed, and accepted, remove the ticket file from `.Codex/tickets/` to keep the queue quiet.

## Change Discipline

Before editing code:

1. Summarize current behavior in 5-10 lines.
2. List exact files to change.
3. Explain the minimum viable implementation.
4. Identify risks.
5. Then implement.

After implementation:

1. List changed files.
2. Explain what changed.
3. Confirm acceptance criteria.
4. Provide manual QA steps.
5. Mention risks or follow-ups.

## Engineering Rules

- Keep code in the custom theme unless there is a strong reason otherwise.
- Do not add heavy plugins or page builders for work that fits in templates, hooks, CSS, or JS.
- Do not mix large refactors with narrow visual or bug tickets.
- Do not break WooCommerce checkout, cart, account, or order flows.
- Prefer the smallest safe change that solves the ticket completely.
- Use existing theme patterns and helpers before introducing new abstractions.
- Keep generated artifacts, videos, reports, and local captures out of commits unless explicitly requested.

## Validation

Default gates for code changes:

```bash
npm run lint
npm run test:e2e:smoke
```

Run visual tests when the change affects layout, copy, navigation, checkout UI, account UI, admin UI, or frontend rendering:

```bash
npm run test:e2e:visual
```

Before production deploy, also complete the manual checks in `MVP2_GO_LIVE_CHECKLIST.md`.
