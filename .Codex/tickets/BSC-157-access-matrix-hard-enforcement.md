# BSC-157 — Access Matrix Hard Enforcement

## Objective
Hacer que la matriz de acceso admin sea real, consistente y segura.

## Context
La pantalla decía que ciertas páginas eran obligatorias, pero no lo bloqueaba realmente ni filtraba el menú de forma coherente.

## Files To Inspect
- admin/bsc-access-page.php
- admin/bsc-admin-menu.php
- admin/bsc-coupons-page.php
- admin/bsc-admin-communications.css
- admin/bsc-admin-ui.php
- admin/bsc-admin-ui.css

## Implementation Plan
- normalizar configuración guardada
- bloquear páginas obligatorias por rol
- ocultar submenús según matriz
- negar acceso directo a páginas restringidas
- alinear cupones con la matriz

## Acceptance Criteria
- Pedidos queda obligatorio para roles operativos
- los menús visibles responden a la matriz
- URL directa a página bloqueada devuelve acceso denegado
- Cupones respeta la matriz

## Manual QA
- guardar cambios en Control de acceso
- validar checkbox bloqueado en Pedidos
- entrar con rol operativo y revisar menú visible
- probar URL directa a una página no permitida

## Rollback Notes
- revertir admin/bsc-access-page.php
- revertir admin/bsc-admin-menu.php
- revertir admin/bsc-coupons-page.php
- revertir admin/bsc-admin-communications.css
- revertir admin/bsc-admin-ui.php
- revertir admin/bsc-admin-ui.css
