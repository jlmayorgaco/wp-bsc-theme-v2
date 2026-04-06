# BSC Tickets — 054 a 066

Tickets de la segunda fase: responsive de Mi Cuenta, checkout, admin BSC ampliado, roles y stock.

---

# BSC-054 — Responsive Mi Cuenta: Lista de pedidos (`/mi-cuenta/orders/`)

## Objetivo
Corregir el layout de la tabla de pedidos en la página de Mi Cuenta para que sea completamente usable en mobile (< 768px) y tablet (768–1024px) sin overflow horizontal ni texto cortado.

## Contexto
La tabla de pedidos en `/mi-cuenta/orders/` usa `BSC_Orders_Table` (en `components/orders/orders-table.php`). En desktop funciona bien, pero en mobile el contenido se corta o genera scroll horizontal. El cliente reporta que los clientes no pueden ver bien sus pedidos desde el celular.

## Archivos a revisar
- `woocommerce/myaccount/orders.php` — template de la lista
- `components/orders/orders-table.php` — clase `BSC_Orders_Table`
- `style.css` — estilos de `.bsc__orders-*`

## Implementación exacta
1. **Tabla en mobile**: convertir la tabla de pedidos en tarjetas stacked en mobile (< 768px):
   - Cada `<tr>` se convierte en un card con `display: block`
   - Cada celda usa `::before { content: attr(data-title) }` para mostrar el label
   - Columnas a mostrar en mobile: número de pedido, fecha, total, estado, botón "Ver"
2. **Tablet** (768–1024px): reducir columnas, eliminar las menos importantes (ej. número de ítems)
3. En `components/orders/orders-table.php`: agregar atributo `data-title="..."` en cada `<td>` para los labels en mobile
4. Verificar que el botón "Ver pedido" es tapeable en mobile (min 44×44px touch target)
5. Verificar paginación: en mobile los botones de página deben ser accesibles

## Qué NO se acepta
- Scroll horizontal en la tabla
- Texto truncado sin posibilidad de verlo completo
- Botones demasiado pequeños para touch

## Criterios de aceptación
- En < 768px: cada pedido se muestra como card vertical legible
- En 768–1024px: tabla compacta sin overflow
- El botón "Ver pedido" tiene al menos 44px de altura
- No hay scroll horizontal en ningún breakpoint

## QA manual
- Chrome DevTools → iPhone 12 → `/mi-cuenta/orders/` → verificar que se ven cards
- Verificar en iPad (768px) → tabla compacta sin overflow
- Tap en "Ver pedido" → navega correctamente al detalle

## Commit sugerido
`fix(my-account): [BSC-054] responsive orders list with card layout on mobile and tablet`

---

# BSC-055 — Responsive Mi Cuenta: Detalle de pedido (`/mi-cuenta/view-order/`)

## Objetivo
Hacer que la vista de detalle de pedido (`/mi-cuenta/view-order/{id}/`) sea completamente usable en mobile y tablet. Corregir overflow, items del pedido, barra de progreso y datos de facturación.

## Contexto
`woocommerce/myaccount/view-order.php` incluye la barra de progreso (`BSC_Order_Progress_Bar`), los ítems del pedido, los datos de dirección y el total. En mobile hay overflow en la tabla de ítems y la barra de progreso se ve cortada.

## Archivos a revisar
- `woocommerce/myaccount/view-order.php`
- `components/orders/order-progress-bar.php`
- `style.css` — estilos `.bsc__thankyou-*`, `.bsc__order-progress-*`

## Implementación exacta
1. **Tabla de ítems del pedido**: en mobile usar layout de lista en vez de tabla:
   - `<tr>` con `display: block` en < 768px
   - Imagen del producto: alinear a la izquierda con texto a la derecha
   - Precio y cantidad en la misma línea
2. **Barra de progreso**: en mobile usar versión vertical (pasos apilados) o al menos asegurarse que los 3 pasos caben sin cortar texto
3. **Datos de envío/facturación**: en mobile apilados (block), en desktop dos columnas
4. **Botones de acción** (`.bsc__thankyou-actions`): ya tienen flex-column en mobile (de BSC-022) — verificar que funciona
5. Verificar que los totales (subtotal, envío, descuento, total) se alinean correctamente en mobile

## Qué NO se acepta
- Overflow horizontal en la tabla de ítems
- Barra de progreso con texto cortado
- Precios que se superponen al nombre del producto

## Criterios de aceptación
- En < 768px: ítems del pedido legibles en formato lista
- Barra de progreso visible completa en mobile
- Todos los totales alineados correctamente
- Los datos de dirección se ven bien en mobile

## QA manual
- DevTools iPhone → `/mi-cuenta/view-order/{id}/` → verificar layout
- Verificar pedido en estado "Enviado" → barra de progreso en paso 2
- Verificar totales (subtotal + envío + total) en mobile

## Commit sugerido
`fix(my-account): [BSC-055] responsive order detail view with stacked items and progress bar on mobile`

---

# BSC-056 — Responsive Bubble Points (`/bubble-points/`) especialmente `profile-points__footer`

## Objetivo
Corregir el layout de la página de Bubble Points en mobile y tablet, con énfasis en la sección `profile-points__footer` que se rompe visualmente en pantallas pequeñas. También corregir cualquier problema de datos stale o hardcodeados.

## Contexto
`page-bubble-points.php` y el plugin en `plugins/bubble-points/` renderizan los puntos, nivel y beneficios del usuario. La sección `.profile-points__footer` contiene los cupones/tickets canjeables y se muestra mal en mobile: overflow, tarjetas demasiado anchas, texto fuera de contenedores.

