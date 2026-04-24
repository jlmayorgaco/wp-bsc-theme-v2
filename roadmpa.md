# BSC MVP 2.0 - Roadmap por Tickets

Fecha: 2026-04-21  
Proyecto: `wp-bsc-theme-v2`  
Fuente base: `MEGA_AUDIT_MVP2.md`

## 1. Horizonte y fases

1. Fase 0: Baseline y gobierno técnico (Semana 1)
2. Fase 1: Seguridad crítica (Semanas 1-2)
3. Fase 2: Performance quick wins (Semanas 2-3)
4. Fase 3: Primitives reutilizables (Semanas 3-5)
5. Fase 4: Refactor de organismos críticos (Semanas 5-8)
6. Fase 5: Refactor de lógica dominio/AJAX (Semanas 8-10)
7. Fase 6: Hardening SCSS architecture (Semanas 10-12)
8. Fase 7: Release controlado MVP 2.0 (Semana 12)

## 2. Tablero maestro de tickets

| ID | Fase | Prioridad | Esfuerzo | Dependencias | Estado |
|---|---|---|---|---|---|
| BSC-MVP2-000 | 0 | Alta | S | - | TODO |
| BSC-MVP2-001 | 1 | Crítica | S | BSC-MVP2-000 | TODO |
| BSC-MVP2-002 | 1 | Crítica | M | BSC-MVP2-000 | TODO |
| BSC-MVP2-003 | 1 | Alta | S | BSC-MVP2-000 | TODO |
| BSC-MVP2-010 | 2 | Alta | M | BSC-MVP2-001 | TODO |
| BSC-MVP2-011 | 2 | Alta | M | BSC-MVP2-001 | TODO |
| BSC-MVP2-012 | 2 | Alta | M | BSC-MVP2-001 | TODO |
| BSC-MVP2-013 | 2 | Media | M | BSC-MVP2-002 | TODO |
| BSC-MVP2-020 | 3 | Alta | M | BSC-MVP2-010 | TODO |
| BSC-MVP2-021 | 3 | Alta | M | BSC-MVP2-020 | TODO |
| BSC-MVP2-022 | 3 | Alta | M | BSC-MVP2-021 | TODO |
| BSC-MVP2-023 | 3 | Media | S | BSC-MVP2-020 | TODO |
| BSC-MVP2-024 | 3 | Media | S | BSC-MVP2-020 | TODO |
| BSC-MVP2-030 | 4 | Alta | L | BSC-MVP2-022 | TODO |
| BSC-MVP2-031 | 4 | Alta | L | BSC-MVP2-022 | TODO |
| BSC-MVP2-032 | 4 | Alta | M | BSC-MVP2-022 | TODO |
| BSC-MVP2-033 | 4 | Media | M | BSC-MVP2-022 | TODO |
| BSC-MVP2-040 | 5 | Alta | L | BSC-MVP2-031 | TODO |
| BSC-MVP2-041 | 5 | Alta | M | BSC-MVP2-040 | TODO |
| BSC-MVP2-042 | 5 | Media | M | BSC-MVP2-040 | TODO |
| BSC-MVP2-050 | 6 | Alta | L | BSC-MVP2-032 | TODO |
| BSC-MVP2-051 | 6 | Media | M | BSC-MVP2-050 | TODO |
| BSC-MVP2-060 | 7 | Crítica | M | BSC-MVP2-050 | TODO |
| BSC-MVP2-061 | 7 | Crítica | S | BSC-MVP2-060 | TODO |

## 3. Tickets detallados

## BSC-MVP2-000
Objetivo: Definir baseline visual/funcional para refactor seguro.  
Alcance: snapshots desktop/mobile + checklist E2E.  
Archivos: `docs/` (nueva documentación), checklists QA.  
Aceptación: baseline aprobado por negocio y tech.  
QA: Home, PLP, PDP, Cart, Checkout, Account, Admin BSC.  
Rollback: no aplica (documental).

## BSC-MVP2-001
Objetivo: Retirar superficie crítica de despliegue web.  
Alcance: `cicd/deploy.php` fuera de runtime público o bloqueado por servidor.  
Aceptación: endpoint inaccesible en producción.  
QA: test 403/404 y flujo de deploy alterno validado.  
Rollback: restaurar ruta solo en entorno controlado.

