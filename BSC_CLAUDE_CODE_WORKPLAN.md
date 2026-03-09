# BSC — PLAN DE TRABAJO PARA CLAUDE CODE
## Bubble Skin Care · WordPress + WooCommerce · Theme Custom

---

## 1. CONTEXTO DEL PROYECTO (leer antes de tocar cualquier archivo)

**Qué es:** Tienda e-commerce sobre WordPress + WooCommerce con una **theme custom propia**. No es un tema de terceros, no usa page builder pesado. Todo el render vive en la theme.

**Regla de oro:** TODO cambio va dentro de la theme custom. No se crean plugins parche. No se mete lógica en el customizer si puede ir en un template PHP o en functions.php.

**Stack real:**
- WordPress + WooCommerce
- Theme custom (probablemente `wp-bsc-theme-v2` o similar)
- CSS/SCSS propio de marca
- JS custom para slider, menú mobile, filtros e interacciones
- Botón flotante WhatsApp
- Fragmentos AJAX de WooCommerce para carrito

**Lo que ya existe en el sitio:**
- Home con hero/slider, carrusel de productos, sección de marcas, favoritos por tipo de piel, bloque institucional, newsletter y footer
- Navegación desktop y mobile diferenciadas
- Páginas: cuenta, categorías, marcas, contacto, encargos, blog
- WooCommerce: carrito, checkout, cuenta, taxonomías de productos

**El problema central hoy:**
El sitio tiene lentitud severa, bugs funcionales (carrito, add-to-cart en iPad), responsive roto en home y mobile, enlaces que no llevan a ningún lado, y el menú mobile desalineado con el diseño de marca. La misión es **cerrar correctamente la theme custom y dejarla lista para producción**.

---

## 2. DIAGNÓSTICO TÉCNICO — QUÉ ESTÁ MAL Y POR QUÉ

### 2.1 Performance
- **Síntoma:** navegación entre secciones muy lenta, no es el internet del usuario
- **Causa probable:** plugins no auditados (herencia del dev anterior "Hinca"), assets CSS/JS cargados globalmente aunque solo se usan en home, imágenes de banners sin comprimir, posible doble inicialización de sliders, cart fragments de WooCommerce cargando en cada request
- **Riesgo:** si se meten imágenes "reales" de banners sin comprimir primero, la lentitud empeora

### 2.2 Hero y responsive del home
- **Síntoma:** en iPad, desktop pequeño y celular el carrito, botones y textos del slide se pisan
- **Causa probable:** z-index y breakpoints inconsistentes entre header sticky y el slider. El header tiene íconos (carrito, perfil) posicionados absolutamente y el slider no tiene safe area calculada
- **Riesgo:** tocar z-index del header puede afectar el mini-cart y el menú mobile si no se hace con escala documentada

### 2.3 Badge del carrito
- **Síntoma:** al bajar cantidades a 0, el contador del carrito sigue mostrando el número anterior (en pantalla se ve "9")
- **Causa probable:** el badge no está escuchando el evento `wc_fragments_refreshed` o `added_to_cart`, o hay caché de fragmentos que no se invalida cuando la cantidad llega a 0 (WooCommerce trata "0" como "remover", lo cual puede no disparar el mismo evento que "agregar")
- **Archivo probable:** JS custom de carrito o `functions.php` donde se registra el fragment del badge

### 2.4 Add-to-cart en iPad
- **Síntoma:** click en "Agregar" en iPad Safari no hace nada
- **Causa probable:** o hay un overlay invisible con z-index mayor tapando el botón, o el evento está registrado como `click` puro sin soporte para eventos `touchstart`/`pointer`, o el botón está deshabilitado por JS en ese breakpoint
- **Riesgo:** es el bug funcional más grave del flujo de compra

### 2.5 Marcas — lentitud y productos vacíos
- **Síntoma 1:** click en bolita de marca carga muy lento
- **Síntoma 2:** al entrar a una marca (ej: `/product-category/group-skin-care/sk-marcas/heimish`) aparece "No hay categoría por defecto configurada para este grupo"
- **Causa probable síntoma 1:** la query que resuelve el bloque de marcas es pesada, posiblemente por imágenes no optimizadas o consultas repetidas por producto
- **Causa probable síntoma 2:** el template de taxonomía/categoría no está ejecutando una `WP_Query` o loop de WooCommerce correcto. Está cayendo en el template de grupo de variaciones en lugar del template de archivo de productos de esa marca. La URL sugiere que "sk-marcas" es una categoría padre y cada marca es subcategoría — el template custom podría no estar manejando ese nivel de jerarquía

