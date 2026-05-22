# BSC Unified Roadmap and Operating Manual

Ultima consolidacion: 2026-05-22
Repositorio: wp-bsc-theme-v2
Rama esperada de release: MVP2
Objetivo: mantener una sola fuente de verdad para roadmap, release, QA, deploy, arquitectura, operaciones, tickets historicos y reglas de trabajo.

## Politica de documentacion

Archivos markdown activos:

- `ROADMAP_BSC.md`: documento unico para roadmap, estado de release, checklist, arquitectura, operaciones, QA y backlog.
- `project documentation`: guia corta para agentes y colaboradores; debe apuntar siempre a este roadmap.

Reglas:

- No crear nuevos `.md` sueltos para tickets, changelog, release notes, checklists o runbooks.
- No usar `.Codex/tickets/` como cola activa. Las tareas nuevas se agregan en la seccion "Backlog operativo" de este archivo.
- La evidencia historica vive en git commits y en las secciones consolidadas de este archivo.
- Si una tarea se completa, moverla o marcarla dentro de este archivo; no crear archivos paralelos.
- Los artefactos generados, videos, reportes, `node_modules`, capturas y resultados de Playwright no deben subirse salvo instruccion explicita.

## Identidad del proyecto

Bubble Skin Care es una tienda ecommerce WordPress + WooCommerce implementada principalmente en este custom theme.

Prioridades de negocio:

1. Lanzar sin regresiones.
2. Preservar checkout, carrito, cuenta, admin, navegacion y SEO.
3. Resolver primero issues visibles reportados por cliente.
4. Mantener cambios pequenos, trazables y reversibles.
5. Mejorar mobile UX, performance y flujos operativos admin.

Stack:

- WordPress 6+
- WooCommerce 7+
- PHP 8+
- Sass
- Playwright
- Swiper 11 local
- Font Awesome local
- Modulos internos bajo `plugins/`

Comandos principales:

```bash
npm run lint
npm run lint:php
npm run lint:js
npm run lint:scss
npm run test:e2e:smoke
npm run test:e2e:visual
npm run compile:css
```

## Estado actual de release

Branch activo: `MVP2`

Gate de cierre P1 documentado el 2026-05-22:

- `npm run lint`: verde.
- `npm run test:e2e:smoke`: verde, 93 passed / 27 skipped.
- `npx playwright test tests/e2e/visual/email-previews.spec.js --workers=1 --reporter=list`: verde, 30 passed.
- Header/mobile nav visual subset: verde en los viewports aplicables.
- `npm run test:e2e:visual`: no verde por snapshots publicos ya desactualizados en home, categoria, producto, checkout, contacto y Bubble Creators. Este refresh de baselines queda fuera del cierre P1 porque requiere revision visual aprobada.
- Account visual page conserva diffs minimos de baseline en pagina de pedidos de cuenta; smoke de `edit-account` si pasa y el ajuste Safari/iPhone de selects esta cubierto por CSS.

Scope ya cerrado en MVP2:

- Hardening de ordenes, cuenta y rutas autenticadas.
- Integridad de Bubble Points, limpieza admin y cobertura visual.
- Separacion de vistas checkout, smoke de cart/coupon y thank-you.
- Modularizacion de header, responsive cleanup y normalizacion de rutas.
- Extraccion de assets inline hacia pipeline CSS/JS.
- Cobertura Playwright de storefront, auth, admin, previews de email y smoke de release.
- Reemplazo de links publicos hardcoded con helpers compartidos donde ya fue abordado.
- Cart quantity hardening con `cart_item_key`, bloqueo de clicks duplicados y reconciliacion desde respuestas WooCommerce.
- Packing view con deduccion por linea desde bodega o showroom.
- Limpieza de mojibake en copy admin/operador tocado en el release pass.
- P1 release-slice: rate limit centralizado para AJAX publico critico, acciones admin mutables por POST+nonce, validacion de cupones, workflow admin de Bubble Creators, previews de email admin-only/noindex, invalidacion de cache de busqueda, redencion Bubble Points con guardrails, accesibilidad de mega menu/search, fix de selects en cuenta para Safari/iPhone, imagenes de cards con sizes/decoding y helper Playwright compatible con lazy images.

Notas residuales:

- WP admin local sigue siendo mas lento y fragil que storefront.
- Safari/iPhone/WebKit necesita gate explicito antes de production GO.
- Visual baselines deben revisarse antes de marcar release verde.
- P1 amplio que queda como seguimiento no bloqueante: refresh de snapshots publicos, auditoria WebKit manual, migraciones de datos no urgentes, paginacion server-side de reportes grandes y rate limiting atomico si se instala object cache/CDN.
- `cicd/deploy.php` esta fuera del scope actual salvo instruccion explicita.

## Mapa del sistema

Runtime:

```text
WordPress bootstrap
  -> functions.php
    -> inc/setup/*
    -> inc/scripts/enqueue-scripts.php
    -> inc/woocommerce.php
    -> inc/ajax/*.php
    -> includes/class-bsc-roles.php
    -> includes/class-bsc-permissions.php
    -> includes/class-bsc-stock.php
    -> emails/bsc-emails.php
  -> selected template
  -> components/header.php
  -> page/template/component content
  -> components/footer.php
```

AJAX:

```text
admin-ajax.php
  -> check_ajax_referer()
  -> capability check when needed
  -> sanitize input
  -> WooCommerce/theme operation
  -> wp_send_json_success() or wp_send_json_error()
```

Areas principales:

- Storefront: `front-page.php`, `components/header.php`, `components/products/*`, `woocommerce/archive-product.php`, `woocommerce/single-product.php`, `page-cart.php`.
- Checkout: `page-checkout.php`, `components/checkout/*`, `inc/ajax/cart-actions.php`, `inc/ajax/checkout-actions.php`, `js/cart.js`, `js/checkout.js`.
- Admin: `admin/bsc-admin-menu.php`, `admin/bsc-orders-page.php`, `admin/bsc-products-page.php`, `admin/bsc-reports-page.php`, `admin/bsc-showroom-page.php`, `includes/class-bsc-permissions.php`.
- Stock dual: `includes/class-bsc-stock.php`, `_stock_bodega`, `_stock_tienda`, logs de movimiento y deducciones por pedido/showroom.
- Emails: `emails/bsc-emails.php`, `emails/bsc-email-helpers.php`, `emails/bsc-order-*.php`, `emails/bsc-followup-*.php`.
- Catalogo interno: `plugins/bsc-catalog/*` absorbio contratos utiles del plugin legacy; el folder legacy `wp-bsc-plugin-v1` no debe ser runtime activo.
- Bubble Points: `plugins/bubble-points/*`, ledger en `bsc_points_ledger`, meta balance `bsc_bubble_points`.
- Tests: `tests/e2e/smoke`, `tests/e2e/visual`, helpers bajo `tests/e2e/helpers`.

Reglas de alto riesgo:

- Cart quantities deben apuntar a `cart_item_key` cuando exista.
- UI de carrito/checkout debe reconciliar desde servidor, no solo estado optimista.
- Cambios de destino en checkout deben evitar respuestas AJAX stale.
- Ajustes de stock deben pasar por helpers para preservar logs.
- Mutaciones de Bubble Points deben pasar por ledger.

## Go-live checklist

Pre-flight:

- Confirmar worktree limpio excepto archivos locales ignorados.
- Confirmar que el artefacto de deploy corresponde al branch/revision esperado.
- Confirmar backup de DB y theme restorable.
- Confirmar WooCommerce status sin fatales activos.
- Confirmar credenciales de pago y reglas de envio en produccion.
- Confirmar acceso admin a Pedidos, Productos, Informes, Showcase y Bubble Points.

Gates requeridos antes de GO:

- `npm run lint`
- `npm run test:e2e:smoke`
- `npm run test:e2e:visual`
- Revision manual home mobile/tablet/desktop.
- Walk-through manual checkout con carrito preparado.
- Cart stress: taps rapidos +/-, decremento a cero, filas duplicadas/variantes y badge.
- Admin order popup: packing, stock source bodega, stock source showroom, labels/PDF.

GO solo si:

- Smoke verde.
- Visual verde y diffs revisados.
- Home, checkout, cuenta, Bubble Points y admin orders spot-checked.
- No hay drift visual no aprobado.
- Backup existe.

NO-GO si:

- Falla checkout smoke.
- Falla cuenta/order detail.
- Visual diff muestra drift no revisado.
- Admin order/product no carga.
- Pago/envio post-deploy roto.

Post-deploy smoke:

- Home carga y header renderiza.
- Categoria carga cards.
- PDP abre desde categoria.
- Add-to-cart actualiza quantity controls.
- Checkout renderiza con carrito preparado.
- Checkout +/- reconcilia cantidad, row total y badge.
- Contacto y Bubble Creators exponen shells AJAX.
- Login/register/account/view-order/thank-you cargan.
- BSC Pedidos, Productos, Informes, Showcase y Bubble Points cargan.
- Email previews cargan para welcome, reset, birthday, order lifecycle y followups.

Rollback:

1. Detener operaciones manuales en admin.
2. Revertir al theme artifact o snapshot previo.
3. Restaurar DB solo si hubo cambios destructivos de datos.
4. Limpiar caches.
5. Repetir smoke storefront y admin.

## Deploy y rollback operativo

Pre-requisitos:

- SSH a produccion.
- WP-CLI instalado.
- Repo git clonado en servidor.
- Backup reciente verificado.

Deploy:

```bash
wp maintenance-mode activate --path=/var/www/html
cd /var/www/html/wp-content/themes/wp-bsc-theme-v2
git fetch origin
git pull origin MVP2
wp cache flush --path=/var/www/html
wp transient delete --all --path=/var/www/html
tail -20 /var/log/php_errors.log
wp maintenance-mode deactivate --path=/var/www/html
```

Smoke manual post-deploy:

- Home sin errores de consola.
- Agregar producto al carrito actualiza badge.
- Checkout visible y funcional.
- Admin BSC carga.
- Sin PHP warnings recientes.

Rollback codigo:

```bash
cd /var/www/html/wp-content/themes/wp-bsc-theme-v2
git revert HEAD --no-edit
wp cache flush --path=/var/www/html
```

Rollback DB:

```bash
wp maintenance-mode activate --path=/var/www/html
gunzip -c /backups/bsc/db/db-DATE.sql.gz | mysql -h DB_HOST -u DB_USER -p DB_NAME
wp cache flush --path=/var/www/html
wp transient delete --all --path=/var/www/html
wp maintenance-mode deactivate --path=/var/www/html
```

## Backups, restore y DR

Capas de backup:

1. DB backup: `scripts/backup-db.sh`, salida `db-YYYY-MM-DD-HH-MM.sql.gz`, frecuencia diaria.
2. WordPress app backup: `scripts/backup-full.sh`, incluye `wp-config.php`, themes, plugins, uploads y languages.
3. VPS state backup: `scripts/backup-vps-state.sh`, incluye nginx/apache, letsencrypt, cron, systemd, ssh, mysql/php config, inventario.
4. Recovery bundle: `scripts/backup-recovery-bundle.sh`, artefacto preferido para drills y retencion off-site.

Schedule recomendado:

- DB: diario 02:00.
- VPS state: diario 02:20.
- WordPress app: diario 02:40.
- Recovery bundle: domingo 03:15.
- Health monitor: cada 15 minutos.

Retencion recomendada:

- DB: 30 dias.
- App backups: 30 dias.
- VPS state: 30 dias.
- Bundles: 60-90 dias.
- Archivo mensual off-site: 6-12 meses.

Restore DB:

