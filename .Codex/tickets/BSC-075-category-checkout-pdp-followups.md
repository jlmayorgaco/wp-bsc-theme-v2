# BSC-075 - Category mobile, checkout country lock, and PDP follow-ups

## Objective
Close four follow-ups together: mobile category filter UX, real checkout country lock to Colombia, PDP gallery touch/responsive behavior above `460px`, and precise loading priority only for the real PDP main image.

## Context
- The `group-skin-care` category page on mobile still showed a noisy button cloud instead of a `Filter + chip + modal` pattern.
- Checkout still allowed other countries technically because the field rendered and backend logic accepted posted country values.
- The PDP gallery only switched to the touch/mobile layout below `460px`, leaving larger phones and touch devices with the wrong interaction.
- The `eager/high` image optimization was attached to a global image attributes filter, so it could prioritize the wrong PDP image.

## Files To Inspect
- `components/product-category.php`
- `js/category-filter.js`
- `components/checkout/checkout-form.php`
- `inc/woocommerce.php`
- `sass/pages/_page-shop.scss`
- `sass/components/products/_woocommerce_gallery.scss`
- `style.css`

## Exact Implementation Plan
1. Keep desktop category buttons and add a mobile-only filter trigger, active badge, chip, and modal options list.
2. Reuse the existing client-side filtering by syncing desktop and mobile controls to one active filter state.
3. Replace checkout country selects with hidden `CO` inputs.
4. Force `CO` again in WooCommerce frontend/AJAX country resolution and posted checkout data.
5. Expand the gallery touch/mobile behavior to `768px` and coarse pointer devices.
6. Move PDP eager/high priority to the WooCommerce gallery-specific image params hook so only the main gallery image gets it.

## Acceptance Criteria
- Mobile category pages show a `Filter` button, active badge, chip, and modal.
- Desktop category pages keep the current inline filter buttons.
- Checkout always uses Colombia even through AJAX/postback.
- PDP gallery thumbs move below the main image on mobile/touch widths above `460px`.
- The zoom overlay stops intercepting touch on those devices.
- Only the real PDP main image gets `loading="eager"` and `fetchpriority="high"`.

## Manual QA
1. Open `http://bsc.local/product-category/group-skin-care/` in a mobile viewport.
2. Confirm `Filter` opens a modal and selecting one option closes it and updates the chip.
3. Clear the chip and confirm the view returns to `Todos`.
4. Open checkout and verify no visible country selector remains.
5. Change department/city and confirm shipping still recalculates.
6. Open a PDP on a phone-size viewport wider than `460px` or on a coarse pointer device.
7. Confirm thumbs are horizontal below the main image and touch interaction is no longer blocked by zoom.
8. Inspect the main gallery image and confirm `eager/high`.

## Rollback Notes
- Revert this ticket to restore the old category mobile buttons, visible checkout country fields, previous gallery breakpoint behavior, and the old global PDP image loading filter.