### 2.6 Menú mobile
- **Síntoma:** plano visualmente, items incorrectos (Registrarme, Políticas, Olvidé contraseña), Skin Care tiene ítems duplicados (Mascarillas 1 y 2, piel seca/mixta que no corresponde), falta logo BSC centrado, iconos incorrectos, aparece texto "Desarrollado con amor por Wappy"
- **Causa probable:** el menú mobile está compuesto por dos fuentes — el WP Menu asignado + render custom en PHP/JS del theme. Los duplicados vienen de que ambas fuentes incluyen algunos ítems. "Wappy" es texto hardcodeado en el template del menú mobile

### 2.7 Newsletter
- **Síntoma:** al enviar el correo no pasa nada visible
- **Causa probable:** el handler del formulario existe pero no dispara feedback visual. Puede ser que el submit sea AJAX y responda 200 pero el JS no procese la respuesta para mostrar el popup/toast

### 2.8 Links del footer/bloques
- **Síntoma:** Envíos y devoluciones, FAQ, Contacto, Bubble Creators no llevan a ningún lado o hacen nada
- **Causa probable:** links en `#` o sin href configurado en el template del footer o en las opciones del theme

---

## 3. ARCHIVOS CLAVE DEL THEME (donde probablemente viven los cambios)

Antes de tocar cualquier cosa, mapear estos archivos en la theme:

```
theme/
├── header.php                    → header desktop + mobile bar
├── footer.php                    → footer negro + copyright + bloque azul
├── functions.php                 → hooks WC, cart fragments, enqueue assets
├── front-page.php (o home.php)   → template del home
├── template-parts/
│   ├── hero/                     → slider principal
│   ├── products/                 → carrusel y cards
│   ├── brands/                   → bloque de marcas y bolitas
│   ├── skin-types/               → favoritos por tipo de piel
│   ├── newsletter/               → formulario de correo
│   └── mobile-menu/              → menú mobile HTML/render
├── woocommerce/
│   ├── taxonomy-product_cat.php  → template de categoría/marca (CRÍTICO)
│   ├── archive-product.php       → listado de productos
│   └── content-product.php       → card individual de producto
├── assets/
│   ├── css/ (o scss/)            → estilos de marca, breakpoints
│   └── js/
│       ├── cart.js               → lógica del badge y mini-cart
│       ├── mobile-menu.js        → apertura/cierre del menú mobile
│       └── slider.js             → hero slider
└── inc/
    └── woocommerce-hooks.php     → overrides de WC (si existe)
```

---

## 4. TICKETS DE TRABAJO — ORDENADOS POR PRIORIDAD

### ══ P0 — BLOQUEANTES DE PRODUCCIÓN ══

---

#### BSC-001 · Auditoría de performance
**Objetivo:** Encontrar qué está haciendo lento el sitio antes de tocar UI.

**Pasos:**
1. Listar todos los plugins activos con `get_plugins()` o via WP-CLI: `wp plugin list --status=active`
2. Revisar `functions.php` y buscar todos los `wp_enqueue_script()` y `wp_enqueue_style()` — verificar cuáles se cargan globalmente vs. por template
3. Revisar si hay `wp_enqueue_*` registrados sin condición en hook `wp_enqueue_scripts`
4. Buscar scripts de terceros en header.php o footer.php hardcodeados (widgets de chat, pixel, GTM mal configurado)
5. Revisar si WooCommerce cart fragments está activo — si `woocommerce_cart_fragments` está enqueueing en todas las páginas o solo donde tiene sentido
6. Verificar imágenes del hero: tamaño real en disco vs. tamaño render
7. Verificar si sliders tienen múltiples instancias inicializadas

**Resultado esperado:** Lista documentada de causas raíz y quick wins aplicables sin meter más dependencias.

**Aceptación:** La navegación entre secciones del home mejora perceptiblemente después de aplicar los quick wins.

