# Temas con el cliente — 20 de noviembre de 2026

Registro de solicitudes e issues conversados hoy. Agregar nuevos puntos al final.

## 1. Tarifas de envío por zona

- **Tipo:** Solicitud de cambio en producción
- **Estado** Publicado y confirmado en producción mediante el [PR #70](https://github.com/jlmayorgaco/wp-bsc-theme-v2/pull/70).
- **Solicitud del cliente:** Configurar tres zonas de envío para Colombia:
  1. **Bogotá y Cundinamarca**
  2. **Resto del país, excepto Bogotá, Cundinamarca y San Andrés y Providencia**
  3. **San Andrés y Providencia**
- La tarifa base de **San Andrés y Providencia** es **$38.000 COP por envío**. Se aplican las reglas vigentes de envío gratis.
- Bogotá y Cundinamarca y el resto del país conservan sus tarifas configuradas. Los tres valores se pueden editar en los ajustes de BSC Admin.

## 2. Filtros e indicadores de inventario en BS Admin

- **Tipo:** Mejora de administración de productos
- **Estado** Implementado en el entorno local.
- **Ubicación:** BS Admin → Productos
- **Filtro de estado solicitado:** Agregar la opción **«Con stock > 0 y publicado»** para listar únicamente productos publicados con inventario mayor que cero.
- **Indicadores solicitados:** En el encabezado, junto a **Resultados visibles**, **Publicados** y **Borradores**, agregar indicadores para **Con stock** y **Sin stock**.
- Los indicadores cuentan productos publicados y en borrador. Se considera **Con stock** si hay unidades en Bodega o Tienda y **Sin stock** si no hay unidades registradas en ninguna de las dos.

## 3. Columna de descuentos en BS Admin

- **Tipo:** Mejora de administración de productos
- **Estado** Implementado en el entorno local.
- **Ubicación:** BS Admin → Productos
- **Solicitud:** Agregar una columna para identificar claramente qué productos tienen descuento y cuáles no, siguiendo como referencia la columna **Location Code**.
- La columna muestra el porcentaje y el precio de oferta cuando hay un precio único, un indicador para las ofertas en variantes y **Sin descuento** cuando no hay oferta.

## 4. Sistema de abonos y saldo pendiente en Showcase

- **Tipo:** Nueva funcionalidad de pagos y gestión de órdenes
- **Estado** Implementado en el entorno local.
- **Solicitud:** En Showcase, agregar **«Abono»** como opción de método de pago, mediante un radio button.
- **Alcance por producto:** El abono se configura para cada producto de la orden y solo puede aplicarse a productos de tipo **Encargo**.
- **Registro del abono inicial:** Para cada producto Encargo, permitir ingresar el valor del abono en pesos colombianos y seleccionar el medio de pago: efectivo, tarjeta o transferencia. Mostrar/calcular cuánto queda pendiente.
- **Persistencia:** Guardar el valor del abono asociado al producto Encargo y a su orden.
- **Estado de la venta:** Si queda saldo pendiente, la venta no debe quedar cerrada. Debe figurar como **«ABONADA PENDIENTE»**.
- **Pago final:** Cuando se realice el envío, un operario debe poder buscar la orden y registrar el pago final. El registro debe guardar cuándo se hizo el pago y permitir añadir un comentario de texto.
- **Cierre:** Al registrar el pago final y saldar el pendiente, la orden queda cerrada.
- En **BSC Admin → Pedidos → Abonadas pendientes**, el operario con acceso a Pedidos puede abrir la orden y registrar el saldo completo en efectivo, tarjeta o transferencia después de confirmar el despacho. El registro guarda el importe, la fecha, el operario y el comentario opcional.