```bash
wp maintenance-mode activate --path=/var/www/html
gunzip -c /backups/bsc/db/db-DATE.sql.gz | mysql -h DB_HOST -u DB_USER -p DB_NAME
wp cache flush --path=/var/www/html
wp transient delete --all --path=/var/www/html
wp maintenance-mode deactivate --path=/var/www/html
```

Restore WordPress files:

```bash
wp maintenance-mode activate --path=/var/www/html
tar -xzf /backups/bsc/full/full-DATE.tar.gz -C /var/www/html
chown -R www-data:www-data /var/www/html/wp-content
chmod -R 755 /var/www/html/wp-content
find /var/www/html/wp-content -type f -name "*.php" -exec chmod 644 {} \;
wp maintenance-mode deactivate --path=/var/www/html
```

Stage recovery bundle:

```bash
bash scripts/restore-bundle.sh /backups/bsc/bundles/recovery-bundle-DATE.tar.gz --restore-root /tmp/bsc-restore
```

Full restore from bundle:

```bash
wp maintenance-mode activate --path=/var/www/html
bash scripts/restore-bundle.sh /backups/bsc/bundles/recovery-bundle-DATE.tar.gz --restore-root /tmp/bsc-restore --apply-db --apply-wordpress --wp-root /var/www/html
chown -R www-data:www-data /var/www/html/wp-content
chmod -R 755 /var/www/html/wp-content
wp cache flush --path=/var/www/html
wp rewrite flush --path=/var/www/html
wp maintenance-mode deactivate --path=/var/www/html
```

DR inventory que debe mantenerse fuera de git:

- VPS provider, cuenta, billing y soporte.
- Registrar/DNS/CDN/WAF.
- IPs, roots, off-site backup destination.
- Credenciales y ubicaciones de llaves.
- SMTP, payment gateway, contactos de escalacion.
- Registro mensual de restore drill.

## Cache y media

Browser cache recomendado:

- Imagenes/fuentes: 1 ano.
- CSS/JS: 1 mes.
- Versionar assets con `BSC_THEME_VERSION`.

Nunca cachear:

```text
/carrito/
/cart/
/checkout/
/mi-cuenta/
/mi-cuenta/*
/wp-admin/
/?wc-ajax=*
/?add-to-cart=*
```

Object cache:

- Redis si hosting lo soporta.
- Si no, object cache por defecto de WordPress es aceptable para trafico moderado.

Transients BSC:

- `bsc_slider_*`: 1 hora, invalidado por `save_post_product`.
- `bsc_menu_categories`: 12 horas, invalidado por term edits/creates.
- `bsc_search_*`: 15 minutos.
- `bsc_report_*`: 1 hora.

Media:

- WebP depende de soporte `imagewebp()`.
- Regenerar imagenes existentes con `wp media regenerate --yes`.
- Sizes: `bsc-card` 400x400, `bsc-hero` 1440x600, `bsc-thumb` 120x120.
- Producto: minimo 800x800, ideal menos de 300 KB.
- Hero: minimo 1440x600, ideal menos de 400 KB.
- Menu/banners: WebP preferido, menos de 150 KB.
- Slide 0 hero: eager/fetchpriority high.
- Cards: lazy por defecto via `wp_get_attachment_image`.

## Monitoreo

Monitoreo externo:

- UptimeRobot o equivalente cada 5 minutos para `https://bubbleskincare.co`.
- Alertas por email del equipo.

Health check servidor:

- HTTP status distinto de 200.
- Disco mayor a 80%.
- SSL expira en menos de 30 dias.
- Backup DB ausente en ultimas 48 horas.

Cron sugerido:

```bash
0 2 * * * /var/www/html/wp-content/themes/wp-bsc-theme-v2/scripts/backup-db.sh
0 3 * * 0 /var/www/html/wp-content/themes/wp-bsc-theme-v2/scripts/backup-full.sh
*/15 * * * * /var/www/html/wp-content/themes/wp-bsc-theme-v2/scripts/monitor-health.sh
```

Logs importantes:

- `/var/log/bsc-backup.log`
- `/var/log/bsc-monitor.log`
- `/var/log/apache2/error.log` o nginx equivalente.
- `/var/log/php_errors.log`

## Playwright y QA baseline

Base URL por defecto: `http://bsc.local`

Overrides:

- `PLAYWRIGHT_BASE_URL`
- `PW_PUBLIC_MODE=storefront|coming-soon`
- `PW_ROUTE_HOME`
- `PW_ROUTE_CATEGORY`
- `PW_ROUTE_CHECKOUT`
- `PW_ROUTE_ACCOUNT`
- `PW_ROUTE_BUBBLE_POINTS`
- `PW_ROUTE_THANK_YOU`
- `PW_ROUTE_PRODUCT`

Viewports oficiales:

- Mobile: 390x844.
- Tablet: 768x1024.
- Desktop: 1440x900.

Comandos:

```bash
npm run test:e2e:smoke
npm run test:e2e:visual
npm run test:e2e:visual:auth
npm run test:e2e:visual:public-auth
npm run test:e2e:visual:emails
npm run test:e2e:visual:update
npm run test:e2e:report
```

Notas:

- Smoke y visual corren con `--workers=1` para evitar flake del stack local.
- Visual esta dividido en public, header, authenticated y email preview.
- Actualizar snapshots solo cuando el ambiente y el cambio visual fueron aprobados.

## Backlog operativo consolidado desde tickets historicos

Tickets recientes integrados/completados:

- Account edit iOS select height: normalizar selects de "Tipo de piel" y "Sensibilidad" para Safari/iPhone.
- Bubble Creators required social links: Instagram/TikTok obligatorios como URL valida de plataforma.
- Bubble Creators success/email routing: success sin recuadro/emoji y email dedicado `bsc_creator_email`.
- Checkout/cart/coupon/shipping audit: alinear totales, cupones, envio y resumen.
- Checkout empty cart state: redirigir checkout vacio a cart empty state.
- Empty cart recommended products: mostrar recomendados bajo "Volver a la tienda".
- Footer account link orders: "Mi cuenta" del footer apunta a ordenes.
- Menu nav image category links: imagenes del mega menu apuntan a group category roots.
- Group category filters: filtros en top-level groups.
- Hide zero dual-stock products: ocultar productos con stock dual total cero.
- Mobile description typography: arreglar bloque SCSS mobile para `.bsc__description`.
- PHP bootstrap refactor phase 1: mover hooks runtime a `inc/security`, `inc/performance`, `inc/routing`, `inc/product`.
- Shop breadcrumbs two-step groups: ocultar breadcrumb intermedio en grupos principales.
- Shop subcategory full-width layout: links de sub-subcategoria full width y sidebar debajo.
- Full codebase audit roadmap: auditoria consolidada en este roadmap.

Tickets historicos que quedan como referencia/seguimiento:

- Green Grape add-to-cart failure: verificar producto especifico en home y PDP si vuelve a aparecer.
- PHP refactorization audit plan: seguir refactors por fases, empezando por bajo riesgo.
- Deep theme audit: base de los P0/P1/P2 listados abajo.

Pendientes historicos consolidados:

- BSC-001: WhatsApp helper centralizado.
- BSC-002: eliminar URLs localhost/bsc.local hardcodeadas.
- BSC-003: quantity controls completos.
- BSC-005: add-to-cart iPad/touch.
- BSC-009: mobile menu redesign.
- BSC-010: unificar arbol mobile/desktop.
- BSC-014: product gallery stretching.
- BSC-045 a BSC-053: backups, monitoreo, accesibilidad, i18n.

## Changelog consolidado

Unreleased/MVP2:

- P1 release-slice cerrado: rate limiter helper por scope/IP/user, limites para carrito/cupones/review/city reload/newsletter/creator/Bubble Points.
- Admin hardening: dashboard clear-cache por POST+nonce, cupones con delete POST+nonce y validacion de porcentaje/fecha, panel BSC Creators con filtros, estados y CSV.
- UX/accesibilidad P1: mega menu y profile dropdown con `aria-expanded`, `hidden`, Escape/focusout; search con listbox/combobox, keyboard navigation y abort de fetch anterior.
- Performance/estabilidad P1: product cards con `wp_get_attachment_image`, `sizes`, `loading` y `decoding`; cache de busqueda versionado por cambios de producto/categoria; helper Playwright robusto con lazy images.
- Email previews: rutas admin-only con `X-Robots-Tag` noindex y nonce compatible con links admin sin romper fixtures read-only.
- Roles custom `bsc_operator` y `bsc_employee`.
- Admin menu BSC con Pedidos, Productos, Venta Presencial, Informes, Configuracion.
- Pedidos admin con filtros, paginacion, estado inline y tracking.
- Estados custom `wc-preparing` y `wc-shipped`.
- Email de envio HTML con tracking.
- CSV de pedidos y packing print view.
- Informes con KPIs y top productos.
- Stock dual bodega/tienda con clase `BSC_Stock`.
- Venta presencial/showroom con carrito JS y descuento de tienda.
- Cache de busqueda y sliders.
- `BSC_THEME_VERSION` para assets.
- Sizes de imagen BSC y cards con `wp_get_attachment_image`.
- Nonce CSRF en registro.
- Responsive fixes en cuenta, pedidos mobile y direcciones.
- Bubble Points con tickets usados/vencidos y flujo de ledger.
- Scripts condicionales, Swiper/Font Awesome locales, cleanup de assets externos.

Notas de version 1.1.0:

- Swiper y Font Awesome migrados a local.
- Trust signals en checkout.
- Fallback hero estatico.
- Guards ABSPATH y nonces en AJAX criticos.
- Newsletter JS extraido.
- Bubble Creators landing funcional.
- Notices de cupones/search mejorados.
- Cart badge sincronizado.
- Mobile menu cleanup.
- Checkout submit vuelve a WooCommerce.
- Thank-you escaping corregido.
- Transient guard para default pages.

Notas de version 1.0.0:

- Tema inicial basado en Underscores.
- Integracion WooCommerce.
- Hero Swiper CPT.
- Mega menu desktop y mobile sidebar.
- Favoritos.
- Bubble Points interno.
- Paginas custom checkout/cart/mi-cuenta/bubble-points.
- AJAX cart/checkout/coupons/filters/search/newsletter.
- Responsive mobile/desktop.

## Resumen ejecutivo

Estado observado:

1. El custom theme concentra frontend, WooCommerce, admin operativo, inventario, Bubble Points, emails y AJAX publico.
2. El repo tiene una superficie de cambios grande y actualmente hay muchos archivos modificados/no rastreados; antes de release se necesita separar lo que entra de lo que queda fuera.
3. Hay buenas bases de seguridad en varios endpoints: `defined( 'ABSPATH' )`, nonces AJAX y capabilities en admin.
4. La aplicacion no tiene una politica uniforme para rate limiting, validacion, errores de email, exportaciones pesadas, cache e invalidacion.
5. Hay riesgo visible de encoding/mojibake en PHP, JS y comentarios que puede filtrarse a textos de usuario.
6. Hay un bug HTML concreto en reportes de stock que renderiza texto `class="..."` dentro de la pagina admin.
7. Bubble Points ya tiene ledger, pero todavia existen rutas/clases que pueden mutar balances fuera del ledger o sin transaccion real.
8. El sistema de inventario tiene buenos updates atomicos, pero necesita pruebas de concurrencia y proteccion contra doble deduccion en ventas presenciales/manuales.
9. La UI publica depende de JS globales; algunos scripts no tienen guards completos ni accesibilidad de teclado suficiente.
10. La validacion visual existe, pero Safari/iPhone/WebKit no esta cubierto de forma confiable, justo donde la cliente reporto diferencias.

Uso recomendado:

- Registrar nuevas tareas directamente en este archivo, bajo la prioridad o backlog correspondiente.
- Resolver primero P0, despues P1, despues P2/P3.
- No mezclar refactors grandes con fixes visuales pequenos.
- Cada ticket debe pasar `npm run lint`, `npm run test:e2e:smoke` y, si toca UI, `npm run test:e2e:visual`.
- Para tickets que tocan checkout, carrito, cuenta, inventario, puntos o admin, agregar QA manual antes de aceptar.

Leyenda de prioridad:

- P0: bloquea release o puede romper ventas, datos, seguridad o operacion diaria.
- P1: alto impacto, debe entrar antes de estabilizar MVP2 si no retrasa un P0.
- P2: mejora importante de calidad, performance, mantenibilidad o UX.
- P3: backlog de producto/operacion, no bloquea release si P0/P1 estan sanos.

## Gate de release recomendado

Antes de deploy:

1. Worktree limpio o con lista aprobada de archivos incluidos.
2. `npm run lint` verde.
3. `npm run test:e2e:smoke` verde.
4. `npm run test:e2e:visual` verde con snapshots actualizados y revisados.
5. QA manual en Chrome desktop, mobile viewport, iPhone/Safari real o WebKit equivalente.
6. Compra completa: catalogo -> producto -> carrito -> checkout -> pago/metodo disponible -> thank you -> cuenta.
7. Operacion admin: pedidos, cambio de estado, tracking, inventario, showroom, reportes, cupones.
8. Emails: contacto, Bubble Creators, orden, tracking, followups y preview admin.
9. Seguridad: roles BSC, access control, endpoints AJAX, nonces y rate limits.
10. Backup y rollback documentados.

---

# P0 - Bloqueadores de release

## BSC-RM-001 - Congelar alcance de cambios y limpiar release slice

Prioridad: P0
Area: Proceso, release, control de riesgo

Problema:
El worktree tiene muchos cambios simultaneos y archivos no rastreados. Sin una frontera clara, cualquier fix pequeno puede salir mezclado con modulos de runtime, CSS compilado, snapshots o artefactos locales.

Evidencia:
- `functions.php` carga modulos nuevos en `inc/performance`, `inc/security`, `inc/product` e `inc/routing`.
- Hay cambios en Sass/CSS, Woo templates, admin, JS, tests y snapshots.
- Existen artefactos locales como `admin.zip`, `admin2.zip`, `Videos/`, `playwright-report/`, `test-results/` y `node_modules/`.

Archivos a inspeccionar:
- `functions.php`
- `style.css`
- `style.css.map`
- `.gitignore`
- `package.json`
- Secciones "Estado actual de release" y "Go-live checklist" en este archivo.

Implementacion minima:
- Crear un ticket de release hygiene.
- Clasificar cada archivo modificado en: entra a MVP2, queda fuera, generado/local, pendiente de decision.
- Asegurar que zip/reportes/videos/resultados no entren a commits.
- Recompilar CSS desde Sass si `style.css` esta desalineado.
- Documentar decision en changelog o release status.

Criterios de aceptacion:
- `git status --short` solo muestra archivos intencionales antes del commit.
- No hay artefactos generados ni zips incluidos en el paquete.
- El release status indica exactamente que tickets entran.

QA:
- Ejecutar `npm run lint`.
- Ejecutar smoke completo.
- Revisar `npm run bundle` o proceso de paquete si aplica.

Riesgo:
Alto. Mezclar cambios sin frontera puede introducir regresiones invisibles en checkout, admin o CSS.

## BSC-RM-002 - Restaurar baseline visual verde y registrar diferencias reales

Prioridad: P0
Area: QA visual, UI

Problema:
La suite visual existe, pero hay evidencia de snapshots desalineados y reportes generados. Si los baselines estan obsoletos, la suite deja de servir como gate.

Evidencia:
- Carpetas `tests/e2e/visual/*` y snapshots existen.
- `playwright-report/` y `test-results/` aparecen en el workspace.
- Reportes anteriores mencionan diffs visuales pequenos en cuenta.

Archivos a inspeccionar:
- `tests/e2e/visual/*.spec.js`
- `tests/e2e/visual/*-snapshots/*`
- `playwright.config.js`
- Seccion "Estado actual de release" en este archivo.

Implementacion minima:
- Correr visual suite en estado controlado.
- Separar fallos reales de snapshots obsoletos.
- Actualizar snapshots solo despues de revision humana.
- Agregar nota de vista/viewport a cada cambio visual aceptado.

Criterios de aceptacion:
- `npm run test:e2e:visual` verde.
- Cada snapshot actualizado tiene motivo en ticket/changelog.
- No se aceptan cambios visuales "porque paso local" sin screenshot revisado.

QA:
- Revisar screenshots de home, catalogo, producto, carrito, checkout, mi cuenta, login y admin critico.

Riesgo:
Alto. Sin baseline confiable se escapan regresiones de layout.

## BSC-RM-003 - Instalar o habilitar validacion WebKit/Safari/iPhone

Prioridad: P0
Area: QA cross-browser

Problema:
La cliente reporto diferencias en Safari/iPhone y el entorno local no garantiza WebKit. Los fixes CSS pueden verse correctos en Chrome y fallar en iOS.

Evidencia:
- Reporte reciente de selects del perfil distintos en Safari/iPhone.
- Playwright WebKit no estaba disponible de forma confiable en la maquina.

Archivos a inspeccionar:
- `playwright.config.js`
- `tests/e2e/smoke/*.spec.js`
- `tests/e2e/visual/*.spec.js`
- `sass/pages/_page-account.scss`
- `woocommerce/myaccount/form-edit-account.php`

Implementacion minima:
- Agregar proyecto WebKit en Playwright si el runtime lo soporta.
- Si local no soporta WebKit, definir BrowserStack/LambdaTest/device real como gate manual.
- Crear smoke visual especifico para `/my-account/edit-account/` en mobile Safari.

Criterios de aceptacion:
- Existe evidencia de captura Safari/WebKit para cuenta, carrito, checkout y producto.
- El gate de release documenta como se valida Safari.

QA:
- iPhone Safari real: mi cuenta, selects, checkout city/state, add to cart, mobile menu.

Riesgo:
Alto. iOS es el navegador de la cliente y ya produjo un bug visible.

## BSC-RM-004 - Corregir HTML roto y encoding en Reportes de stock

Prioridad: P0
Area: Admin UI, calidad

Problema:
La pagina de reportes de stock renderiza un atributo duplicado como texto visible. Ademas el archivo contiene mojibake severo.

Evidencia:
- `admin/bsc-reports-page.php:508` contiene el input correcto.
- `admin/bsc-reports-page.php:509` contiene una segunda linea `class="bsc-admin-reports__stock-search-input">`.
- El mismo archivo contiene cadenas corruptas tipo mojibake UTF-8 mal decodificado.

Archivos a inspeccionar:
- `admin/bsc-reports-page.php`
- `js/admin/bsc-reports.js`
- `sass/admin/_bsc-reports.scss` o Sass admin equivalente

Implementacion minima:
- Eliminar HTML duplicado.
- Reparar textos visibles en espanol.
- Evitar tocar logica SQL en este ticket salvo que sea necesario.
- Agregar assertion smoke/admin para que el texto `class="bsc-admin-reports__stock-search-input"` no aparezca visible.

Criterios de aceptacion:
- Reportes de stock no muestra texto roto.
- El buscador de stock funciona.
- No hay mojibake visible en labels principales de reportes.

QA:
- Abrir WP Admin -> BSC -> Informes -> Stock.
- Buscar por nombre/SKU.
- Cambiar filtros y orden.

Riesgo:
Medio. Es visible para operacion y erosiona confianza en admin.

## BSC-RM-005 - Restringir dashboard BSC y evitar fuga de KPIs a roles bajos

Prioridad: P0
Area: Seguridad, roles, admin

Problema:
El menu principal BSC usa capability `read`; el dashboard puede mostrar KPIs, pedidos recientes y stock a usuarios con acceso basico.

Evidencia:
- `admin/bsc-admin-menu.php` registra el top-level menu con capability amplia.
- Subpaginas usan helper de acceso, pero el dashboard principal puede quedar demasiado abierto.

Archivos a inspeccionar:
- `admin/bsc-admin-menu.php`
- `admin/bsc-access-page.php`
- `includes/class-bsc-permissions.php`
- `includes/class-bsc-roles.php`

Implementacion minima:
- Aplicar `bsc_current_user_has_bsc_page_access` o capability equivalente tambien al dashboard.
- Si un rol no tiene permisos, mostrar pantalla de acceso denegado sin KPIs.
- Revisar que operadores/empleados mantengan solo las paginas necesarias.

Criterios de aceptacion:
- Usuario con solo `read` no ve KPIs ni pedidos.
- Shop manager/admin conservan acceso.
- Roles BSC custom ven solo paginas autorizadas.

QA:
- Probar con admin, shop_manager, bsc_operator, bsc_employee y subscriber.

Riesgo:
Alto. Exposicion de datos operativos y clientes.

## BSC-RM-006 - Usar email configurable de contacto y manejar fallos de wp_mail

Prioridad: P0
Area: Formularios, operacion, emails

Problema:
La pagina de ajustes expone emails operativos, pero el formulario de contacto sigue usando constante/admin email. Ademas los formularios pueden devolver exito aunque `wp_mail` falle.

Evidencia:
- `functions.php` define `BSC_CONTACT_EMAIL`.
- `admin/bsc-admin-menu.php` guarda `bsc_contact_email` y `bsc_creator_email`.
- `inc/ajax/contact-actions.php` usa fallback constante/admin.
- `inc/ajax/creator-actions.php` llama `wp_mail` sin validar resultado.

Archivos a inspeccionar:
- `inc/ajax/contact-actions.php`
- `inc/ajax/creator-actions.php`
- `admin/bsc-admin-menu.php`
- `emails/bsc-email-helpers.php`
- `js/contact.js`
- `js/creator-apply.js`

Implementacion minima:
- Crear helper unico para resolver emails operativos.
- Contacto debe enviar a `bsc_contact_email`.
- Bubble Creators debe enviar a `bsc_creator_email`.
- Si `wp_mail` falla, registrar error y devolver mensaje honesto al usuario.
- Mantener mensaje publico generico para no filtrar config.

Criterios de aceptacion:
- Emails llegan al destino configurado en BSC Settings.
- Formulario no reporta exito si mail falla.
- Hay log admin/debug seguro para fallos de envio.

QA:
- Configurar emails distintos para contacto y creators.
- Enviar ambos formularios.
- Simular mail failure si es posible.

Riesgo:
Alto. Leads y mensajes pueden perderse o mezclarse con quejas/contacto.

## BSC-RM-007 - Unificar Bubble Points en un solo flujo ledger-safe

Prioridad: P0
Area: Datos, fidelizacion, dinero/promos

Problema:
Bubble Points tiene un ledger, pero la clase `BSC_Bubble_Points` todavia permite `add`, `deduct` y `set` directos sobre user meta. Eso puede crear balances sin historial o desalineados.

Evidencia:
- `plugins/bubble-points/includes/store.php` implementa ledger y lock por usuario.
- `plugins/bubble-points/classes/class-bsc-bubble-points.php` muta `bsc_bubble_points` directamente.
- Hay fallback legacy con `_bsc_bp_points` y `_bsc_bp_log`.

Archivos a inspeccionar:
- `plugins/bubble-points/includes/store.php`
- `plugins/bubble-points/classes/class-bsc-bubble-points.php`
- `plugins/bubble-points/includes/redeem.php`
- `plugins/bubble-points/ajax/redeem.php`
- `plugins/bubble-points/admin/admin-actions.php`
- `plugins/bubble-points/admin/admin-handlers.php`
- `plugins/bubble-points/seeds/demo1.php`