---

#### BSC-002 · Corregir hero responsive (iPad, desktop pequeño, mobile)
**Archivo principal:** `header.php`, `assets/css/`, template del hero

**Pasos:**
1. Identificar el breakpoint donde el header sticky entra en conflicto con el slider
2. Revisar z-index del header vs. z-index del slider — el header debe tener z-index mayor pero sin tapar el contenido del slide
3. En iPad (768px–1024px): ajustar padding-top del hero para que el contenido del slide no quede debajo del header
4. Corregir posición de flechas del slider para que queden más hacia los bordes laterales en mobile
5. Validar que el CTA del slide sea visible y clickeable en todos los breakpoints
6. Probar en Safari iPad específicamente (los eventos de scroll y position fixed se comportan diferente)

**CSS a revisar:**
```css
/* Verificar estas reglas y corregir si hay conflicto */
.site-header { z-index: ???; position: sticky; }
.hero-slider { z-index: ???; margin-top: ???; }
.slider-arrow { position: ???; }
```

**Aceptación:** ningún texto, botón o ícono del header se superpone con el contenido del slide en ningún breakpoint.

---

#### BSC-003 · Limpiar contenido del hero
**Archivo:** template del hero / slider (probablemente en `template-parts/hero/` o en el home template)

**Cambios:**
1. Quitar la frase "K Beauty para cada tipo de piel" del markup — buscar con `grep -r "K Beauty para cada tipo de piel" ./`
2. Cambiar el color de los textos del slide al negro de marca BSC — revisar la variable CSS o clase que controla `color` en los elementos `.slide-title`, `.slide-text`
3. Corregir el signo `¡` invertido en "¡Mantén tus labios saludables!" — buscar `!Manten` o `!Mantén` en templates
4. Reemplazar las imágenes placeholder del banner por las imágenes reales (el cliente debe proveer — dejar comentario en el código indicando dónde va cada imagen)

**Aceptación:** el hero muestra contenido final, sin textos heredados, con tipografía en negro BSC y sin caracteres rotos.

---

#### BSC-004 · Corregir badge del carrito cuando cantidad llega a 0
**Archivo:** `assets/js/cart.js` (o equivalente), `functions.php`

**Investigación primero:**
```javascript
// Buscar en el JS si está escuchando estos eventos de WooCommerce:
$(document.body).on('wc_fragments_refreshed', function() { ... });
$(document.body).on('removed_from_cart', function() { ... });
$(document.body).on('wc_fragment_refresh', function() { ... });
```

**Pasos:**
1. Verificar que el fragment del badge esté registrado en `functions.php`:
```php
// Debe existir algo así:
add_filter('woocommerce_add_to_cart_fragments', 'bsc_cart_count_fragment');
function bsc_cart_count_fragment($fragments) {
    $count = WC()->cart->get_cart_contents_count();
    $fragments['span.cart-count'] = '<span class="cart-count">' . $count . '</span>';
    return $fragments;
}
```
2. Si el fragment existe pero no actualiza a 0: el problema está en que WooCommerce puede no disparar `wc_fragments_refreshed` cuando se llega a 0 por la ruta de "update_cart". Agregar escucha a `updated_cart_totals` también.
3. Si no existe el fragment: implementarlo.
4. Verificar que no haya caché de página completa (WP Super Cache, W3 Total Cache, etc.) guardando el badge con un número fijo.

**Aceptación:** al dejar todos los productos en 0 en el carrito, el badge del header se actualiza inmediatamente a 0 o desaparece.

---

#### BSC-005 · Reparar add-to-cart en iPad Safari
**Archivo:** `assets/js/cart.js` o el script que maneja el botón "Agregar"

**Diagnóstico paso a paso:**
1. Abrir Safari DevTools en iPad (o usar simulador) → inspeccionar el botón "Agregar"
2. Verificar si tiene `pointer-events: none` en algún breakpoint
3. Verificar si hay un elemento invisible con `position: absolute` y `z-index` mayor tapando el botón
4. Verificar si el evento está registrado solo como `click` sin `touchstart`:

