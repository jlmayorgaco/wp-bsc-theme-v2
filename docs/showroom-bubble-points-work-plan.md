# Plan de trabajo: Showcase y Bubble Points

## Alcance

Actualizar el registro de ventas presenciales de Showcase para elegir correctamente el origen del inventario, diferenciar ventas de productos disponibles de productos por encargo, guardar datos de despacho y reportar el origen y medio de pago. Mejorar además la confirmación y presentación de los ajustes manuales de Bubble Points en administración.

## Tickets

### BSC-SH-01 — Mostrar productos con miniatura y existencias por ubicación

**Prioridad:** Alta  
**Archivos principales:** `admin/bsc-showroom-page.php`, `js/admin/bsc-showroom.js`, `admin/bsc-showroom.css`

- Incluir la miniatura del producto en cada resultado de búsqueda, con una imagen alternativa cuando no tenga foto.
- Mostrar existencias de Bodega y Tienda para el producto o variante buscada.
- Mantener a la vista los productos sin existencias, con apariencia deshabilitada para una venta normal.
- Permitir seleccionar un producto sin existencias únicamente cuando se active explícitamente “Encargo”.

**Aceptación:** Se ven miniaturas y cantidades por ubicación; un producto sin stock no se puede agregar como venta normal, pero sí se puede elegir como encargo.

### BSC-SH-02 — Seleccionar el inventario que se descuenta

**Prioridad:** Alta  
**Archivos principales:** `admin/bsc-showroom-page.php`, `js/admin/bsc-showroom.js`, `includes/class-bsc-order-stock.php`, `includes/class-bsc-stock.php`

- Antes de agregar cada producto, elegir Bodega o Tienda como fuente del stock.
- Ofrecer únicamente ubicaciones con unidades disponibles para el producto o variante seleccionada.
- Validar de nuevo en el servidor que la cantidad solicitada existe en la ubicación elegida; no confiar en el estado del navegador.
- Descontar exclusivamente de la ubicación elegida, sin trasladar faltantes a la otra ubicación ni permitir stock negativo.
- Guardar la ubicación y cantidad descontada en la línea del pedido para que una devolución reponga solo el stock que realmente salió de allí.

**Aceptación:** La venta desde Bodega reduce Bodega; la venta desde Tienda reduce Tienda; una ubicación sin unidades no se puede seleccionar y una petición manipulada se rechaza.

### BSC-SH-03 — Registrar “Encargo” sin modificar inventario

**Prioridad:** Alta  
**Archivos principales:** `js/admin/bsc-showroom.js`, `includes/class-bsc-order-stock.php`, `admin/bsc-showroom-page.php`

- Añadir una casilla “Encargo” por producto antes de agregarlo a la venta.
- Al activarla, permitir agregar el producto y su cantidad al pedido usando el precio vigente del producto/variante.
- No descontar ni aumentar Bodega o Tienda por las unidades encargadas.
- Guardar la marca “Encargo” y la cantidad en la línea de pedido; dejar el pedido en estado de preparación (`processing`) cuando incluya encargos.
- No convertir faltantes automáticamente en encargo ni descontar una parte de la cantidad: el modo debe quedar elegido explícitamente.
- Asegurar que cancelar o reembolsar un encargo no invente unidades en stock.

**Aceptación:** Un encargo aparece claramente en el pedido con su cantidad y precio; registrar, cancelar o reembolsar el encargo no altera ninguno de los dos inventarios.

### BSC-SH-04 — Guardar origen de venta, pago y datos opcionales de despacho

**Prioridad:** Alta  
**Archivos principales:** `admin/bsc-showroom-page.php`, `js/admin/bsc-showroom.js`, `includes/class-bsc-order-stock.php`

- Añadir un selector obligatorio de origen: WhatsApp o Talleres.
- Mantener Efectivo, Transferencia y Tarjeta como métodos de pago.
- Añadir Dirección y Ciudad opcionales para ventas que se despacharán después.
- Guardar el origen, el medio de pago y los datos de destino en el pedido de WooCommerce, de forma visible en administración.
- Incluir los valores en el envío del formulario y validarlos también en servidor.

**Aceptación:** Cada pedido Showcase conserva el origen y el pago. Dirección y ciudad son opcionales y se guardan en el pedido cuando se ingresan.

### BSC-SH-05 — Incluir origen, pago e inventario en informes

**Prioridad:** Alta  
**Archivos principales:** `admin/bsc-reports-page.php`

- Mostrar origen (WhatsApp/Talleres), medio de pago y tipo de venta (Bodega/Tienda/Encargo) en el informe de ventas.
- Añadir un desglose por origen y medio de pago al informe para el rango de fechas seleccionado.
- Incluir origen y medio de pago en el CSV exportado.
- Invalidar o versionar la caché del informe para que las nuevas dimensiones se reflejen sin mostrar datos antiguos.
- Mostrar “—” o “No registrado” para pedidos históricos sin esos metadatos.

**Aceptación:** El informe en pantalla y el CSV permiten identificar origen y pago de cada venta Showcase; los totales por origen y medio de pago coinciden con los pedidos filtrados.

### BSC-BP-01 — Confirmar ajustes manuales de Bubble Points

**Prioridad:** Media  
**Archivos principales:** `plugins/bubble-points/admin/user-history.php`, `plugins/bubble-points/admin/admin-menu.php`, nuevo script del área administrativa

- Antes de sumar o reducir puntos, mostrar una confirmación accesible con cliente, operación, cantidad, saldo actual y saldo resultante.
- Permitir confirmar o cancelar; solo confirmar envía el formulario.
- Evitar doble envío mientras la operación está en proceso.
- Conservar las comprobaciones de permisos, nonce y registro en el ledger existentes.

**Aceptación:** Cancelar no cambia el saldo; confirmar ejecuta una única operación y la deja en el historial.

### BSC-BP-02 — Ordenar y modernizar la interfaz administrativa de Bubble Points

**Prioridad:** Media  
**Archivos principales:** `plugins/bubble-points/admin/admin-menu.php`, `plugins/bubble-points/admin/user-history.php`, `plugins/bubble-points/admin/class-bsc-bp-list-table.php`, `plugins/bubble-points/admin/bsc-bp-admin.css`

- Dar jerarquía consistente a la lista de clientes, el saldo, el formulario de ajuste y el historial.
- Alinear formularios, campos y botones; distinguir visualmente añadir y reducir puntos.
- Mejorar legibilidad, estados vacíos, etiquetas y adaptación a pantallas estrechas.
- Mantener la navegación y las columnas existentes del historial.

**Aceptación:** La lista y el detalle usan una estructura visual coherente con el panel BSC; los controles tienen etiquetas claras, foco visible y no desbordan en viewport estrecho.

## Orden sugerido

1. BSC-SH-01 y BSC-SH-02.
2. BSC-SH-03, reutilizando la selección de producto y variante.
3. BSC-SH-04.
4. BSC-SH-05, después de definir y guardar la metadata.
5. BSC-BP-01 y BSC-BP-02.

