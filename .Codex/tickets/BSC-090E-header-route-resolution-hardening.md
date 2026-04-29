# BSC-090E - Header route resolution hardening

## Objective
Reduce fragility in header navigation by resolving category/page links through WordPress/WooCommerce helpers where possible, while preserving the current fallback URLs and the approved UI.

## Context
- The header render is already modularized and visually protected.
- Header links currently still depend on many hardcoded relative routes, especially WooCommerce `product-category` URLs.
- If term permalinks or hierarchy change, those links can drift even though the visual header still looks correct.

## Files To Inspect
- `components/header/header-menu-config.php`
- `components/header/BSC_MenuNav.class.php`

## Exact Implementation Plan
1. Extend the header link normalizer to resolve:
   - Woo product categories by final term slug
   - WordPress pages by path
   - posts page for `/blog/`
2. Keep fallback behavior to the existing relative path if no runtime target is found.
3. Correct header link escaping to use `esc_url()` in rendered anchors.
4. Validate with header smoke/visual tests and the public/authenticated visual suite.

## Acceptance Criteria
- Header category/page links resolve through runtime helpers when possible.
- Existing fallback URLs remain intact when no object is found.
- No visual or DOM drift.
- Header/public visual suites remain green.

## Manual QA
1. Desktop: open a few SKIN CARE / HAIR CARE / MAKE UP links and confirm they land correctly.
2. Mobile: open the sidebar and use `CONTACTO`.
3. Confirm no broken `href` values appear in the header markup.

## Rollback Notes
- Revert the header link normalizer and `BSC_MenuNav` escaping change together if any route resolution behaves unexpectedly in production data.