```javascript
// MAL — puede fallar en iOS Safari:
$('.add-to-cart-btn').on('click', function() { ... });

// BIEN — compatible con iOS:
$('.add-to-cart-btn').on('click touchstart', function(e) {
    e.preventDefault();
    // lógica
});
```

5. Si usa AJAX add-to-cart de WooCommerce, verificar que `wc_add_to_cart_params` esté disponible en ese breakpoint
6. Revisar si hay algún script que deshabilite botones en mobile por error

**Aceptación:** add-to-cart funciona en Safari iPad sin doble tap ni recarga de página.

---

#### BSC-006 · Corregir template de páginas de marca (taxonomía)
**Archivo:** `woocommerce/taxonomy-product_cat.php` o el template que resuelve `/product-category/group-skin-care/sk-marcas/[marca]`

**El problema real:**
La URL `/product-category/group-skin-care/sk-marcas/heimish` debe mostrar los productos de esa marca. Hoy muestra "No hay categoría por defecto configurada para este grupo." — esto indica que el template está cayendo en un fallback incorrecto.

**Pasos:**
1. Identificar qué template está siendo usado: agregar temporalmente `echo get_template_part_used();` o revisar Query Monitor
2. Verificar si existe `taxonomy-product_cat.php` en la carpeta WooCommerce del theme
3. Si el template existe pero no hace el loop correcto:
```php
// El loop correcto para mostrar productos de una categoría:
if (have_posts()) {
    woocommerce_product_loop_start();
    while (have_posts()) {
        the_post();
        wc_get_template_part('content', 'product');
    }
    woocommerce_product_loop_end();
} else {
    // fallback de vacío elegante
    echo '<p>No hay productos en esta categoría aún.</p>';
}
```
4. Verificar que la categoría "heimish", "cosrx", etc. tenga productos asignados en WooCommerce (puede ser un problema de datos, no de template)
5. Si es problema de datos: los productos deben estar asignados a la subcategoría correspondiente en WooCommerce → Productos → Categorías

**Aceptación:** al entrar a cualquier marca desde las bolitas, se renderiza el grid/listado real de productos de esa marca.

---

#### BSC-007 · Reducir tiempo de carga al hacer click en bolitas de marcas
**Archivo:** template del bloque de marcas, posiblemente `template-parts/brands/`

**Pasos:**
1. Verificar qué query está haciendo el bloque de marcas para renderizar las bolitas
2. Si está haciendo una `WP_Query` por cada marca para obtener imagen/datos: convertir a una sola query con `get_terms()`
3. Verificar que cada bolita tenga un `href` a la URL correcta hardcodeado (no que resuelva una query al hacer click)
4. Verificar que las imágenes de las bolitas estén en el tamaño correcto (no cargar imágenes de 2000px para círculos de 120px)
5. Si hay un slider/carrusel de marcas, verificar que no esté cargando assets extras innecesarios

**Aceptación:** click en una bolita navega inmediatamente a la página de la marca (la lentitud que queda es de carga de página, no de respuesta al click).

---

#### BSC-008 · Rediseñar y limpiar menú mobile
**Archivo:** `header.php`, `assets/js/mobile-menu.js`, el WP Menu asignado al menú mobile en wp-admin

**Cambios requeridos:**

**En el header mobile (PHP/HTML):**
1. Agregar logo BSC centrado entre la hamburguesa y los íconos — `<a href="<?php echo home_url(); ?>"><img src="logo-bsc.svg"></a>`
2. Cambiar color de las 3 rayitas (hamburguesa) a negro BSC: `.hamburger-icon { color: #000; }`
3. Reemplazar ícono de bolsa por el mismo SVG/icono usado en desktop
4. Agregar ícono de perfil/usuario igual al de desktop
5. Quitar texto hardcodeado "Desarrollado con amor por Wappy" — buscar con `grep -r "Wappy" ./`

**En el WP Menu (admin) o en el render del menú:**
1. Quitar: Registrarme, Políticas de privacidad, Olvidé mi contraseña
2. Dejar: Iniciar sesión, Mis pedidos, Bubble Points
3. En Skin Care: sincronizar con el menú desktop — si el menú desktop viene de un WP Menu, asegurarse de que el mobile use el mismo, no uno separado con ítems extra
4. Eliminar "Mascarillas" duplicadas — verificar si viene del WP Menu (fácil de resolver en admin) o del render custom (toca editar el template)
5. Quitar ítems de tipos de piel (Piel Seca, Mixta, etc.) del menú Skin Care si no corresponden ahí

