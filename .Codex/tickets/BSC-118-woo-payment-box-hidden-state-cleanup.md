# BSC-118 - Woo Payment Box Hidden State Cleanup

## Objective
Eliminar los `style="display:none"` residuales de los payment boxes Woo y reemplazarlos por una clase CSS inicial, sin romper el comportamiento core de apertura/cierre.

## Context
- Los ultimos `style=` reales del checkout/theme estaban en `woocommerce/checkout/payment-method.php` y `woocommerce/myaccount/form-add-payment-method.php`.
- WooCommerce controla la visibilidad de esos bloques con su propio JS (`show/hide/slideDown`), por lo que una clase CSS inicial es suficiente y menos invasiva que reescribir el flujo.

## Files To Inspect
- `woocommerce/checkout/payment-method.php`
- `woocommerce/myaccount/form-add-payment-method.php`
- `sass/components/checkout/_checkout-payment.scss`
- `style.css`
- `style.css.map`

## Exact Implementation Plan
1. Reemplazar el `style="display:none"` inicial por una clase compartida.
2. Definir la regla `display:none` en `_checkout-payment.scss`.
3. Recompilar el CSS final.
4. Revalidar el smoke de checkout para asegurar que el flujo principal sigue vivo.

## Acceptance Criteria
- Los dos templates Woo ya no contienen `style="display:none"`.
- El estado inicial oculto queda controlado por CSS.
- El checkout smoke sigue verde.

## Manual QA
1. Abrir checkout.
2. Cambiar metodo de pago y validar que los bloques se muestran/ocultan.
3. Abrir `Mi cuenta > Add payment method` y validar el mismo comportamiento.

## Rollback Notes
- Revertir este commit restaura el `style="display:none"` original en ambos templates.
