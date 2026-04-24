# BSC Mega Auditoría + Roadmap MVP 2.0

Fecha: 2026-04-21  
Proyecto: `wp-bsc-theme-v2`  
Documento: consolidado estratégico (auditoría + filosofía + inventario + plan de ejecución)

---

## 1) Objetivo de este documento

Consolidar en una sola fuente:
1. La auditoría técnica completa del theme.
2. La filosofía de construcción basada en `BSC_Products_Card`.
3. El inventario de componentes reutilizables actual.
4. Un roadmap ejecutable para llegar a **MVP 2.0** (rápido, seguro, mantenible, pixel-perfect).

---

## 2) Visión MVP 2.0

MVP 2.0 no es rediseño visual. Es una evolución técnica con estas metas:
1. Mantener apariencia y comportamiento actuales (pixel-perfect).
2. Incrementar velocidad (frontend + admin).
3. Subir seguridad y robustez operativa.
4. Convertir componentes en sistema reusable (DRY/KISS/SOLID).
5. Reducir deuda técnica para acelerar siguientes features.

---

## 3) Estado actual (baseline técnico)

Métricas observadas:
- PHP: ~174 archivos / ~16,056 LOC
- JS: ~17 archivos / ~1,401 LOC
- SCSS: ~67 archivos / ~16,473 LOC
- CSS final: `style.css` ~323 KB (expandido)
- `!important`: 139 ocurrencias
- Inline styles en PHP: ~388 ocurrencias
- Inline scripts en PHP: ~13 ocurrencias
- Imágenes estáticas pesadas: hasta ~9.71 MB por archivo

Conclusión de estado actual:
- El sistema funciona y tiene buenas bases en checkout/cart/search.
- Hay deuda alta en acoplamiento, permisos, payload y consistencia de componentes.
- No recomendado lanzar nuevas olas de features sin una capa de orden técnico.

---

## 4) Hallazgos consolidados de auditoría

## 4.1 Críticos

1. Endpoint de despliegue ejecutable desde theme (`cicd/deploy.php`) con `shell_exec`.
- Riesgo: superficie operativa sensible en runtime web.

2. Permisos admin con capacidades demasiado amplias en algunas pantallas/acciones.
- Riesgo: desviación de least privilege.

3. Inconsistencia funcional en Bubble Points redeem (uso de variable antes de definir en flujo de metadata).
- Riesgo: trazabilidad/consistencia de datos.

## 4.2 Altos

1. Alto peso frontend por CSS no optimizado para producción e imágenes grandes.
2. Header/nav con gran hardcoding y baja componibilidad.
3. Duplicación de lógica (ej. guardado de datos de cuenta en múltiples lugares).
4. Uso alto de estilos/scripts inline en admin y plantillas.
5. Consultas administrativas costosas con `limit => -1`.

## 4.3 Medios

1. Arquitectura SCSS parcialmente modular, no completamente por capas.
2. Scripts globales con margen de carga condicional por contexto.
3. Localización/hardcoded strings y enlaces absolutos en varias plantillas.

## 4.4 Bajos

1. Inconsistencias de encoding/comentarios.
2. Año hardcoded en algunas vistas.
3. Convenciones de naming mixtas entre módulos.

---

## 5) Filosofía oficial: patrón `BSC_Products_Card`

Tu `Product Card` se vuelve el estándar de ingeniería de UI para MVP 2.0.

Principios extraídos del patrón:
1. Responsabilidad clara del componente: `setProduct(...)` + `render_*()` + `render()`.
2. API explícita y estable: entradas definidas, salidas predecibles.
3. Fallbacks seguros: placeholder de imagen, marca fallback, control de casos edge.
4. Integración con negocio sin contaminar la vista: stock, tipo de producto, carrito.
5. Escapes/sanitización en salida HTML.
6. Composición modular: imagen, rating, título, marca, precio, CTA, cantidad.
7. Evolución incremental sin romper contrato visual.

### 5.1 Manifiesto “Product Card Philosophy” para todo componente

Todo nuevo componente reusable debe cumplir:
1. **Single Responsibility**: una pieza, un propósito.
2. **Stable Contract**: API de entrada/salida documentada.
3. **Defensive Rendering**: fallback y manejo de vacío/errores.
4. **Presentation First, Logic Isolated**: lógica en helpers/servicios, template limpio.
5. **No Surprise Side Effects**: no mutaciones ocultas fuera de contexto.
6. **Composable by Design**: debe poder usarse en páginas distintas.
7. **Pixel-Preserving Refactor**: ningún refactor rompe UI.