**Rediseño visual (CSS):**
El menú actual se ve plano. Agregar:
- Separadores visuales entre secciones
- Tipografía más prominente para secciones principales
- Padding y espaciado más generoso por ítem
- Estado activo/hover visible

**Aceptación:** el menú mobile muestra solo los ítems correctos, tiene logo BSC centrado y clickeable, iconografía consistente con desktop, sin texto "Wappy", y visualmente no se ve plano.

---

### ══ P1 — IMPORTANTES Y VISIBLES ══

---

#### BSC-009 · Ajustar cards de productos y CTA
**Archivo:** `woocommerce/content-product.php`, CSS de products

1. Cambiar texto del botón de "Agregar" a "¡Lo quiero!" — buscar en el template de product y en `functions.php` si hay un `woocommerce_product_single_add_to_cart_text` filter
```php
add_filter('woocommerce_product_add_to_cart_text', function() {
    return '¡Lo quiero!';
});
```
2. Dar más padding lateral a las cards: `.product-card { padding: 0 16px; }` (ajustar según diseño)
3. Hover del botón con línea/borde negro BSC:
```css
.add-to-cart-btn:hover {
    background: transparent;
    border: 2px solid #000;
    color: #000;
}
```

---

#### BSC-010 · Eliminar espacio excesivo entre carrusel y "Favoritos en BSC" en mobile
**Archivo:** CSS del home, template del home

Buscar el margin-bottom del bloque del carrusel y el padding-top del bloque de favoritos en mobile. Reducirlos. Usar `@media (max-width: 768px)` para aplicar solo en mobile.

---

#### BSC-011 · Acercar columnas de íconos en "Favoritos en BSC"
**Archivo:** template de la sección de tipos de piel, CSS

Reducir el `gap` o `column-gap` entre las dos columnas del grid de íconos. Los íconos de Piel Seca, Mixta, etc. deben verse más integrados visualmente.

---

#### BSC-012 · Scroll automático desde tipos de piel a productos
**Archivo:** `assets/js/` (crear o editar script de favoritos), template de tipos de piel

**Implementación:**
1. Cada ítem de tipo de piel necesita un `data-target` que apunte al ID del slide/sección correspondiente:
```html
<div class="skin-type-item" data-target="#productos-piel-seca">Piel Seca</div>
```
2. El bloque de productos de cada tipo necesita un ID:
```html
<section id="productos-piel-seca">...</section>
```
3. El JS:
```javascript
$('.skin-type-item').on('click', function() {
    const target = $(this).data('target');
    const headerHeight = $('.site-header').outerHeight();
    const offset = $(target).offset().top - headerHeight - 20;
    $('html, body').animate({ scrollTop: offset }, 600);
});
```
4. Si los productos están en un slider/carrusel, el click también debe activar el slide correcto además del scroll.

---

#### BSC-013 · Reorganizar bolitas de marcas en 3x3
**Archivo:** template del bloque de marcas

Cambiar el layout de las bolitas de fila horizontal a grilla 3x3 sobre la imagen. CSS:
```css
.brands-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    position: absolute;
    /* posicionado sobre la imagen */
}
```
Son 9 marcas: COSRX, Some by Mi, Heimish, Pyunkang Yul, I'm from, Beauty of Joseon, TOCOBO, Banila Co, Benton.

---

#### BSC-014 · Quitar espacio entre foto textura crema y bloque azul
**Archivo:** CSS del home o template del home

Buscar `margin-bottom` o `padding-bottom` en el bloque con la foto de textura y `margin-top` o `padding-top` en el bloque azul. Setear a 0.

---

#### BSC-015 · Reducir peso visual del bloque "Bubble Lover / Conoce más de nosotros" en mobile
**Archivo:** CSS, template del bloque institucional

En mobile:
- Reducir `font-size` del título
- Reducir `padding` general del bloque
- Reducir `gap` entre los 3 ítems (envíos, puntos, regalito)

---

#### BSC-016 · Corregir links del footer
**Archivo:** `footer.php` o el template que renderiza el bloque oscuro del footer

