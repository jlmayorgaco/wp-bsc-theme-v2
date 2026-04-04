
Cómo debe ejecutarse este roadmap

Regla clave para el LLM:

1 ticket = 1 entrega cerrada
No mezclar refactor grande con feature visual
Cada ticket debe incluir:
archivos a tocar
criterio de aceptación
casos de prueba manual
rollback simple
No rehacer arquitectura completa al principio
Primero cerrar bugs/blockers visibles, luego UX, luego admin, luego mejoras estructurales


Cada Ticket debe ser guardado en git commit  ocn 
<tipo>(<scope>): [<TICKET-ID>] <resumen corto en imperativo>

fix(cart): [BSC-003] sync quantity controls when product returns to zero
fix(menu-mobile): [BSC-010] unify skin care items with desktop menu
feat(admin-orders): [BSC-031] add operator order management table
feat(backups): [BSC-053] add automated full and db-only backup scripts
refactor(header): [BSC-002] replace hardcoded localhost urls with dynamic wp links
style(product): [BSC-014] prevent gallery image stretching on mobile and desktop
perf(search): [BSC-038] merge triple ajax search queries into a single optimized flow
docs(runbook): [BSC-046] add backup restore and disaster recovery guide
test(cart): [BSC-004] add manual QA notes for cart fragment refresh flow
chore(cron): [BSC-053] add cron installation instructions for backup jobs

ROADMAP MAESTRO BSC v2.1
Fase 0 — Preparación obligatoria

Objetivo: bajar riesgo antes de tocar features.

BSC-000 — Baseline técnico y entorno de trabajo

Objetivo
Crear una base segura para iterar sin romper producción.

Tareas

documentar estructura real del theme
identificar páginas críticas:
front-page
header/footer
product card
product detail
cart
checkout
my account
bubble points
admin custom
activar modo staging
crear checklist de smoke test
crear backup y snapshot del theme actual
revisar si el deploy script se usa o no
fijar convención de ramas:
fix/...
feat/...
refactor/...

Archivos probables

functions.php
inc/setup/theme-setup.php
inc/scripts/enqueue-scripts.php
README.md
CHANGELOG.md

Definition of Done

existe entorno de staging
existe backup restaurable
existe documento corto de rutas/archivos críticos
existe checklist manual de QA

Prompt para LLM
“Create a staging-safe baseline for the BSC WooCommerce theme. Do not add new features. Add README, CHANGELOG, QA checklist, and document critical templates, AJAX handlers, and WooCommerce overrides. Keep code changes minimal and non-breaking.”

Fase 1 — Blockers de lanzamiento

Objetivo: cerrar bugs críticos y cambios del cliente que afectan compra, navegación y contacto.

BSC-001 — Reparar componente WhatsApp y mensaje configurable

El review detecta que el componente WhatsApp está roto y el cliente pidió cambiar el mensaje de WhatsApp .

Objetivo

reconstruir botón flotante WhatsApp
mover número y mensaje a configuración central
evitar hardcodes repetidos

Tareas

reescribir components/whatsapp.php
crear helper único para teléfono y mensaje
reemplazar links hardcodeados en header/footer
permitir texto personalizado para Home / soporte / producto

Archivos

components/whatsapp.php
components/footer.php
components/header.php
functions.php o nuevo helper inc/bsc-contact-options.php

Aceptación

botón flotante visible y funcional
abre WhatsApp con mensaje correcto
no quedan números hardcodeados duplicados
funciona desktop y mobile

QA

click en Home
click en Product
click en mobile
validar encoded URL
BSC-002 — Eliminar URLs localhost y hardcodes de producción

El issue report marca URLs bsc.local dentro del header y menú .

Objetivo
Reemplazar toda URL local o absoluta incorrecta por funciones dinámicas WP.

Tareas

buscar bsc.local
usar home_url(), get_permalink(), wp_get_attachment_url(), get_theme_file_uri()
revisar covers de mega menú
revisar links de blog, rutina, contacto, etc.

Archivos

components/header.php
cualquier template con URL fija

Aceptación

