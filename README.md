# Bubble Skin Care (BSC) — WordPress Theme v2

Custom WordPress + WooCommerce theme for [Bubble Skin Care](https://bubbleskincare.co). Ecommerce de skincare K-beauty enfocado en alto tráfico, mobile-first y operación interna simplificada.

---

## Stack

| Tecnología | Versión | Notas |
|-----------|---------|-------|
| WordPress | >= 6.0 | |
| WooCommerce | >= 7.0 | |
| PHP | >= 8.0 | |
| jQuery | WP nativo | |
| Swiper | v11 local | `vendor/swiper/` — solo home |
| Font Awesome | 6.5.0 local | `vendor/fontawesome/` + `vendor/webfonts/` |
| Bubble Points | plugin interno | `plugins/bubble-points/` |

---

## Requisitos

- PHP >= 8.0
- WordPress >= 6.0
- WooCommerce >= 7.0
- Plugin activo: WooCommerce

---

## Instalación local

```bash
# 1. Clonar el repo dentro de wp-content/themes/
git clone <repo-url> wp-bsc-theme-v2

# 2. Activar el theme en WP Admin → Apariencia → Temas

# 3. Activar WooCommerce si no está activo

# 4. Flush de permalinks: WP Admin → Ajustes → Permalinks → Guardar
#    (necesario para páginas custom: /mi-cuenta, /checkout, /bubble-points, etc.)

# 5. Assets: no requiere build step — CSS y JS están compilados en style.css y js/
```

---

## Convención de ramas

```
fix/BSC-XXX-descripcion-corta      → corrección de bug
feat/BSC-XXX-descripcion-corta     → feature nueva
refactor/BSC-XXX-descripcion-corta → refactor sin cambio de comportamiento
style/BSC-XXX-descripcion-corta    → cambios solo de CSS/HTML visual
perf/BSC-XXX-descripcion-corta     → mejora de performance
docs/BSC-XXX-descripcion-corta     → solo documentación
chore/BSC-XXX-descripcion-corta    → mantenimiento, configuración
```

### Formato de commit

```
<tipo>(<scope>): [<TICKET-ID>] <resumen corto en imperativo>

Ejemplos:
fix(cart): [BSC-003] sync quantity controls when product reaches zero
feat(admin-orders): [BSC-031] add operator order management table
style(menu-mobile): [BSC-009] redesign mobile sidebar with BSC brand
```

---

## Archivos críticos

### Templates de páginas

| Archivo | Responsabilidad |
|---------|----------------|
| `front-page.php` | Home: hero slider, sliders de productos, tabs de tipos de piel, newsletter |
| `components/header.php` | Header desktop + mobile (~630 líneas), menú mega, menú mobile sidebar |
| `components/footer.php` | Footer negro + bloque azul + WhatsApp |
| `components/swiper.php` | Hero slider con Swiper v11, CPT `home_slide` |
| `components/shop.php` | Vista principal del shop |
| `components/product-category.php` | Página de categoría de productos |
| `components/products/card.php` | Tarjeta de producto: add-to-cart, qty control, favoritos |
| `components/products/slider.php` | Slider de productos (home, relacionados) |
| `components/whatsapp.php` | Botón flotante de WhatsApp |
| `page-checkout.php` | Checkout custom |
| `page-cart.php` | Carrito custom |
| `page-mi-cuenta.php` | Mi Cuenta custom |
| `page-bubble-points.php` | Bubble Points landing |
| `page-bubble-creators.php` | Bubble Creators landing |
| `page-contact-us.php` | Página de contacto |
| `page-faq.php` | Preguntas frecuentes |
| `page-shipping-returns.php` | Envíos y devoluciones |

### Includes (inc/)

| Archivo | Responsabilidad |
|---------|----------------|
| `inc/woocommerce.php` | Cart fragments, soporte WC, hooks custom (~304 líneas) |
| `inc/setup/theme-setup.php` | Setup del theme, image sizes, soporte de features |
| `inc/scripts/enqueue-scripts.php` | Enqueue condicional de scripts y estilos |
| `inc/functions_bsc.php` | Funciones helper BSC |

### AJAX handlers (inc/ajax/)

| Archivo | Endpoint(s) | Capability | Nonce |
|---------|------------|-----------|-------|
| `cart-actions.php` | `bsc_add_to_cart`, `bsc_update_cart_quantity`, `bsc_remove_cart_item` | public | `bsc_ajax_nonce` |
| `checkout-actions.php` | acciones del checkout | public | WC nonce |
| `coupons-actions.php` | `bsc_apply_coupon`, `bsc_remove_coupon` | public | `bsc_ajax_nonce` |
| `filters-actions.php` | `bsc_filter_products` | public | `bsc_ajax_nonce` |
| `search-actions.php` | `bsc_search_products` | public | `bsc_ajax_nonce` |
| `newsletter-actions.php` | `bsc_newsletter_subscribe` | public | `bsc_ajax_nonce` |
| `creator-actions.php` | `bsc_submit_creator` | public | `bsc_ajax_nonce` |
| `review-summary-actions.php` | `bsc_get_review_summary` | public | `bsc_ajax_nonce` |

### JavaScript (js/)

| Archivo | Carga en | Responsabilidad |
|---------|---------|----------------|
| `cart.js` | Global | Add-to-cart, qty controls, badge sync, fragments |
| `checkout.js` | Solo checkout | Validación del form, shipping |
| `coupons.js` | Cart + checkout | Aplicar/remover cupones, showNotice() |
| `filters.js` | Shop + archive | Filtros de productos por AJAX |
| `search.js` | Global | Búsqueda AJAX con debounce |
| `mobile-menu.js` | Global | Apertura/cierre del menú mobile |
| `navigation.js` | Global | Navegación desktop |
| `swiper-init.js` | Solo home | Inicialización del hero Swiper |
| `tabs.js` | Solo home | Tabs de tipos de piel |
| `newsletter.js` | Solo home | Form de newsletter |
| `category-filter.js` | Shop | Filtro de categorías por tab |

### WooCommerce overrides (woocommerce/)

| Directorio | Templates sobrescritos |
|-----------|----------------------|
| `woocommerce/cart/` | cart.php, mini-cart.php, cart-totals.php, shipping-calculator.php, + otros |
| `woocommerce/checkout/` | form-checkout.php, form-billing.php, form-shipping.php, payment.php, thankyou.php, + otros |
| `woocommerce/myaccount/` | my-account.php, orders.php, view-order.php, navigation.php, form-edit-account.php, form-edit-address.php, + otros |
| `woocommerce/single-product.php` | Template de producto individual |
| `woocommerce/archive-product.php` | Template de archivo/shop |

### Plugin interno

| Ruta | Responsabilidad |
|------|----------------|
| `plugins/bubble-points/` | Sistema de puntos de fidelización. Clases en `classes/`, hooks en `hooks/`, vistas en `views/` |

---

## Flujo de datos (request → respuesta)

```
Request HTTP
  → WordPress carga functions.php
    → require_once inc/setup/theme-setup.php   (image sizes, WP supports)
    → require_once inc/scripts/enqueue-scripts.php  (scripts condicionales)
    → require_once inc/woocommerce.php          (fragments, hooks WC)
    → require_once inc/ajax/*.php               (registra handlers AJAX)
  → WordPress elige el template
    → page-checkout.php / page-cart.php / etc.
      → get_template_part('components/header')
      → [contenido de la página]
      → get_template_part('components/footer')
        → get_template_part('components/whatsapp')

AJAX Request
  → wp-admin/admin-ajax.php
    → check_ajax_referer()
    → sanitize inputs
    → operación WooCommerce
    → wp_send_json_success/error
```

---

## Custom Post Types

| CPT | Slug | Uso |
|-----|------|-----|
| Hero Slide | `home_slide` | Slides del hero de la home |

---

## Configuración de staging

1. Crear una copia local usando LocalWP o XAMPP.
2. Exportar la DB de producción con WP Migratie o `wp db export`.
3. Importar en local con `wp db import dump.sql`.
4. Actualizar URLs: `wp search-replace 'https://bubbleskincare.co' 'https://bsc.local'`.
5. Activar `define('WP_DEBUG', true)` en `wp-config.php` de staging.

---

## Documentación adicional

| Documento | Descripción |
|-----------|-------------|
| [`docs/deploy.md`](docs/deploy.md) | Runbook de deploy a producción y rollback |
| [`docs/runbook-restore.md`](docs/runbook-restore.md) | Restauración desde backup (DB + archivos) |
| [`docs/architecture.md`](docs/architecture.md) | Arquitectura del theme, endpoints AJAX, roles |
| [`docs/monitoring-setup.md`](docs/monitoring-setup.md) | Configuración de UptimeRobot y scripts de health check |
| [`docs/cache-config.md`](docs/cache-config.md) | Browser cache, gzip, page cache y transients BSC |
| [`docs/media-optimization.md`](docs/media-optimization.md) | Guía de optimización de imágenes y WebP |
| [`.claude/security-checklist.md`](.claude/security-checklist.md) | Auditoría de seguridad de handlers AJAX |
| [`.claude/qa-checklist.md`](.claude/qa-checklist.md) | Checklist de smoke tests manual pre-deploy |
| [`.claude/tickets/`](.claude/tickets/) | Todos los tickets BSC-000 a BSC-053 |
| [`CHANGELOG.md`](CHANGELOG.md) | Historial de cambios por versión |
