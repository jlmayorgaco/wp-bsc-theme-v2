# Changelog — Bubble Skin Care Theme v2

Todos los cambios notables a este proyecto están documentados aquí.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/).

---

## [Unreleased]

### Pendiente
- BSC-001: Reparar componente WhatsApp y helper centralizado
- BSC-002: Eliminar URLs localhost/bsc.local hardcodeadas
- BSC-003: Control de cantidad en card con desaparición al llegar a 0
- BSC-005: Fix add-to-cart en iPad/touch con pointerup
- BSC-009: Rediseño visual del menú mobile
- BSC-010: Unificar árbol de menú mobile con desktop
- BSC-014: Corregir stretching de galería en producto
- BSC-016: Shipping dinámico Bogotá vs resto en checkout
- BSC-029–037: Módulo admin operativo (roles, pedidos, tracking, stock dual)
- BSC-038–053: Performance, seguridad, backups, accesibilidad

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
