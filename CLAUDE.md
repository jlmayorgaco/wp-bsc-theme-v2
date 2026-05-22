# Bubble Skin Care (BSC) - Claude Guide

This is the short agent guide. The single source of truth for roadmap, release notes, QA, deploy, operations, historical tickets, and backlog is `ROADMAP_BSC.md`.

## Project Identity

BSC is a custom WordPress + WooCommerce ecommerce theme. Treat it as a production store where checkout, cart, account, admin orders, SEO, and mobile UX are high-risk surfaces.

## Active Workflow

- Do not create standalone `.md` tickets, release notes, changelogs, runbooks, or checklists.
- Do not use `.Codex/tickets/` or `.claude/tickets/` as active queues.
- Add new work directly to the backlog or relevant section in `ROADMAP_BSC.md`.
- Historical evidence lives in git commits and consolidated notes inside `ROADMAP_BSC.md`.
- Every code change should be traceable to a roadmap/backlog entry or a clear user request.

## Delivery Rules

Before editing:

1. State current behavior.
2. List files to change.
3. State the minimum implementation.
4. Call out risks.
5. Update `ROADMAP_BSC.md` when the task changes roadmap, release, QA, deploy, operations, or backlog state.

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

- `ROADMAP_BSC.md`
