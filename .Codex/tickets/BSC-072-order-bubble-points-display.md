# BSC-072 Order Bubble Points display

## Objective

Fix the Bubble Points value displayed on the customer order view and the checkout thank you page.

## Context

The Bubble Points plugin stores the customer balance in user meta key `bsc_bubble_points` and exposes `bsc_bp_get_balance()`.

The order templates were reading different, non-existent meta keys:

- `woocommerce/myaccount/view-order.php` used `bubble_points_balance`
- `components/checkout/views/checkout-view-thankyou.php` used `bubble_points`

This caused the templates to show `0` or hide the block even when the customer had a real Bubble Points balance.

## Files To Inspect

- `plugins/bubble-points/includes/store.php`
- `plugins/bubble-points/classes/class-bsc-bubble-points.php`
- `woocommerce/myaccount/view-order.php`
- `components/checkout/views/checkout-view-thankyou.php`
- `inc/woocommerce.php`

## Implementation Plan

1. Add a small WooCommerce helper to read Bubble Points for the user attached to an order.
2. Prefer the Bubble Points store helper `bsc_bp_get_balance()`.
3. Fall back to `BSC_Bubble_Points::get()`.
4. Fall back to the real user meta key `bsc_bubble_points`.
5. Update view order and thank you templates to use the shared helper.

## Acceptance Criteria

- My account order detail displays the order customer's real Bubble Points balance.
- Thank you page displays the same real Bubble Points balance when it is greater than zero.
- Templates no longer read `bubble_points_balance` or `bubble_points`.
- Guest orders safely display zero/no points.

## Manual QA

1. Set a test customer balance in `bsc_bubble_points`.
2. Open `/my-account/view-order/{order_id}/` for an order owned by that customer.
3. Confirm the Bubble Points block shows the same value as the Bubble Points profile page.
4. Complete checkout as that user and confirm the thank you page shows the same value when the balance is greater than zero.
5. Confirm guest orders do not trigger PHP notices.

## Rollback Notes

Revert this ticket's commit to restore the previous template-specific meta lookups.
