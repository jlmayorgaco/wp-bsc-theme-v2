# BSC-052 — Arquitectura del Theme

## Flujo de request → respuesta

```
HTTP Request
  └─ WordPress bootstrap (wp-load.php)
       └─ functions.php cargado
            ├─ inc/setup/theme-setup.php      (image sizes, WP supports, default pages)
            ├─ inc/scripts/enqueue-scripts.php (assets condicionales por página)
            ├─ inc/woocommerce.php             (hooks WC, statuses custom, cart fragments)
            ├─ inc/ajax/*.php                  (registra handlers AJAX — wp_ajax_*)
            ├─ includes/class-bsc-roles.php    (roles bsc_operator, bsc_employee)
            ├─ includes/class-bsc-stock.php    (stock dual: bodega / tienda)
            └─ emails/bsc-emails.php           (email dispatcher — hooks en status_changed)
       └─ WordPress elige el template (is_front_page, is_shop, etc.)
            └─ get_header()  → components/header.php
            └─ [contenido de la página]
            └─ get_footer()  → components/footer.php → components/whatsapp.php

AJAX Request (admin-ajax.php)
  └─ check_ajax_referer(nonce)
  └─ sanitize inputs
  └─ operación (WC cart, order, product search, etc.)
  └─ wp_send_json_success/error()
```

---

## Mapa de archivos críticos

### Templates de página

| Archivo | Ruta | Responsabilidad |
|---------|------|----------------|
| Home | `front-page.php` | Hero Swiper, sliders de productos, tabs, newsletter |
| Shop archive | `woocommerce/archive-product.php` | Incluye `components/shop.php` |
| Categoría | `woocommerce/taxonomy-product_cat.php` | Grid de productos por categoría |
| Producto | `woocommerce/single-product.php` | Galería, descripción, add-to-cart |
| Carrito | `page-cart.php` | Carrito custom |
| Checkout | `page-checkout.php` | Checkout custom con validación JS |
| Mi Cuenta | `page-mi-cuenta.php` | Tabs de cuenta (WooCommerce endpoint) |
| Bubble Points | `page-bubble-points.php` | Sistema de puntos de fidelización |
| Login | `page-login.php` | Formulario de login (nativo WP) |
| Registro | `page-register.php` | Formulario de registro (nonce BSC-043) |

### Components

| Archivo | Uso |
|---------|-----|
| `components/header.php` | Header desktop + mobile (~600 líneas) |
| `components/footer.php` | Footer + bloque azul |
| `components/whatsapp.php` | Botón flotante WhatsApp |
| `components/products/card.php` | Tarjeta de producto (BSC_Products_Card) |
| `components/products/slider.php` | Slider de productos (BSC_Products_Sliders) |
| `components/swiper.php` | Hero slider Swiper v11 |
| `components/shop.php` | Grid de categorías + "3 niñas" |
| `components/checkout/checkout-summary.php` | Resumen de orden en checkout |

### Includes

| Archivo | Responsabilidad |
|---------|----------------|
| `inc/woocommerce.php` | Cart fragments, statuses custom, hooks WC |
| `inc/setup/theme-setup.php` | Theme support, image sizes, create default pages |
| `inc/scripts/enqueue-scripts.php` | Scripts condicionales (BSC_THEME_VERSION) |
| `inc/functions_bsc.php` | Helpers varios |
| `includes/class-bsc-roles.php` | Roles operativos WP |
| `includes/class-bsc-permissions.php` | Restricciones de admin por rol |
| `includes/class-bsc-stock.php` | Stock dual (bodega/tienda) |

### Admin

| Archivo | Responsabilidad |
|---------|----------------|
| `admin/bsc-admin-menu.php` | Menú BSC + includes de páginas admin |
| `admin/bsc-orders-page.php` | Dashboard de pedidos (tabla, tracking, CSV) |
| `admin/class-bsc-orders-table.php` | WP_List_Table para pedidos |
| `admin/bsc-reports-page.php` | KPIs de ventas con transient cache |
| `admin/bsc-showroom-page.php` | Venta presencial (Showroom) |
| `inc/admin/product-covers.php` | Meta boxes: imágenes extra + stock dual |

---

## AJAX Endpoints custom