Cambiar los `href` de:
- Envíos y devoluciones → URL real de la página de envíos
- Preguntas frecuentes → URL real del FAQ
- Contacto → `/contacto` o la URL que corresponda

Verificar primero que esas páginas existen en WordPress. Si no existen, crearlas con slug correcto.

---

#### BSC-017 · Corregir bloque "Tienda" / K-Beauty
**Archivo:** `footer.php`

1. Cambiar el texto "Tienda" por "K-Beauty" en el heading del bloque
2. "Entrega inmediata" → `href` a la página diseñada (el cliente debe confirmar el slug)
3. "Encargos" → `href` a WhatsApp con mensaje prellenado:
```html
<a href="https://wa.me/57XXXXXXXXXX?text=Hola%2C%20quiero%20hacer%20un%20encargo%20coreano" target="_blank">Encargos</a>
```
(reemplazar el número de teléfono con el real del negocio)

---

#### BSC-018 · Corregir "Mi cuenta" y "Bubble Creators"
**Archivo:** `footer.php`

1. Quitar el ítem "Datos" del bloque Mi cuenta
2. "Bubble Creators" debe abrir el mini formulario diseñado — si el formulario existe en una página, el link va a esa URL. Si era un modal, el link debe tener un trigger: `href="#bubble-creators-modal"` o similar.

---

#### BSC-019 · Arreglar feedback del newsletter
**Archivo:** `assets/js/newsletter.js` (o donde esté el handler), template del formulario

**Implementación:**
1. Verificar que el submit del formulario esté siendo interceptado con AJAX (no recarga la página)
2. En el callback de éxito, mostrar confirmación:
```javascript
$.ajax({
    url: bsc_ajax.url,
    method: 'POST',
    data: { action: 'bsc_newsletter_subscribe', email: email, nonce: nonce },
    success: function(response) {
        if (response.success) {
            // Mostrar mensaje de éxito
            $('.newsletter-form').html('<p class="success-msg">¡Gracias! Ya eres parte de la comunidad BSC.</p>');
        } else {
            $('.newsletter-error').text('Hubo un error. Intenta de nuevo.').show();
        }
    }
});
```
3. Si el formulario viene de un plugin (Mailchimp for WooCommerce, etc.): revisar la configuración del plugin para activar el feedback de éxito/error.

---

#### BSC-020 · Quitar espacio entre bloque azul y footer negro
**Archivo:** CSS del home/footer

```css
.blue-block { margin-bottom: 0; padding-bottom: 0; }
.site-footer { margin-top: 0; padding-top: 40px; /* el padding que corresponda */ }
```

---

#### BSC-021 · Reemplazar "Bubble Blog" por "Próximamente" + responsive mobile
**Archivo:** template del home donde aparece el bloque del blog

1. Cambiar el texto "Bubble blog" por "Próximamente" o el copy que defina el cliente
2. Mantener el bloque visual pero ocultar el contenido dinámico del blog
3. Corregir el responsive en mobile: el bloque se desborda de pantalla. Revisar `overflow: hidden` y los anchos de la imagen del browser mockup.

---

#### BSC-022 · Agregar botón flotante de WhatsApp en mobile
**Archivo:** `footer.php` o template global, CSS

```html
<!-- Solo visible en mobile -->
<a class="whatsapp-float" href="https://wa.me/57XXXXXXXXXX" target="_blank" aria-label="Contactar por WhatsApp">
    <img src="whatsapp-icon.svg" alt="WhatsApp">
</a>
```
```css
.whatsapp-float {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 999;
    display: none; /* oculto por defecto */
}
@media (max-width: 768px) {
    .whatsapp-float { display: block; }
}
```

**Importante:** verificar que no tape el botón de add-to-cart ni el mini-cart en mobile. Si hay PDPs con botón sticky, el WhatsApp debe tener `bottom` ajustado o ceder espacio.

---

#### BSC-023 · Actualizar copyright
**Archivo:** `footer.php`

Cambiar `© 2020 - 2025` por `© 2020 - 2026` en todas las instancias (desktop, tablet y mobile si el footer tiene renders distintos).

---

### ══ TICKETS DERIVADOS — necesarios para no dejar huecos ══

