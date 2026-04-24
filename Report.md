# BSC Theme Audit Report (Plan-Only)

Fecha: 2026-04-21
Repositorio: `wp-bsc-theme-v2`
Modo: Auditoría completa + plan de ejecución (sin cambios funcionales)
Restricción principal: mantener UI/estilos **pixel-perfect**

## 1) Resumen ejecutivo

El theme es funcional y contiene mejoras recientes (cache de búsqueda, nonces en AJAX, control de roles, optimizaciones básicas). Sin embargo, hay deuda técnica y riesgos de producción en 4 frentes:

1. Seguridad operativa y superficie de ataque (`cicd/deploy.php`, permisos admin demasiado amplios en varias pantallas).
2. Performance frontend (CSS principal grande, imágenes pesadas, carga global de scripts, markup pesado en header/front-page).
3. Mantenibilidad (duplicación de lógica, acoplamiento alto, inline scripts/styles, configuración hardcoded).
4. Arquitectura SCSS parcialmente modular pero con mezcla de responsabilidades (tokens + componentes + utilidades mezclados).

Estado recomendado antes de lanzamiento: **NO GO** hasta cerrar hallazgos críticos y altos.

## 2) Alcance y método de auditoría

Áreas revisadas:
- PHP theme/WooCommerce/admin/AJAX/plugins embebidos
- JS frontend y admin
- SCSS/CSS y organización de estilos
- Activos estáticos (imágenes)
- Flujo de permisos y seguridad
- Preparación para producción

Métricas observadas:
- PHP: 174 archivos / ~16,056 LOC
- JS: 17 archivos / ~1,401 LOC
- SCSS: 67 archivos / ~16,473 LOC
- `style.css`: 323,589 bytes (compilado expandido, no minimizado)
- `!important`: 139 ocurrencias
- Inline style attributes en PHP: 388 ocurrencias
- Inline `<script>` en PHP: 13 ocurrencias
- Imágenes muy pesadas: hasta ~9.71 MB por archivo

## 3) Comportamiento actual (estado base)

1. Theme custom sobre WordPress + WooCommerce con lógica importante en `functions.php`, `inc/`, `admin/`, `components/`.
2. Catálogo, carrito, checkout y búsquedas usan endpoints AJAX propios con nonces compartidos.
3. Header/navigation altamente custom, con estructura hardcoded extensa y variantes desktop/mobile.
4. SCSS compila a `style.css` monolítico; hay tokens, mixins y componentes, pero sin separación estricta por capas.
5. Existen módulos operativos internos (órdenes, stock, showroom, reportes, cupones, Bubble Points) dentro del theme.
6. Hay medidas de hardening ya aplicadas (deshabilitar XML-RPC, ocultar generator, límites de login, quitar emojis).
7. Hay múltiples vistas admin con CSS/JS inline y consultas de alto costo (`limit => -1`) en dashboard/reportes.
8. El tema incluye integraciones de email, stock dual y estados custom de pedidos.

## 4) Hallazgos priorizados

## 4.1 Críticos (bloquean salida a producción)

1. `cicd/deploy.php` expone un endpoint de despliegue dentro del theme con `shell_exec("... git pull")`.
- Riesgo: ejecución remota operativa/abuso de webhook, despliegues no controlados.
- Observación: si `GITHUB_WEBHOOK_SECRET` no está definido, el esquema de firma queda débil por configuración insegura.

2. Permisos admin demasiado amplios en páginas sensibles (`read` en submenús como pedidos/productos/reportes).
- Riesgo: usuarios con `read` + rutas conocidas pueden alcanzar pantallas no previstas (aunque haya controles adicionales en callbacks).
- Afecta principio least privilege.

3. Bug en redención Bubble Points: uso de variable antes de definir.
- Archivo: `plugins/bubble-points/ajax/redeem.php`
- `coupon_value` se usa en metadata del ledger antes de calcularse.
- Riesgo: inconsistencias de trazabilidad, warnings y datos incompletos.

## 4.2 Altos

1. Payload frontend alto por CSS e imágenes:
- `style.css` no minificado (323 KB).
- Varias imágenes de 2–10 MB en `images/shop` y `images/product-category`.
- Impacto: LCP/TBT/CLS en móvil y redes lentas.

2. Header y navegación con acoplamiento/hardcoding extremo.
- Archivo: `components/header.php` muy grande y con gran cantidad de rutas/strings hardcodeadas.
- Impacto: difícil mantenimiento, alta probabilidad de regresiones, duplicación desktop/mobile.

3. Duplicación de lógica de guardado de perfil WooCommerce.
- `inc/functions_bsc.php` y `inc/setup/theme-setup.php` guardan campos similares con reglas distintas.
- Impacto: inconsistencias de datos y debugging complejo.

4. Uso intensivo de inline styles/scripts en admin y algunas vistas frontend.
- Impacto: menor cacheabilidad, menor reusabilidad y mayor deuda de diseño.

