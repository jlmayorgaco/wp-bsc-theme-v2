# BSC-114 - Category Filters Inline Handler Cleanup

## Objective
Eliminar los handlers `oninput` inline de los sliders de precio y mover por completo la sincronizacion visual al JS ya encolado para filtros.

## Context
- `components/products/filters.php` todavia mezclaba markup con comportamiento inline en los ranges de precio.
- `js/filters.js` ya era el punto natural para centralizar esa logica.
- El cambio debe mantener intactos el render visual y el flujo AJAX de filtros.

## Files To Inspect
- `components/products/filters.php`
- `js/filters.js`
- `tests/e2e/smoke/site-smoke.spec.js`

## Exact Implementation Plan
1. Quitar `oninput` de ambos sliders de precio.
2. Mover la sincronizacion de outputs a `js/filters.js` con guards de null.
3. Mantener la regla actual que evita que `min` supere `max` y viceversa.
4. Agregar smoke para validar que los outputs visibles se actualizan.

## Acceptance Criteria
- `filters.php` ya no contiene handlers inline.
- `filters.js` sincroniza outputs al cargar y al mover sliders.
- El smoke de categoria valida el comportamiento base del rango.
- No cambia el DOM ni la UI aprobada del sidebar.

## Manual QA
1. Abrir una categoria con filtros.
2. Mover el slider minimo y validar el output visible.
3. Mover el slider maximo y validar el output visible.
4. Confirmar que el grid sigue refrescando via AJAX.

## Rollback Notes
- Revertir este commit restaura los handlers inline en los ranges y elimina el smoke nuevo.
