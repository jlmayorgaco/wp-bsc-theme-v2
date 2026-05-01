# BSC Active Ticket Queue

This folder is intentionally quiet.

Completed tickets are removed after their delivery commits are accepted. Use git history, `CHANGELOG.md`, and `MVP2_RELEASE_STATUS.md` for completed work.

## Create A New Ticket

Add one file per active delivery:

```text
BSC-###-short-title.md
```

Each ticket must include:

- objective
- context
- files to inspect
- implementation plan
- acceptance criteria
- manual QA
- rollback notes

After implementation, validation, commit, and acceptance, delete the ticket file from this folder.
