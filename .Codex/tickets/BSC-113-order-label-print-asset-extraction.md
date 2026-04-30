# BSC-113 - Order Label Print Asset Extraction

## Objective
Extraer el CSS y JS inline de la vista standalone de etiquetas de despacho a assets dedicados, manteniendo intacta la impresion exacta y la exportacion PDF.

## Context
- `admin/order-label-print.php` todavia inyectaba CSS via `<style>`, handlers `onclick`, un formulario oculto con `style="display:none"` y toda la logica JS inline.
- La pantalla ya era funcional, pero seguia fuera del pipeline de assets y sin smoke dedicado para el popup de etiquetas.
- El riesgo principal es mover assets sin alterar el layout ni el flujo `print` / `download PDF`.

## Files To Inspect
- `admin/order-label-print.php`
- `admin-order-label-print.css`
- `js/admin/bsc-order-label-print.js`
- `tests/e2e/smoke/admin-orders.spec.js`

## Exact Implementation Plan
1. Reemplazar el bloque `<style>` por un `<link rel="stylesheet">` versionado.
2. Mover los handlers `onclick` a `data-*` y un JS externo.
3. Mantener la regla de tamano dinamico en un `style` vacio que el JS llena en runtime.
4. Reemplazar el formulario oculto inline por una clase CSS.
5. Agregar smoke del popup de etiquetas desde `BSC > Pedidos`.

## Acceptance Criteria
- `admin/order-label-print.php` ya no contiene `<script>` inline ni `onclick`.
- El toolbar y el formulario oculto salen del CSS externo.
- `window.BSCLabelPrint` sigue disponible para compatibilidad de la vista.
- El popup de etiquetas abre y renderiza toolbar + etiqueta desde la smoke admin.

## Manual QA
1. Abrir `BSC > Pedidos` y seleccionar un pedido.
2. Abrir `Imprimir con datos (PDF)`.
3. Cambiar ancho y alto, validar que la etiqueta se redimensiona.
4. Probar `Restablecer medida`.
5. Probar `Descargar PDF exacto` y `Imprimir navegador`.

## Rollback Notes
- Revertir este commit restaura el CSS y JS inline de `order-label-print.php`.
- El smoke de etiquetas puede eliminarse junto con el asset externo si se requiere rollback completo.