## Archivos a revisar
- `page-bubble-points.php` — template de la página
- `plugins/bubble-points/views/` — vistas del sistema de puntos
- `style.css` — estilos `.profile-points__*`, `.bsc-ticket--*`

## Implementación exacta
1. **`profile-points__footer`**: en mobile (< 768px):
   - Cada cupón/ticket en una columna completa (`width: 100%`)
   - Grid de tickets: pasar de 3 columnas a 1 columna en mobile, 2 en tablet
2. **Sección de nivel/badge**: centrado y legible en mobile
3. **Barra de progreso de puntos** (si existe): `width: 100%` en mobile
4. **Botón de canje**: ancho mínimo 200px, centrado en mobile
5. Verificar que el saldo de puntos se lee de `user_meta 'bubble_points_balance'` (no hardcodeado — ya corregido en BSC-022, verificar que persiste)
6. En `style.css`: agregar breakpoints para `.profile-points__footer` y sus hijos

## Qué NO se acepta
- Overflow horizontal en la sección de tickets
- Tarjetas de cupones más anchas que la pantalla
- Saldo de puntos hardcodeado (356 o cualquier número fijo)

## Criterios de aceptación
- En < 768px: tickets en 1 columna, sin overflow
- En 768–1024px: tickets en 2 columnas
- El saldo de puntos muestra el valor real del usuario
- Los tickets usados/vencidos tienen `grayscale` y `pointer-events: none`

## QA manual
- DevTools iPhone → `/bubble-points/` → verificar que no hay overflow en `.profile-points__footer`
- Crear usuario con 0 puntos → verificar que no se muestra 356
- Tablet 768px → 2 columnas de tickets

## Commit sugerido
`fix(bubble-points): [BSC-056] responsive profile-points footer with correct grid and real balance`

---

# BSC-057 — Mi Cuenta: Editar Dirección y Editar Cuenta — responsive, seguridad y bugs

## Objetivo
Corregir todos los problemas de usabilidad, responsive, seguridad y formularios en las páginas:
- `/mi-cuenta/edit-address/` (selector billing/shipping)
- `/mi-cuenta/edit-address/billing/` (formulario de facturación)
- `/mi-cuenta/edit-address/shipping/` (formulario de envío)
- `/mi-cuenta/edit-account/` (datos personales + skin profile)

## Contexto
`woocommerce/myaccount/form-edit-address.php` y `form-edit-account.php` tienen inputs de WooCommerce que en mobile se quedan a tamaño fijo o tienen font-size < 16px (dispara zoom en iOS). El formulario de cuenta tiene campos custom (cumpleaños, tipo de piel, necesidades) que pueden no tener validación server-side suficiente.

## Archivos a revisar
- `woocommerce/myaccount/form-edit-address.php`
- `woocommerce/myaccount/form-edit-account.php`
- `woocommerce/myaccount/my-address.php`
- `style.css` — estilos `.bsc__shipping-address`, `.bsc__account-form`, `.bsc__account-grid`
- `inc/setup/theme-setup.php` — hook `woocommerce_save_account_details`

## Implementación exacta

### A. Responsive (todas las páginas)
1. En `style.css`: todos los `<input>`, `<select>`, `<textarea>` dentro de `.bsc__shipping-address` y `.bsc__account-form` deben tener:
   ```css
   @media (max-width: 768px) {
     font-size: 16px !important;  /* evita zoom en iOS */
     width: 100%;
   }
   ```
2. El grid de dos columnas en `.bsc__account-grid` debe colapsar a 1 columna en mobile
3. Los campos de WooCommerce (`.woocommerce-address-fields__field-wrapper`) deben tener `float: none; width: 100%` en mobile (ya parcialmente implementado en BSC-024 — verificar que funciona en estas páginas)
4. El botón de guardar: ancho mínimo 200px, centrado en mobile

### B. Seguridad (`form-edit-account.php` y hook en `theme-setup.php`)
1. Verificar que el hook `woocommerce_save_account_details` en `theme-setup.php` valida todos los campos custom:
   - `bsc_birthday`: validar formato fecha (`YYYY-MM-DD` o `DD/MM/YYYY`)
   - `bsc_skin_type`: whitelist de valores permitidos (Grasa, Mixta, Seca, Normal, Normal a seca, Normal a grasa)
   - `bsc_sensitivity`: whitelist de valores permitidos
   - `bsc_needs1–4`: `sanitize_text_field` ya aplicado, verificar que no se guarda HTML
2. La edición de dirección ya usa el nonce de WooCommerce (`woocommerce-edit-address-nonce`) — verificar que el check está presente
3. Los campos de contraseña en `form-edit-account.php` los maneja WooCommerce nativo — no modificar

### C. Bugs a corregir
1. **Título de la página de dirección**: actualmente dice "Datos de Facturación" para ambas direcciones (billing y shipping) — corregir para mostrar el título correcto según `$load_address`
2. **Selector billing/shipping** en `my-address.php`: verificar que los links a `/billing/` y `/shipping/` son correctos y se ven bien en mobile
3. **Campos de estado/departamento y ciudad** en el formulario de billing: si el usuario tiene una dirección guardada, verificar que los valores se pre-llenan correctamente al volver a la página

## Qué NO se acepta
- font-size < 16px en inputs en mobile
- Zoom automático de iOS en campos de formulario
- Datos guardados sin sanitización whitelist para campos enum

