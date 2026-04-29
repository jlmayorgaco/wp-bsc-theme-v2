# BSC-102 - Admin dashboard/settings asset extraction

## Objective
Move the remaining inline dashboard/settings presentation styles from `admin/bsc-admin-menu.php` into a dedicated admin stylesheet, without changing the approved admin layout.

## Context
- The BSC dashboard still renders KPI cards, panel layout, alert rows, and section headings through inline styles.
- The BSC settings screen also embeds repeated inline heading spacing.
- This is low-risk presentation debt inside the admin area and can be extracted without touching business logic.

## Files To Inspect
- `admin/bsc-admin-menu.php`
- `admin/bsc-admin-dashboard.css`

## Exact Implementation Plan
1. Add an admin stylesheet for `bsc-dashboard` / `bsc-settings`.
2. Replace inline dashboard/settings styles with semantic admin classes.
3. Keep the same DOM structure and rendered content.

## Acceptance Criteria
- `bsc_render_dashboard()` no longer prints inline `<style>`.
- KPI cards, panel grid, low-stock alerts, and settings section headings are styled from the stylesheet.
- The admin render stays visually unchanged.

## Manual QA
1. Open `BSC > Dashboard` and compare KPI layout, alerts, and tables.
2. Open `BSC > Configuración` and compare section heading spacing.

## Rollback Notes
- Remove `admin/bsc-admin-dashboard.css` and its enqueue.
- Restore the original inline style attributes and inline `<style>` block in `admin/bsc-admin-menu.php`.
