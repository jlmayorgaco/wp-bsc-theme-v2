# BSC-160 - Packing Stock Source Deduction

## Objective
Allow operators to confirm packed order items and choose whether each product is deducted from bodega or showroom stock, defaulting to bodega.

## Context
The dual stock model tracks `_stock_bodega` and `_stock_tienda`, but the packing view is currently print-only. Operators need a practical way to mark where the item was physically taken from when packing, including the edge case where fulfillment uses showroom stock.

## Files To Inspect
- `admin/bsc-orders-page.php`
- `admin/bsc-packing-view.css`
- `admin/bsc-packing-view.js`
- `includes/class-bsc-stock.php`

## Exact Implementation Plan
1. Render each packing line with product ID, item ID, quantity, current bodega/showroom stock, and a stock source selector.
2. Add a nonce-protected AJAX handler to deduct stock for a packed line.
3. Store per-order-item source metadata so repeated submissions do not double-deduct.
4. Use `BSC_Stock::adjust()` for stock movement logging.
5. Show packed state and operator feedback in the packing view.

## Acceptance Criteria
- Each order item defaults to source `bodega`.
- Operator can choose `showroom` before confirming a line.
- Confirmed lines are marked as packed and cannot be deducted twice.
- Stock movement log records the deduction reason.
- PHP and JS linters pass.

## Manual QA
1. Open `BSC > Pedidos`, select an order, and open packing view.
2. Confirm one line from bodega and one from showroom.
3. Reopen packing view and verify confirmed lines remain locked.
4. Check product stock and stock history in `BSC > Productos`.

## Rollback Notes
- Revert `admin/bsc-orders-page.php`, `admin/bsc-packing-view.css`, `admin/bsc-packing-view.js`, and `includes/class-bsc-stock.php`.
