# BSC-107 — Storefront Smoke Root-Cause Stabilization

## Objective
Cerrar las dos causas raiz que todavia rompen el smoke storefront: la validacion custom del login y la contaminacion BOM sobre respuestas AJAX JSON.

## Context
- `page-login.php` usa mensajes de error custom via `login.js`, pero mantiene la validacion nativa del browser activa.
- `admin-ajax.php` estaba devolviendo un BOM antes del JSON, lo que hacia fallar `cart.js` aunque el add-to-cart si agregaba el producto.
- `checkout` y `coupon` no eran fallos propios: se caian por la misma ruptura previa del cart flow.

## Files To Inspect
- `page-login.php`
- `admin/bsc-orders-page.php`
- `tests/e2e/smoke/account-auth.spec.js`
- `tests/e2e/smoke/site-smoke.spec.js`

## Exact Implementation Plan
1. Agregar `novalidate` al login custom para que corra la validacion JS propia.
2. Confirmar que `admin/bsc-orders-page.php` queda sin BOM.
3. Escanear PHPs por BOM restante.
4. Revalidar smoke dirigido de login, add-to-cart, checkout y coupon.

## Acceptance Criteria
- Login smoke muestra los mensajes custom esperados.
- Add-to-cart vuelve a montar quantity controls.
- Checkout smoke y coupon smoke dejan de caer por el cart setup.
- No quedan PHPs con BOM en el repo cargado por runtime.

## Manual QA
1. Abrir `/login/` y enviar vacio.
2. Confirmar mensajes custom de usuario y contraseña.
3. Desde category agregar producto y bajar a 0.
4. Ir a checkout con carrito sembrado y abrir cupón.

## Rollback Notes
- Revertir `page-login.php` y restaurar el archivo con BOM anterior reabre exactamente estas fallas de smoke.
