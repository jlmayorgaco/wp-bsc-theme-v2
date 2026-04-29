# BSC-078 - Checkout shipping price by department with admin-configured rates

## Objective
Hacer que el precio de envío en checkout cambie apenas cambia el departamento, y mover los valores de `Bogotá/Cundinamarca` y `resto del país` a campos configurables en admin.

## Context
- El checkout ya dispara recálculo cuando cambia el departamento, pero el backend seguía tratando la dirección como incompleta si no existía ciudad.
- Eso dejaba el precio de envío pegado hasta completar la ciudad o hasta otro refresh posterior.
- Además, la tarifa seguía hardcodeada en `inc/woocommerce.php` con valores fijos.
- El theme ya tiene una pantalla de `Configuración BSC`, así que la solución más segura es colgar los nuevos campos ahí.
- El flujo no debe romper envío gratis, labels existentes ni limpieza de caché de shipping packages.

## Files To Inspect
- `admin/bsc-admin-menu.php`
- `inc/woocommerce.php`
- `js/checkout.js`

## Exact Implementation Plan
1. Agregar dos opciones en `Configuración BSC`: tarifa `Bogotá/Cundinamarca` y tarifa `resto del país`.
2. Reemplazar los costos hardcodeados por helpers que lean esas options.
3. Separar la noción de “dirección completa” de la noción de “contexto suficiente para cotizar envío”.
4. Permitir que el package destination y la recalculación de rates se actualicen con solo `departamento`.
5. Mantener la ciudad como requisito funcional del checkout, pero no como prerequisito para mostrar el valor de envío.
6. Ajustar el mensaje visual del resumen para pedir solo el departamento antes de mostrar el envío.

## Acceptance Criteria
- En checkout, al cambiar el departamento, el valor de envío se actualiza sin esperar a seleccionar ciudad.
- En `BSC > Configuración` existen dos campos nuevos para tarifas:
  - `Tarifa Bogotá/Cundinamarca`
  - `Tarifa resto del país`
- El cálculo usa esos valores en vez de `9000/20000` hardcodeados.
- El envío gratis sigue prevaleciendo cuando aplica.
- Si no hay departamento seleccionado, el resumen sigue ocultando el valor de envío.

## Manual QA
1. Ir a `BSC > Configuración` y confirmar que aparecen los 2 campos nuevos.
2. Guardar `10000` para Bogotá/Cundinamarca y `17000` para resto del país.
3. Abrir `/checkout` con carrito por debajo del umbral de envío gratis.
4. Seleccionar `Bogotá D.C.` o `Cundinamarca` y confirmar que el resumen muestra `10000` sin esperar ciudad.
5. Cambiar a un departamento como `Antioquia` y confirmar que el resumen cambia a `17000` apenas cambia el departamento.
6. Completar ciudad y confirmar que el precio se mantiene coherente.
7. Probar un carrito con envío gratis y confirmar que el resumen sigue mostrando `Gratis`.

## Rollback Notes
- Eliminar los 2 nuevos campos del settings page en `admin/bsc-admin-menu.php`.
- Restaurar la validación de shipping por `departamento + ciudad` en `inc/woocommerce.php`.
- Restaurar el mensaje anterior del checkout en `js/checkout.js`.
