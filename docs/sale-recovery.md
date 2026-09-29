# Respaldos y recuperación de ventas BSC

En **BSC → Recuperar ventas**, un administrador puede descargar la clave,
configurar `bsc.skin.2021@gmail.com` como destinatario y activar los respaldos.
La configuración se guarda en la base de datos de cada instalación; desplegar el
tema no activa por sí solo el correo en producción.

Guarda el archivo `.bsc-key` fuera del servidor y separado de los correos. La
clave no depende de las salts de WordPress. Al restaurar una copia anterior a la
configuración del módulo, sube también esta clave en el formulario de importación.
No publiques claves o archivos de ventas en Git, uploads ni enlaces públicos.

## Qué se respalda

Al confirmar una compra en WooCommerce, y después de cambios en pedidos que ya
están pagados, se captura el estado al finalizar la petición. Incluye importes,
moneda, impuestos, descuentos, líneas de productos/envío/cupones/cargos, variantes,
direcciones, datos mínimos del cliente, referencia de pago, fechas, notas,
devoluciones y metadatos operativos BSC permitidos. Incluye el historial de puntos
del pedido y los canjes vinculados a los cupones usados en esa venta. Excluye
contraseñas, tarjetas, tokens de pago y metadatos arbitrarios de otros plugins.

El archivo JSON se cifra y autentica con AES-256-GCM antes de guardarse en
`{prefix}bsc_sale_backups`. Action Scheduler (WP-Cron como alternativa) envía el
adjunto `.bsc-sale` mediante `wp_mail`, que usa el SMTP configurado en BSC → Emails.
Los fallos tienen cinco intentos; el panel permite reintentar y descargar la copia.
Un barrido cada hora recupera trabajos pendientes. Mantén un cron real que ejecute
las tareas de WordPress con frecuencia, por ejemplo cada minuto.

El panel muestra **Aceptado por SMTP**, no una confirmación de entrega al buzón.
Comprueba recepción, spam y retención de Gmail/Zoho. Las copias aceptadas se borran
de la cola local después de 30 días; los fallos se mantienen para revisión.

## Recuperar una venta faltante

1. Restaura el sitio desde su copia general si hace falta.
2. Descarga del correo **la versión más reciente** del pedido perdido.
3. Sube el archivo y, si hace falta, la clave original.
4. Revisa importe, fecha, cuenta, productos y puntos en la vista previa.
5. Activa el ajuste de inventario solo si la base restaurada todavía no contiene
   esos movimientos. Por defecto se conserva el inventario existente.
6. Confirma **Recuperar venta y agregar a la cuenta**.

La cuenta se busca por el correo registrado en el archivo, no por un ID antiguo.
Si falta, se crea con rol `customer`, contraseña aleatoria y sin email automático;
el cliente puede usar la recuperación de contraseña habitual. No se sobrescriben
las direcciones actuales de una cuenta existente: las direcciones históricas se
guardan en el pedido. El pedido recibe un ID nuevo y conserva el número/ID original
en sus metadatos de recuperación y nota administrativa.

La importación no ejecuta pagos, devoluciones bancarias ni notificaciones de
compra. Se bloquean los hooks de transición durante la importación. Los puntos
se recuperan por movimiento y los que sobreviven se vinculan al nuevo pedido;
el saldo de referencia del archivo nunca sobrescribe el saldo actual. Se usan
bloqueos y una transacción InnoDB para evitar importaciones parciales.

Los productos ausentes se conservan como líneas históricas sin enlazar a un ID
reutilizado. En ese caso no se permite descontar inventario automáticamente.
Los descuentos del pedido se conservan; no se recrean cupones utilizables ni se
reconstruyen automáticamente los contadores globales de uso de cupones.

## Alcance y límites

- El módulo agrega **ventas faltantes**. Si encuentra el UUID, la clave del pedido
  o la misma transacción/pasarela, evita duplicarlo y no reemplaza su estado. Si
  existe un pedido pendiente anterior al pago, o se importó una versión más vieja,
  el administrador debe conciliar ese pedido existente por separado.
- Usa el archivo más reciente y contrasta con la pasarela cualquier pago o
  devolución posterior. Los callbacks externos que usan el ID numérico original
  no se redirigen automáticamente al nuevo pedido recuperado.
- No recupera el historial completo de una cuenta desde una sola compra. Los
  puntos de otras compras, ajustes manuales y canjes ajenos a esa venta necesitan
  sus propios datos. Las cuentas recreadas pueden requerir recuperar más ventas
  para reconstruir su saldo completo.
- Las notas se conservan como notas internas con su fecha original indicada en el
  texto; no se notifican otra vez al cliente. Se incluyen hasta 100 notas por venta.
- El adjunto máximo es 5 MB. Un fallo de captura queda registrado en WooCommerce
  (`bsc-sale-backup`) y se muestra en el panel.
- No sustituye UpdraftPlus ni promete pérdida cero: una caída antes de capturar o
  enviar el archivo puede perder tanto la venta como su trabajo en cola local.

## Validación

`tests/sale-recovery-integration.php` solo funciona por CLI con
`BSC_LOCAL_INTEGRATION=1` y dominio `bsc.local`. Genera datos descartables, intercepta
correo y HTTP externos y limpia sus pedidos/productos/clientes al terminar.
Verifica cifrado, manipulación, reintentos, permisos, duplicados, relación con el
cliente, importes/fechas/envío, puntos e inventario y rollback ante fallo.