5. Dashboard/reportes con consultas potencialmente costosas (`wc_get_orders` con `limit => -1`).
- Impacto: degradación en tiendas con volumen alto.

## 4.3 Medios

1. SCSS con arquitectura incompleta para DRY/KISS/SOLID:
- tokens y tipografías mezclan variables + estilos concretos.
- mixins contienen componentes visuales (no solo utilidades).
- alto uso de `!important` (139).

2. Carga de scripts globales mejorable:
- `navigation.js`, `mobile-menu.js`, `search.js`, `cart.js` cargan globalmente.
- Algunas páginas no usan todos esos scripts.

3. `switch_to_locale('es_ES')` en `init` de forma global.
- Impacto: side effects y sobrecarga innecesaria en requests no relacionados.

4. Hardcoded URLs y enlaces absolutos internos en varios templates.
- Impacto: portabilidad limitada entre ambientes.

5. Archivo `style.scss` con metadata y fuentes no alineadas con estado real de proyecto (versionado/requisitos heredados de underscore).

## 4.4 Bajos

1. Comentarios/strings con encoding inconsistente en algunos archivos.
2. Año hardcoded en footer.
3. Inconsistencias menores de nomenclatura y estilo entre módulos.

## 5) Auditoría SCSS/CSS (objetivo: refactor sin cambio visual)

Situación actual:
- Existe modularidad base (`tokens/`, `mixins/`, `components/`, `pages/`), pero no estricta.
- Hay estilos utilitarios y de componentes mezclados.
- Sobreuso de `!important` para resolver especificidad.

Objetivo de arquitectura:
- `settings/` (tokens puros: colores, spacing, tipografía, z-index, breakpoints).
- `tools/` (mixins/functions sin side effects visuales).
- `generic/` (reset/base/global).
- `elements/` (HTML element defaults).
- `objects/` (layouts reutilizables).
- `components/` (bloques aislados BEM).
- `utilities/` (helpers atómicos controlados).
- `pages/` (solo overrides de composición).

Reglas de refactor:
1. Cero cambio visual: snapshot visual antes/después (desktop/mobile).
2. Mover constantes hardcoded a tokens CSS/SCSS.
3. Reducir `!important` vía arquitectura de especificidad y orden de capas.
4. Unificar breakpoints y spacing scale.
5. Reemplazar duplicación de bloques por mixins/placeholder selectors donde aplique.

## 6) Seguridad (plan de hardening)

Prioridad inmediata:
1. Retirar `cicd/deploy.php` del docroot de theme o bloquear por servidor.
2. Exigir capability granular por página/acción (`manage_woocommerce`, `edit_shop_orders`, etc.).
3. Revisión de todos los handlers `wp_ajax_*` con checklist:
- nonce
- capability (si aplica)
- sanitización entrada
- escape salida
- rate limit (acciones públicas)
4. Centralizar validación de permisos admin para BSC panel.
5. Desactivar ejecución de utilidades CI/CD desde el theme en producción.

## 7) Performance (plan sin cambiar pixel-perfect)

Backend:
1. Reemplazar consultas `limit => -1` por agregaciones/cached counters.
2. Cachear KPIs admin con invalidación dirigida por eventos.
3. Reducir recomputaciones de shipping/rates y limitar hooks redundantes.

Frontend:
1. Minificar CSS/JS producción (mantener source maps opcionales por entorno).
2. Conditional loading por plantilla/tipo de página.
3. Optimizar imágenes estáticas críticas (WebP/AVIF + tamaños responsive).
4. Reducir HTML inline pesado (SVG repetidos y bloques duplicados).
5. Medir Core Web Vitals antes/después (LCP/CLS/INP).

## 8) Refactor PHP/JS (DRY/KISS/SOLID)

1. Separar dominio por módulos:
- `Domain/Cart`, `Domain/Checkout`, `Domain/Orders`, `Domain/Stock`, `Domain/Search`, `Domain/Admin`.
2. Mover handlers AJAX a clases por contexto con contratos claros.
3. Eliminar duplicaciones (ej. guardado de perfil, helpers de envío, rendering repetido).
4. Reducir lógica en templates: templates solo presentación.
5. Estandarizar respuestas JSON y manejo de errores frontend.

## 9) Plan de tickets para lanzamiento (1 ticket = 1 entrega)

### TICKET BSC-AUD-001 — Baseline y seguridad crítica
- Objetivo: cerrar riesgos críticos de explotación y acceso.
- Contexto: bloqueantes de producción.
- Archivos objetivo: `cicd/deploy.php`, `admin/*.php`, `includes/class-bsc-permissions.php`, handlers AJAX.
- Implementación mínima:
  - eliminar/bloquear endpoint deploy en runtime web
  - ajustar capabilities por pantalla
  - corregir bug Bubble Points `coupon_value`
