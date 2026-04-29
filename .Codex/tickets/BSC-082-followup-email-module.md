# BSC-082 — Módulo de correos y seguimiento

## Objetivo
Centralizar los correos clave de cliente en el theme BSC, dejando plantillas editables en PHP/HTML/CSS y agregando un módulo admin para automatizar correos de seguimiento.

## Contexto
- Ya existen correos transaccionales de compra y envío dentro del theme.
- Registro de usuario y recuperación de contraseña no tienen branding BSC consistente.
- Existe meta de cumpleaños (`bsc_birthday`) pero no se usa para automatización.
- No existe lógica para "hace mucho no compras" ni "se te acabó el producto".
- Se requiere timeout por producto con default global de `30` días configurable desde admin.

## Archivos a inspeccionar
- `functions.php`
- `admin/bsc-admin-menu.php`
- `admin/bsc-product-edit-page.php`
- `admin/bsc-orders-page.php`
- `emails/bsc-emails.php`
- `emails/bsc-email-header.php`
- `emails/bsc-email-footer.php`
- `page-register.php`

## Plan de implementación
1. Crear helpers reutilizables para envío/render de correos HTML del theme.
2. Crear un módulo nuevo `Emails` dentro de BSC admin con settings y ejecución manual.
3. Agregar scheduler diario para cumpleaños, inactividad y recompra.
4. Agregar override de días de recompra por producto.
5. Implementar plantillas PHP nuevas para:
   - usuario nuevo
   - recuperar contraseña
   - cumpleaños
   - hace mucho no compras
   - se te acabó el producto
6. Reusar compra/envío existentes como parte del sistema total de correos.

## Acceptance Criteria
- Existe un módulo `BSC > Emails`.
- El admin puede configurar:
  - activación general del módulo
  - correo de usuario nuevo
  - correo de recuperación de contraseña
  - correo de cumpleaños
  - días de inactividad
  - correo de recompra
  - días globales de recompra por defecto
- Los templates quedan como archivos PHP editables en `emails/`.
- El sistema envía:
  - bienvenida al crear cuenta customer
  - recuperación de contraseña branded BSC
  - cumpleaños una vez por año
  - inactividad una vez por última compra vencida
  - recompra agrupando varios productos vencidos en un solo correo
- Cada producto puede sobreescribir los días de recompra desde el editor BSC.
- Compra y envío siguen funcionando con sus templates existentes.

## Manual QA
1. Abrir `BSC > Emails` y guardar settings.
2. Verificar que aparezcan las rutas de templates esperadas.
3. Crear un usuario customer nuevo y confirmar correo de bienvenida.
4. Ejecutar flujo de recuperar contraseña y confirmar correo HTML branded.
5. Crear un usuario con `bsc_birthday` del día y correr `Ejecutar seguimiento ahora`.
6. Validar correo de cumpleaños y que no se reenvíe el mismo año.
7. Crear una orden antigua elegible y validar correo de inactividad.
8. Crear una orden antigua con varios productos elegibles y validar correo agrupado de recompra.
9. Editar un producto y guardar override de días de recompra.
10. Confirmar que compra y envío sigan usando sus templates previos.

## Rollback
- Remover la carga de `emails/bsc-email-helpers.php` y `emails/bsc-followup-emails.php` en `functions.php`.
- Remover el submenu `Emails` del admin.
- Eliminar las nuevas plantillas y el field `_bsc_repurchase_days`.
- Mantener intactos los correos transaccionales preexistentes de compra y envío.
