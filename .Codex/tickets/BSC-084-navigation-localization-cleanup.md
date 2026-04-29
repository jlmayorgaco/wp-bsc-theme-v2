# BSC-084 — Limpieza de navegación y localización frontend

## Objetivo
Unificar la navegación de carrito hacia `checkout`, corregir textos visibles que seguían en inglés y reemplazar rutas internas hardcodeadas por URLs dinámicas de WordPress/WooCommerce.

## Contexto
- El modo `coming soon` se mantiene intencionalmente activo para invitados.
- No se debe tocar `product_cat` ni la compatibilidad con CSV externos.
- La corrección se centra en navegación global, cuenta, checkout y copy frontend visible.

## Archivos a revisar
- `components/header.php`
- `components/footer.php`
- `components/my-account/my-account-header.php`
- `components/swiper.php`
- `front-page.php`
- `page-login.php`
- `components/checkout/checkout-form.php`
- `woocommerce/coming-soon.php`
- `woocommerce/checkout/form-login.php`
- `woocommerce/checkout/form-billing.php`
- `woocommerce/checkout/payment.php`
- `woocommerce/checkout/thankyou.php`
- `woocommerce/myaccount/dashboard.php`
- `inc/setup/theme-setup.php`
- `inc/woocommerce.php`

## Plan de implementación
1. Cambiar los accesos de carrito visibles para que lleven a `wc_get_checkout_url()`.
2. Sustituir rutas internas hardcodeadas por `home_url()`, `wc_get_page_permalink()` y `wc_get_account_endpoint_url()`.
3. Corregir labels, mensajes y CTAs visibles en inglés dentro del theme y los overrides Woo visibles.
4. Corregir el slug erróneo de marca en home.
5. Eliminar UI muerta del checkout ligada a “otra dirección” que ya estaba oculta.

## Criterios de aceptación
- Header, footer y cart-link reutilizable llevan al checkout.
- No quedan textos visibles clave en inglés en login, coming soon, checkout thankyou y dashboard de mi cuenta.
- Los links de cuenta y login/registro usan URLs dinámicas.
- El bloque de marcas en home apunta al slug correcto para `Pyunkang Yul`.
- Checkout ya no renderiza el toggle oculto de “otra dirección”.

## QA manual
1. Revisar header y footer en desktop/mobile y confirmar que el icono/botón de carrito abre checkout.
2. Abrir `/login/` y validar labels, errores y botones en español.
3. Abrir `/checkout/`, `/checkout/order-received/...` y `/mi-cuenta/` para verificar textos visibles en español.
4. Revisar `/` y confirmar que `Pyunkang Yul` lleve a la marca correcta.
5. Confirmar que checkout siga calculando envío usando la dirección de facturación.

## Riesgos
- Si existe lógica externa que dependa de slugs exactos de páginas custom, habrá que revisar redirecciones fuera del theme.
- La limpieza del bloque oculto de shipping asume que el flujo oficial sigue siendo enviar a la misma dirección de facturación.

## Rollback
- Revertir los archivos listados en este ticket.