Implementacion minima:
- Hacer que toda mutacion pase por `bsc_bp_add_ledger_entry`.
- Dejar metodos directos como wrappers con reason/meta obligatoria o deprecarlos.
- Crear reconciliacion: balance meta = SUM ledger.
- Agregar comando/admin tool de auditoria de balances.

Criterios de aceptacion:
- No hay mutacion directa de `bsc_bubble_points` fuera del store autorizado.
- Cada cambio tiene ledger row con reason.
- Reconciliacion detecta y corrige diferencias.

QA:
- Completar orden y otorgar puntos.
- Redimir puntos.
- Ajuste manual desde admin.
- Recalcular y comparar balance.

Riesgo:
Alto. Los puntos equivalen a descuentos y pueden afectar dinero real.

## BSC-RM-008 - Blindar inventario contra doble deduccion y carreras

Prioridad: P0
Area: Inventario, operaciones, ventas

Problema:
El sistema de stock hace updates atomicos, pero las rutas de deduccion presencial/manual necesitan locks/idempotencia igual de estricta que pedidos web.

Evidencia:
- `includes/class-bsc-stock.php` usa SQL atomico para no dejar stock negativo.
- La deduccion web marca allocation por order item.
- `deduct_tienda()` no evidencia un guard idempotente equivalente por venta presencial o evento manual.

Archivos a inspeccionar:
- `includes/class-bsc-stock.php`
- `admin/bsc-showroom-page.php`
- `admin/bsc-orders-page.php`
- `admin/bsc-products-page.php`
- `tests/e2e/smoke/*stock*`

Implementacion minima:
- Definir idempotency key por venta/item/admin action.
- Rechazar segunda deduccion del mismo evento.
- Agregar tests de concurrencia basicos para dos requests compitiendo por ultima unidad.
- Agregar logs de rollback claro.

Criterios de aceptacion:
- Dos clicks repetidos no descuentan doble.
- Stock nunca queda negativo.
- El log muestra una sola salida por venta/item.

QA:
- Crear producto con 1 unidad tienda y simular dos ventas simultaneas.
- Repetir click de registrar venta.
- Revisar stock log.

Riesgo:
Alto. Puede vender inventario inexistente o perder trazabilidad.

## BSC-RM-009 - Corregir carrito con productos duplicados/variaciones y keys obligatorias

Prioridad: P0
Area: Carrito, checkout, ventas

Problema:
Al actualizar cantidad se envia `product_id` y opcionalmente `cart_item_key`. Si falta key, el backend puede tocar el primer item que coincida por producto, lo cual falla con variaciones o lineas duplicadas.

Evidencia:
- `inc/ajax/cart-actions.php` acepta `cart_item_key` pero mantiene fallback por `product_id`.
- `js/cart.js` calcula key desde `data-item-key`, pero no todos los contextos garantizan key.

Archivos a inspeccionar:
- `inc/ajax/cart-actions.php`
- `js/cart.js`
- `woocommerce/cart/cart.php`
- `components/checkout/*`
- `page-cart.php`

Implementacion minima:
- Renderizar `data-item-key` en todos los controles de carrito/checkout.
- En backend, requerir key para decrement/increment cuando hay mas de una linea candidata.
- Cubrir variaciones y bundles si existen.

Criterios de aceptacion:
- Cantidad se actualiza en la linea correcta.
- Variaciones del mismo producto no se mezclan.
- Si falta key, respuesta de error es clara y no muta carrito.

QA:
- Agregar dos variaciones o dos lineas con metadata distinta.
- Incrementar/decrementar cada linea.
- Remover cada linea.

Riesgo:
Alto. Mutar el item equivocado afecta la compra.

## BSC-RM-010 - Reparar mojibake visible y fijar encoding UTF-8

Prioridad: P0
Area: Calidad visual, contenido, DX

Problema:
Hay cadenas corruptas en PHP y JS por mojibake UTF-8 mal decodificado. Algunas son comentarios, pero otras son textos visibles como botones, emails, admin y formularios.

Evidencia:
- `front-page.php` contiene texto corrupto donde deberia decir "Ultimos" con acento.
- `js/contact.js`, `js/newsletter.js`, `js/mobile-menu.js`, `plugins/bubble-points/ajax/redeem.php` contienen mojibake en mensajes.
- `woocommerce/myaccount/form-edit-address.php` muestra textos corruptos.
- `admin/bsc-reports-page.php` tiene mojibake severo.

Archivos a inspeccionar:
- `front-page.php`
- `js/*.js`
- `admin/*.php`
- `inc/scripts/enqueue-scripts.php`
- `woocommerce/myaccount/*.php`
- `plugins/bubble-points/**/*.php`

Implementacion minima:
- Inventariar textos visibles corruptos.
- Corregir archivos a UTF-8 sin BOM.
- Agregar check simple en lint o script: fallar si aparecen secuencias comunes de mojibake en strings visibles.

Criterios de aceptacion:
- No hay mojibake visible en frontend/admin principales.
- Los archivos corregidos siguen en UTF-8.
- Tests smoke no ven textos corruptos conocidos.

QA:
- Revisar home, cuenta, direcciones, contacto, newsletter, Bubble Points y reportes.

Riesgo:
Alto. Deteriora UI y puede ocultar errores reales en textos.

## BSC-RM-011 - Validar checkout completo despues de cambios de ciudad/envio

Prioridad: P0
Area: Checkout, ventas

Problema:
El checkout depende de recarga AJAX de ciudad, mutation de `$_POST`, resumen custom y `update_checkout`. Es una zona fragil para conversion.

Evidencia:
- `js/checkout.js` recarga `#billing_city_field`, dispara `update_checkout` y refresca resumen.
- `inc/ajax/checkout-actions.php` renderiza campo ciudad.
- `inc/woocommerce.php` muta destinos y shipping.

Archivos a inspeccionar:
- `js/checkout.js`
- `inc/ajax/checkout-actions.php`
- `inc/woocommerce.php`
- `components/checkout/*`
- `woocommerce/checkout/*`

Implementacion minima:
- Crear smoke que selecciona departamento/ciudad y verifica shipping/total.
- Evitar dependencia de side effects en `$_POST` si se puede encapsular.
- Manejar errores de ciudad sin dejar campo bloqueado.

Criterios de aceptacion:
- Cambiar departamento actualiza ciudad y shipping.
- Checkout no queda en loading infinito.
- Resumen muestra envio solo cuando hay destino suficiente.

QA:
- Chrome desktop, mobile viewport, Safari/iPhone.
- Departamento sin ciudad, cambio rapido entre departamentos, submit con campo incompleto.

Riesgo:
Alto. Puede bloquear pedidos.

## BSC-RM-012 - Revisar rutas coming soon para no bloquear APIs/checkout/admin

Prioridad: P0
Area: Routing, disponibilidad

Problema:
El guard de coming soon puede incluir template para guests en muchas rutas. Debe excluir admin, AJAX, REST, cron, feeds, checkout si negocio lo requiere y webhooks.

Evidencia:
- `functions.php` carga `inc/routing/frontend-routing.php`.
- El modulo nuevo afecta runtime global.

Archivos a inspeccionar:
- `inc/routing/frontend-routing.php`
- `functions.php`
- WooCommerce settings de coming soon

Implementacion minima:
- Crear matriz de rutas permitidas/bloqueadas.
- Excluir `wp-admin`, `admin-ajax.php`, REST, cron, webhooks, assets, login y callbacks de pago.
- Definir si checkout debe bloquearse solo cuando tienda esta realmente cerrada.

Criterios de aceptacion:
- Guests ven coming soon solo donde corresponde.
- Admin/AJAX/REST/webhooks funcionan.
- No se rompen assets ni pagos.

QA:
- Abrir home guest, producto guest, admin, REST, AJAX search, checkout callback/pago si aplica.

Riesgo:
Alto. Puede dejar la tienda inutilizable o romper pagos.

---

# P1 - Seguridad, datos y operacion

## BSC-RM-013 - Estandarizar rate limiting de AJAX publico

Prioridad: P1
Area: Seguridad, performance

Problema:
Algunos endpoints publicos tienen limites y otros no. Un usuario con nonce de pagina puede spamear carrito, cupones, review summary o city reload.

Evidencia:
- Rate limiting existe en newsletter/contact/creator/search.
- Cart/coupon/review/city no tienen limites consistentes.

Archivos a inspeccionar:
- `inc/security/security-hooks.php`
- `inc/ajax/cart-actions.php`
- `inc/ajax/coupons-actions.php`
- `inc/ajax/review-summary-actions.php`
- `inc/ajax/checkout-actions.php`
- `plugins/bsc-catalog/classes/class-bsc-catalog-ajax-controller.php`

Implementacion minima:
- Crear helper `bsc_rate_limit( $scope, $limit, $window )`.
- Aplicar limites por IP + user ID cuando exista.
- Responder 429 con mensaje seguro y retry.

Criterios de aceptacion:
- Endpoints criticos tienen budget documentado.
- No se rompe UX normal.
- Tests cubren limite en al menos dos endpoints.

QA:
- Simular multiples requests a search, coupon, cart y city reload.

Riesgo:
Medio-alto. Limites agresivos pueden afectar usuarios reales; limites flojos pueden afectar servidor.

## BSC-RM-014 - Hacer rate limiter robusto para proxy/CDN y concurrencia

Prioridad: P1
Area: Seguridad, infraestructura

Problema:
El rate limiter usa `REMOTE_ADDR` y transients con get/set no atomico. En proxy/CDN puede agrupar usuarios o usar IP equivocada; bajo concurrencia puede undercount.

Evidencia:
- `inc/security/security-hooks.php` lee `$_SERVER['REMOTE_ADDR']`.
- Transients se incrementan con lectura/escritura simple.

Archivos a inspeccionar:
- `inc/security/security-hooks.php`
- Config de hosting/CDN

Implementacion minima:
- Definir trusted proxy headers permitidos.
- Usar clave con user ID cuando haya sesion.
- Evaluar update atomico via option/cache o tabla pequena.
- Agregar logs de rate limit sin PII excesiva.

Criterios de aceptacion:
- IP real solo se toma desde headers confiables.
- No se bloquea a todo un grupo de usuarios por proxy.
- Race basica no permite saltar limite facilmente.

QA:
- Probar con y sin `X-Forwarded-For` segun entorno controlado.

Riesgo:
Medio-alto. Error en IP puede bloquear clientes reales.

## BSC-RM-015 - Migrar newsletter de option gigante a tabla/lista administrable

Prioridad: P1
Area: Datos, CRM, performance

Problema:
Newsletter guarda suscriptores en un option array que crece indefinidamente. Esto no escala, no tiene estado, export robusto ni control de duplicados avanzado.

Evidencia:
- `inc/ajax/newsletter-actions.php` usa `get_option('bsc_newsletter_subscribers', [])` y `update_option`.

Archivos a inspeccionar:
- `inc/ajax/newsletter-actions.php`
- `admin/bsc-admin-menu.php`
- `emails/*`

Implementacion minima:
- Crear tabla `bsc_newsletter_subscribers` o CPT/admin page liviana.
- Guardar email normalizado, fecha, IP hash, source, consent.
- Export CSV paginado.
- Migrar option antiguo una sola vez.

Criterios de aceptacion:
- No se escribe mas en option gigante.
- Admin puede listar/exportar.
- Duplicados se manejan por email normalizado.

QA:
- Suscribir nuevo email, duplicado, email invalido.
- Exportar CSV.

