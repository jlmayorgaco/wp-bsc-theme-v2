# Bubble Skin Care (BSC) — AGENTS.md

## 1. Project Identity

Bubble Skin Care (BSC) is a custom **WordPress + WooCommerce** ecommerce implementation built primarily through a **custom theme**.

This repository should be treated as a production-oriented ecommerce codebase with the following business priorities:

1. Launch safely without regressions
2. Fix visible client-reported issues first
3. Preserve checkout, cart, account, navigation, and SEO
4. Improve mobile UX and performance
5. Build maintainable internal admin workflows for operations
6. Keep implementation minimal, traceable, and reversible

---

## 2. Core Working Principles

### 2.1 Golden Rules
- Everything should live in the custom theme unless there is a strong reason otherwise.
- Do not introduce heavy plugins or page builders for things that fit in theme templates, hooks, CSS, or JS.
- Do not perform large architecture rewrites when a ticket only asks for a contained fix.
- Do not mix a big refactor with a visual-only ticket.
- Do not break WooCommerce core flows.
- Prefer the smallest safe change that solves the ticket completely.

### 2.2 Delivery Rules
- **1 ticket = 1 closed delivery**
- Each ticket must include:
  - objective
  - context
  - files to inspect
  - exact implementation plan
  - acceptance criteria
  - manual QA
  - rollback notes
- Every change must be tied to a ticket ID.
- Every ticket should end with a clean git commit.

### 2.3 Change Discipline
Before editing code:
1. Summarize the current behavior in 5–10 lines
2. List the exact files to change
3. Explain the minimum viable implementation
4. Identify risks
5. Only then implement

After implementation:
1. List changed files
2. Explain what changed
3. Confirm acceptance criteria
4. Provide manual QA steps
5. Mention risks or follow-ups

---

## 3. Current Project Goals

The current roadmap is organized around these priorities:

### Priority 1 — Launch blockers
- WhatsApp broken or inconsistent
- hardcoded localhost / stale production URLs
- cart quantity / cart badge desync
- iPad/touch add-to-cart failures
- missing real contact email flow
- mobile menu issues

### Priority 2 — UX and responsive fixes
- mobile menu redesign
- consistent mobile/desktop category navigation
- home slider behavior
- product gallery stretching
- checkout coupon and shipping UX
- account mobile usability
- Bubble Points responsive cleanup

### Priority 3 — Operations / internal tooling
- simplified admin for operator/employee
- orders dashboard
- tracking code workflow
- shipping emails
- exports for packing
- reports
- dual stock model
- showroom sale flow

### Priority 4 — Technical hardening
- search optimization
- caching
- N+1 query cleanup
- asset versioning
- responsive images
- form security
- backup automation
- monitoring
- restore documentation

---

## 4. Ticket Execution Model

The repository follows a ticket-driven workflow.

### Ticket source
All ticket details should live in dedicated files under:

```text
.Codex/tickets/