## BSC-MVP2-002
Objetivo: Endurecer permisos admin bajo least privilege.  
Alcance: `admin/*.php`, `includes/class-bsc-permissions.php`, `admin/bsc-admin-menu.php`.  
Aceptación: cada vista/acción exige capability correcta.  
QA: matriz por rol admin/employee/operator/customer.  
Rollback: revert del ticket.

## BSC-MVP2-003
Objetivo: Corregir inconsistencias críticas en Bubble Points redeem.  
Alcance: `plugins/bubble-points/ajax/redeem.php` y validaciones asociadas.  
Aceptación: redención consistente, sin warnings, ledger correcto.  
QA: redención feliz, redención insuficiente, cupón generado y aplicado.  
Rollback: revert del ticket.

## BSC-MVP2-010
Objetivo: Build frontend de producción optimizado.  
Alcance: minificación CSS/JS y versionado de assets.  
Archivos: `package.json`, pipeline de compilación, `inc/scripts/enqueue-scripts.php`.  
Aceptación: assets minificados en producción, sourcemaps controlados.  
QA: smoke visual completo y cache busting correcto.  
Rollback: volver al build anterior.

## BSC-MVP2-011
Objetivo: Reducir peso de imágenes estáticas críticas.  
Alcance: `images/shop/*`, `images/product-category/*`, placeholders.  
Aceptación: reducción sustancial de KB sin pérdida visual perceptible.  
QA: comparación visual pixel-check en páginas críticas.  
Rollback: restaurar assets previos versionados.

## BSC-MVP2-012
Objetivo: Carga condicional de scripts por contexto real.  
Alcance: `inc/scripts/enqueue-scripts.php`, `scripts/script_init.php`.  
Aceptación: solo se cargan scripts usados por plantilla/contexto.  
QA: funcionalidad sin regressions en search/cart/checkout/menu.  
Rollback: fallback a estrategia actual.

## BSC-MVP2-013
Objetivo: Bajar costo de consultas admin pesadas.  
Alcance: `admin/bsc-admin-menu.php`, reportes/dashboard, invalidación de transients.  
Aceptación: eliminación de patrones `limit => -1` donde aplique.  
QA: tiempos de carga admin mejorados en staging con data realista.  
Rollback: revert del ticket.

## BSC-MVP2-020
Objetivo: Crear primitive `Button` como estándar único.  
Alcance: consolidar variantes actuales de botón.  
Archivos: `sass/components`, `sass/mixins`, templates con botones.  
Aceptación: botón base + modifiers, sin cambio visual.  
QA: botones en card, auth, checkout, admin UI pública.  
Rollback: revert por módulo.

## BSC-MVP2-021
Objetivo: Crear primitives de tipografía (`Title`, `Text`, `Price`).  
Alcance: unificar `.bsc__title`, `.bsc__description`, estilos de precio/texto.  
Archivos: `sass/tokens/_typography.scss`, `sass/components/*`.  
Aceptación: estilos unificados, uso transversal.  
QA: home/shop/product/account.  
Rollback: revert del ticket.

## BSC-MVP2-022
Objetivo: Crear primitives de formulario (`FormField`, `Input`, `Select`, `Textarea`, estados).  
Alcance: checkout, account, contacto, newsletter.  
Archivos: `sass/components/forms/*`, templates Woo y componentes checkout.  
Aceptación: patrón reusable con errores/validación visual consistente.  
QA: formularios end-to-end.  
Rollback: revert del ticket.

## BSC-MVP2-023
Objetivo: Crear primitive `Image` (ratio/fallback/lazy).  
Alcance: cards, sliders, grillas de categorías, profile/brand blocks.  
Aceptación: wrapper reusable aplicado al menos en 3 organismos.  
QA: responsive + fallback sin saltos visuales.  
Rollback: revert del ticket.

## BSC-MVP2-024
Objetivo: Crear primitive `Badge/Notice`.  
Alcance: estados de orders, alerts de cupón, mensajes checkout/cart.  
Aceptación: sistema de estados consistente y reusable.  
QA: mensajes success/warn/error/info.  
Rollback: revert del ticket.

## BSC-MVP2-030
Objetivo: Refactor modular de Header/Nav con filosofía Product Card.  
Alcance: `components/header.php`, `components/header/*.class.php`, SCSS header.  
Aceptación: contratos claros por bloque + paridad visual total.  
QA: desktop/mobile/nav/search/profile flows.  
Rollback: toggle por versión de header.