Riesgo:
Medio. Migracion debe preservar datos existentes.

## BSC-RM-016 - Migrar Bubble Creators applications a flujo administrable

Prioridad: P1
Area: Leads, admin, datos

Problema:
Las solicitudes de creators se guardan en un option array sin workflow, busqueda, export, estado ni retencion.

Evidencia:
- `inc/ajax/creator-actions.php` usa `bsc_creator_applications`.

Archivos a inspeccionar:
- `inc/ajax/creator-actions.php`
- `page-bubble-creators.php`
- `js/creator-apply.js`
- `admin/bsc-admin-menu.php`

Implementacion minima:
- Crear tabla/CPT `bsc_creator_application`.
- Campos: nombre, email, instagram, tiktok, mensaje, status, source, created_at.
- Admin page con filtros, export y cambio de estado.
- Migrar option antiguo.

Criterios de aceptacion:
- Solicitudes no se pierden si falla email.
- Equipo puede revisar solo creators sin mezclarlos con contacto.
- Instagram/TikTok quedan validados server-side y client-side.

QA:
- Crear solicitud valida.
- Rechazar links no validos.
- Ver solicitud en admin y exportar.

Riesgo:
Medio. Es informacion comercial sensible.

## BSC-RM-017 - Nonce y metodo seguro para acciones GET de admin

Prioridad: P1
Area: Seguridad admin

Problema:
Hay acciones admin por GET o con nonce parcial. Limpiar cache/reportes/exportar/eliminar debe estar blindado contra CSRF y accidental clicks.

Evidencia:
- `admin/bsc-admin-menu.php` tiene accion de limpiar cache dashboard via GET.
- `admin/bsc-reports-page.php` limpia cache/exporta por GET con nonces en algunas rutas.
- `admin/bsc-coupons-page.php` elimina cupones via GET.

Archivos a inspeccionar:
- `admin/bsc-admin-menu.php`
- `admin/bsc-reports-page.php`
- `admin/bsc-coupons-page.php`

Implementacion minima:
- Convertir acciones destructivas a POST cuando sea viable.
- Agregar nonce en cada accion GET que permanezca por compatibilidad.
- Confirmacion UI para delete/clear.

Criterios de aceptacion:
- No hay clear/delete/export sensible sin nonce.
- Usuario sin capability correcta no puede ejecutar accion.

QA:
- Intentar ejecutar URLs sin nonce.
- Ejecutar con nonce desde UI.

Riesgo:
Medio. Cambiar metodo puede romper enlaces existentes.

## BSC-RM-018 - Normalizar `wp_unslash` y sanitizacion en admin/AJAX

Prioridad: P1
Area: Seguridad, calidad PHP

Problema:
Hay multiples lecturas de `$_POST`/`$_GET` sin `wp_unslash` antes de sanitize. WordPress espera unslash antes de sanitize para datos de request.

Evidencia:
- `admin/bsc-admin-menu.php` guarda settings con `$_POST` directo.
- `admin/bsc-products-page.php`, `admin/bsc-orders-page.php`, `inc/ajax/cart-actions.php` tienen lecturas mixtas.

Archivos a inspeccionar:
- `admin/*.php`
- `inc/ajax/*.php`
- `plugins/bubble-points/admin/*.php`

Implementacion minima:
- Crear helper pequeno para request scalar/array si encaja con el codigo.
- Aplicar a settings y endpoints mas criticos primero.
- Agregar PHPCS o grep guard para nuevas lecturas directas.

Criterios de aceptacion:
- Settings BSC usan `wp_unslash` antes de sanitize.
- Nuevos endpoints siguen una convencion consistente.

QA:
- Guardar settings con caracteres especiales.
- Enviar formularios AJAX normales.

Riesgo:
Medio. Cambiar sanitizacion puede alterar datos guardados.

## BSC-RM-019 - Validar y limitar cupones admin

Prioridad: P1
Area: Promociones, dinero

Problema:
La creacion/edicion de cupones necesita reglas estrictas para montos, porcentajes, expiracion, uso y combinaciones.

Evidencia:
- `admin/bsc-coupons-page.php` administra cupones custom.
- `js/coupons.js` aplica/remueve cupones en carrito/checkout.

Archivos a inspeccionar:
- `admin/bsc-coupons-page.php`
- `inc/ajax/coupons-actions.php`
- `js/coupons.js`

Implementacion minima:
- Validar `percent <= 100`, montos no negativos, fechas validas y usage limit.
- Definir si cupones son combinables.
- Mostrar errores admin especificos.
- Tests para aplicar/remover y totales.

Criterios de aceptacion:
- Admin no puede crear cupon invalido.
- Frontend muestra mensaje claro si cupon no aplica.
- Totales se sincronizan en carrito y checkout.

QA:
- Cupon fijo, porcentaje, expirado, uso agotado, no combinable.

Riesgo:
Medio-alto. Descuentos incorrectos impactan ingresos.

## BSC-RM-020 - Hacer exportaciones grandes seguras

Prioridad: P1
Area: Admin, performance

Problema:
Reportes y pedidos exportan CSV recorriendo pedidos/productos en request web. En catalogos/historial grandes puede agotar memoria o timeout.

Evidencia:
- `admin/bsc-reports-page.php` exporta pedidos en bloques dentro del request.
- `admin/bsc-orders-page.php` tiene bulk export.

Archivos a inspeccionar:
- `admin/bsc-reports-page.php`
- `admin/bsc-orders-page.php`
- `admin/class-bsc-orders-table.php`

Implementacion minima:
- Definir maximo de rango por export directo.
- Para export grande, usar job/background o paginacion descargable.
- Streaming CSV con headers correctos y flush controlado.

Criterios de aceptacion:
- Export de rango grande no tumba admin.
- UI avisa limites o progreso.
- CSV mantiene columnas correctas.

QA:
- Exportar 1 dia, 30 dias y rango grande con muchos pedidos.

Riesgo:
Medio. Puede afectar operacion diaria.

## BSC-RM-021 - Invalidar cache de busqueda y catalogo en cambios de producto

Prioridad: P1
Area: Cache, catalogo

Problema:
La busqueda AJAX cachea por query, pero no hay invalidacion clara al cambiar producto/precio/stock/imagen.

Evidencia:
- `inc/ajax/search-actions.php` usa transients `bsc_search_*`.
- Cache de sliders/menu se limpia en otros hooks, pero busqueda/catalogo necesita politica propia.

Archivos a inspeccionar:
- `inc/ajax/search-actions.php`
- `inc/setup/theme-setup.php`
- `inc/admin/product-covers.php`
- `admin/bsc-products-page.php`

Implementacion minima:
- Agregar version key global para busqueda.
- Incrementar version en `save_post_product`, cambios de terminos, stock/precio.
- TTL razonable y cache key con contexto necesario.

Criterios de aceptacion:
- Cambiar titulo/precio/imagen se refleja tras guardar.
- Cache no queda stale por horas.

QA:
- Buscar producto, editar precio/titulo, buscar de nuevo.

Riesgo:
Medio. Cache stale confunde a clientes.

## BSC-RM-022 - Proteger preview de emails en produccion

Prioridad: P1
Area: Seguridad, privacidad

Problema:
Los previews de email son utiles, pero deben estar limitados a admin/capability, noindex y sin datos reales innecesarios.

Evidencia:
- `emails/bsc-email-previews.php` se carga globalmente desde `functions.php`.

Archivos a inspeccionar:
- `emails/bsc-email-previews.php`
- `emails/bsc-email-helpers.php`
- `functions.php`

Implementacion minima:
- Requerir capability alta.
- Agregar nonce si hay acciones.
- Usar datos fake por defecto.
- Header noindex/noarchive.

Criterios de aceptacion:
- Guest no puede ver preview.
- Usuarios sin capability no pueden ver preview.
- No se filtra informacion de pedidos reales por URL predecible.

QA:
- Abrir preview como guest, subscriber, admin.

Riesgo:
Medio-alto. Emails pueden contener PII.

## BSC-RM-023 - Definir retencion y privacidad para datos de formularios

Prioridad: P1
Area: Privacidad, compliance

Problema:
Contacto, newsletter, creators y logs pueden guardar PII sin retencion ni export/delete.

Evidencia:
- Newsletter/creators guardan options.
- Contacto envia emails.
- Logs de stock/puntos guardan admin/user/order metadata.

Archivos a inspeccionar:
- `inc/ajax/newsletter-actions.php`
- `inc/ajax/creator-actions.php`
- `emails/*`
- `includes/class-bsc-stock.php`
- `plugins/bubble-points/includes/store.php`

Implementacion minima:
- Documentar que se guarda, por cuanto tiempo y donde.
- Agregar delete/export si aplica.
- Evitar guardar IP cruda; usar hash si se necesita abuso/rate limit.

Criterios de aceptacion:
- Politica tecnica de PII documentada.
- Admin puede borrar datos de creators/newsletter si se solicita.

QA:
- Crear y borrar un registro de prueba.

Riesgo:
Medio. Datos personales sin control elevan riesgo legal/operativo.

## BSC-RM-024 - Revisar hardcoded localhost/URLs absolutas

Prioridad: P1
Area: Deploy, SEO, portability

Problema:
El contenido de ejemplo y algunos snippets usan `http://bsc.local`. URLs absolutas pueden colarse a produccion.

Evidencia:
- La peticion del usuario trae HTML con `http://bsc.local`.
- Backlog existente menciona hardcoded localhost.

Archivos a inspeccionar:
- `components/**/*.php`
- `page-*.php`
- `sass/**/*.scss`
- `js/**/*.js`
- Database/content via WP admin si aplica

Implementacion minima:
- Grep para `bsc.local`, `localhost`, `127.0.0.1`, dominios viejos.
- Reemplazar en codigo por helpers `home_url`, `site_url`, `get_permalink`.
- Documentar si hay contenido en DB que requiere search-replace.

Criterios de aceptacion:
- Codigo versionado no contiene URLs locales salvo fixtures o tests.
- Navegacion/product cards usan URLs dinamicas.

QA:
- Smoke en entorno local y staging con dominio distinto.

Riesgo:
Medio. Links rotos en produccion afectan ventas/SEO.

## BSC-RM-025 - Auditar WooCommerce templates contra version instalada

Prioridad: P1
Area: Compatibilidad WooCommerce

Problema:
Hay muchos overrides WooCommerce con versiones distintas. Si WooCommerce actualizo templates, pueden faltar hooks, seguridad o markup esperado.

Evidencia:
- `woocommerce/cart/cart-totals.php` version 2.3.6.
- `woocommerce/checkout/review-order.php` version 5.2.0.
- Otros templates van de 2.0.0 a 9.9.0.

Archivos a inspeccionar:
- `woocommerce/**/*.php`
- WooCommerce System Status templates

Implementacion minima:
- Comparar cada override contra version WooCommerce instalada.
- Clasificar: actualizado, necesita merge, puede eliminarse.
- Mantener solo overrides realmente custom.

Criterios de aceptacion:
- No hay templates critical outdated sin decision.
- Hooks requeridos por WooCommerce siguen presentes.

QA:
- Cart, checkout, my-account, lost password, payment, thankyou.

Riesgo:
Medio-alto. Overrides viejos rompen flujos despues de updates.

## BSC-RM-026 - Endurecer redemption de Bubble Points y cupones generados

Prioridad: P1
Area: Fidelizacion, cupones, abuso

Problema:
Redimir puntos crea cupones dinamicos. Faltan guardrails como expiracion, trazabilidad, limites por usuario y limpieza de cupones no usados.

