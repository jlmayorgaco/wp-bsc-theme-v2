# BSC Architecture Summary

## 1. System Overview

BSC is a **WordPress + WooCommerce** project using a **custom theme as the main application layer**.

Architecture style:
- WordPress core → CMS + routing
- WooCommerce → ecommerce engine (products, cart, checkout, orders)
- Custom Theme → UI + business logic + AJAX + UX
- Minimal plugin reliance (except necessary modules like Bubble Points)

All critical logic should live in:
- theme templates
- theme PHP helpers
- AJAX handlers inside theme
- JS modules scoped per feature

---

## 2. High-Level Layering
[ Browser / UI ]
↓
[ Theme Templates + JS ]
↓
[ Theme PHP (helpers + AJAX) ]
↓
[ WooCommerce Core ]
↓
[ WordPress Core ]
↓
[ MySQL DB ]

---

## 3. Core Theme Structure

Key directories (logical, may vary slightly in naming):
theme/
├── components/
│ ├── header.php
│ ├── footer.php
│ ├── whatsapp.php
│ ├── products/
│ │ ├── card.php
│ │ ├── slider.php
│ │ └── grid.php
│ ├── home/
│ └── ui/
├── inc/
│ ├── setup/
│ │ └── theme-setup.php
│ ├── scripts/
│ │ └── enqueue-scripts.php
│ ├── ajax/
│ │ ├── cart-actions.php
│ │ ├── checkout-actions.php
│ │ ├── coupons-actions.php
│ │ └── search-actions.php
│ ├── woocommerce.php
│ └── helpers/
├── js/
│ ├── cart.js
│ ├── checkout.js
│ ├── coupons.js
│ ├── mobile-menu.js
│ ├── search.js
│ └── swiper-init.js
├── sass/
├── assets/
├── woocommerce/
│ ├── single-product.php
│ ├── archive-product.php
│ ├── cart/
│ └── myaccount/
└── functions.php

---

## 4. Critical Functional Modules

### 4.1 Cart System

**Files**
- `components/products/card.php`
- `js/cart.js`
- `inc/ajax/cart-actions.php`
- `woocommerce/cart/*`

**Flow**
User click (+ / add)
→ JS (cart.js)
→ AJAX call
→ PHP handler (cart-actions.php)
→ WooCommerce cart update
→ fragments refresh
→ UI update (header, footer, mini-cart)

**Risks**
- fragment desync
- qty = 0 edge case
- mobile touch events
- double triggers

---

### 4.2 Checkout System

**Files**
- `inc/woocommerce.php`
- `inc/ajax/checkout-actions.php`
- `js/checkout.js`
- `js/coupons.js`
- Woo templates

**Flow**
User updates city / coupon
→ JS
→ AJAX
→ WooCommerce recalculation
→ totals + shipping update
→ UI refresh
- `components/header.php`
- `js/mobile-menu.js`
- CSS/SASS

**Responsibilities**
- navigation tree
- auth-aware options
- categories / skin care structure

**Risks**
- duplicated menu sources
- incorrect links
- broken touch interaction
- visual hierarchy issues

---

### 4.5 Search System

**Files**
- `js/search.js`
- `inc/ajax/search-actions.php`

**Flow**
input → debounce → AJAX → query → return results → render
### 4.7 Bubble Points Module

**Location**
- `plugins/bubble-points/*`

**Responsibilities**
- loyalty system
- coupons / rewards

**Integration**
- theme overrides styles + layout

**Risks**
- responsive issues
- visual inconsistencies
- state misrepresentation

---

### 4.8 Admin Extension (Planned/Partial)

**Expected files**

admin/
includes/


**Responsibilities**
- custom roles
- simplified order dashboard
- tracking flow
- reports
- inventory logic

**Risks**
- permission leaks
- mixing wp-admin default + custom UI
- inconsistent states

---

## 5. Data & State Flow

### 5.1 Cart State
- WooCommerce session
- updated via AJAX
- reflected through fragments

### 5.2 Order State
- WooCommerce order post type
- extended with meta (tracking, stock logic)

### 5.3 UI State
- JS-managed (cart, sliders, menu)
- partially server-driven via PHP templates

---

## 6. AJAX Architecture

All dynamic behavior flows through:


JS → admin-ajax.php → PHP handler → WooCommerce/DB → JSON → UI update


### Common handlers
- cart
- checkout
- coupons
- search
- creators/contact forms

### Rules
- always validate input
- always sanitize
- always return structured JSON
- avoid duplicate endpoints

---

## 7. Performance Hotspots

Watch these areas:

- product loops
- AJAX search
- cart fragment refresh
- large images in sliders
- duplicated scripts
- WooCommerce queries
- global JS loaded everywhere

---

## 8. Integration Points

### External
- WhatsApp link
- email (contact/newsletter)
- possible shipping providers (future)
- possible payment gateway extensions

### Internal
- WooCommerce hooks
- WP hooks
- AJAX handlers
- template overrides

---

## 9. Key Design Decisions

- Theme is the main application layer (not plugins)
- Prefer hooks over overriding Woo core logic
- Prefer modular JS per feature
- Prefer small, safe changes over large rewrites
- Prefer server-rendered structure + JS enhancements

---

## 10. How to Navigate the Codebase (for Claude)

When working on a ticket:

1. Identify module:
   - cart / checkout / product / menu / account / admin

2. Open:
   - component (UI)
   - JS (interaction)
   - AJAX handler (logic)
   - Woo template (if needed)

3. Trace flow:
   UI → JS → AJAX → PHP → Woo → response → UI

4. Apply minimal change at correct layer:
   - UI issue → component/CSS
   - interaction issue → JS
   - logic issue → AJAX/PHP
   - data issue → Woo query/meta

5. Validate:
   - desktop
   - mobile
   - guest
   - logged-in
   - edge cases

---

## 11. Anti-Patterns to Avoid

- duplicating WooCommerce logic
- rewriting cart flow unnecessarily
- adding heavy libraries for simple UI
- inline JS everywhere
- CSS hacks without responsive validation
- multiple sources of truth for menu/cart
- hardcoded URLs or contact data
- breaking fragment refresh logic
- loading all scripts globally

---

## 12. Summary

This project is:

- a **theme-driven WooCommerce app**
- with **AJAX-heavy UI**
- requiring **strict control of cart/checkout state**
- with **mobile UX as a priority**
- and **incremental, ticket-based evolution**

Work should always be:
- scoped
- minimal
- testable
- reversible