| Acción | Archivo | Método | Nonce | Capability | Rate limit |
|--------|---------|--------|-------|-----------|-----------|
| `bsc_add_to_cart` | `inc/ajax/cart-actions.php` | POST | `bsc_ajax_action` | public | — |
| `update_cart_quantity` | `inc/ajax/cart-actions.php` | POST | `bsc_ajax_action` | public | — |
| `bsc_remove_cart_item` | `inc/ajax/cart-actions.php` | POST | `bsc_ajax_action` | public | — |
| `bsc_get_cart_quantities` | `inc/ajax/cart-actions.php` | POST | `bsc_ajax_action` | public | — |
| `bsc_reload_city_fields` | `inc/ajax/checkout-actions.php` | POST | `bsc_ajax_action` | public | — |
| `bsc_apply_coupon` | `inc/ajax/coupons-actions.php` | POST | `bsc_ajax_action` | public | — |
| `bsc_remove_coupon` | `inc/ajax/coupons-actions.php` | POST | `bsc_ajax_action` | public | — |
| `bsc_filter_products` | `inc/ajax/filters-actions.php` | GET | `bsc_ajax_action` | public | — |
| `bsc_search_products` | `inc/ajax/search-actions.php` | GET | `bsc_ajax_action` | public | cache 15min |
| `bsc_get_review_summary` | `inc/ajax/review-summary-actions.php` | POST | `bsc_ajax_action` | public | — |
| `bsc_newsletter_subscribe` | `inc/ajax/newsletter-actions.php` | POST | `bsc_ajax_action` | public | 60s/IP |
| `bsc_contact_form_submit` | `inc/ajax/contact-actions.php` | POST | `bsc_ajax_action` | public | 60s/IP |
| `bsc_creator_apply` | `inc/ajax/creator-actions.php` | POST | `bsc_ajax_action` | public | 300s/IP |
| `bsc_update_order_status` | `admin/bsc-orders-page.php` | POST | `bsc_orders_nonce` | `edit_orders` | — |
| `bsc_save_tracking` | `admin/bsc-orders-page.php` | POST | `bsc_orders_nonce` | `edit_orders` | — |
| `bsc_register_showroom_sale` | `admin/bsc-showroom-page.php` | POST | `bsc_showroom_nonce` | `edit_orders` | — |
| `bsc_showroom_search` | `admin/bsc-showroom-page.php` | GET | `bsc_showroom_nonce` | `edit_orders` | — |

---

## WooCommerce hooks custom

| Hook | Archivo | Descripción |
|------|---------|-------------|
| `woocommerce_order_status_processing` | `inc/woocommerce.php` | Deduce stock bodega al procesar pedido |
| `woocommerce_order_status_changed` | `emails/bsc-emails.php` | Dispara email BSC por cambio de estado |
| `wc_order_statuses` | `inc/woocommerce.php` | Registra `wc-preparing`, `wc-shipped` |
| `woocommerce_checkout_fields` | `inc/woocommerce.php` | Agrega campo Cédula al billing |
| `woocommerce_package_rates` | `inc/woocommerce.php` | Oculta envío gratis si < $300.000 después de descuento |
| `woocommerce_add_to_cart_fragments` | `inc/woocommerce.php` | Fragmento `a.cart-contents` para mini-cart |

---

## Sistema de Bubble Points

El sistema de puntos vive en `plugins/bubble-points/` (plugin bundled dentro del theme).

- **Acumulación:** se otorgan puntos al completar un pedido (hook en `woocommerce_order_status_completed`)
- **Saldo:** guardado en `user_meta` con clave `bubble_points_balance`
- **Canje:** los puntos se convierten en cupones canjeables
- **Vista usuario:** `page-bubble-points.php` → `plugins/bubble-points/views/`
- **Admin:** WP Admin → Bubble Points (submenú automático del plugin)

---

## Modelo de Stock Dual

Cada producto WooCommerce tiene dos campos de stock independientes del stock nativo WC:

| Meta key | Descripción | Editado en |
|----------|-------------|-----------|
| `_stock_bodega` | Stock disponible para pedidos web | Product admin → "BSC Stock Dual" |
| `_stock_tienda` | Stock disponible en tienda física | Product admin → "BSC Stock Dual" |
| `_envio_tipo` | `bodega` / `tienda` / `ambos` | Product admin → "BSC Stock Dual" |

- Pedido web procesado: `BSC_Stock::deduct_bodega()` descuenta `_stock_bodega`.
- Vista de empaque: el operador puede confirmar descuento por item desde `bodega` por defecto o `tienda`/showroom, usando `BSC_Stock::adjust()`.
- Venta presencial: `BSC_Stock::deduct_tienda()` descuenta `_stock_tienda`.
- El stock nativo de WooCommerce se mantiene por compatibilidad pero no es la fuente de verdad

---

## Roles y permisos

| Rol WP | Capabilities | Páginas admin permitidas |
|--------|-------------|-------------------------|
| `bsc_operator` | read, edit_orders | bsc-dashboard, bsc-orders, bsc-showroom |
| `bsc_employee` | read, edit_orders, edit_products | + bsc-products |
| `administrator` | Todas | Todas |

Implementados en `includes/class-bsc-roles.php` y `includes/class-bsc-permissions.php`.

---

## Assets y versionado

- `BSC_THEME_VERSION` en `functions.php` — versión estática para producción (actualizar en cada deploy)
- En `WP_DEBUG=true` usa `filemtime()` para cache busting automático en desarrollo
- Swiper v11: `vendor/swiper/` (local) — carga solo en front_page
- Font Awesome 6.5.0: `vendor/fontawesome/` + `vendor/webfonts/` (local) — global
