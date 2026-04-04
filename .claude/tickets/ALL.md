🧠 BSC MASTER TICKETS ROADMAP (v1 — Production Ready)
🔴 BSC-000 — Eliminar URLs hardcodeadas (CRÍTICO)
Contexto

El sitio tiene URLs tipo bsc.local, lo que rompe producción.

Problema
links rotos
imágenes no cargan
navegación inconsistente
Archivos a revisar
header.php
footer.php
functions.php
templates en components/
JS con URLs hardcoded
Implementación
Buscar:
"bsc.local"
"http://localhost"
Reemplazar por:
home_url()
site_url()
get_template_directory_uri()
❌ No hacer
dejar 1 solo link hardcoded
usar strings manuales para URLs internas
✅ Aceptación
no existe ningún .local
navegación completa funcional
QA
navegar menú completo
revisar imágenes
probar móvil
🔴 BSC-001 — Arreglar WhatsApp global
Contexto

WhatsApp es canal clave y está inconsistente.

Problema
número incorrecto o duplicado
no aparece en móvil
mensaje inconsistente
Archivos
components/whatsapp.php
footer.php
header.php
Implementación
Crear helper:
function bsc_get_whatsapp_link()
Centralizar:
número
mensaje
Usar en TODO el proyecto
❌ No hacer
hardcodear número en múltiples lugares
duplicar lógica
✅ Aceptación
mismo número en todo el sitio
botón visible en móvil
QA
abrir WhatsApp desde:
home
producto
móvil
🔴 BSC-003 — FIX Cart qty = 0 (CRÍTICO)
Contexto

Bug grave de ecommerce.

Problema
qty llega a 0 pero UI no se sincroniza
Archivos
js/cart.js
inc/ajax/cart-actions.php
components/products/card.php
Implementación
Detectar qty = 0
Llamar:
WC()->cart->remove_cart_item()
Refrescar fragments
Resetear UI
❌ No hacer
solo ocultar UI
no actualizar Woo cart
✅ Aceptación
eliminar último item limpia TODO
QA
agregar producto
bajar a 0
verificar badge
🔴 BSC-004 — Sincronizar badge carrito
Problema

Badge incorrecto vs cart real

Archivos
cart.js
header/footer
Implementación
usar fragments SIEMPRE
❌ No hacer
actualizar badge manual sin Woo
✅ Aceptación
badge siempre correcto
🔴 BSC-005 — Fix iPad / touch cart
Problema

doble click / eventos duplicados

Implementación
usar pointerup
prevenir doble ejecución
❌ No hacer
usar solo click
QA
probar iPad / simulador
🟠 BSC-008 — Fix menú móvil (estructura)
Problema
duplicado
plano
confuso
Archivos
header.php
mobile-menu.js
Implementación
una sola fuente de menú
jerarquía clara
❌ No hacer
duplicar arrays
QA
abrir menú
navegar categorías
🟠 BSC-009 — Rediseño menú móvil (UX)
Problema

UI pobre

Implementación
agrupar:
cuenta
pedidos
puntos
❌ No hacer
copiar desktop literal
🟠 BSC-010 — Espaciado productos mobile
Problema

cards muy juntas/separadas

Implementación
ajustar grid y gap
🟠 BSC-011 — Fix favoritos layout
Problema

columns mal en mobile

🟠 BSC-012 — Scroll con offset header
Problema

anchor queda oculto

Implementación
scrollY - headerHeight
🟠 BSC-014 — Fix gallery responsive
Problema

imagenes estiradas

Solución
object-fit
ratio correcto
🟠 BSC-016 — Shipping dinámico
Problema

envío no actualiza

Archivos
checkout.js
ajax
Implementación
trigger recalculation
🟠 BSC-018 — Coupon UX mobile
Problema

feedback invisible

Implementación
mostrar mensajes arriba
🟠 BSC-021 — My Account mobile
Problema

tablas ilegibles

Implementación
convertir a cards
🟠 BSC-023 — Fix product gallery extra images
🔵 BSC-025 — Optimizar búsqueda
Problema

lenta

Implementación
debounce 300ms
limitar resultados (5–10)
payload mínimo
❌ No hacer
query por cada keypress
🔵 BSC-026 — Reducir JS global
Problema

scripts innecesarios

Implementación
enqueue condicional
🔵 BSC-027 — Optimizar imágenes
🔵 BSC-028 — Optimizar queries Woo
🟣 BSC-029 — Roles personalizados
Roles
admin
operador
empleado
🟣 BSC-030 — Admin menu BSC
🟣 BSC-031 — Orders dashboard
Features
lista pedidos
estado
cliente
total
🟣 BSC-033 — Tracking flow
Flujo
operador agrega guía
guardar meta
enviar email
🟣 BSC-035 — Reports
Métricas
ventas mes
pedidos
🟣 BSC-036 — Dual stock
🟣 BSC-037 — Showroom flow
🟢 BSC-053 — Backup system (CRÍTICO)
Contexto

NO hay backup automático

Script backup (Linux)
#!/bin/bash

DATE=$(date +%F-%H-%M)
BACKUP_DIR="/backups"

# DB
mysqldump -u USER -pPASSWORD DBNAME > $BACKUP_DIR/db-$DATE.sql

# Files
tar -czf $BACKUP_DIR/files-$DATE.tar.gz /var/www/html

# Delete old backups (7 days)
find $BACKUP_DIR -type f -mtime +7 -delete
Cronjob
0 2 * * * /path/to/backup.sh
❌ No hacer
no tener backup
guardar en mismo servidor sin rotación
✅ Aceptación
backup diario funcional
restore probado
🧪 QA GLOBAL (para TODOS los tickets)

Siempre validar:

Cart
add
remove
qty = 0
Checkout
shipping
coupon
Mobile
menú
botones
forms
Usuarios
guest
logged
🚨 REGLAS PARA MODELOS "TONTOS"

Este roadmap está diseñado para que el modelo:

SIEMPRE:

lea ticket completo
solo modifique archivos listados
haga cambios mínimos
valide WooCommerce

NUNCA:

reescriba arquitectura
toque módulos no listados
duplique lógica Woo
deje código incompleto