Evidencia:
- `plugins/bubble-points/ajax/redeem.php` crea `shop_coupon` y descuenta puntos despues.
- Coupon tiene `usage_limit` 1 y `customer_email`, pero no expiry ni meta de owner clara para auditoria.

Archivos a inspeccionar:
- `plugins/bubble-points/ajax/redeem.php`
- `plugins/bubble-points/includes/store.php`
- `inc/ajax/coupons-actions.php`

Implementacion minima:
- Agregar expiry razonable a cupones de puntos.
- Guardar meta `_bsc_bp_user_id`, `_bsc_bp_points_used`, `_bsc_bp_ledger_id`.
- Rate limit redemption.
- Job para limpiar cupones expirados/no usados si negocio lo acepta.

Criterios de aceptacion:
- Cada cupon de puntos es trazable a usuario/ledger.
- No se pueden generar cupones en loop sin limite.

QA:
- Redimir, aplicar cupon, intentar reutilizar, revisar meta/admin.

Riesgo:
Medio-alto. Puede generar descuentos indebidos.

## BSC-RM-027 - Reconciliacion de stock y auditoria admin

Prioridad: P1
Area: Inventario

Problema:
Hay logs de movimiento, pero se necesita reconciliacion operativa: stock actual vs movimientos vs ordenes.

Evidencia:
- `includes/class-bsc-stock.php` registra movimientos.
- `admin/bsc-products-page.php` muestra stock/log.
- `admin/bsc-reports-page.php` reporta stock.

Archivos a inspeccionar:
- `includes/class-bsc-stock.php`
- `admin/bsc-products-page.php`
- `admin/bsc-reports-page.php`

Implementacion minima:
- Reporte de diferencias: stock meta vs sumatoria esperada.
- Export de movimientos por producto/rango.
- Alerta para stock negativo/imposible aunque SQL lo prevenga.

Criterios de aceptacion:
- Admin puede auditar un producto end-to-end.
- Diferencias quedan visibles.

QA:
- Ajuste manual, venta web, venta showroom, rollback/fallo simulado.

Riesgo:
Medio. Sin reconciliacion, errores pequenos se acumulan.

## BSC-RM-028 - Centralizar respuesta AJAX y manejo de errores

Prioridad: P1
Area: Calidad, UX, soporte

Problema:
Algunos endpoints devuelven `wp_send_json_success`, otros devuelven fragments de Woo directamente. El JS tiene que adivinar forma de respuesta.

Evidencia:
- `inc/ajax/cart-actions.php` usa `WC_AJAX::get_refreshed_fragments()` en rutas de exito.
- `js/cart.js` trata success false y fragments sin success.

Archivos a inspeccionar:
- `inc/ajax/cart-actions.php`
- `inc/ajax/coupons-actions.php`
- `inc/ajax/review-summary-actions.php`
- `js/cart.js`
- `js/coupons.js`

Implementacion minima:
- Documentar contrato por endpoint.
- Para endpoints custom, responder siempre `{ success, data, message }`.
- Encapsular compatibilidad con fragments en helper JS.

Criterios de aceptacion:
- JS no depende de formas ambiguas.
- Errores se muestran sin tocar DOM antes de confirmar exito.

QA:
- Add, remove, increment, decrement, coupon apply/remove.

Riesgo:
Medio. Cambiar contrato puede romper flows si no se hace por etapas.

---

# P1 - UI, UX y accesibilidad

## BSC-RM-029 - Accesibilidad de mega menu y profile dropdown

Prioridad: P1
Area: Navegacion, accesibilidad

Problema:
El menu desktop depende de hover. El profile dropdown no tiene null guard y puede lanzar error si faltan nodos.

Evidencia:
- `js/navigation.js` registra hover en `.bsc__menu-nav--button`.
- `profileBtn.addEventListener` se llama sin verificar `profileBtn`/`dropdown`.

Archivos a inspeccionar:
- `js/navigation.js`
- `components/header.php`
- `components/header/header-menu-config.php`
- Sass de header/menu

Implementacion minima:
- Guard para nodos faltantes.
- Soporte focus/blur, Escape, aria-expanded y aria-controls.
- Navegacion por teclado en menu.

Criterios de aceptacion:
- No hay error JS en paginas sin profile button.
- Menu abre con teclado y cierra con Escape.
- Hover sigue funcionando.

QA:
- Desktop keyboard only.
- Mobile no queda afectado.

Riesgo:
Medio. Navegacion es flujo critico.

## BSC-RM-030 - Unificar menu mobile/desktop y rutas de imagenes del mega menu

Prioridad: P1
Area: Navegacion, UX

Problema:
El menu desktop y mobile tienen estructuras separadas y riesgo de divergencia. Las imagenes del menu deben apuntar a su categoria root correcta.

Evidencia:
- Se ajustaron links de `.bsc__menu-nav-image` recientemente.
- Backlog existente menciona unificar mobile/desktop menu.

Archivos a inspeccionar:
- `components/header.php`
- `components/header/header-menu-config.php`
- `js/mobile-menu.js`
- `sass/components/header/*`

Implementacion minima:
- Una sola fuente de configuracion para grupos/categorias/imagenes.
- Renderers separados solo para markup responsive.
- Tests smoke para links de imagenes y links de texto.

Criterios de aceptacion:
- Cambiar una categoria actualiza desktop y mobile.
- Imagen de Skin Care lleva a `/product-category/group-skin-care/` y equivalentes.

QA:
- Click en imagenes de cada grupo.
- Mobile menu open/close/search/account/cart.

Riesgo:
Medio. Menu roto impacta descubrimiento de productos.

## BSC-RM-031 - Busqueda con keyboard navigation, abort y aria completo

Prioridad: P1
Area: UX, accesibilidad, performance

Problema:
La busqueda renderiza resultados con role option, pero no tiene listbox completo, active descendant, arrow navigation ni abort controller para fetch en carrera.

Evidencia:
- `js/search.js` usa fetch y cache session-only.
- Resultados tienen `role="option"` pero no se observa control de flechas.
- Requests antiguos pueden resolver despues de requests nuevos.

Archivos a inspeccionar:
- `js/search.js`
- `inc/ajax/search-actions.php`
- `components/header.php`

Implementacion minima:
- Usar AbortController o sequence id.
- Agregar ArrowUp/ArrowDown/Home/End/Escape/Enter.
- Roles `combobox`, `listbox`, `aria-expanded`, `aria-activedescendant`.
- Mensajes de loading/empty con `aria-live`.

Criterios de aceptacion:
- Busqueda usable sin mouse.
- No muestra resultados stale al teclear rapido.
- Mobile y desktop mantienen comportamiento.

QA:
- Teclado completo, lector basico, resize desktop/mobile.

Riesgo:
Medio. Busqueda afecta conversion.

## BSC-RM-032 - Formularios de cuenta robustos en Safari/iPhone

Prioridad: P1
Area: Cuenta, responsive, iOS

Problema:
Los selects de cuenta se vieron delgados/diferentes en Safari/iPhone. Hay que formalizar el fix y cubrir todos los inputs similares.

Evidencia:
- Ruta reportada: `/my-account/edit-account/`.
- Archivo custom: `woocommerce/myaccount/form-edit-account.php`.
- JS: `js/account-edit.js`.

Archivos a inspeccionar:
- `woocommerce/myaccount/form-edit-account.php`
- `sass/pages/_page-account.scss`
- `js/account-edit.js`
- Visual snapshots cuenta

Implementacion minima:
- CSS cross-browser para input/select/date.
- `appearance` consistente sin romper date picker.
- Min height y line-height estable.
- Visual WebKit/iPhone gate.

Criterios de aceptacion:
- Selects no se ven colapsados en iPhone.
- Labels y columnas no se superponen.
- Guardar datos sigue funcionando.

QA:
- Safari/iPhone real.
- Chrome desktop y mobile.

Riesgo:
Medio. Cuenta es zona visible para cliente y usuarios recurrentes.

## BSC-RM-033 - Product gallery y cards sin stretching ni layout shift

Prioridad: P1
Area: Producto, catalogo, UI

Problema:
Backlog existente menciona galeria estirada. Cards y sliders deben tener dimensiones estables para evitar saltos.

Evidencia:
- Hay ticket previo de gallery stretching.
- Product recommendations/cards se reutilizan en producto y carrito vacio.

Archivos a inspeccionar:
- `woocommerce/single-product.php`
- `components/products/card.php`
- `components/products/slider.php`
- Sass de product details/cards
- `js/swiper-init.js`

Implementacion minima:
- Definir aspect-ratio para imagen principal, thumbs y cards.
- `object-fit` correcto.
- Skeleton/placeholder estable si imagen demora.

Criterios de aceptacion:
- No hay imagenes deformadas.
- Cards no cambian alto al cargar precio/rating.
- Desktop y mobile estables.

QA:
- Productos con imagen vertical, horizontal, sin imagen, galeria larga.

Riesgo:
Medio. Afecta percepcion de calidad.

## BSC-RM-034 - Estandarizar empty states y mensajes de exito/error

Prioridad: P1
Area: UX, contenido

Problema:
Cada formulario/notificacion usa estructura distinta. El caso Bubble Creators fue ajustado manualmente, pero se necesita patron comun.

Evidencia:
- `js/coupons.js` crea toast complejo.
- `js/contact.js`, `js/newsletter.js`, `js/creator-apply.js` usan notices propios.
- Empty cart ahora incluye recomendaciones.

Archivos a inspeccionar:
- `page-cart.php`
- `js/contact.js`
- `js/newsletter.js`
- `js/creator-apply.js`
- `js/coupons.js`
- Sass de notices/buttons

Implementacion minima:
- Crear componente CSS para notice inline/toast.
- Estados: success, error, warning, info.
- Role/aria-live consistente.
- Copys en espanol sin mojibake.

Criterios de aceptacion:
- Formularios comparten patron visual.
- Mensajes no tienen recuadros negros inesperados ni emojis inconsistentes.
- Accesibles con screen reader.

QA:
- Contacto, creators, newsletter, cupon, checkout notice.

Riesgo:
Medio. Cambios visuales globales necesitan snapshots.

## BSC-RM-035 - Focus states visibles y controles tactiles de 44px

Prioridad: P1
Area: Accesibilidad, mobile UX

Problema:
Botones, chips, tabs, icon buttons y sliders deben tener focus visible y target tactil suficiente.

Evidencia:
- UI tiene muchos botones custom: menu, search, add-to-cart, qty, coupons, tabs, filters.

Archivos a inspeccionar:
- `sass/**/*.scss`
- `js/tabs.js`
- `js/category-filter.js`
- `js/filters.js`
- `js/cart.js`

Implementacion minima:
- Audit de focus visible por componente.
- Min target 44x44 en mobile donde aplica.
- No depender solo de color.

Criterios de aceptacion:
- Navegacion con Tab es visible.
- Botones tactiles no quedan pequenos en iPhone.

QA:
- Keyboard desktop.
- Mobile tap test.

Riesgo:
Medio. Puede cambiar spacing visual.

## BSC-RM-036 - Revisar mobile filters y category tabs

Prioridad: P1
Area: Catalogo, mobile UX

Problema:
Filtros y subcategorias usan modales/chips/client-side filtering. Necesitan validacion de estado, scroll lock, focus trap y retorno de foco.

Evidencia:
- `js/category-filter.js` implementa modal mobile.
- `js/filters.js` maneja filters y AJAX.

Archivos a inspeccionar:
- `js/category-filter.js`
- `js/filters.js`
- `plugins/bsc-catalog/classes/*`
- Sass de catalog/category filters

Implementacion minima:
- Focus trap en modal mobile.
- Escape/click outside consistente.
- Estado activo visible y persistente.
- Evitar que filtros abran/cambien layout inesperadamente.

