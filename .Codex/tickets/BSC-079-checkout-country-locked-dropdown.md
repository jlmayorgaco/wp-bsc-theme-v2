# BSC-079 - Checkout country locked to Colombia

## Objective
Mostrar `Colombia` por defecto en checkout y evitar cualquier cambio manual del pais desde el dropdown.

## Context
- El backend ya estaba forzando `CO` en checkout y shipping.
- En la UI se habia ocultado el campo pais, pero el requerimiento ahora es mostrarlo como dropdown bloqueado.
- El cambio debe seguir siendo seguro frente a manipulacion del form.
- WooCommerce no envia campos `disabled`, asi que el valor real debe seguir posteandose con un hidden input.

## Files To Inspect
- `components/checkout/checkout-form.php`
- `inc/woocommerce.php`

## Exact Implementation Plan
1. Renderizar un dropdown visible con solo `Colombia`.
2. Marcar ese dropdown como `disabled`.
3. Mantener un hidden input con `billing_country=CO` y `shipping_country=CO`.
4. Conservar la capa backend que fuerza `CO` para checkout y shipping.

## Acceptance Criteria
- En checkout se ve `Colombia` como pais por defecto.
- El usuario no puede cambiar el pais desde el dropdown.
- El checkout sigue enviando `CO` correctamente.
- No se rompe la recarga de ciudades ni el calculo de envio.

## Manual QA
1. Abrir `/checkout`.
2. Confirmar que el campo `Pais` muestra `Colombia`.
3. Confirmar que el dropdown esta deshabilitado.
4. Cambiar departamento y ciudad y verificar que el shipping siga recalculando.
5. Completar compra de prueba y validar que billing/shipping country quede en `CO`.

## Rollback Notes
- Restaurar los hidden inputs directos anteriores en `components/checkout/checkout-form.php`.
- El backend no necesita rollback porque ya debe seguir forzando `CO`.
