# BSC-093 — Public form script extraction and contact link hardening

## Objective
Extract inline JavaScript from public landing pages into the theme asset pipeline, and normalize the remaining hardcoded internal links tied to contact and Bubble Creators flows.

## Context
- `page-contact-us.php` still embeds its AJAX behavior inline and hardcodes the shop CTA to `/shop/`.
- `page-bubble-creators.php` still embeds its AJAX behavior inline.
- `components/footer.php` still hardcodes `/bubble-creators/`.
- WhatsApp runtime behavior is already centralized, but the display string should come from the same source of truth.

## Files To Inspect
- `page-contact-us.php`
- `page-bubble-creators.php`
- `components/footer.php`
- `inc/bsc-contact-options.php`
- `inc/scripts/enqueue-scripts.php`
- `js/contact.js`
- `js/creator-apply.js`
- `tests/e2e/smoke/site-smoke.spec.js`

## Exact Implementation Plan
1. Remove inline contact and creator scripts from the templates.
2. Add dedicated page-level JS files and enqueue them only where needed.
3. Replace hardcoded internal links with WordPress/WooCommerce helpers.
4. Reuse the centralized WhatsApp display helper in contact UI.
5. Add smoke coverage that exercises both AJAX shells with mocked responses.

## Acceptance Criteria
- No inline `<script>` remains in `page-contact-us.php` or `page-bubble-creators.php`.
- Contact page uses a dynamic shop URL and centralized WhatsApp display string.
- Footer Bubble Creators link is no longer hardcoded.
- Public forms still show success/error states correctly.
- Visual output remains unchanged.

## Manual QA
1. Open `contact-us` and submit the contact form.
2. Open `bubble-creators` and submit the creator form.
3. Confirm WhatsApp CTA on contact page opens the expected WhatsApp flow.
4. Confirm footer Bubble Creators link still reaches the same page.

## Rollback Notes
- Restore inline scripts in the two templates.
- Remove `js/contact.js` and `js/creator-apply.js`.
- Revert the enqueue rules and helper/link changes.
