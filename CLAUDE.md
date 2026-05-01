# Bubble Skin Care (BSC) - Claude Guide

This file mirrors the active agent workflow in `AGENTS.md`. Keep both files aligned.

## Project Identity

BSC is a custom WordPress + WooCommerce ecommerce theme. Treat it as a production store where checkout, cart, account, admin orders, SEO, and mobile UX are high-risk surfaces.

## Active Workflow

- Active tickets live only in `.Codex/tickets/`.
- Closed ticket files are removed from active queues after their commits are accepted.
- Do not use `.claude/tickets/` as an active queue; historical ticket evidence is in git history.
- Every code change should reference a ticket ID in the commit message.

## Delivery Rules

Before editing:

1. State current behavior.
2. List files to change.
3. State the minimum implementation.
4. Call out risks.

After editing:

1. Run the relevant validation.
2. Summarize changed files.
3. Provide manual QA and rollback notes.
4. Commit cleanly.

## Default Validation

```bash
npm run lint
npm run test:e2e:smoke
```

Use visual tests for UI-affecting changes:

```bash
npm run test:e2e:visual
```

## Current Release Docs

- `MVP2_RELEASE_STATUS.md`
- `MVP2_GO_LIVE_CHECKLIST.md`
- `CHANGELOG.md`
- `docs/architecture.md`