## BSC-MVP2-031
Objetivo: Refactor modular de Checkout suite.  
Alcance: `components/checkout/*`, `woocommerce/checkout/*`, `js/checkout.js`, AJAX checkout.  
Aceptación: checkout estable, validaciones y shipping intactos.  
QA: checkout E2E múltiples escenarios.  
Rollback: revert del ticket.

## BSC-MVP2-032
Objetivo: Refactor Products suite (`card` patrón + slider + filters).  
Alcance: `components/products/*`, `js/filters.js`, `js/category-filter.js`, SCSS.  
Aceptación: reutilización efectiva y menor duplicación.  
QA: catálogo, categorías, filtros combinados, add-to-cart.  
Rollback: revert del ticket.

## BSC-MVP2-033
Objetivo: Refactor Orders + My Account blocks.  
Alcance: `components/orders/*`, `components/my-account/*`, Woo account templates.  
Aceptación: UI consistente, lógica desacoplada, cero regresión visible.  
QA: cuenta, pedidos, estados, direcciones.  
Rollback: revert del ticket.

## BSC-MVP2-040
Objetivo: Extraer servicios de dominio y adelgazar handlers AJAX.  
Alcance: `inc/ajax/*`, funciones de negocio en servicios por dominio.  
Aceptación: handlers = validar + delegar + responder.  
QA: cart/checkout/search/coupons/creator/contact endpoints.  
Rollback: revert por dominio.

## BSC-MVP2-041
Objetivo: Estandarizar respuestas JSON y manejo de errores frontend.  
Alcance: JS y endpoints AJAX unificados.  
Aceptación: contrato JSON común (status/message/data/code).  
QA: simulación de fallos red/nonce/permiso.  
Rollback: revert del ticket.

## BSC-MVP2-042
Objetivo: Consolidar duplicaciones funcionales detectadas.  
Alcance: guardado de cuenta duplicado, helpers repetidos, hooks redundantes.  
Aceptación: una sola fuente de verdad por responsabilidad.  
QA: account save, shipping rules, hooks críticos.  
Rollback: revert del ticket.

## BSC-MVP2-050
Objetivo: Migrar SCSS a arquitectura por capas completa.  
Alcance: settings/tools/generic/elements/objects/components/utilities/pages.  
Aceptación: orden claro de capas y menor complejidad de cascada.  
QA: regresión visual full-site.  
Rollback: revert por módulos.

## BSC-MVP2-051
Objetivo: Reducir `!important` y hardcodes de estilo.  
Alcance: top offenders en checkout/swiper/bubble-points/product pages.  
Aceptación: reducción medible de `!important` sin ruptura visual.  
QA: smoke visual en puntos críticos responsive.  
Rollback: revert del ticket.

## BSC-MVP2-060
Objetivo: Preparar release candidate MVP 2.0 con checklist GO/NO-GO.  
Alcance: freeze, QA final, documentación operativa, plan de monitoreo.  
Aceptación: 0 críticos, 0 altos sin mitigación activa.  
QA: suite completa end-to-end + visual checks.  
Rollback: procedimiento documentado y probado.

## BSC-MVP2-061
Objetivo: Despliegue controlado + verificación post-release.  
Alcance: rollout, observabilidad, hotfix window, cierre de release.  
Aceptación: estabilidad en producción y KPIs dentro de umbral.  
QA: smoke post-deploy inmediato + 24h seguimiento.  
Rollback: activación inmediata del plan si se rompe flujo crítico.

## 4. Priorización MVP (orden recomendado de ejecución)

1. BSC-MVP2-000
2. BSC-MVP2-001
3. BSC-MVP2-002
4. BSC-MVP2-003
5. BSC-MVP2-010
6. BSC-MVP2-011
7. BSC-MVP2-012
8. BSC-MVP2-020
9. BSC-MVP2-021
10. BSC-MVP2-022
11. BSC-MVP2-030
12. BSC-MVP2-031
13. BSC-MVP2-032
14. BSC-MVP2-040
15. BSC-MVP2-050
16. BSC-MVP2-060
17. BSC-MVP2-061

## 5. Regla de entrega por ticket

1. Objetivo + contexto confirmado.
2. Implementación mínima viable.
3. Criterios de aceptación cumplidos.
4. QA manual documentado.
5. Notas de rollback listas.
6. Commit limpio asociado al ticket.
