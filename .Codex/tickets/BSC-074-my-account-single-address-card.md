# BSC-074 — My Account single address card

## Objective
Dejar una sola tarjeta en `/mi-cuenta/edit-address/`, ocultando la de entrega y renombrando la tarjeta visible a `Facturación y Entregas`.

## Context
- La vista overview de direcciones no carga directamente el formulario de edición.
- WooCommerce delega `/mi-cuenta/edit-address/` a `woocommerce/myaccount/my-address.php` cuando no se solicita un tipo específico.
- Esa plantilla estaba renderizando dos tarjetas: `billing` y `shipping`.
- El usuario reportó que siguen apareciendo ambas tarjetas después del cambio anterior.
- El ajuste debe ser mínimo para evitar nuevos bugs en My Account.

## Files To Inspect
- `woocommerce/myaccount/form-edit-address.php`
- `woocommerce/myaccount/my-address.php`

## Exact Implementation Plan
1. Confirmar que la ruta overview usa `my-address.php`.
2. Reducir el arreglo de direcciones renderizadas a solo `billing`.
3. Cambiar el label de `billing` a `Facturación y Entregas`.
4. No tocar la ruta directa de shipping para no romper acceso manual al endpoint.

## Acceptance Criteria
- En `/mi-cuenta/edit-address/` solo se ve una tarjeta.
- La tarjeta visible muestra el título `Facturación y Entregas`.
- Ya no aparece la tarjeta `Entrega de pedidos`.
- El botón `Editar datos` de la tarjeta visible sigue funcionando.

## Manual QA
1. Abrir `http://bsc.local/mi-cuenta/edit-address/`.
2. Confirmar que solo se renderiza una tarjeta de dirección.
3. Validar que el título de la tarjeta sea `Facturación y Entregas`.
4. Hacer click en `Editar datos` y comprobar que abre la edición de facturación.
5. Probar la ruta directa `/mi-cuenta/edit-address/shipping/` para confirmar que no quedó rota.

## Rollback Notes
- Restaurar `shipping` dentro de `$get_addresses` en `woocommerce/myaccount/my-address.php`.
- Restaurar el label anterior de `billing` a `Facturación`.
