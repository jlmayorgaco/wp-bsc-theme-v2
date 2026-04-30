# BSC-116 - Coming Soon Template Asset Extraction

## Objective
Sacar el bloque `<style>` de `woocommerce/coming-soon.php` a un CSS dedicado, sin cambiar el shell aprobado del modo mantenimiento.

## Context
- `coming-soon.php` seguia siendo un template standalone con todo el CSS inline.
- La smoke ya valida el shell de mantenimiento cuando `PW_PUBLIC_MODE` no esta en storefront, asi que el refactor puede quedar cubierto sin agregar nuevos tests.
- Tambien habia texto con encoding roto en el template.

## Files To Inspect
- `woocommerce/coming-soon.php`
- `woocommerce-coming-soon.css`
- `tests/e2e/smoke/site-smoke.spec.js`

## Exact Implementation Plan
1. Mover el CSS a `woocommerce-coming-soon.css`.
2. Cargarlo via `<link rel="stylesheet">` versionado.
3. Corregir las cadenas del template a salida UTF-8 limpia.
4. Revalidar la smoke del shell en modo coming-soon.

## Acceptance Criteria
- `coming-soon.php` ya no contiene `<style>` inline.
- El layout aprobado del modo mantenimiento no cambia.
- La smoke del shell sigue verde en coming-soon mode.

## Manual QA
1. Poner la tienda en modo coming soon.
2. Abrir home y shop.
3. Validar logo, mensajes y social icons.
4. Probar el CTA de WhatsApp.

## Rollback Notes
- Revertir este commit restaura el CSS inline del template y elimina el asset dedicado.
