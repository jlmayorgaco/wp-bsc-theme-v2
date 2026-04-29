# BSC-077 - PDP gallery desktop height alignment

## Objective
Make the desktop PDP main gallery image slightly taller so its visible height aligns with the total height of the 4 thumbnail items plus gaps.

## Context
- The current gallery uses a square main viewport and a fixed 4-thumb vertical column.
- In some desktop widths, the main viewport renders a bit shorter than the thumbnail column.
- Borders on gallery images also contribute to small visual mismatches.
- The user requested a contained style fix without changing the established desktop/mobile layout.

## Files To Inspect
- `sass/components/products/_woocommerce_gallery.scss`
- `style.css`

## Exact Implementation Plan
1. Introduce a shared desktop min-height based on the 4-thumb stack height.
2. Apply that min-height to the main gallery wrapper and viewport.
3. Apply the same fixed height to the thumbnail column on desktop.
4. Set gallery images to `box-sizing: border-box` so borders do not inflate the measured height.
5. Reset the min-height in the mobile/touch breakpoint to avoid oversized mobile viewports.

## Acceptance Criteria
- On desktop, the main gallery viewport no longer appears shorter than the 4 stacked thumbnails.
- The 4 thumbnail items plus gaps visually align with the main image height.
- Mobile layout remains unchanged.

## Manual QA
1. Open a PDP on desktop with 4 gallery thumbnails.
2. Confirm the main image block visually aligns in height with the thumbnail column.
3. Resize the browser around tablet/desktop widths and confirm the alignment still holds.
4. Open the same PDP in mobile viewport and confirm the previous mobile layout still applies.

## Rollback Notes
- Revert this ticket to restore the previous square-only main viewport sizing.
