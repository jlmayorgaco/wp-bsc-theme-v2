# BSC-067 - Checkout trust signals redesign

## Objective
Replace the checkout trust signal placeholders with the seven-icon vertical reassurance block from the client design, using the provided checkout icon assets and responsive styling.

## Context
The trust block lives in `components/checkout/views/checkout-view-main.php` below the checkout form. The current markup has broken image `src` attributes and short placeholder copy. The icon assets are present in `images/checkout/` as `1ICONOS_CHECKOUT.png` through `7ICONOS_CHECKOUT.png`.

## Files to Inspect
- `components/checkout/views/checkout-view-main.php`
- `images/checkout/`
- `sass/style.scss`
- `sass/components/checkout/`
- `style.css`

## Implementation Plan
1. Render the seven trust rows from a small PHP array to keep the markup consistent.
2. Fix icon URLs with `esc_url()` and decorative alt text.
3. Use HTML entities for Spanish accents to avoid encoding issues in this theme.
4. Add a dedicated checkout trust-signals SCSS partial.
5. Import the partial from `sass/style.scss`.
6. Compile Sass to update `style.css` and `style.css.map`.

## Acceptance Criteria
- The checkout trust block shows seven rows matching the provided design.
- Each row has the correct icon, bold emphasis, and dotted separator.
- Image URLs are valid and escaped.
- The layout remains readable on desktop, tablet, and mobile.
- The trust block does not affect checkout form, cart, summary, or payment layout.

## Manual QA
1. Open `/checkout` on desktop and compare the trust block against the reference image.
2. Confirm all seven icons load.
3. Resize to tablet and mobile widths and confirm text wraps without overlap.
4. Confirm the checkout form still submits normally.

## Rollback Notes
Revert this ticket's changes in `components/checkout/views/checkout-view-main.php`, `sass/components/checkout/_checkout-trust-signals.scss`, `sass/style.scss`, compiled CSS assets, and the checkout icon assets if they were introduced by this ticket.