búsqueda global no devuelve bsc.local
no hay links rotos en staging
imágenes cargan desde entorno activo
BSC-003 — Product card: sumar/restar cantidad con desaparición al volver a 0

El PDF pide explícitamente agregar + y luego -, y que al volver a 0 desaparezca el control y regrese el botón normal .

Objetivo
Mejorar el control de cantidad del card sin desincronizar carrito.

Tareas

mantener botón “¡Lo quiero!”
tras agregar:
mostrar control - qty +
si qty vuelve a 0:
remover control
volver a mostrar botón original
sincronizar badge header/footer y mini-cart

Archivos

components/products/card.php
js/cart.js
inc/ajax/cart-actions.php
quizá woocommerce/cart/mini-cart.php

Aceptación

add item crea control
plus incrementa
minus decrementa
qty 0 restaura botón
header/footer/mini-cart muestran mismo conteo
sin glitches visuales

QA

1 producto
2 productos distintos
último item a 0
guest y logged user
iPad/touch
BSC-004 — Corregir desincronización del cart badge y fragmentos

El issue lo marca como crítico cuando cantidad llega a 0 .

Objetivo
Forzar sincronización real entre UI local y WooCommerce fragments.

Tareas

revisar flujo de update_cart_quantity
disparar get_refreshed_fragments
actualizar header cart, footer cart, checkout summary
contemplar edge case carrito vacío

Archivos

js/cart.js
inc/ajax/cart-actions.php
components/footer.php
header cart fragment

Aceptación

nunca quedan contadores inconsistentes
carrito vacío refleja 0 en toda la UI
no hay race conditions visibles
BSC-005 — iPad/touch: add-to-cart confiable

Hay issue crítico por eventos touch en iPad .

Objetivo
Hacer add-to-cart confiable en touch devices.

Tareas

migrar anchor a button donde aplique
manejar pointerup / touchstart con guardas
prevenir doble disparo click/touch
mantener accesibilidad

Archivos

components/products/card.php
js/cart.js

Aceptación

add to cart funciona en iPad/tablet
no duplica productos
botón mantiene feedback visual
BSC-006 — Home: renombrar “Últimos Lanzamientos K-Beauty”

El cliente pidió cambiarlo por “Últimos Lanzamientos” .

Objetivo
Ajuste editorial puntual.

Archivos

front-page.php
si aplica, opciones de slider/home

Aceptación

el texto visible cambia en desktop/mobile
no rompe estilos
BSC-007 — Home mobile slider: iniciar en slide 2 solo en ese swiper

El PDF lo pide de forma explícita para mobile solamente .

Objetivo
Configurar initialSlide = 1 solo en mobile para un único swiper.

Tareas

identificar slider exacto
aplicar condición por breakpoint
no afectar otros swipers
conservar loop/navegación

Archivos

js/swiper-init.js
quizá front-page.php

Aceptación

en mobile ese slider inicia en el segundo ítem
en desktop sigue normal
otros swipers no cambian
BSC-008 — Home: formulario de contacto y correos reales

El cliente pide habilitar correos reales @bubbleskincare.co y que el formulario de Home envíe emails reales .

Objetivo
Volver operativo el contacto real.

Tareas

revisar formulario actual o crearlo si no existe
sanitización
nonce
rate limiting
envío a correo real configurado
feedback success/error visible
opcional: copia al usuario

Archivos

front-page.php
page-contact-us.php
inc/ajax/... nuevo o existente
functions.php

Aceptación

el form envía mail real
mensajes de error/success visibles
protegido contra spam básico
no usa mail hardcodeado regado
Fase 2 — Rediseño móvil y navegación

Objetivo: arreglar experiencia móvil que el cliente ve “muy plana” y alinear mobile con desktop.

BSC-009 — Menú mobile: rediseño visual completo

El cliente pide rediseño, quitar “wappy” y que el menú no sea plano .

Objetivo
Rehacer UI del menú celular manteniendo marca BSC.

Tareas

