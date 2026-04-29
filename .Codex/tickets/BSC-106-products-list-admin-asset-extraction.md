# BSC-106 — Products List Admin Asset Extraction

## Objective
Extraer el CSS y JS inline del listado `BSC > Productos` a assets admin dedicados, manteniendo intactos el guardado inline de stock y el modal de historial.

## Context
- `admin/bsc-products-page.php` todavia inyecta comportamiento con `wp_add_inline_script()`.
- La tabla y el modal dependen de muchos `style="..."` embebidos.
- Ya existe smoke admin para esta pantalla, asi que el cambio puede quedar protegido.

## Files To Inspect
- `admin/bsc-products-page.php`
- `admin/bsc-products.css`
- `js/admin/bsc-products.js`
- `tests/e2e/smoke/admin-products.spec.js`

## Exact Implementation Plan
1. Crear CSS admin dedicado para filtros, tabla, estados, inputs y modal.
2. Reemplazar `wp_add_inline_script()` por un JS externo con config localizada.
3. Mover los estilos inline del render PHP a clases estables.
4. Mantener los AJAX handlers sin cambiar contratos.
5. Revalidar con smoke admin del listado y editor.

## Acceptance Criteria
- `admin/bsc-products-page.php` ya no usa `wp_add_inline_script()`.
- La mayor parte del layout de tabla/modal sale de `admin/bsc-products.css`.
- El guardado inline de stock sigue funcionando.
- El modal de historial sigue abriendo/cerrando y renderizando datos.
- `tests/e2e/smoke/admin-products.spec.js` pasa completo.

## Manual QA
1. Abrir `BSC > Productos`.
2. Editar stock bodega/tienda y verificar feedback visual.
3. Abrir historial de stock de un producto.
4. Cerrar modal con boton y overlay.
5. Navegar a la pagina de edicion desde el listado.

## Rollback Notes
- Revertir este commit restaura el script inline y los estilos embebidos del listado.
