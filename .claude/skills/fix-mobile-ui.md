# Skill: Fix Mobile UI

## Purpose

This skill defines how to safely fix **mobile UI and responsive issues** in the BSC WordPress + WooCommerce project.

Use this skill for problems such as:
- broken mobile layouts
- overlapping elements
- flat or cluttered mobile menu
- hard-to-tap controls
- excessive spacing
- distorted product images
- tables unusable on mobile
- hidden notices or buttons below the fold

Goal:
- make the interface clear, touch-friendly, and consistent
- preserve existing desktop behavior unless the ticket says otherwise
- avoid fragile CSS hacks
- keep UX aligned with BSC rules

---

## When to Use This Skill

Use this skill for tickets involving:
- mobile menu redesign
- responsive layout fixes
- mobile-only spacing/alignment issues
- account/order mobile rendering
- checkout coupon/shipping mobile visibility
- product detail gallery mobile behavior
- hidden or overlapping controls on small screens

Examples:
- `BSC-007`
- `BSC-009`
- `BSC-010`
- `BSC-011`
- `BSC-012`
- `BSC-014`
- `BSC-018`
- `BSC-020`
- `BSC-021`
- `BSC-023`
- `BSC-024`

---

## Core Principles

### 1. Mobile-first, not mobile-only
Fixes must work on mobile first, but cannot break:
- tablet / iPad
- desktop

### 2. Clarity over decoration
Internal and public UI must remain:
- readable
- obvious
- easy to tap
- visually organized

### 3. Touch usability matters
Mobile UI is not just a smaller desktop layout.

Always consider:
- tap target size
- spacing between controls
- no hover dependency
- no hidden important feedback

### 4. Minimal safe change
Prefer:
- targeted CSS / structure fixes
- small template adjustments
- preserving existing component logic

Avoid:
- broad redesigns unless the ticket explicitly asks for them

---

## Typical Mobile UI Workflow

Expected diagnosis path:

