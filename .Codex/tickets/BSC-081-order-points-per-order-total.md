# BSC-081 - Bubble Points per order total in order views

## Objective
Mostrar en `view-order` y `thankyou` solo los Bubble Points generados por esa compra, usando la regla fija `1 punto por cada 1000 COP`.

## Context
- Las vistas de orden ya no deben mostrar el balance acumulado del usuario.
- El helper anterior dependia de `_bsc_bp_points_awarded` o del ledger `bsc_points_ledger`.
- Eso hacia que la UI dependiera del estado de escritura del ledger/meta en lugar de la regla de negocio visible.
- El plugin Bubble Points ya premia ordenes con `floor($order->get_total() / 1000)`.
- La forma mas segura es calcular directamente desde el total de la orden y reutilizar ese helper en las vistas.

## Files To Inspect
- `inc/woocommerce.php`
- `woocommerce/myaccount/view-order.php`
- `components/checkout/views/checkout-view-thankyou.php`

## Exact Implementation Plan
1. Reemplazar el helper de puntos por orden para que calcule `floor(order_total / 1000)`.
2. Mantener el helper compartido usado por `view-order` y `thankyou`.
3. Eliminar fallbacks que pudieran volver a mostrar el balance del usuario.
4. Ajustar el copy del thank you para que hable de puntos generados, no acumulados.

## Acceptance Criteria
- `view-order` muestra solo los puntos de esa compra.
- `thankyou` muestra el mismo valor por compra.
- La regla aplicada es `1 Bubble Point por cada 1000 COP` del total de la orden.
- La UI no depende del balance total del usuario.

## Manual QA
1. Crear o revisar una orden con total conocido, por ejemplo `74,000 COP`.
2. Abrir `/mi-cuenta/view-order/{id}` y confirmar que muestra `74` Bubble Points.
3. Abrir la pantalla de thank you de esa misma orden y confirmar que muestra el mismo valor.
4. Comparar con el balance del perfil y confirmar que ya no intenta mostrar el acumulado total del usuario.

## Rollback Notes
- Restaurar el helper anterior basado en `_bsc_bp_points_awarded` / ledger.
- Restaurar los fallbacks previos en `view-order` y `thankyou`.
