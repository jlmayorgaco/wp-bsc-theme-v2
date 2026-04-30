# BSC-152 — Shared admin UI foundation

## Objective
Crear una base compartida de UI admin para reutilizar patrones de toolbar, acciones, badges, feedback y edición inline.

## Context
Las pantallas admin de BSC repiten estilos y patrones de interacción con implementaciones aisladas. Eso hace más lento mantenerlas y más difícil volver consistente el comportamiento.

## Files to inspect
- `admin/bsc-admin-ui.php`
- `admin/bsc-admin-ui.css`
- `admin/bsc-admin-menu.php`
- enqueues de pantallas admin que consumen la base

## Exact implementation plan
1. Crear helper único para enqueue del CSS compartido.
2. Crear stylesheet base con:
   - toolbar
   - grupos de acciones
   - notes
   - badges
   - inline editor
   - toast
3. Encolar la base en las pantallas que se van a refactorizar en este tramo.

## Acceptance criteria
- Existe un CSS admin compartido en el theme.
- Las pantallas refactorizadas pueden consumir la base sin duplicar reglas nuevas.
- No se altera lógica de negocio.

## Manual QA
1. Abrir `bsc-access`, `bsc-products`, `bsc-reports` y `bsc-showroom`.
2. Confirmar que cargan sin errores de assets.

## Rollback notes
Revertir el commit de `BSC-152`.
