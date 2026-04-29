# BSC-073 Admin order label custom print size

## Objective

Force the admin "Imprimir con datos (PDF)" label print view to use the intended custom page size instead of the browser default Letter size, and allow the operator to adjust width and height in millimeters before printing.

## Context

The current print view already hardcodes `@page` to `100mm x 153mm`, but that size is embedded statically in the CSS file. Operators need to adjust the physical label size in `mm`, and the print dialog should receive that exact size before opening so the generated PDF/print layout matches the label stock more reliably.

## Files To Inspect

- `admin/order-label-print.php`
- `admin-order-label-print.css`
- `sass/pages/admin/_order-label-print.scss`
- `admin/class-bsc-order-labels.php`

## Implementation Plan

1. Keep the existing self-contained print HTML/CSS flow.
2. Add width/height controls in the print toolbar using millimeter inputs.
3. Inject a dynamic `<style>` block that overrides `@page` and label dimensions with the current `mm` values.
4. Re-apply the chosen size immediately before `window.print()`.
5. Persist the operator's chosen dimensions in browser `localStorage` for the next print session.

## Acceptance Criteria

- Opening "Imprimir con datos (PDF)" still shows the same label design.
- The operator can change width and height in `mm` directly in the print view.
- Clicking print uses the adjusted `mm` dimensions, not the default Letter layout.
- Reloading the print view in the same browser restores the last used dimensions.
- Restoring defaults returns the label to `100mm x 153mm`.

## Manual QA

1. Open the admin orders page and click `Imprimir con datos (PDF)` for one order.
2. Confirm the print toolbar shows `Ancho (mm)` and `Alto (mm)` inputs.
3. Print once with the default `100 x 153 mm` size and confirm the preview uses the custom label dimensions.
4. Change the size, for example to `101 x 150 mm`, and print again.
5. Reload the print page and confirm the previous values persist in the same browser.
6. Click `Restablecer medida` and confirm the inputs return to `100 x 153`.

## Rollback Notes

Revert this ticket's changes in `admin/order-label-print.php` to restore the previous fixed-size print view without adjustable `mm` controls.