## Criterios de aceptación
- Formularios usables en iPhone sin zoom involuntario
- Grid de dos columnas colapsa correctamente en mobile
- Título "Datos de Facturación" vs "Datos de Envío" correcto según la página
- Campos custom validados con whitelist en el server

## QA manual
- iPhone → `/mi-cuenta/edit-address/billing/` → tap en campo → NO debe haber zoom
- Guardar una dirección → verificar que los datos persisten correctamente
- Intentar enviar `bsc_skin_type=<script>alert(1)</script>` → no debe guardarse
- Verificar el título H2: billing → "Datos de Facturación", shipping → "Datos de Envío"

## Commit sugerido
`fix(my-account): [BSC-057] responsive and secure edit address and account forms`

---

# BSC-058 — Checkout: ocultar envío sin dept/ciudad y corregir flat rate Cundinamarca/Bogotá

## Objetivo
Dos correcciones al checkout:
1. **No mostrar opciones de envío** mientras el usuario no haya seleccionado departamento Y ciudad — mostrar mensaje orientativo.
2. **Corregir el cálculo de flat rate**: cuando se selecciona Cundinamarca + Bogotá debe aplicar la tarifa de Bogotá; para cualquier otro destino, la tarifa general. Actualmente siempre muestra $9.000.

## Contexto
El checkout usa `js/checkout.js` para validación y `inc/woocommerce.php` para el hook `woocommerce_package_rates`. WooCommerce calcula el envío basándose en la dirección de billing/shipping, que se actualiza en el evento `update_checkout`. El problema de que siempre muestra 9.000 sugiere que WooCommerce no está detectando correctamente la zona de envío para Bogotá, probablemente porque el código de ciudad no coincide con el configurado en el shipping zone o porque el hook de `woocommerce_package_rates` tiene un bug en la detección.

## Archivos a revisar
- `js/checkout.js` — lógica de validación y eventos de actualización
- `inc/woocommerce.php` — hook `woocommerce_package_rates` y `woocommerce_before_calculate_totals`
- `woocommerce/checkout/form-billing.php` — formulario de billing
- WooCommerce Admin → Ajustes → Envíos → Zonas de envío (verificar manualmente)

## Implementación exacta

### A. Ocultar envío hasta que haya dept + ciudad seleccionados

**En `js/checkout.js`:**
```javascript
// Monitorear cambios en state y city
$(document).on('change', '#billing_state, #billing_city', function() {
    const state = $('#billing_state').val();
    const city  = $('#billing_city').val();
    toggleShippingVisibility(state, city);
});

function toggleShippingVisibility(state, city) {
    const $shippingRow = $('.woocommerce-shipping-totals, #shipping_method');
    const $shippingMsg = $('#bsc-shipping-pending-msg');
    if (!state || !city) {
        $shippingRow.closest('tr').hide();
        if (!$shippingMsg.length) {
            $('<tr id="bsc-shipping-pending-msg"><td colspan="2" style="color:#888;font-size:13px;padding:8px 0">Selecciona tu departamento y ciudad para ver las opciones de envío.</td></tr>')
                .insertAfter('.cart-subtotal');
        }
    } else {
        $shippingRow.closest('tr').show();
        $shippingMsg.remove();
    }
}

// Llamar al inicializar por si ya hay valores guardados
$(document).on('updated_checkout', function() {
    const state = $('#billing_state').val();
    const city  = $('#billing_city').val();
    toggleShippingVisibility(state, city);
});
```

### B. Corregir flat rate Cundinamarca/Bogotá

**Verificación del problema:**
1. En WP Admin → WooCommerce → Ajustes → Envíos → verificar que existe:
   - Zona 1: "Bogotá" con condición: `Colombia > Cundinamarca` y ciudad `Bogotá`
   - Zona 2: "Colombia" (resto del país)
2. Si las zonas están bien configuradas, el problema puede ser que WooCommerce no detecta la ciudad porque el valor del campo `billing_city` no coincide exactamente con el nombre en la zona.

**Solución en PHP** (si las zonas no funcionan correctamente):
En `inc/woocommerce.php`, reemplazar o complementar el filtro `woocommerce_package_rates`:
```php
add_filter('woocommerce_package_rates', 'bsc_force_shipping_by_location', 20, 2);
function bsc_force_shipping_by_location( array $rates, array $package ): array {
    $state = $package['destination']['state'] ?? '';
    $city  = strtolower( trim( $package['destination']['city'] ?? '' ) );

    $is_bogota = ( $state === 'CUN' && in_array( $city, ['bogotá', 'bogota', 'bogota d.c.', 'bogotá d.c.'] ) );

    foreach ( $rates as $rate_id => $rate ) {
        if ( $rate->method_id !== 'flat_rate' ) continue;
        // Solo dejar la tarifa correcta para el destino
        if ( $is_bogota && str_contains( strtolower( $rate->label ), 'bogot' ) ) continue; // keep
        if ( ! $is_bogota && ! str_contains( strtolower( $rate->label ), 'bogot' ) ) continue; // keep
        unset( $rates[ $rate_id ] );
    }
    return $rates;
}
```
3. **Importante**: verificar que las dos flat rates en WooCommerce tienen labels distintos que permitan diferenciarlas (ej. "Envío Bogotá" vs "Envío Colombia"). Si hay solo una flat rate configurada, crear la segunda en WP Admin.
4. Eliminar el hook `woocommerce_before_calculate_totals` que llama `calculate_shipping()` innecesariamente — puede causar loops.

