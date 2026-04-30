# BSC-110 — Admin Showroom Asset Extraction

## Objective
Move inline CSS and inline JS out of the showroom sale admin screen into dedicated assets without changing the approved UI or breaking the physical-sale workflow.

## Context
`admin/bsc-showroom-page.php` mixed page render, form styling, search-result rendering, cart-row rendering, and notice styling in a single template. The screen already worked, so the safe path is to preserve WooCommerce/AJAX behavior and only extract presentation and browser logic.

## Files to inspect
- `admin/bsc-showroom-page.php`
- `admin/bsc-showroom.css`
- `js/admin/bsc-showroom.js`
- `tests/e2e/smoke/admin-showroom.spec.js`

## Exact implementation plan
1. Add page-scoped enqueueing for showroom CSS/JS.
2. Localize `ajaxUrl`, nonce, and UI strings to the external script.
3. Replace inline `style` attributes with stable admin classes.
4. Move search/cart/notice browser logic into `js/admin/bsc-showroom.js`.
5. Add admin smoke coverage for page load and search/add-to-cart behavior.

## Acceptance criteria
- `admin/bsc-showroom-page.php` contains no inline `style` or `<script>` blocks.
- Showroom CSS/JS load only on the showroom screen.
- Product search, add-to-cart, and notice behavior still work.
- Admin smoke covers showroom load and mocked search/add-to-cart.

## Manual QA
1. Open `BSC > Showcase` and verify product search layout, customer form, payment radios, and recent sales sidebar.
2. Search a real product, select it, and add it to the cart.
3. Change quantity and remove an item from the cart.
4. Trigger an empty-state notice and a successful sale registration.

## Rollback notes
- Revert the commit for `BSC-110` to restore the previous inline asset implementation.
- Remove `admin/bsc-showroom.css`, `js/admin/bsc-showroom.js`, and `tests/e2e/smoke/admin-showroom.spec.js` if full rollback is required.
