# Bubble Skin Care (BSC) — Project Overview

## 1. What this project is

Bubble Skin Care (BSC) is a **custom ecommerce website** built with:

- WordPress (CMS + routing)
- WooCommerce (products, cart, checkout, orders)
- Custom Theme (main application layer)

This is not a generic WP site.  
The theme acts as a **full application layer**, including:

- UI/UX rendering
- business logic (PHP)
- AJAX interactions
- custom flows (cart, checkout, account, admin)

---

## 2. Business Objective

The goal is to deliver a **production-ready ecommerce experience** that is:

- fast
- visually aligned with brand
- fully functional (cart, checkout, account)
- mobile-first usable
- operationally manageable (orders, tracking, inventory)

Success = users can:
- browse products
- navigate categories and brands
- add/remove items reliably
- complete checkout without friction
- interact via WhatsApp/contact
- receive consistent feedback

---

## 3. Current State of the Project

The project is **mid-stage**, with:

### Already implemented
- custom theme structure
- WooCommerce integration
- product catalog and categories
- homepage with custom sections
- mobile + desktop menus (partially divergent)
- AJAX cart interactions
- custom account pages (partially)
- Bubble Points (loyalty module)
- visual identity mostly defined

### Known problems
- slow performance
- inconsistent mobile UX
- cart state desynchronization
- broken or incomplete forms
- duplicated logic (menu, data, UI)
- incomplete admin workflows
- missing feedback in key interactions
- fragile WooCommerce integration points

---

## 4. Current Phase

The project is in a **stabilization + production-readiness phase**.

Work is organized into phases:

### Phase 1 — Fix blockers
- cart issues
- add-to-cart reliability
- broken links
- WhatsApp integration
- contact forms
- mobile menu functionality

### Phase 2 — UX and responsive fixes
- mobile menu redesign
- product UI
- checkout UX
- account usability
- homepage layout corrections

### Phase 3 — Core functionality completion
- shipping logic
- coupon UX
- account flows
- product galleries
- brand/category consistency

### Phase 4 — Admin and operations
- roles (admin / operator / employee)
- simplified order dashboard
- tracking code flow
- reporting
- inventory model (web + showroom)

### Phase 5 — Performance and hardening
- search optimization
- caching
- query optimization
- image handling
- form security
- backups and monitoring

---

## 5. How work is structured

All work is **ticket-driven**.

### Rules
- 1 ticket = 1 closed delivery
- no mixing of unrelated changes
- no large refactors unless required
- every change must be traceable

### Ticket location
.claude/tickets/BSC-XXX.md


### Each ticket defines
- objective
- context
- files to inspect
- implementation steps
- acceptance criteria
- QA cases

---

## 6. Development Model

### Layer responsibility

| Layer | Responsibility |
|------|----------------|
| Theme | UI + business logic |
| WooCommerce | ecommerce engine |
| WordPress | CMS + routing |

### Flow model


UI → JS → AJAX → PHP → WooCommerce → Response → UI


---

## 7. Key System Areas

Claude should recognize these modules:

### Frontend
- homepage sections
- product cards and sliders
- category and brand pages
- mobile and desktop menus
- footer and WhatsApp

### Core ecommerce
- cart system
- checkout system
- coupons
- shipping logic

### User account
- orders list
- order detail
- profile
- navigation

### Loyalty / engagement
- Bubble Points
- Bubble Creators
- newsletter
- contact

### Admin (evolving)
- roles and permissions
- orders dashboard
- tracking flow
- reports
- stock model

---

## 8. Constraints

### Technical
- must remain compatible with WooCommerce
- avoid heavy plugins
- avoid breaking SEO
- avoid introducing global side effects

### UX
- must work on:
  - desktop
  - tablet
  - mobile
- must support touch interactions
- must provide visible feedback

### Performance
- current site is slow → optimization is mandatory
- avoid adding heavy JS/CSS
- prefer optimizing existing components

---

## 9. Risk Awareness

High-risk areas:
- cart and fragment system
- checkout recalculation
- coupon handling
- mobile menu logic
- WooCommerce template overrides
- AJAX handlers

Changes in these areas must be:
- minimal
- tested
- reversible

---

## 10. How to approach any task

When working on any ticket:

1. Identify the module:
   - cart / checkout / product / menu / account / admin

2. Inspect:
   - UI component
   - JS file
   - AJAX handler
   - WooCommerce template

3. Understand current behavior

4. Apply minimal safe change

5. Validate:
   - desktop
   - mobile
   - guest
   - logged-in
   - edge cases

---

## 11. Definition of Done

A change is complete when:

- it solves the ticket objective
- it does not break existing flows
- it works on mobile and desktop
- WooCommerce behavior is preserved
- acceptance criteria are met
- manual QA is defined
- commit follows convention

---

## 12. Guiding Principle

This project must evolve through:

- controlled changes
- clear scope
- strong QA awareness
- respect for existing flows

Avoid:
- overengineering
- unnecessary rewrites
- uncontrolled changes across modules

Prefer:
- small, correct, testable steps
- incremental improvement
- stability over novelty