## Qué NO se acepta
- Mostrar envío antes de que el usuario seleccione destino
- Siempre mostrar el mismo precio de envío sin importar la ciudad
- Modificar la configuración de WooCommerce Shipping Zones directamente desde código (debe hacerse desde admin)

## Criterios de aceptación
- Sin dept/ciudad: sección de envío oculta, mensaje visible
- Cundinamarca + Bogotá → tarifa de Bogotá
- Cualquier otro destino → tarifa general
- El mensaje desaparece cuando se seleccionan ambos campos

## QA manual
- Checkout vacío → departamento no seleccionado → envío oculto con mensaje
- Seleccionar Cundinamarca + Bogotá → verificar que aparece la tarifa correcta de Bogotá
- Seleccionar Antioquia + Medellín → aparece la tarifa general
- Completar pedido con Bogotá → el precio de envío es el correcto en el resumen

## Commit sugerido
`fix(checkout): [BSC-058] hide shipping until state+city selected and fix Bogota flat rate detection`

---

# BSC-059 — Admin BSC: Agregar páginas al menú BSC y protección por roles

## Objetivo
Expandir el menú BSC en el admin de WordPress para incluir:
- **BSC Home Favorites** (`bsc-home-favorites`) — gestión de los sliders/tabs de la home
- **BSC Slides** (link al CPT `home_slide`) — editor de los hero slides
- **BSC Bubble Points** (`bsc-bubble-points`) — gestión del sistema de puntos

Además, asegurar que estas páginas y todo el menú BSC está protegido correctamente por roles (ver BSC-060 para el detalle de roles).

## Contexto
El menú BSC actual (`admin/bsc-admin-menu.php`) tiene: Dashboard, Pedidos, Productos, Venta Presencial, Informes, Configuración. El cliente necesita también gestionar los slides de la home, los favoritos de la home y el sistema de puntos desde el admin BSC centralizado.

## Archivos a revisar
- `admin/bsc-admin-menu.php` — menú principal BSC
- `admin/bsc-orders-page.php` — para usar como referencia de estructura
- `includes/class-bsc-permissions.php` — allowed pages por rol

## Implementación exacta

### 1. Agregar submenús en `bsc_add_admin_menu()`

```php
// Home Favorites — gestión de productos destacados en la home
add_submenu_page(
    'bsc-dashboard',
    __( 'Home Favorites', 'bsc-2-0' ),
    __( 'Home Favorites', 'bsc-2-0' ),
    'manage_woocommerce',          // shop manager + admin
    'bsc-home-favorites',
    'bsc_render_home_favorites_page'
);

// Home Slides — link al CPT home_slide
add_submenu_page(
    'bsc-dashboard',
    __( 'Hero Slides', 'bsc-2-0' ),
    __( 'Hero Slides', 'bsc-2-0' ),
    'manage_woocommerce',
    'bsc-hero-slides',
    'bsc_render_hero_slides_redirect'
);

// Bubble Points
add_submenu_page(
    'bsc-dashboard',
    __( 'Bubble Points', 'bsc-2-0' ),
    __( 'Bubble Points', 'bsc-2-0' ),
    'manage_woocommerce',
    'bsc-bubble-points',
    'bsc_render_bubble_points_page'
);
```

### 2. Función `bsc_render_hero_slides_redirect()`
Redirige al editor nativo del CPT `home_slide` en vez de crear una página custom:
```php
function bsc_render_hero_slides_redirect(): void {
    wp_safe_redirect( admin_url('edit.php?post_type=home_slide') );
    exit;
}
```

### 3. `bsc_render_home_favorites_page()`
Vista con dos secciones:
- Lista editable de SKUs por pestaña (Últimos Lanzamientos, Piel Seca, Piel Normal, etc.)
- Formulario `<form method="POST">` que guarda los SKUs en `update_option('bsc_home_favorites_{slug}', $skus_array)`
- Nonce: `bsc_home_favorites_nonce`
- Guardar: `add_action('admin_post_bsc_save_home_favorites', 'bsc_save_home_favorites')`

### 4. `bsc_render_bubble_points_page()`
Vista de administración del sistema de puntos:
- Tabla de usuarios con sus puntos actuales (paginada, 20/página)
- Formulario para ajustar puntos manualmente: `update_user_meta($user_id, 'bubble_points_balance', $new_balance)`
- Solo accesible para admin y shop manager

### 5. Actualizar `BSC_Permissions::restrict_admin_access()` para incluir las nuevas páginas
```php
// shop_manager allowed list (full BSC)
$allowed_shop_manager = [
    'bsc-dashboard', 'bsc-orders', 'bsc-products', 'bsc-showroom',
    'bsc-reports', 'bsc-settings', 'bsc-home-favorites', 'bsc-hero-slides', 'bsc-bubble-points'
];
```

## Criterios de aceptación
- Las tres nuevas páginas aparecen en el menú BSC
- Solo admin y shop_manager pueden acceder a bsc-home-favorites, bsc-bubble-points
- bsc_operator solo puede ver bsc-orders y bsc-showroom (sin acceso a las nuevas páginas)
- Hero Slides redirige al editor nativo del CPT

## Commit sugerido
`feat(admin): [BSC-059] add home-favorites hero-slides and bubble-points to BSC menu`

---

# BSC-060 — Sistema de roles: Admin, Shop Manager, Operator y Customer default

## Objetivo
Redefinir y proteger el sistema de roles de WordPress para BSC:
- **Administrator**: acceso total sin restricciones
- **Shop Manager**: acceso completo a todo el menú BSC (incluidas nuevas páginas del BSC-059)
- **bsc_operator**: acceso únicamente a BSC → Pedidos y BSC → Venta Presencial
- **Customer**: rol por defecto para todos los nuevos registros