### 5.2 Estándar mínimo por componente (Definition of Done)

1. Tiene contrato de props/datos.
2. Tiene variante base + modifiers claros.
3. Usa tokens/mixins compartidos (no hardcode innecesario).
4. Tiene estado vacío/error/loading si aplica.
5. Tiene checklist QA visual y funcional.

---

## 6) Inventario reusable consolidado

## 6.1 Fundaciones

- Tokens: color, estructura, tipografía, tamaños.
- Mixins utilitarios: flex, botón, etc.
- Base global: resets/generic.

## 6.2 Átomos (reusables base)

- Button family: `.bsc__button`, `.bsc__button--outline`, `.bsc__button-add-to-cart`.
- Typography family: `.bsc__title`, `.bsc__description`, `.bsc__price`.
- Form controls base: `.bsc__field`, `.bsc__input`, `.bsc__label`.
- Icon wrappers y badge states (orders/coupons/alerts).

## 6.3 Moléculas

- Product Card (`components/products/card.php`).
- Quantity Controls (`.bsc__quantity-controls`, plus/minus/value).
- Search result item (renderizado desde `js/search.js`).
- Coupon blocks (applied/error/success).

## 6.4 Organismos

- Header/Nav system (`BSC_HeaderNav`, `BSC_MenuNav`, mobile sidebar).
- Footer + WhatsApp.
- Checkout suite: form/cart/summary/payment/coupons.
- Products suite: card/slider/filters.
- Orders suite: progress/table/zero-state.
- My Account suite.
- Bubble Points suite (perfil/modal/cupones/listado).

## 6.5 Comportamiento reusable (JS)

- Cart engine (`js/cart.js`).
- Checkout engine (`js/checkout.js`).
- Search engine (`js/search.js`).
- Mobile menu/navigation/tabs/swiper init.

## 6.6 Oportunidades inmediatas de estandarización

1. `Button` base único (reemplazar duplicación de estilos de botón).
2. `Title/Text` primitives centralizadas.
3. `FormField` base para input/select/textarea/error.
4. `Image` wrapper con ratio/lazy/fallback.
5. `Section` + `Container` + `Grid` layout primitives.
6. `Badge/Notice` estándar para estados.

---

## 7) Arquitectura objetivo MVP 2.0

## 7.1 UI Layer target

Estructura sugerida:
- `components/primitives/` (Button, Title, Text, Icon, Image, Input, Badge)
- `components/composites/` (ProductCard, SearchItem, CouponCard, QuantityControl)
- `components/sections/` (Header, Footer, CheckoutSummary, ProductSlider)

## 7.2 Styles target

Capas:
1. `settings` (tokens puros)
2. `tools` (mixins/functions)
3. `generic` (reset/base)
4. `elements` (etiquetas HTML)
5. `objects` (layout)
6. `components` (BEM)
7. `utilities` (helpers)
8. `pages` (overrides mínimos)

## 7.3 Domain logic target

- Servicios por dominio: cart, checkout, shipping, stock, search, orders.
- AJAX handlers delgados: validar + delegar a servicio + responder JSON.
- Templates sin reglas de negocio pesadas.

---

## 8) Roadmap MVP 2.0 (macro)

## Fase 0 — Baseline y gobierno técnico (semana 1)

Objetivo:
- Congelar baseline visual/funcional para refactor seguro.

Entregables:
1. Matriz de páginas críticas (Home, PLP, PDP, Cart, Checkout, Account, Admin BSC).
2. Checklists QA por flujo.
3. Convenciones oficiales de componentes y nomenclatura.

Aceptación:
- Baseline aprobado por negocio y técnico.

---

## Fase 1 — Seguridad crítica y hardening (semana 1-2)

Objetivo:
- Cerrar bloqueantes de seguridad.

Tickets:
1. Remover/bloquear ejecución de `cicd/deploy.php` en runtime web.
2. Ajustar permisos por capacidad real (least privilege) en admin BSC.
3. Corregir bug de redención Bubble Points.

Aceptación:
- 0 hallazgos críticos abiertos.

---

## Fase 2 — Performance quick wins (semana 2-3)

