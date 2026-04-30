# BSC Active Ticket Queue

This folder is the active Codex ticket queue. Completed historical tickets should be moved out of the active queue after their delivery commit is accepted.

## Active Now

- `BSC-158-visible-mojibake-cleanup.md`
- `BSC-159-checkout-cart-edge-case-hardening.md`
- `BSC-160-packing-stock-source-deduction.md`
- `BSC-161-ticket-docs-release-cleanup.md`

## Cleanup Rule

If a ticket is already implemented, tested, and represented in the changelog or release notes, remove it from this active queue or archive it outside the active ticket folder. Keep new work ticket-driven and one delivery per ticket.

## Current Sweep Notes

- Older `BSC-058` through `BSC-157` files appear to be completed delivery records from prior MVP2 work.
- They should not be treated as open implementation work unless a fresh regression is reported.
- This sweep leaves the historical files in place for traceability and creates a clear active index first.