## Contexto
El sistema actual (`includes/class-bsc-roles.php` y `includes/class-bsc-permissions.php`) tiene `bsc_operator` y `bsc_employee`. El cliente quiere simplificar: solo `bsc_operator` (operador de pedidos) y aprovechar el `shop_manager` nativo de WooCommerce para el acceso completo a BSC. Los nuevos registros desde la web siempre deben ser `customer`.

## Archivos a revisar
- `includes/class-bsc-roles.php` — definición de roles
- `includes/class-bsc-permissions.php` — restricciones de acceso
- `admin/bsc-admin-menu.php` — visibilidad de menús por capability
- `page-register.php` — rol asignado al crear cuenta

## Implementación exacta

### 1. Actualizar `BSC_Roles::create()` en `class-bsc-roles.php`
- Eliminar `bsc_employee` (ya no se usa)
- Mantener `bsc_operator` con capabilities: `read`, `edit_orders`
- Shop Manager nativo de WooCommerce ya tiene `manage_woocommerce` + `edit_products` — no modificar

```php
// Remover bsc_employee si existe
remove_role('bsc_employee');

// bsc_operator: solo pedidos y showroom
add_role('bsc_operator', 'BSC Operator', [
    'read'        => true,
    'edit_orders' => true,
]);
```

### 2. Actualizar `BSC_Permissions::restrict_admin_access()` en `class-bsc-permissions.php`

```php
// Shop Manager → acceso completo a BSC (no redirigir)
if ( in_array('shop_manager', (array) $user->roles, true) ) {
    // Permitir todas las páginas BSC — no aplicar restricciones extra
    return;
}

// bsc_operator → solo pedidos + showroom
if ( in_array('bsc_operator', (array) $user->roles, true) ) {
    $page    = sanitize_text_field( $_GET['page'] ?? '' );
    $allowed = ['bsc-dashboard', 'bsc-orders', 'bsc-showroom'];
    if ( ! in_array($page, $allowed, true) ) {
        wp_safe_redirect( admin_url('admin.php?page=bsc-orders') );
        exit;
    }
}
```

### 3. Actualizar `bsc_restrict_admin_menus()` en `bsc-admin-menu.php`
- Solo ocultar menús nativos WP para `bsc_operator`
- Shop Manager: no ocultar nada (ya tiene acceso propio)

### 4. Asegurar Customer como rol por defecto en registros web
Verificar que `page-register.php` ya asigna `'role' => 'customer'` en `wp_insert_user()`. Si no es así, corregirlo.

También agregar en `functions.php`:
```php
// Asegurar que el rol por defecto de WordPress está configurado como 'customer'
add_filter('pre_option_default_role', function() {
    return 'customer';
});
```

### 5. Actualizar submenú `bsc_add_admin_menu()` para usar `manage_woocommerce`
- Las páginas que deben ser accesibles para shop_manager deben usar `'manage_woocommerce'` como capability (shop_manager ya tiene esta capability)
- Las páginas admin-only usan `'manage_options'`

## Qué NO se acepta
- Shop Manager sin acceso a BSC Pedidos
- Nuevos registros con rol `subscriber` o `editor`
- bsc_operator con acceso a configuración o productos

## Criterios de aceptación
- Crear usuario con rol shop_manager → puede ver todo el menú BSC
- Crear usuario con rol bsc_operator → solo ve BSC Pedidos y BSC Venta Presencial
- Registrar cuenta nueva desde `/register/` → el usuario es Customer
- Administrator: sin restricciones

## Commit sugerido
`fix(roles): [BSC-060] redefine BSC roles — shop_manager full BSC access, operator orders-only, customer default`

---

# BSC-061 — BSC Dashboard: implementación completa con KPIs relevantes

## Objetivo
Transformar el BSC Dashboard (`/wp-admin/admin.php?page=bsc-dashboard`) de su estado actual (2 KPIs básicos) a un dashboard operativo completo con los indicadores más importantes para el cliente.

## Contexto
`admin/bsc-admin-menu.php` → función `bsc_render_dashboard()` actualmente muestra "Pedidos hoy" y "Pedidos pendientes". El cliente necesita un overview real de su negocio en una sola pantalla.

## Archivos a revisar
- `admin/bsc-admin-menu.php` — función `bsc_render_dashboard()`
- `admin/bsc-reports-page.php` — para reutilizar lógica de KPIs con cache

## Implementación exacta

### KPI widgets a implementar (con transient cache 30min):

1. **Ventas hoy** — suma de `get_order_total()` de pedidos del día con status completed/processing
2. **Pedidos hoy** — count de pedidos creados hoy
3. **Pedidos pendientes** — count status pending + on-hold
4. **Pedidos en preparación** — count status wc-preparing
5. **Pedidos enviados** — count status wc-shipped (sin completar)
6. **Tasa de conversión** — pedidos del mes / visitas (si hay datos disponibles, si no omitir)
7. **Últimos 5 pedidos** — tabla compacta con número, cliente, total, estado, link a detalle
8. **Productos con stock bodega = 0** — alerta visual con lista de productos sin stock

### Estructura HTML:
```php
<div class="wrap bsc-admin-dashboard">
    <h1>BSC Dashboard</h1>
    <div class="bsc-kpi-grid">
        <!-- KPI cards: ventas hoy, pedidos hoy, pendientes, preparando, enviados -->
    </div>
    <div class="bsc-dashboard-tables">
        <div class="bsc-recent-orders"> <!-- últimos 5 pedidos --> </div>
        <div class="bsc-stock-alerts">  <!-- productos sin stock bodega --> </div>
    </div>
</div>
```

