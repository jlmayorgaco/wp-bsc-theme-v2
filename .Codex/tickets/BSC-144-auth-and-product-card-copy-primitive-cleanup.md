# BSC-144 — Auth and product-card copy + primitive cleanup

## Objective
Fix visible mojibake in the login/register flow and product cards, while consolidating the main auth/product CTAs onto reusable button primitives without changing the approved design.

## Context
- Public-facing templates still contained visible broken copy such as `Â¡`, `ContraseÃ±a`, and `âˆ’`.
- Product-card CTA styles were duplicated in page/component Sass.
- Auth pages still depended on page-local CTA styling instead of a reusable button primitive.

## Files to inspect
- `page-login.php`
- `page-register.php`
- `components/products/card.php`
- `sass/components/primitives/_buttons.scss`
- `sass/components/products/_card.scss`
- `sass/pages/_page-signins.scss`

## Exact implementation plan
1. Rewrite login/register templates in clean UTF-8-safe markup.
2. Fix product-card CTA copy and quantity symbols.
3. Add reusable primitive variants for auth CTAs and product-card CTAs.
4. Remove duplicated CTA styling from page/product Sass, leaving layout-only rules where possible.
5. Rebuild CSS and refresh targeted visual baselines.

## Acceptance criteria
- Login, register, and product-card copy render correctly in Spanish.
- Product-card buttons and auth CTAs use shared primitive styling hooks.
- No visual regression in category/login/register baselines.
- Add-to-cart quantity controls remain functional.

## Manual QA
1. Open login and register pages in mobile/desktop and validate all headings, labels, and CTAs.
2. Open a category grid and confirm product-card CTA copy is correct.
3. Add a product from category, verify the quantity controls render `− / +` correctly.
4. Reduce quantity to `0` and confirm the CTA returns.

## Rollback notes
- Revert the rewritten templates and restore the previous page-local/product-local CTA styles.
