# BSC-080 - Checkout locked country field styling

## Objective
Hacer que el campo de pais bloqueado en checkout se vea igual a los demas campos del formulario, manteniendo `Colombia` fija y el dropdown deshabilitado.

## Context
- El pais ya estaba bloqueado logicamente a `CO`.
- El nuevo select visible de pais usaba un `id` distinto al de los campos originales de WooCommerce.
- Por eso no heredaba los estilos base del checkout y el navegador lo pintaba con apariencia nativa de `disabled`.
- El resultado visible era un dropdown con fuente, espaciado y triangulo inconsistentes frente al resto del formulario.

## Files To Inspect
- `components/checkout/checkout-form.php`
- `sass/components/checkout/_checkout-form.scss`
- `style.css`

## Exact Implementation Plan
1. Agregar una clase dedicada al select de pais bloqueado.
2. Incluir esa clase en el mismo bloque de estilos que usan los selects del checkout.
3. Normalizar el estado `disabled` para conservar color, opacity y tipografia.
4. Mantener el arrow custom por CSS en lugar del arrow nativo del navegador.

## Acceptance Criteria
- El campo `Pais` en checkout se ve alineado con los otros campos.
- `Colombia` mantiene misma fuente, padding y altura que los otros selects.
- El triangulo del dropdown se ve consistente.
- El campo sigue deshabilitado y no se puede cambiar.

## Manual QA
1. Abrir `/checkout`.
2. Comparar visualmente `Pais` con `Departamento` y `Ciudad`.
3. Confirmar que el alto, fuente y padding se ven iguales.
4. Confirmar que el triangulo del dropdown ya no se ve roto.
5. Confirmar que el usuario no puede cambiar `Colombia`.

## Rollback Notes
- Quitar la clase `bsc__locked-country-select` del markup.
- Quitar los estilos dedicados del SCSS/CSS.