Criterios de aceptacion:
- Usuario mobile puede filtrar y cerrar sin perder contexto.
- No hay scroll de fondo cuando modal esta abierto.

QA:
- Catalogo, categoria root, subcategoria, no-results.

Riesgo:
Medio. Catalogo es flujo principal.

## BSC-RM-037 - Contenido en espanol consistente y sin mezcla de idiomas

Prioridad: P1
Area: Copy, i18n

Problema:
Hay textos en ingles, espanol sin tildes, espanol corrupto y labels mezclados. Esto afecta percepcion y accesibilidad.

Evidencia:
- Woo templates conservan strings ingleses.
- JS usa labels como `Shopping Cart with`.
- Algunas cadenas tienen mojibake.

Archivos a inspeccionar:
- `js/*.js`
- `woocommerce/**/*.php`
- `components/**/*.php`
- `page-*.php`

Implementacion minima:
- Inventario de strings visibles.
- Definir si se usa `__()`/`esc_html__()` con text domain o strings directas.
- Corregir labels, aria labels y botones.

Criterios de aceptacion:
- Flujos principales estan en espanol coherente.
- Aria labels tambien estan localizados.

QA:
- Header, footer, carrito, checkout, cuenta, emails.

Riesgo:
Medio. Cambiar strings puede afectar snapshots.

---

# P1 - Performance y estabilidad

## BSC-RM-038 - Conditional enqueue y reducir JS global

Prioridad: P1
Area: Performance frontend

Problema:
Algunos scripts se cargan globalmente aunque solo se usan en header o sliders. Navigation/search son globales, pero deben ser pequenos, robustos y sin errores.

Evidencia:
- `inc/scripts/enqueue-scripts.php` encola navigation, mobile menu, search y slider hint en todas las paginas.

Archivos a inspeccionar:
- `inc/scripts/enqueue-scripts.php`
- `js/navigation.js`
- `js/search.js`
- `js/bsc-slider-mobile-hint.js`
- `scripts/script_init.php`

Implementacion minima:
- Auditar peso y dependencia de cada script global.
- Cargar scripts page-specific donde sea posible.
- Mantener header core sin errores y con guards.

Criterios de aceptacion:
- No hay JS global que falle si falta markup.
- Paginas simples no cargan scripts innecesarios pesados.

QA:
- Home, producto, carrito, checkout, contacto, cuenta.

Riesgo:
Medio. Desencolar mal puede romper interacciones.

## BSC-RM-039 - Optimizar imagenes y tamanos en cards/sliders/menu

Prioridad: P1
Area: Performance, UI

Problema:
Cards, sliders, menu y hero usan imagenes de producto/categoria. Si cargan tamanos originales o sin lazy/eager correcto, empeora LCP y scroll.

Evidencia:
- Product cards renderizan imagenes desde uploads.
- Menu usa imagenes clicables por categoria.
- Home usa hero swiper.

Archivos a inspeccionar:
- `components/products/card.php`
- `components/products/slider.php`
- `components/header/header-menu-config.php`
- `components/swiper.php`
- `front-page.php`

Implementacion minima:
- Usar `wp_get_attachment_image` con size, srcset y loading.
- LCP hero con `fetchpriority="high"` solo para imagen principal visible.
- Lazy para cards fuera de viewport.

Criterios de aceptacion:
- Imagenes tienen width/height.
- No hay CLS por imagenes.
- LCP mejora o no empeora.

QA:
- Lighthouse/PageSpeed local o WebPageTest staging.
- Visual snapshots.

Riesgo:
Medio. Cambiar sizes puede afectar nitidez.

## BSC-RM-040 - Revisar preconnect externo a fonts.cdnfonts.com

Prioridad: P1
Area: Performance, privacidad

Problema:
Se agrega preconnect a `https://fonts.cdnfonts.com`. Si no se usa realmente, crea conexion innecesaria y posible preocupacion de privacidad.

Evidencia:
- `inc/performance/frontend-cleanup.php` agrega preconnect.

Archivos a inspeccionar:
- `inc/performance/frontend-cleanup.php`
- `header.php`
- CSS/fonts en `sass` y assets

Implementacion minima:
- Confirmar si esa fuente se usa en produccion.
- Si no se usa, remover preconnect.
- Si se usa, alojar local o documentar dependencia.

Criterios de aceptacion:
- No hay preconnect externo innecesario.
- Fonts cargan sin FOIT severo.

QA:
- Network tab en home/producto.

Riesgo:
Bajo-medio.

## BSC-RM-041 - Performance de reportes de stock con paginacion real

Prioridad: P1
Area: Admin performance

Problema:
El reporte de stock carga todos los productos y metadatos en una consulta grande. En catalogos grandes puede ser lento.

Evidencia:
- `admin/bsc-reports-page.php` genera tabla completa y filtro client-side.

Archivos a inspeccionar:
- `admin/bsc-reports-page.php`
- `js/admin/bsc-reports.js`

Implementacion minima:
- Paginacion server-side.
- Busqueda server-side por SKU/titulo.
- KPIs agregados separados y cacheados.

Criterios de aceptacion:
- Reporte carga rapido con catalogo grande.
- Busqueda no requiere renderizar todo el catalogo.

QA:
- Dataset grande o seed.
- Ordenar, buscar, filtrar, exportar.

Riesgo:
Medio. Cambia admin UX.

## BSC-RM-042 - Reducir llamadas redundantes en checkout/cart fragments

Prioridad: P1
Area: Performance, checkout

Problema:
Despues de cambios de carrito se refrescan fragments, review summary y update_checkout. Puede generar multiples requests por click.

Evidencia:
- `js/cart.js` llama `refreshCartFragments`, `refreshReviewSummary` y `update_checkout`.
- `js/checkout.js` refresca resumen en `updated_checkout`.

Archivos a inspeccionar:
- `js/cart.js`
- `js/checkout.js`
- `inc/ajax/review-summary-actions.php`

Implementacion minima:
- Crear coordinador/debounce para refresh de checkout.
- Evitar doble refresh cuando Woo ya emite `updated_checkout`.
- Medir requests por accion antes/despues.

Criterios de aceptacion:
- Un cambio de cantidad no dispara requests duplicadas innecesarias.
- Totales siguen correctos.

QA:
- Network tab: add, plus, minus, remove, coupon.

Riesgo:
Medio-alto. Tocar checkout/cart requiere smoke completo.

## BSC-RM-043 - CSS source of truth y maps confiables

Prioridad: P1
Area: Build, CSS

Problema:
`style.css` y `style.css.map` pueden quedar fuera de sync con Sass. Esto causa diffs grandes y bugs que se arreglan en CSS compilado pero no en fuente.

Evidencia:
- Cambios recientes tocaron Sass y CSS compilado.
- Build Sass existe en `package.json`.

Archivos a inspeccionar:
- `sass/**/*.scss`
- `style.css`
- `style.css.map`
- `package.json`

Implementacion minima:
- Documentar comando unico de build CSS.
- Agregar check de CI/local que falla si Sass compilado cambia despues de build.
- No editar `style.css` manualmente salvo emergencia documentada.

Criterios de aceptacion:
- `npm run build` deja CSS estable.
- Diffs de CSS corresponden a Sass.

QA:
- Ejecutar build y comparar git diff.

Riesgo:
Medio. CSS desalineado genera regresiones invisibles.

---

# P2 - Calidad de codigo, pruebas y mantenibilidad

## BSC-RM-044 - Agregar PHP lint/static checks

Prioridad: P2
Area: Code quality PHP

Problema:
El lint actual parece centrado en JS/CSS/tests. PHP necesita gate para syntax, WordPress standards y patrones peligrosos.

Archivos a inspeccionar:
- `package.json`
- `composer.json` si existe
- `vendor/`
- `phpcs.xml` si existe

Implementacion minima:
- Agregar `php -l` sobre PHP del theme.
- Evaluar PHPCS WordPress Coding Standards en modo warning primero.
- Excluir vendor/node_modules.

Criterios de aceptacion:
- Syntax PHP rota falla antes de deploy.
- Nuevas lecturas `$_POST` directas quedan visibles.

QA:
- Introducir prueba controlada o verificar comando sobre repo actual.

Riesgo:
Medio. PHPCS completo puede generar mucho ruido; empezar incremental.

## BSC-RM-045 - Tests PHP para stock y Bubble Points

Prioridad: P2
Area: Tests backend

Problema:
Las zonas de mayor riesgo economico son stock y puntos. Hoy dependen mucho de QA manual.

Archivos a inspeccionar:
- `includes/class-bsc-stock.php`
- `plugins/bubble-points/includes/store.php`
- `plugins/bubble-points/ajax/redeem.php`
- `tests/`

Implementacion minima:
- Crear harness WP test o tests de integracion ligeros.
- Casos: no stock negativo, doble deduccion, ledger insert failure, insufficient points, redeem rollback.

Criterios de aceptacion:
- Tests automaticos cubren flows economicos principales.

QA:
- Ejecutar suite local.

Riesgo:
Medio. Setup WP tests puede tomar tiempo.

## BSC-RM-046 - Smoke tests para formularios publicos

Prioridad: P2
Area: QA automatizado

Problema:
Contacto, newsletter y Bubble Creators son leads importantes y hoy su validacion puede romperse sin smoke dedicado.

Archivos a inspeccionar:
- `tests/e2e/smoke/*.spec.js`
- `page-contact-us.php`
- `page-bubble-creators.php`
- `front-page.php`
- `js/contact.js`
- `js/newsletter.js`
- `js/creator-apply.js`

Implementacion minima:
- Tests con inputs vacios, invalidos y validos.
- Mock/spy de admin-ajax o validar respuesta si ambiente lo permite.
- Verificar mensajes sin mojibake.

Criterios de aceptacion:
- Suite falla si required de IG/TikTok deja de aplicarse.
- Contacto/newsletter muestran errores correctos.

QA:
- Ejecutar smoke.

Riesgo:
Bajo-medio.

## BSC-RM-047 - Smoke tests para roles/admin BSC

Prioridad: P2
Area: Seguridad admin, QA

Problema:
El access control BSC es custom. Necesita tests por rol para evitar fugas y bloqueos.

Archivos a inspeccionar:
- `includes/class-bsc-roles.php`
- `includes/class-bsc-permissions.php`
- `admin/bsc-access-page.php`
- `admin/bsc-admin-menu.php`
- `tests/e2e/smoke/admin-*`

Implementacion minima:
- Seed de usuarios por rol.
- Test: dashboard/pedidos/productos/reportes/showroom/config segun permisos.
- Verificar denial sin datos sensibles.

Criterios de aceptacion:
- Cada rol ve solo lo autorizado.

QA:
- Ejecutar smoke admin.

Riesgo:
Medio. Admin custom es sensible.

## BSC-RM-048 - Pruebas de checkout con matriz de edge cases

Prioridad: P2
Area: QA checkout

Problema:
Checkout tiene muchos edge cases: usuario guest/logged-in, shipping distinto, coupon, city reload, item removed, empty cart redirect.

Archivos a inspeccionar:
- `tests/e2e/smoke/checkout*.spec.js`
- `js/checkout.js`
- `js/cart.js`
- `js/coupons.js`

Implementacion minima:
- Matriz smoke minima:
  - guest checkout valido
  - logged-in checkout valido
  - coupon apply/remove
  - city/state change
  - remove last item on checkout redirects cart
  - submit missing required field

Criterios de aceptacion:
- Regresiones de checkout fallan automaticamente.

QA:
- Ejecutar smoke en Chromium y WebKit/device cuando posible.