### Cache:
```php
$cache_key = 'bsc_dashboard_kpis';
$data = get_transient($cache_key);
if (!$data) {
    // calcular KPIs
    set_transient($cache_key, $data, 30 * MINUTE_IN_SECONDS);
}
```

### CSS inline (dentro del render):
- Grid responsivo de KPI cards: 2 columnas en mobile, 4 en desktop
- Stock alerts: fondo rojo suave `#fff5f5`, borde `#fc8181`
- Últimos pedidos: tabla compacta con hover y link al pedido

## Criterios de aceptación
- Dashboard muestra los 5 KPI numéricos en cards visuales
- Tabla de últimos 5 pedidos con link al detalle
- Alerta de productos con stock = 0 visible
- Los datos se actualizan al expirar el transient (30min)

## Commit sugerido
`feat(admin-dashboard): [BSC-061] full operational KPI dashboard with recent orders and stock alerts`

---

# BSC-062 — BSC Products: tabla completa de productos para Shop Manager

## Objetivo
Implementar la página `bsc-products` (`/wp-admin/admin.php?page=bsc-products`) como una tabla funcional de productos, reemplazando el placeholder actual "En construcción (BSC-034)". Debe ser simple y orientada a las tareas del equipo interno: ver stock, ver precio, editar rápido.

## Contexto
`admin/bsc-admin-menu.php` → `bsc_render_products_page()` actualmente solo tiene un placeholder con link al editor nativo de WooCommerce. El cliente quiere una vista custom dentro del BSC que muestre los datos más relevantes de los productos.

## Archivos a revisar
- `admin/bsc-admin-menu.php` — función `bsc_render_products_page()`
- `admin/class-bsc-orders-table.php` — para usar como referencia de `WP_List_Table`
- `includes/class-bsc-stock.php` — para mostrar stock bodega + tienda

## Implementación exacta

### 1. Crear `admin/bsc-products-page.php` con la función `bsc_render_products_page()`

Columnas de la tabla:
- **Imagen** (thumbnail 60×60px)
- **Nombre** (con link a edición BSC simplificada — BSC-065)
- **SKU**
- **Precio** (precio regular y precio de oferta si existe)
- **Stock Bodega** (`_stock_bodega`) — con input editable inline
- **Stock Tienda** (`_stock_tienda`) — con input editable inline
- **Estado** (published/draft)
- **Acciones** (Editar en BSC, Ver en tienda)

### 2. Paginación
- 20 productos por página
- Usar `WP_Query` con `update_post_meta_cache: true` y `update_post_term_cache: true`

### 3. Filtros básicos
- Buscador por nombre/SKU (query param `s`)
- Filtro por estado (todos / publicados / borradores)

### 4. Edición inline de stock
- Los inputs de `_stock_bodega` y `_stock_tienda` son editables en la tabla
- Al cambiar el valor y hacer blur: AJAX `bsc_update_product_stock` que guarda el meta
- Nonce: `bsc_products_nonce`
- Capability check: `edit_products`

### 5. Enqueue de script admin
En `admin/bsc-admin-menu.php`, enqueue de `js/bsc-admin-products.js` solo en la página `bsc-products`.

## Criterios de aceptación
- La tabla carga en < 2s con 100 productos
- Se puede editar el stock bodega y tienda inline
- La búsqueda por nombre/SKU funciona
- Los thumbnails se cargan con `loading="lazy"`

## Commit sugerido
`feat(admin-products): [BSC-062] implement BSC products table with inline stock editing`

---

# BSC-063 — BSC Reports: implementación completa con filtros y exportación

## Objetivo
Ampliar la página de Informes (`/wp-admin/admin.php?page=bsc-reports`) más allá de los KPIs básicos actuales, agregando: filtro de fecha flexible, desglose por categoría, top de productos más vendidos, y exportación a CSV.

## Contexto
`admin/bsc-reports-page.php` tiene un filtro de fecha básico (por mes) y muestra 3 KPIs + top 5 productos. El cliente necesita poder ver la data con más detalle y exportarla.

## Archivos a revisar
- `admin/bsc-reports-page.php` — implementación actual

## Implementación exacta

### 1. Filtros mejorados
- Rango de fechas customizable: `date_from` y `date_to` (inputs `type="date"`)
- Preset rápidos: Hoy, Esta semana, Este mes, Mes anterior, Este año
- Filtro por estado: completed, processing, preparing, shipped (checkboxes)

### 2. KPI cards (ya existentes, mejorar):
- Total de ventas (suma)
- Número de pedidos
- Ticket promedio
- **Nuevo**: Ventas de Showroom vs Ventas Web (usando `_bsc_is_showroom_sale`)
- **Nuevo**: Promedio de ítems por pedido

### 3. Top 10 productos más vendidos
- Tabla con: producto, categoría, unidades vendidas, ingresos totales
- Links a edición del producto

### 4. Desglose por categoría
- Tabla: categoría, pedidos que la incluyen, ingresos totales
- Ordenar por ingresos desc

### 5. Exportación CSV
- Botón "Exportar CSV" que descarga los datos del rango seleccionado
- Columnas: fecha, #pedido, cliente, productos, total, estado, canal (web/showroom)
- Usar `fputcsv` con BOM UTF-8 (ya implementado en BSC-034 — reutilizar)

