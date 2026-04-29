# BSC-076 - Category mobile filter animation

## Objective
Make the mobile category filter menu open and close with a polished animation instead of appearing instantly.

## Context
- The mobile filter modal already existed and worked functionally.
- Its open/close behavior was abrupt because JS only toggled `hidden`.
- The user requested a nicer animated presentation for the category menu in mobile.
- The change should preserve backdrop close, `Escape` close, and current filter selection logic.

## Files To Inspect
- `js/category-filter.js`
- `sass/pages/_page-shop.scss`
- `style.css`

## Exact Implementation Plan
1. Add modal visibility states controlled by CSS classes instead of instant hide/show only.
2. Animate the backdrop with a fade.
3. Animate the modal panel with slide-up + fade.
4. Animate filter options with a short staggered entrance.
5. Respect `prefers-reduced-motion` to avoid forcing animation.

## Acceptance Criteria
- Tapping `Filter` in mobile opens the category menu with animation.
- Closing by backdrop, close button, option select, or `Escape` animates out cleanly.
- The filter logic still applies exactly as before.
- Reduced-motion users get the same functionality without animation.

## Manual QA
1. Open `http://bsc.local/product-category/group-skin-care/` in a mobile viewport.
2. Tap `Filter` and confirm the overlay fades in and the panel slides up.
3. Confirm category options animate in smoothly.
4. Close using the backdrop and confirm the panel fades/slides out cleanly.
5. Select a category and confirm the modal closes with animation and updates the chip.
6. Test `Escape` from a keyboard-enabled mobile/tablet browser if available.

## Rollback Notes
- Revert this ticket to restore the previous instant open/close behavior.
