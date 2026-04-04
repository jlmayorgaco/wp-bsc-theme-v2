# BSC — QA Smoke Test Checklist

Ejecutar antes de cada deploy a producción. Marcar ✅ o ❌ con fecha y notas.

**Fecha:** ___________  
**Branch/Commit:** ___________  
**Ejecutado por:** ___________

---

## 1. Navegación general

- [ ] La home carga sin errores JS en consola
- [ ] El header muestra logo, menú y carrito correctamente
- [ ] El mega menú desktop se abre al hover en Skin Care
- [ ] Los links del mega menú llevan a las categorías correctas (sin 404)
- [ ] El footer carga con todos los links visibles
- [ ] El botón de WhatsApp es visible y abre la app con número correcto
- [ ] La búsqueda devuelve resultados al escribir un producto existente
- [ ] Las páginas `/faq`, `/shipping-returns`, `/contact-us` cargan sin error

---

## 2. Menú mobile

- [ ] El botón hamburguesa abre el menú sidebar en mobile
- [ ] El menú cubre el contenido (z-index correcto, no queda detrás)
- [ ] Se puede navegar a categorías desde el menú mobile
- [ ] El menú se cierra al hacer tap fuera o en el botón de cierre
- [ ] El ícono de perfil mobile lleva a login (guest) o pedidos (logueado)

---

## 3. Shop y categorías

- [ ] `/shop` carga con la grilla de productos
- [ ] Los filtros de productos funcionan (por categoría, precio, marca)
- [ ] Una categoría (ej. `/product-category/skin-care/`) carga productos
- [ ] La paginación funciona en shop y categorías

---

## 4. Tarjeta de producto (card)

- [ ] El botón "¡Lo quiero!" agrega el producto al carrito
- [ ] El badge del carrito en header y footer se actualiza correctamente
- [ ] Incrementar qty con `+` funciona y actualiza el badge
- [ ] Decrementar qty con `—` hasta 0 elimina el producto y restaura el botón
- [ ] En iPad/touch: tap en el botón agrega el producto una sola vez (sin duplicado)

---

## 5. Detalle de producto

- [ ] La galería de imágenes carga sin stretching
- [ ] Las thumbnails se pueden clickear y cambian la imagen principal
- [ ] El botón "Añadir al carrito" (o "Ver opciones" para variables) funciona
- [ ] Los productos variables muestran el selector de variaciones

---

## 6. Carrito

- [ ] Ir a `/cart` muestra los productos correctamente
- [ ] Modificar cantidad desde el carrito actualiza el total
- [ ] Eliminar un producto del carrito funciona
- [ ] El mini-cart en el header muestra los productos correctos
- [ ] Carrito vacío muestra el estado vacío correcto

---

## 7. Checkout

- [ ] Ir a `/checkout` muestra el formulario correctamente en mobile y desktop
- [ ] Al cambiar la ciudad, el precio de envío se actualiza automáticamente
- [ ] Aplicar un cupón válido muestra descuento y mensaje de éxito
- [ ] Aplicar un cupón inválido muestra mensaje de error visible
- [ ] Completar el checkout con datos correctos procesa el pedido
- [ ] La página de thank-you muestra el resumen del pedido

---

## 8. Mi Cuenta

- [ ] Login en `/mi-cuenta` o `/login` funciona correctamente
- [ ] El dashboard de Mi Cuenta carga sin errores
- [ ] La lista de pedidos carga y muestra las órdenes
- [ ] Ver detalle de un pedido funciona
- [ ] Editar dirección funciona en mobile (sin overflow, inputs accesibles)
- [ ] Editar datos de cuenta funciona
- [ ] Logout funciona y redirige correctamente

---

## 9. Bubble Points

- [ ] La página `/bubble-points` carga sin errores
- [ ] Los puntos del usuario se muestran correctamente (usuario logueado)
- [ ] Los cupones/tickets inactivos se ven en grayscale
- [ ] El listado es responsive en mobile

---

## 10. Performance básica

- [ ] La home carga en menos de 5 segundos en conexión 4G
- [ ] No hay errores 404 en la consola de red (imágenes, scripts, estilos)
- [ ] No hay errores JS en la consola del navegador en ninguna página crítica
- [ ] Las imágenes del hero tienen `fetchpriority="high"` (visible en DevTools → Elements)

---

## 11. SEO básico

- [ ] La home tiene `<title>` correcto
- [ ] Las páginas de producto tienen meta description
- [ ] No hay links internos con `bsc.local` o `localhost` (buscar en el HTML fuente)

---

## 12. Admin (si aplica)

- [ ] El admin de WordPress carga sin errores
- [ ] WooCommerce → Pedidos muestra los pedidos
- [ ] Se puede cambiar el estado de un pedido manualmente
- [ ] Los productos se pueden editar (precio, stock, imagen)

---

## Resultado final

| Sección | Estado | Notas |
|---------|--------|-------|
| Navegación | | |
| Menú mobile | | |
| Shop | | |
| Cards | | |
| Producto | | |
| Carrito | | |
| Checkout | | |
| Mi Cuenta | | |
| Bubble Points | | |
| Performance | | |
| SEO básico | | |
| Admin | | |

**Decisión de deploy:** ✅ Aprobar / ❌ Bloquear  
**Notas:** ___________________________________________
