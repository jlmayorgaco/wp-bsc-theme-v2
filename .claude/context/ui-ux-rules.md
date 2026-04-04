# BSC UI / UX Rules

## 1. Purpose

This document defines how UI and UX must be implemented and modified in the BSC project.

Goals:
- maintain brand consistency
- ensure mobile-first usability
- avoid regressions in critical flows
- enforce clear, usable, responsive interfaces

This is a production ecommerce UI — clarity and reliability are more important than visual experimentation.

---

## 2. Design Principles

### 2.1 Clarity over complexity
- UI must be immediately understandable
- avoid decorative elements that reduce usability
- do not overload screens with too many actions

### 2.2 Consistency
- same components behave the same everywhere
- same buttons, spacing, colors, and patterns must be reused
- mobile and desktop must share the same logic (layout can differ, behavior should not)

### 2.3 Feedback
Every user action must provide feedback:
- loading state
- success state
- error state

Never leave the user guessing.

---

## 3. Mobile-First Rule

This project must be treated as **mobile-first**.

Every UI change must be validated in:
- mobile
- tablet (iPad)
- desktop

### 3.1 Touch interaction rules
- buttons must be easily tappable
- no reliance on hover-only behavior
- avoid small clickable areas
- ensure spacing between interactive elements

### 3.2 Breakpoint discipline
Do not fix UI only for one breakpoint.

Always validate:
- small mobile (≤ 375px)
- large mobile (≈ 414px)
- tablet (≈ 768–1024px)
- desktop

---

## 4. Core UI Areas and Rules

---

### 4.1 Header & Navigation

**Files**
- `components/header.php`
- `js/mobile-menu.js`

**Rules**
- logo must always be visible and clickable
- navigation must be consistent between mobile and desktop
- no duplicated menu items
- no dead links
- menu hierarchy must be clear

**Mobile menu**
- must not look flat
- must be readable and structured
- must avoid clutter
- must include only relevant items:
  - login / account
  - orders
  - Bubble Points
- must remove:
  - duplicated categories
  - irrelevant system links (e.g. password reset)

---

### 4.2 Product Cards

**Files**
- `components/products/card.php`

**Rules**
- CTA must be clear: “¡Lo quiero!”
- button must have visible hover and active states
- quantity controls must be intuitive:
  - show `+ / -` only after adding
  - disappear at qty = 0
- spacing between cards must be balanced
- cards must not feel cramped or too separated

**Do not**
- hide important actions
- create inconsistent CTA styles

---

### 4.3 Cart UX

**Files**
- `js/cart.js`
- cart fragments UI

**Rules**
- cart must always reflect real state
- removing last item must clearly show empty state
- badge must always be correct
- updates must feel instant

**Feedback required**
- loading indicator when updating
- visible change after action

---

### 4.4 Checkout UX

**Files**
- `js/checkout.js`
- `js/coupons.js`

**Rules**
- shipping updates must feel automatic
- coupon application must show clear result
- errors must be visible (not hidden below screen)
- layout must not shift unpredictably

**Mobile rules**
- buttons must be reachable
- error/success messages must be visible without scrolling
- avoid elements jumping or overlapping

---

### 4.5 Product Detail

**Files**
- `woocommerce/single-product.php`

**Rules**
- images must not stretch
- gallery must maintain aspect ratio
- additional images must be:
  - horizontal in desktop
  - vertical in mobile
- CTA must be prominent and easy to reach

---

### 4.6 Homepage

**Rules**
- sections must have consistent spacing
- no excessive vertical gaps
- no overlapping elements
- slider content must be readable at all sizes

**Slider rules**
- controls must not overlap text
- text must not collide with icons or cart
- must work correctly in mobile and tablet

---

### 4.7 Brands & Categories

**Rules**
- brand grid must feel balanced (e.g., 3x3)
- clicking a brand must:
  - respond quickly
  - show correct products
- avoid dead states (empty results unless expected)

---

### 4.8 My Account / Orders

**Files**
- `woocommerce/myaccount/*`

**Rules**
- mobile must use list/cards, not tables
- order number must be visually clear (chip/tag style)
- tabs must be:
  - full width in mobile
  - easy to switch
  - scroll to top on change

---

### 4.9 Forms (Newsletter, Contact, Creators)

**Rules**
- always show:
  - success message
  - error message
- inputs must be readable
- labels must be clear
- buttons must have clear states

**Do not**
- allow silent submission
- leave user without feedback

---

### 4.10 Footer

**Files**
- `components/footer.php`

**Rules**
- all links must work
- no placeholder `#`
- spacing must be consistent
- no unnecessary empty space above footer

---

### 4.11 WhatsApp

**Rules**
- floating button must always be visible in mobile
- must open with correct pre-filled message
- must not block critical UI elements

---

## 5. Spacing and Layout Rules

### 5.1 Vertical rhythm
- sections should feel evenly spaced
- avoid:
  - large empty gaps
  - cramped stacking

### 5.2 Alignment
- elements must align consistently
- avoid visual “drift” across breakpoints

### 5.3 Grid discipline
- use consistent column behavior
- avoid random element widths

---

## 6. Interaction Rules

### 6.1 Button states
Every button should have:
- default
- hover (desktop)
- active
- disabled (if applicable)

### 6.2 Loading states
Required for:
- add to cart
- checkout updates
- coupon apply
- AJAX forms

### 6.3 Error handling
- errors must be visible
- errors must be understandable
- errors must not require scrolling to find

---

## 7. Visual Consistency

### 7.1 Color usage
- use BSC black as primary action color where defined
- avoid introducing new arbitrary colors

### 7.2 Typography
- maintain readable font sizes
- avoid overly small text in mobile
- ensure contrast

---

## 8. Accessibility Baseline

Every UI change must preserve:
- readable text
- visible focus state
- clickable/tappable areas large enough
- semantic buttons/links where possible

---

## 9. Anti-Patterns

Do not:
- fix UI only for desktop
- fix UI only for mobile and break desktop
- use random spacing tweaks without checking all breakpoints
- rely only on hover for critical actions
- hide important actions behind interactions
- allow forms to fail silently
- introduce inconsistent component styles
- break existing UI flows to “improve design”

---

## 10. QA Checklist for UI Changes

For any UI-related ticket:

### Must test:
- desktop
- mobile
- tablet

### Must validate:
- spacing
- alignment
- interaction
- feedback states
- readability

### Must check:
- no overlap
- no hidden elements
- no broken links
- no unreachable buttons

---

## 11. Final Rule

UI/UX changes must:
- improve clarity
- reduce friction
- preserve functionality
- work across devices

If a change looks good but breaks usability → it is incorrect.

Prefer:
- simple
- clear
- stable
- predictable interfaces