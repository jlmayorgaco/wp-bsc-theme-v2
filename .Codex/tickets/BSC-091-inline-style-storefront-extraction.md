# BSC-091 - Extract storefront inline styles into the SCSS pipeline

## Objective
Move safe inline CSS from storefront/account PHP templates into the existing SCSS pipeline without changing approved visuals, DOM structure, or frontend behavior.

## Context
- `MVP2` already has visual regression coverage for public critical pages, account, Bubble Points, thank-you, and email previews.
- There are still inline `style=""` attributes in PHP templates across storefront/account pages.
- The current goal is to remove the low-risk inline styles first:
  - hidden feedback/notice blocks
  - auth CTA spacing/font sizing
  - auth divider styling
  - account address row flex layout
- This ticket explicitly excludes:
  - inline email CSS
  - inline SVG `<style>` blocks
  - WooCommerce core hidden form states that are more sensitive to JS/core behavior

## Files To Inspect
- `front-page.php`
- `page-bubble-creators.php`
- `page-contact-us.php`
- `page-login.php`
- `page-register.php`
- `woocommerce/myaccount/my-address.php`
- `sass/pages/_page-home.scss`
- `sass/pages/_page-bubble-creators.scss`
- `sass/pages/_page-contact-us.scss`
- `sass/pages/_page-signins.scss`
- `sass/components/profile/_my_address.scss`
- `tests/e2e/visual/public-pages.spec.js`
- `tests/e2e/helpers/env.js`

## Exact Implementation Plan
1. Remove inline `display:none` from storefront feedback/notice blocks and define the initial hidden state in SCSS.
2. Replace inline auth CTA and divider styling in login/register with explicit SCSS modifier classes.
3. Replace the inline flex row in `my-address` with a semantic class and SCSS rule.
4. Leave WooCommerce core hidden form markup and email inline CSS untouched in this slice.
5. Extend the public visual suite to include the touched public templates:
   - contact
   - login
   - register
   - bubble creators
6. Recompile `style.css` and verify the visual suite still passes.

## Acceptance Criteria
- No remaining inline styles in the safe storefront/account templates listed above.
- Hidden notices still show/hide correctly through the existing JS.
- Login/register CTAs and divider render pixel-identical to the approved version.
- The account address rows keep the same visual layout.
- Public visual regression covers the newly touched templates.
- Existing critical visual tests remain green.

## Manual QA
1. Visit home and submit the newsletter form with invalid and valid input.
2. Visit Bubble Creators and Contact pages and verify notice visibility still works.
3. Visit login/register and compare CTA sizing and divider spacing against the approved UI.
4. Visit `Mi cuenta > Direcciones` and confirm the address rows keep their horizontal label/value layout.
5. Run the public visual suite and the account/post-purchase visual suite.

## Rollback Notes
- Revert the template class additions and SCSS changes if any visible drift appears.
- Revert the added public visual baselines if the route coverage needs to be redesigned.
