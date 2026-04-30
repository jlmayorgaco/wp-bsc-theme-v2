# BSC-150 - Admin orders explicit row save and pending state

## Objective
Cambiar el control de estado en `wp-admin/admin.php?page=bsc-orders` para que:

1. solo permita `Recibido` y `Terminado`
2. no guarde automaticamente al cambiar el dropdown
3. tenga un boton `Guardar` por fila
4. marque visualmente los cambios pendientes
5. muestre confirmacion visual cuando el guardado termina

## Context
Hoy el dropdown guarda al instante y expone demasiados estados para el flujo operativo que el cliente quiere usar en esta pantalla.

## Files to inspect
- `admin/class-bsc-orders-table.php`
- `admin/bsc-orders-page.php`
- `js/bsc-admin-orders.js`
- `admin/bsc-admin-orders.css`

## Exact implementation plan
1. Reducir las opciones del dropdown a `wc-processing` y `wc-completed`.
2. Mapear estados intermedios existentes a una vista coarse de `Recibido` para que no aparezcan cambios falsos.
3. Reemplazar el autosave por un boton `Guardar` en cada fila.
4. Anadir un badge `Guardar cambios` y borde naranja mientras haya un cambio pendiente.
5. Mostrar un toast discreto `Elemento guardado` cuando el AJAX responda OK.
6. Mantener tracking y bulk actions fuera de este ticket.

## Acceptance criteria
- El dropdown de estado en cada fila solo muestra `Recibido` y `Terminado`.
- Cambiar el select no guarda automaticamente.
- Cada fila tiene boton `Guardar`.
- Cuando el valor difiere del original, el select muestra borde naranja y aparece badge de pendiente.
- Al guardar, desaparece el estado pendiente y se muestra confirmacion visual.

## Manual QA
1. Abrir `wp-admin/admin.php?page=bsc-orders`.
2. Cambiar una fila de `Recibido` a `Terminado`.
3. Confirmar que no se guarda hasta pulsar `Guardar`.
4. Confirmar borde naranja + badge mientras esta pendiente.
5. Pulsar `Guardar` y confirmar toast `Elemento guardado`.
6. Recargar pagina y confirmar persistencia del estado.

## Rollback notes
Revertir el commit de `BSC-150` para volver al autosave inline anterior.
