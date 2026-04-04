# BSC Known Issues

This document lists **known bugs, fragile areas, and technical risks** in the BSC project.

Purpose:
- prevent reintroducing known bugs
- avoid breaking fragile flows
- guide debugging and prioritization
- give Claude Code immediate awareness of real system risks

---

## 1. Critical Issues (Must Not Regress)

### 1.1 Cart badge desynchronization

**Symptoms**
- cart badge does not update when quantity reaches 0
- UI shows items but cart is actually empty (or vice versa)

**Affected areas**
- `js/cart.js`
- `inc/ajax/cart-actions.php`
- header/footer cart UI
- WooCommerce fragments

**Root cause pattern**
- fragment refresh not triggered
- stale DOM state
- edge case when qty = 0

**Rules**
- always refresh fragments after cart update
- always handle qty = 0 explicitly
- always sync header, footer, and mini-cart

---

### 1.2 iPad / touch add-to-cart issues

**Symptoms**
- add-to-cart works on desktop but fails or duplicates on iPad/tablet
- inconsistent behavior between click and touch

**Affected areas**
- `components/products/card.php`
- `js/cart.js`

**Root cause pattern**
- click + touch double firing
- missing pointer event handling

**Rules**
- use safe event handling (pointerup / touchstart with guards)
- prevent duplicate triggers
- test on touch, not just click

---

### 1.3 WhatsApp component broken or inconsistent

**Symptoms**
- floating button missing on mobile
- wrong number or message
- inconsistent behavior across pages

**Affected areas**
- `components/whatsapp.php`
- header/footer

**Root cause pattern**
- hardcoded values
- inconsistent rendering across templates

**Rules**
- centralize phone and message
- avoid hardcoding in multiple places
- ensure visibility on mobile

---

### 1.4 Hardcoded URLs (localhost / stale links)

**Symptoms**
- links pointing to `bsc.local`
- broken navigation in production
- incorrect images or assets

**Affected areas**
- header
- menus
- templates

**Root cause pattern**
- absolute URLs hardcoded
- missing WP helpers

**Rules**
- use:
  - `home_url()`
  - `get_permalink()`
  - `get_theme_file_uri()`
- never hardcode environment-specific URLs

---

### 1.5 Newsletter / contact forms not providing feedback

**Symptoms**
- user submits form but sees no confirmation
- unclear if submission worked

**Affected areas**
- newsletter block
- contact forms
- creators form

**Root cause pattern**
- missing success/error UI state
- AJAX response not handled

**Rules**
- always show:
  - success message
  - error message
- never fail silently

---

## 2. High-Risk Functional Areas

### 2.1 WooCommerce cart + fragments

**Risks**
- stale UI
- broken mini-cart
- inconsistent totals

**Rules**
- never bypass WooCommerce state blindly
- always validate fragment refresh
- always test:
  - add
  - remove
  - qty change
  - last item removal

---

### 2.2 Checkout shipping recalculation

**Risks**
- shipping not updating when city changes
- Bogotá vs rest logic incorrect

**Affected**
- `js/checkout.js`
- `inc/ajax/checkout-actions.php`
- WooCommerce hooks

**Rules**
- always trigger recalculation on location change
- validate totals update visually and internally

---

### 2.3 Coupon flow

**Risks**
- coupon applied but not reflected
- error messages not visible
- mobile UX broken

**Affected**
- `js/coupons.js`
- checkout templates

**Rules**
- always show feedback
- always test mobile behavior
- never hide error messages below viewport

---

### 2.4 Product card quantity controls

**Risks**
- UI and cart state mismatch
- controls not reverting correctly at qty = 0

**Rules**
- UI must reflect real cart state
- quantity controls must disappear at 0
- original button must be restored

---

### 2.5 Mobile menu inconsistencies

**Symptoms**
- duplicated items
- mismatch with desktop menu
- broken links

**Affected**
- `components/header.php`
- mobile menu JS

**Rules**
- single source of truth for menu
- mobile must mirror desktop structure
- remove duplicates

---

### 2.6 Brand / category pages not showing correct products

**Symptoms**
- clicking a brand shows wrong or empty products

**Affected**
- taxonomy templates
- product queries

