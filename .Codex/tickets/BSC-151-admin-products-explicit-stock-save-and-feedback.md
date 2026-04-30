# BSC-151 — Admin Products Explicit Stock Save And Feedback

## Objective
Unificar la edición de stock de productos con el patrón de guardado explícito usado en pedidos.

## Context
La tabla de productos autosalvaba stock al hacer `change/blur`, con feedback débil y acciones poco descubribles.

## Files To Inspect
- admin/bsc-products-page.php
- admin/bsc-products.css
- js/admin/bsc-products.js

## Implementation Plan
- pasar de autosave a guardado explícito por fila
- mostrar badge de cambios pendientes y botón Guardar
- mejorar labels de acciones y feedback toast
- preservar historial y edición de producto

## Acceptance Criteria
- cambiar stock no persiste hasta pulsar Guardar
- la fila marca cambios pendientes
- el guardado actualiza ambos stocks de la fila
- el historial sigue funcionando

## Manual QA
- cambiar stock bodega y tienda en una fila
- verificar badge pendiente y botón Guardar
- guardar y confirmar toast
- abrir Historial y comprobar carga

## Rollback Notes
- revertir admin/bsc-products-page.php
- revertir admin/bsc-products.css
- revertir js/admin/bsc-products.js
