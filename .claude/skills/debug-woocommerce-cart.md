# Skill: Debug WooCommerce Cart

## Purpose

This skill defines how to debug cart-related issues safely in the BSC WordPress + WooCommerce project.

Use this skill for problems such as:
- cart badge desynchronization
- quantity controls not matching real cart state
- mini-cart not refreshing
- qty = 0 edge cases
- add-to-cart failures
- duplicate add-to-cart behavior
- iPad / touch inconsistencies
- stale totals or fragment issues

Goal:
- identify the real source of truth
- trace the cart update flow end-to-end
- fix issues without breaking WooCommerce session/state

---

## Core Principle

WooCommerce is the source of truth for cart state.

The frontend UI is only a representation of:
- Woo cart session
- server-side state
- fragment-rendered state

Never assume the DOM is the true cart state.

---

## Typical Cart Flow in BSC

Expected flow:

```text
Product UI → JS event → AJAX request → PHP cart handler → WooCommerce cart update → fragment refresh → UI sync