### 6. Cache
- Transient keyed por `md5(date_from . date_to . status_filter)`, TTL 1 hora
- Botón "Limpiar caché" que hace `delete_transient($cache_key)` con nonce

## Criterios de aceptación
- Los filtros de fecha funcionan y actualizan los datos
- La exportación CSV descarga los pedidos correctamente
- El desglose por categoría es visible
- Los datos de showroom vs web están diferenciados

## Commit sugerido
`feat(admin-reports): [BSC-063] advanced reports with date filters category breakdown and CSV export`

---

# BSC-064 — BSC Settings: configuración general del theme

## Objetivo
Implementar la página de Configuración BSC (`/wp-admin/admin.php?page=bsc-settings`) con los ajustes más relevantes del theme que actualmente están hardcodeados en el código.

## Contexto
`admin/bsc-admin-menu.php` → `bsc_render_settings_page()` actualmente solo tiene "En construcción". El cliente necesita poder cambiar configuraciones básicas sin editar código.

## Archivos a revisar
- `admin/bsc-admin-menu.php` — función `bsc_render_settings_page()`
- `functions.php` — constantes configurables

## Implementación exacta

### Secciones de configuración:

**1. General**
- `bsc_whatsapp_number` — número de WhatsApp (actualmente hardcodeado `573156922859`)
- `bsc_contact_email` — email de contacto (actualmente `BSC_CONTACT_EMAIL` en `functions.php`)
- `bsc_free_shipping_threshold` — monto mínimo para envío gratis (actualmente 300000 en `inc/woocommerce.php`)

**2. Tienda**
- `bsc_bogota_shipping_label` — label de la tarifa de Bogotá (para identificarla en BSC-058)
- `bsc_default_max_products_slider` — cantidad de productos por slider (actualmente `max_products = 5`)

**3. Emails**
- `bsc_email_from_name` — nombre del remitente en emails BSC
- `bsc_email_from_address` — dirección del remitente

### Implementación:
- Usar `update_option('bsc_whatsapp_number', sanitize_text_field($val))`
- Formulario con nonce `bsc_settings_nonce`
- Al guardar: `update_option()` para cada campo
- Al cargar: `get_option('bsc_settings_whatsapp', '573156922859')` con fallback al valor hardcodeado

### Actualizar los archivos que usan valores hardcodeados:
- `components/footer.php`: reemplazar `573156922859` por `get_option('bsc_whatsapp_number', '573156922859')`
- `components/whatsapp.php`: idem
- `inc/woocommerce.php`: reemplazar `300000` por `(int) get_option('bsc_free_shipping_threshold', 300000)`

## Criterios de aceptación
- Cambiar el número de WhatsApp desde admin → el botón de WhatsApp en el frontend usa el nuevo número
- Cambiar el umbral de envío gratis → se aplica en el cálculo del checkout
- El formulario guarda con nonce y sanitiza todos los inputs

## Commit sugerido
`feat(admin-settings): [BSC-064] implement BSC settings page with configurable WhatsApp email and shipping options`

---

# BSC-065 — BSC Products: vista de detalle simplificada para Shop Manager

## Objetivo
Crear una vista simplificada de edición de producto dentro del admin BSC (`/wp-admin/admin.php?page=bsc-product-edit&id={id}`) para que el Shop Manager pueda editar los campos más importantes de un producto sin usar la interfaz nativa y compleja de WooCommerce.

## Contexto
La edición nativa de WooCommerce es compleja para usuarios no técnicos. El cliente quiere que el Shop Manager pueda editar: nombre, descripción corta, imágenes, galería, categorías, reseñas, marcas, covers de producto y stock dual — sin ver las opciones avanzadas de WooCommerce.

## Archivos a revisar
- `admin/bsc-admin-menu.php` — donde se registra la nueva sub-página
- `inc/admin/product-covers.php` — meta boxes de imágenes extra y stock dual
- `includes/class-bsc-stock.php` — para guardar los stocks

## Implementación exacta

### 1. Registrar la ruta
En `bsc_add_admin_menu()`:
```php
add_submenu_page(
    null,  // oculto del menú lateral
    __('Editar Producto — BSC', 'bsc-2-0'),
    __('Editar Producto', 'bsc-2-0'),
    'edit_products',
    'bsc-product-edit',
    'bsc_render_product_edit_page'
);
```

### 2. Crear `admin/bsc-product-edit-page.php`

Secciones del formulario (todo en una página, `method="POST"`):

**Sección A: Básico**
- Nombre del producto (`post_title`)
- Descripción corta (`post_excerpt`, con editor simple — no Gutenberg)
- Estado: publicado / borrador

**Sección B: Imágenes**
- Imagen principal (`_thumbnail_id`) — uploader con media library
- Galería de imágenes (`_product_image_gallery`) — múltiples imágenes
- Covers extra BSC (`_bsc_extra_image_1`, `_bsc_extra_image_2`, `_bsc_extra_image_3`) — ya implementados en `inc/admin/product-covers.php`

**Sección C: Categorías y datos**
- Categorías (`product_cat`) — checkboxes de las categorías existentes
- Tags (`product_tag`) — input de texto
- SKU (`_sku`)

**Sección D: Stock**
- Stock Bodega (`_stock_bodega`)
- Stock Tienda (`_stock_tienda`)
- Tipo de envío (`_envio_tipo`): bodega / tienda / ambos

**Sección E: Reseñas**
- Toggle `comment_status` (habilitar/deshabilitar reseñas)