revisar estructura actual del sidebar móvil
mejorar jerarquía visual
spacing, divisores, iconografía, CTA principal
eliminar “Desarrollado con amor por Wappy”
revisar z-index y animación

Archivos

components/header.php
js/mobile-menu.js
sass/style.scss

Aceptación

menú móvil se ve premium
no queda detrás del contenido
se abre/cierra suave
contenido ordenado y legible
BSC-010 — Menú mobile skin care igual a desktop

El PDF dice que en mobile debe quedar igual al de computador y quitar duplicados como doble mascarilla/piel seca-mixta, etc.

Objetivo
Unificar árbol de navegación mobile/desktop.

Tareas

usar misma fuente de datos para ambas versiones
remover duplicados
alinear orden e ítems
evitar mantener dos menús divergentes

Archivos

components/header.php
clases BSC_HeaderNav / BSC_MenuNav si aplica

Aceptación

mobile y desktop muestran mismo set de categorías
no hay “mascarillas” repetido
no aparece listado alterno incorrecto
BSC-011 — Cambiar la X del menú celular por hamburguesa

El PDF lo pide tal cual .

Objetivo
Ajuste visual de iconografía.

Aceptación

icono cerrado = hamburguesa
icono abierto = estado visual claro
accesible con aria-label
BSC-012 — Mobile profile icon debe linkear a Profile/Orders

El PDF pide que el icono de perfil móvil lleve a profile orders .

Objetivo
Corregir navegación de cuenta en mobile.

Archivos

components/header.php
rutas de my account

Aceptación

icono perfil lleva al destino correcto
guest user redirige a login si aplica
logged user entra a orders o dashboard según regla definida
BSC-013 — En /shop mostrar las 3 niñas

Pedido textual del PDF .

Objetivo
Restaurar/agregar asset decorativo correcto en shop.

Archivos

woocommerce/archive-product.php
components/shop.php o landing
assets/images

Aceptación

las 3 niñas aparecen donde corresponde
responsive correcto
no afectan LCP en exceso
Fase 3 — Product page y galerías

Objetivo: cerrar defectos visuales y contenido del detalle de producto.

BSC-014 — Product detail gallery sin stretching en mobile/desktop

El PDF lo pide explícitamente .

Objetivo
Corregir proporciones de galería.

Tareas

revisar tamaños y object-fit
revisar Swiper/galería Woo overrides
asegurar consistencia portrait/square

Archivos

woocommerce/single-product.php
galerías de Woo
CSS/SASS product gallery

Aceptación

imágenes no se estiran
thumbnails y principal se ven correctas
mobile/desktop aprobados
BSC-015 — Debajo de producto: agregar 3 imágenes extra

El cliente pide 3 imágenes debajo del producto: en desktop en row, en mobile en column .

Objetivo
Agregar bloque multimedia extra configurable.

Tareas

definir origen:
campos del producto
galerías extendidas
custom fields
render debajo del summary
responsive row/column

Archivos

woocommerce/single-product.php
admin product fields si se necesitan
CSS

Aceptación

se pueden cargar/configurar 3 imágenes
desktop: fila
mobile: columna
no rompe productos sin esas imágenes
Fase 4 — Checkout y carrito flotante

Objetivo: pulir compra, envío, cupón y microinteracciones.

BSC-016 — Shipping price Bogotá vs resto: actualización automática

El PDF lo exige en checkout .

Objetivo
Actualizar tarifa de envío automáticamente según ciudad/región sin recarga manual.

Tareas

revisar lógica checkout actual
detectar cambio de ciudad/departamento
recalcular shipping por AJAX
mostrar diferencia Bogotá / otros

Archivos

inc/woocommerce.php
inc/ajax/checkout-actions.php
js/checkout.js

Aceptación

cambiar ciudad actualiza tarifa automáticamente
Bogotá y no-Bogotá muestran valor correcto
no se requiere refresh manual
BSC-017 — Floating cart: misma animación swing al agregar ítem repetido

Pedido del PDF .

Objetivo
Hacer consistente la animación del carrito flotante.

Archivos

js/cart.js
CSS del footer__shopping-cart

Aceptación

