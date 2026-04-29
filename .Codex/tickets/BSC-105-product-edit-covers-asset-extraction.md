# BSC-105 — Product Edit / Covers Admin Asset Extraction

## Objective
Extraer CSS y JS inline del editor simplificado de producto BSC y de los metaboxes de covers/stock a assets admin dedicados, sin cambiar el flujo de guardado ni el DOM critico del media uploader.

## Context
- `admin/bsc-product-edit-page.php` mezclaba render, estilos inline y logica JS inline.
- `inc/admin/product-covers.php` tambien renderizaba metaboxes nativos con estilos inline.
- El proyecto ya tiene limpieza progresiva de assets admin; este editor era el siguiente mayor foco.

## Files To Inspect
- `admin/bsc-product-edit-page.php`
- `inc/admin/product-covers.php`
- `js/admin/product-covers.js`
- `admin/bsc-product-edit.css`
- `js/admin/bsc-product-edit.js`
- `tests/e2e/smoke/admin-products.spec.js`

## Exact Implementation Plan
1. Crear un CSS admin dedicado para layout, fields, previews y category tree del editor.
2. Mover el JS inline del editor a `js/admin/bsc-product-edit.js`.
3. Pasar la data del category tree por `wp_localize_script` en vez de HTML/JS inline.
4. Reemplazar los estilos inline del render PHP por clases estables.
5. Ajustar `product-covers.js` para que inyecte markup con clases, no con `style="..."`.
6. Agregar smoke admin para listado y editor de productos.

## Acceptance Criteria
- `admin/bsc-product-edit-page.php` ya no contiene `<script>` inline.
- Los bloques principales del editor ya no dependen de `style="..."` fijos en el template.
- `inc/admin/product-covers.php` usa clases para los metaboxes nativos.
- El category tree y el media uploader siguen funcionando.
- Existe smoke admin para `bsc-products` y `bsc-product-edit`.

## Manual QA
1. Abrir `BSC > Productos` y luego editar un producto.
2. Cambiar la imagen principal y removerla.
3. Editar galeria y confirmar el preview.
4. Cambiar root tabs de categorias y buscar subcategorias.
5. Guardar y verificar redirect correcto a `bsc-products`.
6. En editor nativo de Woo, validar metaboxes de covers y stock dual.

## Rollback Notes
- Revertir este commit restaura el render inline anterior y elimina el smoke nuevo.
