# BSC-115 - Shop Landing Inline Style Extraction

## Objective
Sacar los estilos inline restantes de `components/shop.php` al pipeline SCSS, manteniendo el layout aprobado de la landing de tienda y endureciendo los links de categorias.

## Context
- `components/shop.php` todavia renderizaba gran parte de la landing con `style="..."` embebido.
- El template ademas seguia construyendo links de categorias por path manual cuando WordPress ya puede resolverlos.
- El cambio es seguro porque no toca checkout ni JS de compra.

## Files To Inspect
- `components/shop.php`
- `sass/pages/_page-shop.scss`
- `style.css`
- `style.css.map`
- `tests/e2e/helpers/env.js`
- `tests/e2e/smoke/site-smoke.spec.js`

## Exact Implementation Plan
1. Reemplazar `style="..."` por clases ya existentes o nuevas clases semanticas.
2. Mover ese styling a `_page-shop.scss`.
3. Resolver los links principales del shop con `get_term_link()` y fallback seguro.
4. Compilar el CSS final.
5. Agregar smoke para validar que la landing de tienda sigue renderizando tarjetas.

## Acceptance Criteria
- `components/shop.php` ya no contiene inline styles.
- La landing de tienda mantiene el mismo layout visual.
- Los links principales de grupos usan resolucion WP/Woo segura.
- El smoke de tienda pasa en storefront mode.

## Manual QA
1. Abrir `/shop/`.
2. Validar hero de grupos y grid de subcategorias.
3. Abrir varias tarjetas de subcategoria.
4. Revisar spacing, bordes e imagenes en desktop y mobile.

## Rollback Notes
- Revertir este commit restaura los inline styles originales y la construccion manual de links.
