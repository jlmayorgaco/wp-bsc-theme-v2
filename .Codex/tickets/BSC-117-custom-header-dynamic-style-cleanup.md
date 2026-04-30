# BSC-117 - Custom Header Dynamic Style Cleanup

## Objective
Eliminar el `<style>` emitido desde `inc/custom-header.php` y mover el CSS dinamico del custom header al pipeline del stylesheet principal via `wp_add_inline_style()`.

## Context
- `inc/custom-header.php` seguia imprimiendo CSS directamente en `wp_head`.
- El cambio no altera layout ni markup; solo cambia el mecanismo de inyeccion del CSS dinamico para texto del header.
- El stylesheet principal `bsc-2-0-style` ya esta encolado en frontend, por lo que es el punto correcto para adjuntar estas reglas.

## Files To Inspect
- `inc/custom-header.php`
- `inc/scripts/enqueue-scripts.php`

## Exact Implementation Plan
1. Retirar `wp-head-callback` del soporte de custom header.
2. Crear una funcion que construya el mismo CSS dinamico.
3. Adjuntar ese CSS con `wp_add_inline_style('bsc-2-0-style', ...)` en `wp_enqueue_scripts`.
4. Mantener intacta la logica de color custom y ocultado del texto.

## Acceptance Criteria
- `custom-header.php` ya no contiene un bloque `<style>` renderizado manualmente.
- El CSS dinamico del header se adjunta al stylesheet principal.
- La logica actual de color custom / texto oculto se conserva.

## Manual QA
1. Activar un color custom de titulo en el customizer.
2. Verificar que `site-title` y `site-description` reflejan el color.
3. Ocultar el texto del header y validar que sigue oculto visualmente.

## Rollback Notes
- Revertir este commit restaura el callback HTML original en `wp_head`.