producto nuevo: swing
producto repetido: también swing
no spamea animación
BSC-018 — Cupón: botón negro y flotante + notice fijo en mobile

El PDF da incluso reglas CSS aproximadas para el notice de error fijo bottom .

Objetivo
Corregir UX del cupón en checkout/cart mobile.

Tareas

estilizar botón aplicar en negro
botón sticky/flotante si ese es el diseño aprobado
notice de éxito/error fixed bottom en mobile
evitar que “salga en el piso”

Archivos

js/coupons.js
inc/ajax/coupons-actions.php
templates checkout/cart
CSS/SASS

Aceptación

botón aplicar se ve según diseño
error/success notice visible siempre en mobile
cupón sigue funcionando
BSC-019 — Loading states en add-to-cart, checkout, filtros, cupón

El review marca falta de loading states .

Objetivo
Añadir feedback consistente en operaciones AJAX.

Aceptación

botones muestran loading
se bloquea doble click
errores y success son visibles
Fase 5 — Perfil, órdenes y Bubble Points

Objetivo: dejar “Mi Cuenta” usable y coherente en mobile.

BSC-020 — Orders tabs al 100% width + scroll al inicio al hacer click

Pedido del PDF .

Objetivo
Mejorar navegación en perfil.

Archivos

woocommerce/myaccount/*
page-mi-cuenta.php
JS tabs/scroll

Aceptación

tabs ancho completo en mobile
al cambiar tab hace scroll al inicio del contenido
BSC-021 — Orders mobile: tabla → lista con chip rosado

El PDF pide reemplazar tabla por lista y el # en chip rosado con letras negras .

Objetivo
Hacer órdenes legibles en mobile.

Archivos

woocommerce/myaccount/orders.php
CSS my-account

Aceptación

mobile usa cards/lista
número de orden en chip rosado
desktop mantiene tabla o diseño adecuado
BSC-022 — Order/[id] revisión de estilos live

El PDF lo marca como revisión puntual .

Objetivo
Pulir detalle de orden.

Tareas

spacing
estados
productos
totales
tracking
responsive

Aceptación

pantalla se ve consistente con marca
sin desbordes ni bloques rotos
BSC-023 — Bubble Points: revisión visual completa

El cliente pide revisión de estilos, grayscale de no activos y responsividad .

Objetivo
Dejar Bubble Points usable y visualmente correcto.

Tareas

revisar vistas del plugin Bubble Points
aplicar grayscale a cupones/tickets inactivos
mejorar carrusel/listado en mobile
corregir layout general

Archivos

plugins/bubble-points/views/*
plugins/bubble-points/hooks/*
CSS del plugin/theme

Aceptación

no activos se ven deshabilitados
responsive correcto
componente no se rompe en pantallas pequeñas
BSC-024 — Edit address / mis datos / envío y dirección responsivos

El PDF pide responsivizar esas vistas .

Objetivo
Corregir forms de cuenta para mobile.

Archivos

woocommerce/myaccount/edit-address.php
woocommerce/myaccount/edit-account.php
CSS my account

Aceptación

forms sin overflow
inputs legibles y clicables
spacing consistente
Fase 6 — Páginas institucionales y contenido faltante

Objetivo: completar páginas que el cliente pidió.

BSC-025 — Diseñar/terminar Bubble Creators

Pedido del cliente desde el PDF Home/Ajustes .

Objetivo
Completar landing + formulario de Bubble Creators.

Archivos

page-bubble-creators.php
inc/ajax/creator-actions.php

Aceptación

diseño consistente con marca
formulario funcional
mensajes visibles
admin recibe lead
BSC-026 — Diseñar/terminar Shipping & Returns

Pedido del cliente .

Archivos

page-shipping-returns.php

Aceptación

contenido maquetado
responsive
acorde al diseño BSC
BSC-027 — Diseñar/terminar FAQ

Pedido del cliente .

Archivos

page-faq.php

Aceptación

acordeones/FAQ claros
responsive
accesible
BSC-028 — Diseñar/terminar Contacto

Pedido del cliente .

Archivos

page-contact-us.php

Aceptación

diseño completo
formulario funcional
correo real integrado
Fase 7 — Admin, pedidos, roles y operación

Objetivo: implementar el backend operativo que pidió el cliente y que NEW_FEATURES ya bosqueja con roles, dashboard, pedidos, tracking y stock dual .

BSC-029 — Crear roles Admin / Operativo / Empleado

El PDF pide Admin total y Operativo con acceso limitado a pedidos/productos; NEW_FEATURES describe roles custom similares .

Objetivo
Crear roles custom y restricciones reales.

Tareas

bsc_operator
bsc_employee
capabilities claras
redirects seguros en admin
ocultar menús no permitidos

Archivos

nuevo includes/class-bsc-roles.php
nuevo includes/class-bsc-permissions.php
hooks admin

Aceptación

admin ve todo
operativo solo pedidos y tareas permitidas
empleado ve productos + pedidos según definición final
BSC-030 — Menú admin BSC custom

NEW_FEATURES propone menú BSC con Pedidos, Productos, Informes, Configuración .

Objetivo
No exponer el backend de Woo completo al operativo.

Archivos

admin/bsc-admin-menu.php
admin/bsc-dashboard.php

Aceptación

menú aparece según rol
acceso restringido real
UX simplificada
BSC-031 — Módulo de pedidos tipo Kyte / lista operativa

El PDF pide un módulo tipo Kyte o similar, con filtro por fechas, export y flujo operativo .

Objetivo
Crear vista de pedidos rápida para empaque/operación.

Tareas

tabla con:
código
fecha
cliente
items
estado
guía
acciones
filtros por fecha
búsqueda
paginación

Archivos

admin/bsc-orders-page.php
admin/class-bsc-orders-table.php
JS admin

Aceptación

operativo puede trabajar solo desde esta vista
carga rápido
UX limpia
BSC-032 — Estados de pedido simplificados

PDF y NEW_FEATURES hablan de estados como pendiente / enviado y custom statuses más ricos .

Objetivo
Definir set final de estados y mapearlos bien en WooCommerce.

Recomendación
Mantener un set reducido:

Pago recibido
En preparación
Enviado
Entregado
Cancelado
Fallido

Aceptación

visibles en admin custom y frontend order progress
cambio de estado no rompe emails/analytics
BSC-033 — Campo de guía + autocompletado de estado + email al cliente

Pedido explícito del PDF y contemplado en NEW_FEATURES tracking/email .

Objetivo
Cuando se agregue guía:

guardar tracking code y link
cambiar estado a enviado
mandar email automático bonito

Archivos

admin/...
emails/...
hooks Woo order status
meta order helpers

Aceptación

guardar guía funciona
estado cambia automáticamente
email llega con número y link correcto
BSC-034 — Exportación de pedidos para empaque

El PDF pide seleccionar pedidos y descargar un PDF o ZIP con lo que toca empacar .

Objetivo
Primer entregable simple y útil.

Enfoque recomendado
v1:

selección múltiple
export CSV limpio + print view HTML/PDF básico
v2:
ZIP con fichas individuales

Aceptación

se pueden seleccionar N pedidos
se descarga export útil para operación
BSC-035 — Analytics básicos por mes

El cliente preguntó por analíticas tipo cuántos productos y cuánto dinero entró por mes .

Objetivo
Crear módulo simple de reportes.

KPIs

ventas por mes
órdenes por mes
ticket promedio
top productos
meses flojos/fuertes

Archivos

admin/bsc-reports-page.php
consultas Woo orders

Aceptación

admin ve dashboard con métricas básicas
filtros por rango de fechas
datos consistentes con Woo
BSC-036 — Doble inventario: web + showroom

El PDF pide manejar inventario web y ventas físicas manuales; NEW_FEATURES ya propone stock tienda/bodega .

Objetivo
Implementar stock dual real sin sobreventa.

Modelo recomendado

_stock_tienda
_stock_bodega
stock visible total o según estrategia
ventas web descuentan bodega por defecto
ventas showroom descuentan tienda
fallback opcional definido por negocio

Aceptación

se editan ambos stocks por producto
venta showroom descuenta inventario correcto
venta web descuenta inventario correcto
BSC-037 — Usuario showroom + checkout/admin simplificado para venta física

Pedido explícito del PDF .

Objetivo
Permitir registrar venta presencial.

Tareas

rol/showroom
flujo de creación rápida de pedido
métodos de pago:
efectivo
transferencia
tarjeta presencial
descuenta stock tienda

Aceptación

se puede registrar venta física sin pasar por checkout normal
inventario se ajusta
reporte mensual la incluye
Fase 8 — Performance, seguridad y deuda técnica

Objetivo: que el sitio no solo “funcione” sino que quede sano.

BSC-038 — Optimizar búsqueda AJAX (unificar triple query)

ISSUE marca 3 queries separadas en search .

Objetivo
Reducir carga y tiempo de respuesta.

Aceptación

menos queries
mismos resultados o mejores
debounce y caching básico
BSC-039 — Cachear búsquedas y queries repetidas

Basado en PERF del review/issues .

Objetivo
Bajar TTFB y repetición de consultas.

BSC-040 — Optimizar product card y taxonomías evitando N+1

Marcado en PERF-003 .

Objetivo
Reducir queries por tarjetas/loops.

BSC-041 — Versionado de assets sin filemtime en producción

Issue PERF-004 .

Objetivo
Quitar I/O innecesario por request.

BSC-042 — Responsive images, srcset, lazy load y placeholders correctos

Issue PERF-005 .

Objetivo
Mejorar mobile performance.

BSC-043 — Seguridad formularios: nonce, sanitización, límites, CSRF

ISSUE lista problemas de seguridad en formularios/AJAX .

Objetivo
Asegurar forms de contacto, newsletter, creators, login/register custom.

BSC-044 — Documentación mínima + smoke tests + changelog

El review remarca falta de documentación y testing .

Objetivo
Dejar terreno mantenible.

Orden sugerido de ejecución real
Sprint 1

BSC-001 a BSC-008

Sprint 2

BSC-009 a BSC-015

Sprint 3

BSC-016 a BSC-024

Sprint 4

BSC-025 a BSC-033

Sprint 5

BSC-034 a BSC-037

Sprint 6

BSC-038 a BSC-044


# [TICKET_ID] Título

## Objetivo
Explica en 2-4 líneas qué problema resuelve y por qué.

## Contexto
Qué pidió el cliente o qué bug existe.
No asumir nada fuera del ticket.

## Archivos a revisar
- ruta/archivo1
- ruta/archivo2

## Implementación exacta
1. ...
2. ...
3. ...

## Restricciones
- no romper desktop
- no cambiar otras vistas
- mantener estilo actual BSC
- no introducir nuevas dependencias salvo que sea imprescindible

## Criterios de aceptación
- ...
- ...
- ...

## QA manual
- caso 1
- caso 2
- caso 3

## Entregables
- archivos modificados
- resumen breve
- riesgos o pendientes


Infra / Backups / Operación

BSC-045 — Sistema de backups automáticos 3-2-1
Basado en la recomendación de full semanal, incremental/diario y DB frecuente del plan de producción .
Objetivo:

backup de base de datos
backup de archivos críticos
copia local
copia off-site
limpieza automática por retención

BSC-046 — Runbook de restore y disaster recovery
Tiene mucho valor porque no sirve de nada tener backup si no saben restaurar rápido .
Objetivo:

pasos exactos para restaurar DB
pasos exactos para restaurar wp-content
checklist de validación post-restore
RTO/RPO realistas

BSC-047 — Monitoreo y alertas básicas de producción
Vale la pena. El plan propone uptime, alertas de caída, backup fallido, SSL, etc.
Objetivo:

alerta si la web cae
alerta si falla backup
alerta si SSL va a expirar
alerta si disco se llena

BSC-048 — Hardening básico WordPress/WooCommerce
Sí lo incluiría porque el proyecto ya tenía varios temas de seguridad y formularios .
Objetivo:

nonces/CSRF
limitar intentos de login
deshabilitar XML-RPC si no se usa
permisos correctos
reducir exposición innecesaria
Performance / Infra técnica

BSC-049 — Cache y optimización de servidor
El plan nuevo insiste en cache, compresión, PHP moderno, Redis, CDN, etc.
Objetivo:

page cache
browser cache
gzip/brotli
revisión Redis/object cache si el hosting lo permite

BSC-050 — Optimización de imágenes y media pipeline
Sí entra. Ya estaba alineado con issues previos de performance y el plan lo refuerza .
Objetivo:

WebP
lazy load
tamaños correctos
limpieza de imágenes gigantes
Calidad / Accesibilidad / Mantenibilidad

BSC-051 — Auditoría de accesibilidad mínima WCAG para checkout, menú y forms
Sí la metería, pero no como prioridad 1. El plan nuevo lo sugiere y el theme ya tenía problemas de labels, tabs y navegación por teclado .

BSC-052 — Documentación operativa técnica del proyecto
Sí, totalmente. El review ya marcaba falta de documentación y testing y el plan de producción agrega runbooks operativos .

### BSC-045 — Sistema de backups automáticos 3-2-1
Objetivo: Implementar backups automáticos de base de datos y archivos críticos con copia local y off-site, retención automática y logs.
Incluye:
- backup DB comprimido
- backup wp-content / uploads / theme
- archivo .env o config segura
- limpieza por antigüedad
- log por ejecución
- posibilidad de restauración manual
Aceptación:
- genera backup local correctamente
- sube copia remota correctamente
- elimina backups vencidos según política
- deja log legible por fecha


### BSC-047 — Monitoreo y alertas básicas de producción
Objetivo: Configurar monitoreo mínimo de disponibilidad, backup fallido, expiración SSL y capacidad de disco.
Incluye:
- healthcheck HTTP
- alerta email/Telegram/Slack
- alerta backup fallido
- alerta disco >80%
Aceptación:
- alertas llegan correctamente
- se prueba caída simulada
- se prueba backup fallido

### BSC-048 — Hardening básico WordPress/WooCommerce
Objetivo: Aplicar capa mínima de seguridad operativa y de aplicación.
Incluye:
- nonces y CSRF en forms
- rate limit login
- permisos archivos/directorios
- deshabilitar XML-RPC si no se usa
- remover exposición innecesaria
Aceptación:
- forms sensibles protegidos
- login protegido
- permisos revisados

### BSC-049 — Cache y optimización de servidor
Objetivo: Mejorar TTFB y estabilidad general con caché y compresión.
Incluye:
- page cache
- browser cache
- gzip/brotli
- revisión object cache
Aceptación:
- mejora medible en Lighthouse/PageSpeed
- no rompe WooCommerce/cart fragments
### BSC-050 — Optimización de imágenes y media pipeline
Objetivo: Reducir peso de imágenes y mejorar carga en mobile.
Incluye:
- WebP
- lazy load
- tamaños responsive
- revisión de imágenes decorativas pesadas
Aceptación:
- reducción medible de peso
- sin stretching ni imágenes borrosas
### BSC-051 — Accesibilidad mínima en navegación, forms y checkout
Objetivo: Corregir problemas básicos de accesibilidad.
Incluye:
- labels
- focus states
- navegación teclado
- contraste básico
Aceptación:
- formularios legibles y navegables
- tabs y menú funcionales con teclado

### BSC-051 — Accesibilidad mínima en navegación, forms y checkout
Objetivo: Corregir problemas básicos de accesibilidad.
Incluye:
- labels
- focus states
- navegación teclado
- contraste básico
Aceptación:
- formularios legibles y navegables
- tabs y menú funcionales con teclado

### BSC-052 — Documentación operativa y técnica
Objetivo: Dejar documentación mínima para mantenimiento, deploy, backup y restore.
Incluye:
- README técnico
- CHANGELOG
- runbook backup
- runbook restore
- checklist QA
Aceptación:
- cualquier dev puede entender estructura y operaciones básicas