### 3. Guardar cambios
- Nonce `bsc_product_edit_nonce`
- Capability check: `edit_products` + verificar que el post es de tipo `product`
- Usar `wp_update_post()` para nombre/descripción/estado
- Usar `update_post_meta()` para metas
- Usar `wp_set_object_terms()` para categorías y tags
- Redirigir de vuelta a `bsc-products` después de guardar

### 4. Acceso desde la tabla de productos BSC-062
En `bsc-products-page.php`, la columna "Acciones" debe tener link a `admin.php?page=bsc-product-edit&id={product_id}`.

## Criterios de aceptación
- El formulario muestra los datos actuales del producto al cargar
- Se pueden cambiar nombre, descripción corta, categorías, imagen principal, galería, stocks
- Al guardar, los cambios se reflejan en el frontend sin necesidad de usar WooCommerce nativo
- No hay acceso a campos avanzados (variaciones, atributos técnicos, shipping data)

## Commit sugerido
`feat(admin-products): [BSC-065] simplified BSC product editor for shop manager`

---

# BSC-066 — Gestión de Stock Dual en admin: vista completa con incremento/decremento

## Objetivo
Implementar la gestión visual completa del stock dual (`_stock_bodega` / `_stock_tienda`) en el admin BSC: ver ambos stocks, incrementar/decrementar con botones, historial básico de movimientos y alertas de stock bajo.

## Contexto
`includes/class-bsc-stock.php` implementa `deduct_bodega()` y `deduct_tienda()` automáticamente en pedidos. Pero el equipo necesita poder ajustar el stock manualmente desde el admin (reposición de bodega, inventario físico, correcciones). Actualmente solo se puede editar en la vista de producto nativa de WooCommerce o en la tabla de BSC-062 con edición inline.

## Archivos a revisar
- `includes/class-bsc-stock.php`
- `inc/admin/product-covers.php` — meta box de stock dual
- `admin/bsc-products-page.php` (de BSC-062)

## Implementación exacta

### 1. Ampliar `BSC_Stock` con métodos de ajuste manual

```php
/**
 * Ajustar stock manualmente (reposición o corrección).
 * @param int    $product_id
 * @param string $type        'bodega' | 'tienda'
 * @param int    $delta       positivo (sumar) o negativo (restar)
 * @param string $reason      razón del ajuste para el log
 */
public static function adjust( int $product_id, string $type, int $delta, string $reason = '' ): int {
    $meta_key = ( $type === 'tienda' ) ? '_stock_tienda' : '_stock_bodega';
    $current  = (int) get_post_meta( $product_id, $meta_key, true );
    $new      = max( 0, $current + $delta );
    update_post_meta( $product_id, $meta_key, $new );

    // Log del movimiento
    $log   = get_post_meta( $product_id, '_bsc_stock_log', true ) ?: [];
    $log[] = [
        'date'    => current_time('mysql'),
        'type'    => $type,
        'delta'   => $delta,
        'before'  => $current,
        'after'   => $new,
        'reason'  => sanitize_text_field( $reason ),
        'user_id' => get_current_user_id(),
    ];
    // Mantener solo los últimos 50 movimientos
    if ( count($log) > 50 ) $log = array_slice($log, -50);
    update_post_meta( $product_id, '_bsc_stock_log', $log );

    return $new;
}

public static function get_log( int $product_id ): array {
    return get_post_meta( $product_id, '_bsc_stock_log', true ) ?: [];
}
```

### 2. AJAX `bsc_adjust_stock`

En `admin/bsc-products-page.php`:
```php
add_action('wp_ajax_bsc_adjust_stock', 'bsc_ajax_adjust_stock');
function bsc_ajax_adjust_stock(): void {
    check_ajax_referer('bsc_products_nonce', 'nonce');
    if ( ! current_user_can('edit_products') ) wp_send_json_error(['message' => 'Sin permisos'], 403);

    $product_id = absint($_POST['product_id']);
    $type       = in_array($_POST['type'], ['bodega', 'tienda'], true) ? $_POST['type'] : 'bodega';
    $delta      = intval($_POST['delta']);
    $reason     = sanitize_text_field($_POST['reason'] ?? '');

    $new_stock = BSC_Stock::adjust($product_id, $type, $delta, $reason);
    wp_send_json_success(['new_stock' => $new_stock]);
}
```

### 3. UI en la tabla de productos (BSC-062)
- Columnas de stock: `[−] 15 [+]` con botones pequeños
- Al clickar `[+]` o `[−]`: modal pequeño pidiendo cantidad y razón opcional, luego AJAX
- El valor se actualiza inline sin reload

### 4. Modal de historial de movimientos
- Icono de reloj en cada fila de producto → abre modal con `BSC_Stock::get_log($product_id)`
- Tabla: fecha, tipo (bodega/tienda), delta (+/-), usuario, razón

### 5. Alerta de stock bajo en Dashboard (BSC-061)
- Productos con `_stock_bodega` < 3: destacar en rojo en el dashboard
- Configurar el umbral de alerta en BSC Settings (BSC-064): `bsc_low_stock_threshold`, default 3

## Criterios de aceptación
- Se puede incrementar/decrementar stock bodega y tienda desde la tabla de productos BSC
- El historial de movimientos muestra los últimos 50 ajustes con fecha y usuario
- El dashboard alerta sobre productos con stock bodega < 3
- Los ajustes se reflejan inmediatamente en el frontend (sin caché de stock)

## Commit sugerido
`feat(stock): [BSC-066] dual stock adjustment with history log and low-stock alerts in BSC admin`