Riesgo:
Medio. Tests pueden ser lentos/flaky si no se aislan datos.

## BSC-RM-049 - Definir seeds/datos de prueba estables

Prioridad: P2
Area: QA, DX

Problema:
Tests visuales/smoke dependen de productos, usuarios, stock y cupones. Si cambian datos reales, los tests flaquean.

Archivos a inspeccionar:
- `tests/`
- `plugins/bubble-points/seeds/demo1.php`
- herramientas Local/WP-CLI disponibles

Implementacion minima:
- Crear seed idempotente para productos/categorias/usuarios/cupones.
- Separar datos demo de produccion.
- Documentar reset local.

Criterios de aceptacion:
- Nuevo dev puede correr tests con datos conocidos.
- Snapshots no dependen de productos aleatorios.

QA:
- Reset local y correr smoke/visual.

Riesgo:
Medio. Seeds mal aislados pueden tocar contenido real si se ejecutan en entorno incorrecto.

## BSC-RM-050 - Auditoria de dependencias y assets externos

Prioridad: P2
Area: Seguridad, performance

Problema:
El theme usa npm packages, vendor PHP, FontAwesome, Swiper y posiblemente fonts externas. Se necesita inventario de versiones y vulnerabilidades.

Archivos a inspeccionar:
- `package.json`
- `package-lock.json`
- `vendor/`
- `scripts/script_init.php`
- `inc/performance/frontend-cleanup.php`

Implementacion minima:
- Ejecutar audit npm en modo reporte.
- Revisar vendor PHP y origen.
- Listar CDNs/assets externos.

Criterios de aceptacion:
- Dependencias criticas tienen version y uso documentado.
- No hay assets externos innecesarios.

QA:
- Network tab y comandos audit.

Riesgo:
Bajo-medio.

## BSC-RM-051 - Normalizar helpers de URL y escaping

Prioridad: P2
Area: Calidad, seguridad frontend

Problema:
El theme renderiza muchas URLs, HTML y atributos manualmente. Necesita convencion consistente para `esc_url`, `esc_attr`, `esc_html`, `wp_kses_post`.

Archivos a inspeccionar:
- `components/**/*.php`
- `page-*.php`
- `admin/*.php`
- `inc/bsc-url-helpers.php`

Implementacion minima:
- Audit de templates publicos primero.
- Crear checklist por PR/ticket.
- Corregir hallazgos donde haya output de datos usuario/producto.

Criterios de aceptacion:
- Outputs dinamicos principales estan escapados.
- No se introduce HTML no sanitizado desde user input.

QA:
- Revisar pagina con datos que contienen caracteres especiales.

Riesgo:
Medio. Escapar tarde puede cambiar markup esperado.

## BSC-RM-052 - Eliminar funciones duplicadas y acoplamientos de theme plugin

Prioridad: P2
Area: Arquitectura

Problema:
El theme carga "plugins" internos desde `plugins/*/index.php`. Es pragmatico, pero mezcla responsabilidades y puede crear orden de carga fragil.

Evidencia:
- `functions.php` auto-requiere `plugins/*/index.php`.
- Bubble Points define install hooks, admin, AJAX y views dentro del theme.

Archivos a inspeccionar:
- `functions.php`
- `plugins/bubble-points/index.php`
- `plugins/bsc-catalog/index.php`

Implementacion minima:
- Documentar contrato de modulos internos.
- Requerir archivos en orden explicito si el glob puede ser fragil.
- Evitar side effects pesados en load global.

Criterios de aceptacion:
- Orden de carga esta documentado.
- AJAX/admin/front hooks no dependen de casualidad del glob.

QA:
- Smoke de catalogo y Bubble Points.

Riesgo:
Medio. Cambiar orden puede romper hooks.

## BSC-RM-053 - Revisar clases JS globales y errores de consola

Prioridad: P2
Area: Frontend quality

Problema:
Errores JS silenciosos en una pagina pueden romper otros scripts globales. Hace falta gate de consola.

Evidencia:
- `js/navigation.js` puede lanzar error por nodos faltantes.
- `js/cart.js` y otros hacen `console.error` en fallos.

Archivos a inspeccionar:
- `tests/e2e/smoke/*`
- `js/*.js`

Implementacion minima:
- En Playwright, fallar smoke por console error no esperado.
- Whitelist explicita para errores externos conocidos.
- Agregar guards a scripts globales.

Criterios de aceptacion:
- Home/product/cart/checkout/account no generan console errors.

QA:
- Ejecutar smoke con console listener.

Riesgo:
Bajo-medio.

## BSC-RM-054 - Documentar flujo de deploy, rollback y cache

Prioridad: P2
Area: Operacion

Problema:
Hay release docs, pero los nuevos modulos de cache/routing/performance/admin necesitan pasos explicitos de deploy y rollback.

Archivos a inspeccionar:
- `ROADMAP_BSC.md`
- `inc/performance/frontend-cleanup.php`
- `inc/routing/frontend-routing.php`

Implementacion minima:
- Agregar seccion de cache clear, CSS build, DB migrations, roles, BP schema.
- Rollback por ticket.
- Verificacion post-deploy.

Criterios de aceptacion:
- Cualquier dev puede desplegar siguiendo checklist.
- Rollback no depende de memoria.

QA:
- Dry run local/staging.

Riesgo:
Bajo-medio.

## BSC-RM-055 - Crear matriz de compatibilidad mobile/responsive

Prioridad: P2
Area: UX QA

Problema:
Los bugs reportados son visuales/responsive. Se necesita matriz fija de viewports y paginas.

Archivos a inspeccionar:
- `tests/e2e/visual/*.spec.js`
- `playwright.config.js`

Implementacion minima:
- Viewports: 390x844, 430x932, 768x1024, 1024x768, 1366x768, 1440x900.
- Paginas: home, catalogo, producto, cart, checkout, cuenta, creators, contacto.
- Definir browser coverage.

Criterios de aceptacion:
- Visual suite cubre las paginas donde suele haber reclamos.

QA:
- Ejecutar visual y revisar diffs.

Riesgo:
Bajo-medio. Suite puede tardar mas.

---

# P3 - Producto, admin y mejoras operativas

## BSC-RM-056 - Admin CRM para Creators y Newsletter

Prioridad: P3
Area: Producto, operacion

Objetivo:
Dar al equipo una vista operativa de leads: estado, notas, export, filtros, fecha, redes sociales y origen.

Archivos a inspeccionar:
- `admin/bsc-admin-menu.php`
- `inc/ajax/creator-actions.php`
- `inc/ajax/newsletter-actions.php`

Acceptance:
- Admin ve solicitudes sin entrar a base de datos.
- Se puede exportar CSV.
- Se puede marcar estado: nuevo, contactado, aprobado, descartado.

## BSC-RM-057 - Dashboard operativo con filtros y permisos por rol

Prioridad: P3
Area: Admin UX

Objetivo:
Dashboard BSC con KPIs utiles por rango, alertas de stock, pedidos pendientes y accesos rapidos, todo respetando permisos.

Archivos a inspeccionar:
- `admin/bsc-admin-menu.php`
- `admin/bsc-reports-page.php`
- `includes/class-bsc-permissions.php`

Acceptance:
- Operador no ve finanzas si no esta autorizado.
- Admin puede filtrar KPIs por rango.

## BSC-RM-058 - Mejorar recomendaciones de productos

Prioridad: P3
Area: Conversion

Objetivo:
Recomendados en producto/carrito vacio deben usar estrategia clara: categoria, stock disponible, best sellers, margen o configuracion admin.

Archivos a inspeccionar:
- `components/products/slider.php`
- `components/products/card.php`
- `page-cart.php`
- `woocommerce/single-product.php`

Acceptance:
- No recomienda productos sin stock/no publicados.
- Cards usan el mismo componente en todas las superficies.

## BSC-RM-059 - Notificaciones y estados de pedido mas robustos

Prioridad: P3
Area: Operacion, emails

Objetivo:
Centralizar emails de tracking/followup, evitar dobles envios, permitir preview seguro y registrar envio por orden.

Archivos a inspeccionar:
- `emails/*`
- `admin/bsc-orders-page.php`
- `admin/bsc-followup-emails-page.php`

Acceptance:
- Cada email operativo tiene log de envio.
- Reintento manual no duplica accidentalmente sin confirmacion.

## BSC-RM-060 - Monitoreo post-launch

Prioridad: P3
Area: Operacion, observabilidad

Objetivo:
Tener senales basicas despues del deploy: errores PHP, errores JS, conversion checkout, fallos de email, pedidos fallidos, rate limit spikes.

Archivos a inspeccionar:
- `wp-config.php` del entorno, si aplica
- hosting logs
- `inc/security/security-hooks.php`
- `emails/*`

Acceptance:
- Existe dashboard o checklist diario post-launch.
- Incidentes tienen owner y procedimiento.

---

# Tickets existentes a consolidar

Estos items ya aparecen o se infieren de documentos/backlog existentes y deben mapearse a tickets nuevos o cerrarse con evidencia:

- BSC-001: helper WhatsApp/admin setting.
- BSC-002: hardcoded localhost.
- BSC-003: cantidad/quantity controls.
- BSC-005: iPad add-to-cart.
- BSC-009: mobile menu redesign.
- BSC-010: unificar mobile/desktop menu.
- BSC-014: product gallery stretching.
- BSC-045 a BSC-053: backups, monitoring, accesibilidad, i18n y checks pre-launch.

Regla: no mantener dos tickets para el mismo problema. Si un ticket anterior queda cubierto por `BSC-RM-*`, enlazarlo y cerrar el duplicado.

---

# Matriz de QA manual por area

Carrito y checkout:

- Agregar producto desde card, producto y recomendado.
- Incrementar, decrementar y remover.
- Carrito vacio muestra CTA y recomendados.
- Checkout con departamento/ciudad valido.
- Cupon aplicar/remover.
- Remover ultimo item desde checkout redirige correctamente.

Cuenta:

- Login, registro, lost password.
- Edit account en desktop y iPhone/Safari.
- Guardar nombre, cumpleanos, tipo de piel, sensibilidad y necesidades.
- Direcciones billing/shipping.

Admin:

- BSC Dashboard por rol.
- Pedidos: filtrar, cambiar estado, tracking, packing.
- Productos: stock bodega/tienda, log, editar producto.
- Showroom: buscar producto, registrar venta, rollback por fallo.
- Reportes: ventas, stock, export.
- Cupones: crear, eliminar, aplicar en frontend.
- Bubble Points: balance, historial, ajuste manual, redencion.

Frontend:

- Header menu desktop/mobile.
- Search desktop/mobile.
- Catalogo filtros/subcategorias.
- Product details, galeria, recomendados.
- Contacto, newsletter, Bubble Creators.
- Footer cart/floating cart.

Cross-browser:

- Chrome desktop.
- Chrome mobile viewport.
- Safari/iPhone real o WebKit.
- iPad/tablet para add-to-cart y menu.

---

# Definition of Done

Toda tarea de esta lista debe cerrar con:

1. Entrada actualizada en este `ROADMAP_BSC.md`; no crear ticket `.md` separado.
2. Archivos tocados limitados al objetivo.
3. Riesgos y rollback documentados en la seccion correspondiente.
4. `npm run lint` ejecutado o razon clara si no aplica.
5. `npm run test:e2e:smoke` ejecutado para cambios funcionales.
6. `npm run test:e2e:visual` ejecutado para cambios UI/layout.
7. QA manual especifico completado si toca checkout, cart, account, admin, stock, emails o puntos.
8. Estado de release actualizado dentro de este documento cuando el cambio entra a MVP2.
9. Commit limpio con resumen concreto.