---

#### BSC-024 · Normalizar breakpoints del proyecto
Crear (o verificar que exista) un mapa de breakpoints único en el SCSS/CSS del theme:
```scss
$bp-mobile: 480px;
$bp-tablet: 768px;
$bp-tablet-lg: 1024px;
$bp-desktop: 1280px;
$bp-desktop-lg: 1440px;
```
Unificar todas las media queries del theme para que usen estas variables. Eliminar breakpoints arbitrarios dispersos.

---

#### BSC-025 · Documentar y normalizar z-index globales
Crear una escala de z-index en el CSS del theme:
```scss
// Z-INDEX SCALE BSC
$z-base: 1;
$z-overlay: 10;
$z-slider: 20;
$z-header: 100;
$z-mini-cart: 200;
$z-menu-mobile: 300;
$z-modal: 400;
$z-whatsapp-float: 999;
$z-toast: 1000;
```
Aplicar a todos los elementos posicionados. Esto resuelve los conflictos de overlay del hero y el problema del add-to-cart en iPad.

---

#### BSC-026 · Auditoría de links internos
Hacer un crawl manual de:
- home (todos los CTAs y links)
- header desktop y mobile
- footer
- bloques intermedios del home
- páginas de marcas

Verificar que ninguno apunte a `#` ni a una URL que devuelva 404. Documentar los que necesitan URL real del cliente.

---

#### BSC-027 · Optimizar imágenes del proyecto
Antes de subir imágenes "reales" de banners:
- Tamaño máximo para hero/banner: 1920px de ancho, compresión 80%, formato WebP con fallback JPG
- Tamaño máximo para bolitas de marcas: 200px de diámetro, compresión 85%
- Tamaño máximo para cards de producto: usar los sizes que ya configura WooCommerce
- Activar lazy load nativo (`loading="lazy"`) en imágenes fuera del viewport (excepto el primer slide del hero que es LCP)

---

#### BSC-028 · QA cross-browser antes de producción
Probar mínimamente en:
- Safari iPhone (iOS 16+)
- Safari iPad (iPadOS 16+)
- Chrome desktop
- Edge desktop
- Chrome Android

Flujos a validar en cada uno:
1. Abrir home → navegar entre secciones → velocidad
2. Abrir menú mobile → navegar categorías → cerrar
3. Agregar producto al carrito → verificar badge → ir al carrito → cambiar cantidad a 0 → badge = 0
4. Click en una marca → ver productos
5. Click en tipo de piel → scroll automático
6. Enviar newsletter → ver confirmación
7. Click en Encargos → WhatsApp se abre con mensaje
8. Ver footer → todos los links funcionan

---

#### BSC-029 · Cleanup de plugins antes de producción
Revisar con el cliente y confirmar cuáles plugins son necesarios. Candidatos a desactivar/eliminar:
- Cualquier plugin de "coming soon" o "maintenance mode" heredado
- Plugins de backup si hay solución externa
- Plugins de SEO duplicados
- Cualquier plugin del dev anterior que no tenga uso real
- Page builders instalados que no se usen

---

### ══ TICKETS DE SALIDA A PRODUCCIÓN ══

---

#### BSC-030 · Backup completo pre-deploy
- Backup de archivos del servidor (zip de `/wp-content/`)
- Export de base de datos completa
- Documentar versiones de WP, WooCommerce y tema

#### BSC-031 · Staging final
- Levantar staging que refleje producción exacta
- Aplicar todos los cambios en staging primero
- Validar smoke test completo en staging antes de mover a producción

#### BSC-032 · Deploy a producción
1. Aplicar cambios al server de producción
2. Vaciar toda la caché (OPcache, WP cache, CDN si hay)
3. Validar inmediatamente: home, carrito, add-to-cart, marcas, menú mobile, newsletter, WhatsApp, footer

#### BSC-033 · Plan de rollback
Tener claro cómo volver al estado anterior si el deploy rompe algo crítico:
- Restaurar backup de archivos
- Restaurar base de datos
- Vaciar caché

---

## 5. ORDEN DE EJECUCIÓN RECOMENDADO

