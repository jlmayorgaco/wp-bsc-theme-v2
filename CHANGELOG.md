# Changelog — Bubble Skin Care Theme v2

Todos los cambios notables a este proyecto están documentados aquí.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/).

---

## [Unreleased]

### Added
- BSC-029: Custom WP roles `bsc_operator` (edit_orders) y `bsc_employee` (edit_orders + edit_products)
- BSC-030: Admin menu custom "BSC" con subpáginas: Pedidos, Productos, Venta Presencial, Informes, Configuración
- BSC-031: Tabla de pedidos admin con filtros, paginación 25/página, select de estado inline, inputs de tracking
- BSC-032: Estatutos de pedido custom: `wc-preparing` (En preparación), `wc-shipped` (Enviado)
- BSC-033: Email de envío HTML con código de tracking y CTA "Rastrear mi pedido" (`emails/bsc-order-shipped.php`)
- BSC-034: Exportación CSV de pedidos con UTF-8 BOM y vista de impresión para packing
- BSC-035: Página de informes con KPIs (ventas totales, cantidad, ticket promedio) y top 5 productos
- BSC-036: Modelo de stock dual (`_stock_bodega`, `_stock_tienda`) con clase `BSC_Stock` y meta box en producto
- BSC-037: Flujo de venta presencial (showroom) — búsqueda de productos, carrito JS, registro de venta, descuento de `_stock_tienda`
- BSC-038: Caché de búsqueda con transients 15min por término; caché del lado del cliente (objeto `searchCache`, max 20 entradas)
- BSC-039: Transients para fallback del slider de productos (1h TTL); invalidación en `save_post_product`, `edited_term`, `created_term`
- BSC-041: Constante `BSC_THEME_VERSION` para versionado de assets sin filemtime I/O en producción
- BSC-042: Tamaños de imagen registrados: `bsc-card` (400×400), `bsc-hero` (1440×600), `bsc-thumb` (120×120); cards usan `wp_get_attachment_image()` para srcset automático
- BSC-043: Nonce CSRF en `page-register.php`; checklist de seguridad en `security checklist`
- BSC-044: README.md expandido con stack completo, archivos críticos, roles y stock dual; CHANGELOG actualizado con todas las entradas BSC-029–044

### Fixed
- BSC-020: Scroll automático al contenido en páginas de Mi Cuenta (evita que el usuario vea solo la navegación)
- BSC-021: Cards de pedidos en mobile con layout de bloque y labels `data-title` legibles
- BSC-022: Vista de detalle de pedido: eliminado `$mapped_status` hardcodeado y `$bubble_points = 356` fijo
- BSC-023: Tickets usados/vencidos en Bubble Points con `grayscale(1)` y `pointer-events:none`
- BSC-024: Formularios de dirección WooCommerce responsive en mobile (width 100%, font-size 16px)
- BSC-029: Permisos de admin: `bsc_operator` ahora tiene acceso a bsc-dashboard y bsc-showroom

### Performance
- BSC-038: Búsqueda AJAX: min 3 chars → 2, debounce 350ms → 400ms, límite 12 → 8 resultados
- BSC-039: Fallback del slider no ejecuta WP_Query en cada page load (transient 1h)
- BSC-040: `update_post_meta_cache` y `update_post_term_cache` habilitados en queries de slider y filtros
- BSC-041: Assets encolados con `BSC_THEME_VERSION` estático en producción (sin filemtime por request)
- BSC-042: Cards de producto con `srcset` automático via `wp_get_attachment_image()`

### Security
- BSC-043: CSRF nonce en formulario de registro (`page-register.php`)
- BSC-043: Todos los handlers AJAX verificados: nonce ✅, sanitización ✅, rate limiting ✅ (contact/newsletter/creators)

### Pendiente
- BSC-001: Reparar componente WhatsApp y helper centralizado
- BSC-002: Eliminar URLs localhost/bsc.local hardcodeadas
- BSC-003: Control de cantidad en card con desaparición al llegar a 0
- BSC-005: Fix add-to-cart en iPad/touch con pointerup
- BSC-009: Rediseño visual del menú mobile
- BSC-010: Unificar árbol de menú mobile con desktop
- BSC-014: Corregir stretching de galería en producto
- BSC-045–053: Backups, monitoreo, accesibilidad, i18n

---

## [1.1.0] — 2026-03-16

### Added
- Swiper v11 migrado a local (`vendor/swiper/`) — eliminado CDN externo
- Font Awesome 6.5.0 migrado a local (`vendor/fontawesome/`, `vendor/webfonts/`) — eliminado CDN externo
- Trust signals bar en checkout (pago seguro, datos protegidos, envío, WhatsApp)
- Fallback hero estático cuando no hay slides del CPT `home_slide`
- ABSPATH guard en los 8 handlers AJAX de `inc/ajax/`
- `js/newsletter.js` — extraído de `front-page.php` inline, encolado solo en home
- Bubble Creators landing page con formulario funcional (`page-bubble-creators.php`)
- Estilos `.bsc__coupon-notice` (success/error) para coupons.js
- Estilos `.search-result-info`, `.search-result-brand`, `.search-result-price`

### Fixed
- BSC-004: Badge del carrito sincronizado con `cart_count` real; edge case carrito vacío → badge a 0
- BSC-006: URLs de marcas en home usan `get_term_link()` en vez de URL plana
- BSC-008: Menú mobile: logos correctos, items auth-aware, eliminado texto "Wappy"
- `coupons.js`: reemplazado `alert()` con `showNotice()` inline (verde/rojo)
- `checkout.js`: eliminado AJAX submit custom → WooCommerce maneja el submit
- `cart.js`: eliminado doble trigger de `update_checkout` que causaba race conditions
- `components/products/card.php`: productos variables muestran "Ver opciones", corregido doble slash en URL de favorito
- `woocommerce/cart/cart.php`: eliminado `echo` de debug
- `checkout-view-thankyou.php`: escaping correcto con `esc_html()`, `wc_price()` en lugar de función `price()` custom
- `inc/setup/theme-setup.php`: transient guard `bsc_pages_checked` (24h) para evitar N queries por request

### Performance
- BSC-001: Scripts cargados condicionalmente (tabs.js solo home, filters.js solo shop, checkout.js solo checkout, coupons.js solo cart/checkout)
- `wp_localize_script` consolidado — eliminadas 3 llamadas duplicadas
- Hero slider: `posts_per_page` cambiado de `-1` a `10`
- `functions.php`: deshabilitados emojis WP, oEmbed, wlwmanifest; dequeue wp-block-library

### Security
- Nonces añadidos en handlers AJAX críticos
- ABSPATH guard en todos los handlers AJAX

---

## [1.0.0] — 2026-01-01

### Added
- Tema inicial BSC basado en _s (Underscores)
- Integración completa con WooCommerce
- Hero Swiper en home con CPT `home_slide`
- Mega menú desktop con categorías Skin Care
- Menú mobile sidebar
- Sistema de favoritos
- Bubble Points plugin interno (`plugins/bubble-points/`)
- Páginas custom: checkout, cart, mi-cuenta, bubble-points
- AJAX handlers: cart, checkout, coupons, filters, search, newsletter
- Búsqueda AJAX con debounce (título, SKU, marca)
- Filtros de productos con AJAX
- Responsive mobile/desktop

---

## Notas

- El formato de commit de este proyecto es: `<tipo>(<scope>): [BSC-XXX] <resumen>`
- Cada ticket tiene su archivo en `project ticket archive`
