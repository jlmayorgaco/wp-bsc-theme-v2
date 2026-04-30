# BSC-149 - My Account orders two-step progress bar

## Objective

Reduce the progress bar shown in `/my-account/orders/` to two visible states only:

- `Recibido`
- `Entregado`

## Context

The current list view reuses the shared `BSC_Order_Progress_Bar` component and renders three states:

- `Recibido`
- `Enviado`
- `Entregado`

The request is to simplify only the orders list page, without changing:

- order detail view
- thank-you view
- backend status semantics

## Files to inspect

- `components/orders/order-progress-bar.php`
- `components/orders/orders-table.php`
- `sass/components/orders/_progress_bar.scss`
- `style.css`

## Exact implementation plan

1. Add a compact display mode to the progress bar component.
2. Keep the current default mode unchanged for detail/thank-you usage.
3. In compact mode:
   - visible labels become `Recibido` and `Entregado`
   - `processing`, `pending`, `on-hold`, `preparing`, and `shipped` remain on step 1
   - only `completed` activates step 2
4. Update width helpers to support `50%`.

## Acceptance criteria

- `/my-account/orders/` shows only two labels: `Recibido` and `Entregado`
- order detail and thank-you keep the current progress behavior
- shipped orders do not appear as delivered

## Manual QA

1. Open `/my-account/orders/` with an order in `processing`.
2. Open `/my-account/orders/` with an order in `shipped`.
3. Open `/my-account/orders/` with an order in `completed`.
4. Confirm:
   - only 2 labels are visible
   - `shipped` is not shown as delivered
5. Open a thank-you page and an order detail page to confirm they still use the existing 3-step bar.

## Rollback notes

- Remove the compact display mode from the progress bar component.
- Revert the orders table render call and the `50%` width helper.
