# BSC-100 — Packing view asset extraction

## Objective
Remove the inline CSS and inline onclick handlers from the admin packing view and load that printable page from dedicated assets.

## Context
The main orders screen was already moved to assets in `BSC-099`, but the popup packing view still embedded its own `<style>` block and inline button handlers. That made the flow inconsistent and harder to test.

## Files to Inspect
- `admin/bsc-orders-page.php`
- `admin/bsc-packing-view.css`
- `admin/bsc-packing-view.js`
- `tests/e2e/smoke/admin-orders.spec.js`

## Minimum Viable Implementation
1. Replace the popup `<style>` block with a linked stylesheet.
2. Replace inline `onclick` actions with a linked script.
3. Keep the printable markup visually identical by preserving the same class structure and spacing.
4. Extend admin smoke coverage to open the packing view popup.

## Risks
- The popup depends on the bulk-action JS setting `target=_blank` at submit time.
- Print CSS must preserve the same spacing and card layout as the previous inline block.
- Popup smoke can flake if the form submits before the new tab listener is registered.

## Acceptance Criteria
- `bsc_render_packing_view()` no longer embeds inline CSS or inline button handlers.
- The packing view popup still opens and renders the order card.
- The admin smoke test passes for the popup flow.

## Manual QA
1. Login to `wp-admin/admin.php?page=bsc-orders`.
2. Select one order and open `Vista de empaque`.
3. Confirm the popup shows the same card layout as before.
4. Confirm `Imprimir` and `Cerrar` still work.

## Rollback Notes
Revert `admin/bsc-orders-page.php`, `admin/bsc-packing-view.css`, `admin/bsc-packing-view.js`, and `tests/e2e/smoke/admin-orders.spec.js`.