**Root cause pattern**
- incorrect query args
- wrong taxonomy mapping

**Rules**
- validate taxonomy query
- ensure products are filtered correctly

---

## 3. Performance Issues

### 3.1 Slow navigation / heavy homepage

**Symptoms**
- long load times
- slow interaction

**Likely causes**
- heavy images
- large sliders
- duplicated scripts
- global JS loading

**Rules**
- avoid adding new heavy assets
- prefer optimizing existing components

---

### 3.2 AJAX search inefficiency

**Symptoms**
- lag while typing
- unnecessary queries

**Root cause pattern**
- multiple parallel queries
- missing debounce

**Rules**
- use debounce
- reduce query count
- return minimal data

---

### 3.3 N+1 product queries

**Symptoms**
- slow product grids
- repeated queries per item

**Rules**
- avoid per-item queries
- reuse preloaded data where possible

---

### 3.4 WooCommerce fragments overuse

**Symptoms**
- unnecessary network calls
- slow cart updates

**Rules**
- only refresh fragments when necessary
- avoid redundant triggers

---

## 4. UI / Responsive Issues

### 4.1 Slider overlap and layout issues

**Symptoms**
- text overlapping buttons or icons
- inconsistent positioning across breakpoints

**Rules**
- validate on:
  - desktop
  - tablet
  - mobile
- avoid absolute positioning hacks without fallback

---

### 4.2 Excess spacing between sections

**Symptoms**
- large vertical gaps
- broken visual flow

**Rules**
- fix spacing consistently across breakpoints
- avoid patching only one screen size

---

### 4.3 Product gallery stretching

**Symptoms**
- distorted images on mobile or desktop

**Rules**
- use proper aspect ratio handling
- validate image container behavior

---

### 4.4 Mobile usability issues

**Symptoms**
- hard to click elements
- elements too close
- hidden content

**Rules**
- ensure proper spacing
- ensure readable text
- ensure touch-friendly controls

---

## 5. Data & Consistency Issues

### 5.1 Multiple sources of truth

**Examples**
- menu defined in WP + custom render
- cart state in DOM vs WooCommerce
- contact info duplicated across templates

**Rules**
- reduce duplication
- centralize data where possible

---

### 5.2 Hardcoded content

**Examples**
- phone numbers
- email addresses
- static links

**Rules**
- move to:
  - theme options
  - helper functions
  - centralized config

---

## 6. Admin / Operations Risks

### 6.1 Role permission leaks

**Risks**
- operator seeing admin-only data
- unintended access to settings/payments

**Rules**
- strictly control capabilities
- test each role explicitly

---

### 6.2 Order workflow inconsistencies

**Risks**
- tracking not saved
- status not updated correctly
- emails not sent

**Rules**
- ensure state transitions are consistent
- validate email triggers

---

## 7. Forms & Security Issues

### 7.1 Missing validation / sanitization

**Risks**
- invalid data
- security vulnerabilities

**Rules**
- always sanitize input
- always validate required fields

---

### 7.2 Missing nonce / CSRF protection

**Risks**
- unauthorized requests

**Rules**
- use nonces where appropriate
- validate server-side

---

### 7.3 Silent failures

**Symptoms**
- forms appear to work but do nothing

**Rules**
- always return explicit success/error
- always display feedback

---

## 8. Testing Gaps

Common missing validations:
- mobile behavior
- iPad/touch interactions
- guest vs logged-in flows
- last-item removal in cart
- coupon edge cases
- shipping recalculation

---

## 9. Debugging Guidelines

When debugging:
1. identify module (cart, checkout, menu, etc.)
2. trace flow:
   UI → JS → AJAX → PHP → Woo → response → UI
3. inspect:
   - JS events
   - network requests
   - PHP handler
   - WooCommerce state
4. test:
   - edge cases
   - mobile
   - multiple items
   - zero state

---

## 10. Summary

This project has:
- fragile cart and checkout interactions
- performance issues
- mobile UX inconsistencies
- duplicated logic and data
- incomplete form handling
- admin workflows still evolving

All changes must:
- preserve WooCommerce integrity
- respect current UI behavior
- be minimal and safe
- be tested across devices