# BSC — Remediación de readiness 2026-09-26

Este documento traduce `BSC_PROD_READINESS_AUDIT_2026-09-26.md` en cambios verificables del theme. No sustituye la validación contra staging/producción.

## Cerrado en código

- Skin Quiz AI: almacenamiento fuera del document root, migración de rutas legacy, validación de path y `Cache-Control: no-store`.
- Privacidad: política del theme describe selfie, retención máxima configurable de 90 días, Google Gemini, perfil derivado y solicitud de borrado.
- Cache: Home/shop/productos anónimos anuncian cache público corto; usuarios, cookies WooCommerce, cuenta, carrito, checkout, AJAX y rutas sensibles hacen bypass.
- Proxy/rate limit: headers forwarding solo se aceptan desde `BSC_TRUSTED_PROXY_CIDRS`; object cache externo usa incremento atómico.
- SMTP: constante/entorno tiene precedencia sobre `wp_options`.
- Analytics: GA4 no carga ni emite eventos hasta consentimiento; el banner es accesible y respeta rechazo.
- Deploy: ref único configurable con default `refs/heads/main`; rollback vuelve a ejecutar cleanup.
- Bundle: `vendor` runtime se conserva, `vendor/autoload.php` se excluye y el ZIP se inspecciona automáticamente.
- Assets: Swiper usa la versión real del archivo mediante `bsc_get_asset_version()`.
- Cron: el instalador de cron añade `wp cron event run --due-now` cada 5 minutos cuando encuentra WP-CLI.
- UI: foco visible, `prefers-reduced-motion`, `text-wrap: balance`, transiciones explícitas y CSS administrativo dentro del pipeline Sass.
- Gates: request/output strict, CSS build sync, lint JS/SCSS/PHP, asset/dependency audit, bundle audit y domain tests.

## Configuración requerida antes de GO

En producción, definir en `wp-config.php` o en el entorno del servidor:

```php
define( 'BSC_SKIN_QUIZ_PRIVATE_DIR', '/var/lib/bsc/skin-quiz' );
define( 'BSC_TRUST_PROXY_HEADERS', true );
define( 'BSC_TRUSTED_PROXY_CIDRS', 'CIDR_DEL_PROXY_AUTORIZADO' );
define( 'BSC_GA4_REQUIRE_CONSENT', true );
```

El directorio privado debe existir fuera del document root y ser escribible por PHP. `BSC_TRUSTED_PROXY_CIDRS` debe contener únicamente rangos/IPs reales del CDN o reverse proxy; si no se configura, el theme ignora forwarding headers y usa `REMOTE_ADDR`.

## Evidencia todavía externa al theme

- Wompi: CARD aprobada/rechazada, PSE aprobado/pending/abandonado, webhook/return duplicado, retry en `order-pay`, stock y correos.
- SMTP: entrega real a Gmail, Outlook y móvil; retirar `bsc_smtp_password` de `wp_options` si se usa secret externo.
- Backup: restore drill en staging de DB, uploads, configuración y permisos; registrar fecha, duración y resultado.
- Nginx/CDN: URL directa de `uploads/bsc-private` debe responder 403/404 y la carpeta nueva no debe estar bajo webroot.
- Producción: pin exacto de PHP/WordPress/WooCommerce/Wompi; deploy desde `main` por SHA/tag; rollback probado.
- Rendimiento: Lighthouse/WebPageTest con cache fría/caliente; validar LCP < 2.5–3.0 s, CLS < 0.1, hit de cache en páginas públicas y bypass en checkout/account.
- Browser: levantar `bsc.local` o staging y ejecutar Playwright smoke, WebKit, visual, teclado, zoom 200%, lector de pantalla y flujo completo de Skin Quiz.
- WPCS/PHPStan: ejecutar en el entorno que tenga Composer y dependencias PHP del proyecto.

## Comandos locales ejecutados

```text
npm run lint
npm run audit:php-requests -- --strict
npm run audit:php-output -- --strict
npm run audit:deps
npm run audit:assets
npm run bundle
npm run test:domain
php 8.2: tests/frontend-routing-local-host.php
```

Resultado: gates disponibles en verde; WPCS/PHPStan y pruebas browser quedan pendientes por dependencias/servicio local no disponibles en esta sesión.
