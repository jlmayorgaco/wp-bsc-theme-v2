# BSC-083 - Etiquetas: PDF exacto con tamano real

## Objetivo
Evitar que `Imprimir con datos (PDF)` dependa del print dialog del navegador para respetar el tamano de hoja de la etiqueta.

## Contexto
- La vista actual de etiquetas usa HTML + `window.print()`.
- El template define `@page` y tamano en `mm`.
- En navegadores como Edge/Chrome, el destino `Microsoft Print to PDF` puede ignorar el tamano custom y forzar `Letter/A4`.
- Eso genera mucho espacio en blanco aunque la etiqueta visual este bien.

## Archivos a inspeccionar
- `admin/class-bsc-order-labels.php`
- `admin/order-label-print.php`

## Plan de implementacion
1. Mantener la vista actual como preview editable.
2. Agregar un endpoint admin que genere un PDF real desde servidor.
3. Hacer que el boton principal de la vista abra `PDF exacto`.
4. Mantener el print dialog del navegador como fallback secundario.

## Acceptance Criteria
- Existe un boton `Descargar PDF exacto`.
- El PDF generado usa el ancho/alto en `mm` definidos en la vista.
- El archivo PDF ya no sale con `Letter` como pagina interna.
- El boton de impresion del navegador sigue disponible, pero ya no es la salida recomendada.

## Manual QA
1. Abrir `BSC > Pedidos`.
2. Seleccionar pedidos y entrar a `Imprimir con datos (PDF)`.
3. Cambiar ancho y alto.
4. Hacer clic en `Descargar PDF exacto`.
5. Confirmar que el PDF abre con tamano de pagina real, sin canvas en `Letter`.
6. Probar tambien `Imprimir navegador` y confirmar que queda como fallback.

## Rollback
- Remover el endpoint `admin_post_bsc_order_labels_pdf`.
- Volver el boton principal a `window.print()`.
- Mantener la vista HTML existente.
