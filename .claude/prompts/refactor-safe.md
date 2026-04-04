# Skill: Safe Refactor

## Purpose

This skill defines how to perform **safe, controlled refactors** in the BSC WordPress + WooCommerce project.

Goal:
- improve code quality without breaking functionality
- reduce duplication only where necessary
- keep changes minimal and reversible
- preserve WooCommerce integrity

---

## When to Use This Skill

Use safe refactor ONLY when:

- duplication directly affects the ticket implementation
- code is too fragile to safely modify without cleanup
- logic is repeated in multiple places and causing inconsistency
- readability blocks correct understanding of behavior
- a small structural improvement is needed to complete the ticket safely

---

## When NOT to Refactor

Do NOT refactor when:

- the ticket is purely visual (CSS/UI tweak)
- the code works and refactor is only “nice to have”
- it requires touching multiple unrelated modules
- it changes architecture significantly
- it risks breaking WooCommerce flows (cart, checkout, orders)
- it increases scope beyond the ticket

If unsure → do NOT refactor.

---

## Refactor Scope Rules

- keep refactor strictly within the ticket scope
- do not touch unrelated modules
- do not rename files globally
- do not reorganize directories
- do not introduce new patterns across the entire project

Refactor must be:
- local
- contained
- justified

---

## Safe Refactor Patterns

### 1. Extract helper function

Before:
- repeated logic inside multiple templates

After:
- extract into helper function in `inc/helpers/`

Use when:
- same logic appears multiple times
- reduces duplication safely

---

### 2. Isolate logic from template

Before:
- heavy logic inside `.php` template mixed with HTML

After:
- move logic into helper or function
- keep template mostly presentational

---

### 3. Simplify conditionals

Before:
```php
if ($a) {
    if ($b) {
        // logic
    }
}
After:

if (!$a || !$b) {
    return;
}
// logic
4. Remove dead code

Allowed:

unused variables
commented-out blocks
obvious leftovers

Do NOT remove:

code that may still be referenced indirectly
code without understanding its usage
5. Normalize repeated constants/data

Example:

phone numbers
URLs
repeated labels

Move to:

helper function
centralized config
High-Risk Refactor Areas

Refactor with extreme caution in:

js/cart.js
inc/ajax/cart-actions.php
inc/woocommerce.php
checkout logic
coupon logic
WooCommerce template overrides
account/order flows

These areas are tightly coupled to WooCommerce state.

WooCommerce Safety Rules During Refactor

Never:

duplicate cart logic
compute totals manually
bypass WooCommerce session
break fragment refresh
change checkout flow unintentionally

Always:

use WooCommerce APIs
preserve existing hooks
test edge cases
Required Workflow
Step 1 — Justify refactor

Explain:

why refactor is needed
what risk exists without it
Step 2 — Define scope

List:

exact files affected
exact logic being refactored
Step 3 — Keep change minimal
change only what is required
avoid cascading edits
Step 4 — Implement
maintain behavior
preserve naming clarity
keep code readable
Step 5 — Validate behavior

Confirm:

behavior unchanged (unless intended)
WooCommerce flow intact
UI unchanged (if not part of ticket)
Refactor Output Format
Refactor summary
what was refactored
why it was necessary
Files changed
file/a
file/b
Behavior validation
confirm no functional regression
confirm WooCommerce integrity
Risk check
any potential side effects
areas that need QA
Suggested commit
refactor(<scope>): [<TICKET-ID>] <short description>

Example:

refactor(cart): [BSC-003] extract quantity sync logic into helper
QA Requirements After Refactor

Always test:

Functional
original behavior still works
edge cases still handled
WooCommerce
add to cart
remove item
qty = 0
fragments refresh
UI
no visual regressions
no layout changes (unless intended)
Anti-Patterns

Do not:

refactor entire modules for small tickets
rename variables everywhere without necessity
introduce new abstractions just for elegance
move logic across layers unnecessarily
mix refactor + feature + bugfix in one change
hide behavior changes under “refactor”
Golden Rule

A refactor is correct only if:

it improves clarity or safety
it does NOT change behavior unintentionally
it does NOT increase risk
it does NOT expand scope beyond the ticket

If in doubt → do not refactor.