```text
Template / component
→ CSS / SCSS rules
→ JS behavior (if interactive)
→ responsive validation
→ responsive validation

Likely files involved:

components/header.php
components/footer.php
components/products/card.php
woocommerce/single-product.php
woocommerce/myaccount/*
front-page.php
js/mobile-menu.js
js/cart.js
js/checkout.js
sass/style.scss
other module-specific SCSS
Step-by-Step Fix Workflow
Step 1 — Define the exact symptom

Be precise:

overlap?
hidden button?
spacing too large?
unreadable text?
impossible to tap?
table unusable?
image stretching?
notice below viewport?

Do not work from vague wording like “mobile is bad”.

Step 2 — Identify affected breakpoints

Check at least:

small mobile (~320–375px)
medium/large mobile (~390–430px)
tablet (~768px+)
desktop sanity check

Never fix only one screen width.

Step 3 — Identify the responsible layer

Ask:

Is this mainly template structure?
CSS responsive issue?
JS state/interaction issue?
notice positioning issue?
wrong semantic element?

Use the correct layer:

structure issue → template
layout issue → CSS/SCSS
toggle/open/close/interaction issue → JS
mixed issue → smallest coordinated change
Step 4 — Check touch usability

Validate:

tap targets are large enough
controls are not too close together
no interaction depends on hover
fixed elements do not block critical buttons
menu, cart, forms, and CTA actions are reachable
Step 5 — Fix the smallest correct thing

Prefer:

local component fixes
scoped responsive rules
structural cleanup only if necessary
replacing unusable table layout with card/list layout where ticket requires it

Avoid:

giant CSS overrides
global selector hacks
random !important
magic numbers without justification
Step 6 — Validate layout + behavior

After fixing, check:

layout
readability
interaction
feedback visibility
no unintended desktop regression
Common Mobile UI Problem Patterns
1. Overlapping elements

Examples:

slider text over buttons/icons
notices over CTA
fixed floating elements blocking controls

Fix direction:

re-evaluate stacking, spacing, and layout flow
avoid absolute-position quick hacks unless validated
2. Excess vertical spacing

Examples:

giant gaps between sections
empty-looking pages on mobile

Fix direction:

reduce section margins/padding consistently
ensure rhythm remains balanced
3. Unusable tables on mobile

Examples:

orders table overflowing
unreadable columns

Fix direction:

convert mobile rendering to cards/list
keep desktop table if appropriate
4. Hidden notices / feedback

Examples:

coupon error visible only below fold
success/error message not seen

Fix direction:

make notices visible in viewport
use fixed/sticky patterns only if justified and non-blocking
5. Flat or cluttered mobile menu

Examples:

no visual hierarchy
duplicated items
too many irrelevant links

Fix direction:

simplify options
align with desktop structure
improve spacing, grouping, and visual hierarchy
6. Distorted images

Examples:

stretched gallery image
squashed decorative asset

Fix direction:

proper aspect-ratio handling
correct object-fit / container behavior
test portrait and landscape content
7. Small tap targets

Examples:

tiny icon-only controls
quantity buttons too small
hard-to-tap menu rows

Fix direction:

enlarge touch area
improve spacing
prefer button semantics where possible
File-Level Mobile Debug Checklist
components/header.php

Check:

mobile sidebar structure
menu item duplication
logo position
account/cart icons
auth-aware links
js/mobile-menu.js

Check:

open/close behavior
state classes
touch behavior
scroll lock if any
components/products/card.php

Check:

CTA size
quantity control spacing
card width and gap
image fit
woocommerce/single-product.php

Check:

gallery layout
product media stacking
extra images row/column behavior
woocommerce/myaccount/*

Check:

tables vs lists
tabs width
order detail readability
form overflow
js/checkout.js / js/coupons.js

Check:

notice visibility
sticky/fixed actions
layout shift during updates
sass/style.scss

Check:

breakpoint-specific overrides
duplicated/conflicting responsive rules
global selectors affecting multiple modules
Safe Fixing Rules
Allowed
scoped responsive CSS fixes
small template structure improvements
converting mobile table UI to card/list layout
improving spacing and hierarchy
fixing fixed/sticky notice placement
improving tap target sizes
tightening mobile-only component rendering
Do not
redesign unrelated modules
add heavy libraries for layout fixes
use large global overrides
hide content instead of fixing structure
break desktop to fix mobile
rely on hover for mobile interactions
Responsive Validation Checklist

For any mobile UI fix, validate:

Layout
no overflow
no overlap
no clipped content
no giant gaps
Readability
text readable
buttons readable
notices readable
Interaction
easy to tap
no blocked buttons
no hover-only access
menu opens/closes correctly
Consistency
matches BSC visual language
mobile logic matches desktop logic where appropriate
no duplicated actions/options
Manual QA Checklist

Always include practical QA such as:

test at 320–375px
test at 390–430px
test on iPad width
verify desktop unchanged
open/close mobile menu
tap all key actions
verify forms and notices are visible
verify fixed elements do not block checkout/cart actions

If the module is ecommerce-related, also test:

add to cart from mobile
coupon apply/remove
order list/detail
account navigation
product image gallery
Recommended Output Format

When using this skill, return:

Problem summary
what was broken on mobile
where it appeared
affected breakpoints/devices
Root cause
structure / CSS / JS / mixed
Files reviewed
file/a
file/b
file/c
Fix strategy
concise explanation of the minimal safe fix
Risks
desktop regression risk
tablet-specific behavior
fixed/sticky interference
duplicated selector impact
Manual QA
breakpoint checks
touch/tap checks
module-specific flows
Suggested commit
style(<scope>): [<TICKET-ID>] <short imperative summary>

Examples:

style(menu-mobile): [BSC-009] redesign mobile navigation layout
style(product-gallery): [BSC-014] prevent gallery image stretching on mobile
style(my-account): [BSC-021] convert mobile orders table into card list
style(coupons): [BSC-018] keep coupon feedback visible on mobile
High-Risk Anti-Patterns

Do not:

patch one breakpoint only
stack !important rules without understanding cascade
hide broken content instead of fixing it
move critical actions below the fold unintentionally
make desktop worse while fixing mobile
use fixed elements that block checkout/cart CTAs
assume visual fix = functional fix
Golden Rule

A mobile UI fix is correct only if:

the interface is clearer and easier to use
touch interaction works
mobile layout is stable across common widths
desktop is preserved unless intentionally changed
the solution is scoped, readable, and testable