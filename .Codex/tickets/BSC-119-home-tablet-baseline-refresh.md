# BSC-119 - Home Tablet Baseline Refresh

## Objective
Actualizar el snapshot `home-tablet` para reflejar el estado visual aprobado actual del home en `bsc.local`.

## Context
- La suite `public-pages` quedo verde salvo `home` en tablet.
- El diff es estable entre retries y afecta solo el hero superior, no la estructura del layout.
- Mobile y desktop ya coinciden; el desajuste es un baseline tablet desactualizado.

## Files To Inspect
- `tests/e2e/visual/public-pages.spec.js`
- `tests/e2e/visual/public-pages.spec.js-snapshots/home-tablet-win32.png`

## Exact Implementation Plan
1. Regenerar solo el snapshot `home` en el proyecto `tablet`.
2. Revalidar la suite publica completa.
3. Documentar el refresh como sincronizacion de baseline, no como cambio funcional.

## Acceptance Criteria
- `home-tablet-win32.png` se actualiza al estado actual aprobado.
- `public-pages.spec.js` vuelve a quedar verde completo.

## Manual QA
1. Abrir home en viewport tablet.
2. Confirmar que el hero mostrado coincide con el snapshot nuevo.
3. Revisar que no haya drift en el resto de secciones.

## Rollback Notes
- Revertir este commit restaura el baseline anterior.