- Aceptación:
  - no existe ejecución remota desde theme
  - usuarios no autorizados no acceden a páginas/acciones sensibles
  - redención genera ledger consistente
- QA manual:
  - pruebas por rol (admin/employee/operator/customer)
  - prueba de redención y creación de cupón
- Rollback:
  - feature flags o revert por commit único

### TICKET BSC-AUD-002 — Performance estático sin cambio visual
- Objetivo: bajar payload y mejorar tiempos de carga.
- Contexto: mobile UX + Core Web Vitals.
- Archivos objetivo: `style.css` pipeline, `images/*`, `inc/scripts/enqueue-scripts.php`, `components/front-page/header`.
- Implementación mínima:
  - build producción minificado
  - optimización de imágenes pesadas
  - carga condicional de scripts
- Aceptación:
  - reducción medible de KB transferidos
  - sin variación visual pixel (baseline snapshots)
- QA manual:
  - home, shop, product, cart, checkout desktop/mobile
- Rollback:
  - conservar assets previos versionados

### TICKET BSC-AUD-003 — SCSS refactor estructural (pixel-perfect)
- Objetivo: ordenar SCSS con reusabilidad y consistencia.
- Contexto: deuda técnica alta en estilos.
- Archivos objetivo: `sass/**`
- Implementación mínima:
  - arquitectura por capas
  - tokens/mixins centralizados
  - reducción progresiva de `!important`
- Aceptación:
  - misma UI (comparación visual)
  - menor duplicación y menor complejidad de cascada
- QA manual:
  - smoke visual de componentes críticos
- Rollback:
  - branch dedicada + revert total de ticket

### TICKET BSC-AUD-004 — Refactor funcional PHP/JS
- Objetivo: reducir acoplamiento y duplicaciones.
- Contexto: mantenibilidad y velocidad de desarrollo.
- Archivos objetivo: `functions.php`, `inc/**`, `js/**`, `components/**`.
- Implementación mínima:
  - consolidar hooks duplicados
  - extraer clases/servicios por dominio
  - normalizar handlers AJAX
- Aceptación:
  - paridad funcional completa
  - menor complejidad ciclomática y duplicación
- QA manual:
  - flujos E2E (catalogo?carrito?checkout?cuenta)
- Rollback:
  - revert por ticket

### TICKET BSC-AUD-005 — Release hardening y producción
- Objetivo: checklist final de despliegue seguro.
- Contexto: salida controlada a producción.
- Archivos objetivo: pipeline/build/docs.
- Implementación mínima:
  - checklist pre-release
  - plan de monitoreo y rollback
  - documentación operativa
- Aceptación:
  - release checklist completo
  - métricas de salud post-release validadas
- QA manual:
  - smoke completo en staging y post-deploy
- Rollback:
  - rollback documentado por ventana de tiempo

## 10) Checklist de QA manual (obligatorio antes de GO LIVE)

1. Home (slider, tabs favoritos, newsletter, buscador).
2. Header desktop/mobile (menús, búsqueda, perfil, cierre de sesión).
3. Catálogo/filtros/categorías y paginación.
4. Product page (galería, add-to-cart touch iPad/mobile).
5. Cart (qty/badge sync, remove, cupones).
6. Checkout (ciudad/departamento, shipping dinámico, pago).
7. My Account (orders, edit-account, direcciones, bubble points).
8. Admin BSC por rol (operator/employee/admin).
9. Emails transaccionales (confirmado, enviado, etc.).
10. Seguridad básica (nonces, permisos, rutas sensibles, no debug output).

## 11) Criterios GO / NO-GO

GO solo si:
- 0 hallazgos críticos abiertos
- 0 hallazgos altos sin plan de mitigación activo
- Paridad visual confirmada en baseline pixel-check
- Flujos WooCommerce críticos validados en staging
- Plan de rollback probado

NO-GO si:
- permanece `deploy.php` ejecutable en web
- permisos admin siguen en `read` para pantallas sensibles
- persisten errores funcionales en redención, carrito o checkout

## 12) Riesgos residuales

1. Acoplamiento histórico de lógica en templates puede hacer que algunos refactors requieran fases adicionales.
2. Dependencia de plugin de ciudades CO en shipping puede generar edge cases regionales.
3. Volumen futuro de datos (newsletter/creators en options) degradará admin si no se migra a storage adecuado.

## 13) Recomendación final

Secuencia recomendada para lanzar:
1. Cerrar críticos (BSC-AUD-001).
2. Ejecutar performance quick wins sin riesgo visual (BSC-AUD-002).
3. Refactor SCSS y arquitectura por tickets pequeños con visual regression (BSC-AUD-003 y BSC-AUD-004).
4. Hardening de release y monitoreo (BSC-AUD-005).

Con esta ruta, el theme puede llegar a producción con estilo intacto, mejor performance y seguridad significativamente más sólida.
