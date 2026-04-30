# BSC-159 - Checkout and Cart Edge Case Hardening

## Objective
Harden custom cart and checkout interactions against duplicate taps, stale AJAX responses, cart-line ambiguity, and failed WooCommerce fragment refreshes.

## Context
The storefront cart UI currently works for the common simple-product path, but several controls use `product_id` as the only line identifier. That can desync when the same product appears in more than one cart line or when WooCommerce rejects a quantity update. Checkout city/shipping refreshes can also race when users change department quickly.

## Files To Inspect
- `js/cart.js`
- `js/checkout.js`
- `inc/ajax/cart-actions.php`
- `components/checkout/checkout-cart.php`
- `components/products/card.php`

## Exact Implementation Plan
1. Add cart-item-key support to checkout/cart controls while keeping product-id fallback for product cards.
2. Make quantity updates disable the clicked controls during the request and reconcile UI from server response.
3. Return updated cart item keys, quantities, item totals, and cart count from AJAX handlers.
4. Guard checkout city reloads and review summary refreshes against stale responses.
5. Keep WooCommerce fragment refresh as a secondary sync path, not the only source of truth.

## Acceptance Criteria
- Rapid +/- clicks do not send overlapping updates for the same cart line.
- Checkout cart controls update the correct line by cart item key.
- Removing the final unit restores the product card CTA and updates checkout summary.
- Stale city AJAX responses are ignored if a newer department was selected.
- JS syntax lint remains green.

## Manual QA
1. Add a product from category, increment/decrement rapidly, and confirm badge/controls settle correctly.
2. Open checkout, change department twice quickly, and confirm the city field matches the last department.
3. Remove an item from checkout sidebar and confirm totals/shipping summary update.
4. Try decrementing to zero and confirm the row disappears without a stale quantity.

## Rollback Notes
- Revert this ticket's changes in `js/cart.js`, `js/checkout.js`, `inc/ajax/cart-actions.php`, and checkout cart markup.