Objetivo:
- Mejorar velocidad sin tocar diseño.

Tickets:
1. Build de producción (CSS/JS minificado).
2. Optimización de imágenes estáticas grandes.
3. Carga condicional de scripts por contexto.
4. Limpieza de consultas costosas en admin (caches e invalidación dirigida).

KPIs esperados:
- menor peso transferido en Home/PLP/PDP
- mejor LCP en móvil

---

## Fase 3 — Design primitives y estandarización base (semana 3-5)

Objetivo:
- Crear la base reusable tipo “design system interno”.

Tickets:
1. `Button` primitive (todas las variantes actuales).
2. `Title/Text` primitives.
3. `Input/Select/Textarea/FormField` primitives.
4. `Image` primitive con fallback.
5. `Badge/Notice` primitives.

Aceptación:
- Primitivos usados en al menos 3 módulos reales.
- 0 cambios visuales perceptibles.

---

## Fase 4 — Refactor de organismos por prioridad negocio (semana 5-8)

Objetivo:
- Reescribir organismos principales siguiendo la filosofía Product Card.

Orden recomendado:
1. Header/Nav (alto acoplamiento actual).
2. Checkout suite.
3. Product suite (slider/filters/meta).
4. Orders + Account blocks.

Aceptación:
- Contratos claros por componente.
- Reducción visible de duplicación.

---

## Fase 5 — Lógica de dominio y AJAX (semana 8-10)

Objetivo:
- Separar presentación de negocio.

Tickets:
1. Servicios por dominio (`CartService`, `CheckoutService`, `SearchService`, etc.).
2. Estandarizar respuestas JSON y errores.
3. Unificar validaciones y sanitización.

Aceptación:
- AJAX handlers delgados y consistentes.

---

## Fase 6 — SCSS architecture hardening (semana 10-12)

Objetivo:
- Terminar migración a arquitectura por capas.

Tickets:
1. Mover estilos mezclados a capas correctas.
2. Reducir `!important` en áreas críticas.
3. Consolidar tokens de spacing, typografía, color, breakpoints.

Aceptación:
- menor complejidad de cascada
- menor número de overrides urgentes

---

## Fase 7 — Release MVP 2.0 (semana 12)

Objetivo:
- Salida controlada con monitoreo.

Checklist GO LIVE:
1. 0 críticos, 0 altos sin mitigación activa.
2. QA completa de flujos críticos WooCommerce.
3. Validación visual desktop/mobile.
4. Plan de rollback probado.

---

## 9) Ticket template operativo (usar en cada entrega)

1. **Objetivo**
2. **Contexto**
3. **Archivos a inspeccionar**
4. **Implementación mínima viable**
5. **Riesgos**
6. **Criterios de aceptación**
7. **QA manual**
8. **Rollback**

---

## 10) QA matriz mínima por fase

Flujos obligatorios por cada release:
1. Add to cart (desktop + touch/iPad).
2. Cart qty + badge sync + remove.
3. Coupon apply/remove.
4. Checkout completo (state/city/shipping/payment).
5. Account (orders/edit-account/addresses).
6. Search desktop/mobile.
7. Admin BSC por rol (operator/employee/admin).

---

## 11) KPIs de éxito MVP 2.0

Técnicos:
1. Reducir tamaño de CSS cargado en frontend.
2. Reducir tiempo de interacción de Home/PLP.
3. Reducir duplicación de estilos/componentes.
4. Bajar incidencias de regresión por release.

De producto/operación:
1. Menos tickets por fallos de checkout/cart.
2. Menos fricción operativa en admin BSC.
3. Mayor velocidad para lanzar nuevas features.

---

## 12) Riesgos y mitigación

Riesgos:
1. Refactor grande en header puede afectar navegación.
2. Checkout tiene reglas sensibles (shipping/city plugin).
3. Admin operativo depende de permisos y estados custom.

Mitigación:
1. Refactor por fases, no big-bang.
2. Feature flags por módulo crítico.
3. Visual regression y QA de flujos end-to-end antes de merge.

---

## 13) Decisión estratégica

A partir de este documento, toda nueva pieza UI de BSC debe seguir la filosofía:
**"Si no puede comportarse como Product Card (contrato claro, reusable, robusto), no está lista."**

Este enfoque protege lo que ya funciona, acelera desarrollo y reduce regresiones para MVP 2.0.
