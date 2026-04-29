# BSC-104 - Admin access/followup style extraction

## Objective
Remove inline presentation styles from the BSC access-control page and the followup-emails admin page by moving them into a shared admin stylesheet.

## Context
- `admin/bsc-access-page.php` and `admin/bsc-followup-emails-page.php` still render width, spacing, color, and monospace presentation inline.
- These pages have low interaction complexity and are safe candidates for admin style extraction.

## Files To Inspect
- `admin/bsc-access-page.php`
- `admin/bsc-followup-emails-page.php`
- `admin/bsc-admin-communications.css`

## Exact Implementation Plan
1. Enqueue a shared admin stylesheet only for `bsc-access` and `bsc-followup-emails`.
2. Replace inline style attributes with semantic classes.
3. Keep the rendered copy and table structure unchanged.

## Acceptance Criteria
- No inline style remains in the targeted pages, except core WordPress-generated markup outside these templates.
- Access-control table and followup-email sections preserve spacing and hierarchy.
- No business logic changes.

## Manual QA
1. Open `BSC > Acceso` and verify intro text, column widths, slug rendering, and save button spacing.
2. Open `BSC > Emails` and compare section headings, inline number controls, notices, and template table width.

## Rollback Notes
- Remove `admin/bsc-admin-communications.css` and its enqueue.
- Restore the original inline styles in both templates.