```
1. BSC-001  Performance (auditoría — no tocar UI sin esto)
2. BSC-024  Normalizar breakpoints
3. BSC-025  Normalizar z-index
4. BSC-004  Badge del carrito
5. BSC-005  Add-to-cart iPad
6. BSC-006  Template de marcas (productos vacíos)
7. BSC-007  Lentitud en click de marcas
8. BSC-008  Menú mobile
9. BSC-002  Hero responsive
10. BSC-003  Contenido del hero
11. BSC-013  Grid 3x3 de marcas
12. BSC-009  Cards y CTA
13. BSC-012  Scroll automático tipos de piel
14. BSC-010  Espaciado carrusel/favoritos mobile
15. BSC-011  Columnas de íconos favoritos
16. BSC-014  Espacio textura crema/bloque azul
17. BSC-015  Bubble Lover mobile
18. BSC-016  Links del footer
19. BSC-017  Bloque K-Beauty/Tienda
20. BSC-018  Mi cuenta/Bubble Creators
21. BSC-019  Newsletter feedback
22. BSC-020  Espacio bloque azul/footer
23. BSC-021  Bubble Blog → Próximamente
24. BSC-022  WhatsApp flotante mobile
25. BSC-023  Copyright 2026
26. BSC-026  Auditoría de links
27. BSC-027  Imágenes optimizadas
28. BSC-028  QA cross-browser
29. BSC-029  Cleanup de plugins
30. BSC-030  Backup
31. BSC-031  Staging final
32. BSC-032  Deploy
33. BSC-033  QA post-deploy
```

---

## 6. CRITERIOS DE ACEPTACIÓN GLOBAL (el sitio está listo para producción cuando...)

- [ ] La navegación entre secciones del home es fluida
- [ ] El hero no tiene superposiciones de texto/botones en ningún dispositivo
- [ ] El slider tiene imágenes reales y contenido correcto
- [ ] El badge del carrito refleja el número real de productos (incluido 0)
- [ ] Add-to-cart funciona en iPad Safari
- [ ] Al entrar a cualquier marca se ven sus productos reales
- [ ] El click en bolitas de marcas responde rápido
- [ ] El menú mobile tiene logo BSC, iconos correctos, sin ítems sobrantes, no se ve plano
- [ ] Click en tipo de piel hace scroll al bloque correcto con offset del header sticky
- [ ] Newsletter muestra confirmación visible al enviar
- [ ] "Encargos" abre WhatsApp con mensaje prellenado
- [ ] Todos los links del footer llevan a páginas reales
- [ ] Bubble Blog dice "Próximamente" y se ve bien en mobile
- [ ] Botón de WhatsApp flotante visible en mobile sin tapar otros elementos
- [ ] Copyright dice 2020 - 2026
- [ ] No hay errores JS en consola
- [ ] No hay warnings PHP nuevos en logs
- [ ] QA aprobado en Safari iPhone, Safari iPad, Chrome desktop, Edge desktop

---

## 7. REGLAS PARA CLAUDE CODE AL TRABAJAR EN ESTE PROYECTO

1. **Antes de proponer código**, identificar el archivo exacto del theme donde vive el cambio
2. **No crear plugins nuevos** — si el cambio cabe en la theme, va en la theme
3. **No duplicar lógica de WooCommerce** — usar hooks nativos (`add_filter`, `add_action`) antes de reescribir
4. **No meter librerías externas** sin revisar que no estén ya cargadas
5. **Cada cambio de CSS** debe tener su breakpoint correcto y no asumir solo desktop
6. **Cada cambio de JS** debe considerar Safari iOS (sin eventos `hover`, cuidado con `click` en elementos no-ancla)
7. **Antes de cambiar cualquier URL** del footer/menú, verificar que la página destino existe
8. **Para el template de marcas** — no asumir que es un problema de CSS. Revisar primero si la query devuelve productos
9. **Para el badge del carrito** — no hacer polling. Usar los eventos nativos de WooCommerce
10. **Para el WhatsApp flotante** — verificar z-index contra add-to-cart sticky en PDPs antes de definir la posición

---

*Documento generado para trabajo autónomo de Claude Code en el proyecto Bubble Skin Care.*
*Fuentes: PDF de ajustes del cliente + claude.md + todos_notion_bsc.md + resumen_proyecto_bsc.md*
