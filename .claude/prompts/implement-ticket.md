# Implement Ticket Prompt

You are implementing a single ticket in the BSC WordPress + WooCommerce project.

## Goal
Complete the assigned ticket safely, minimally, and fully, without introducing unrelated changes.

## Mandatory context
Before doing anything, read:
1. `CLAUDE.md`
2. the active ticket file in `.Codex/tickets/`
3. any directly relevant docs only:
   - `.claude/context/architecture-summary.md`
   - `.claude/context/coding-rules.md`
   - `.claude/context/known-issues.md`
   - `.claude/context/ui-ux-rules.md`
   - `.claude/context/wordpress-woocommerce-rules.md`
   - module docs if needed

Do **not** read unrelated files unless the ticket requires them.

---

## Execution rules

### 1. Scope discipline
- Work on **one ticket only**
- Do not mix unrelated changes
- Do not do broad refactors unless required for safety
- Do not change behavior outside the ticket scope unless necessary to complete the fix safely

### 2. Safe-change policy
Prefer:
- minimal safe changes
- existing hooks and patterns
- existing architecture
- existing UI language

Avoid:
- unnecessary dependencies
- rewrites
- speculative improvements
- unrelated cleanup

---

## Required workflow

### Step 1 — Understand the ticket
Start by outputting:

#### Ticket summary
- ticket ID
- title
- objective
- main risks

#### Files to inspect first
List only the files that are most likely relevant.

### Step 2 — Inspect current behavior
Before editing, summarize:
- current behavior
- probable root cause
- implementation strategy
- risks / edge cases

Keep this concise and practical.

### Step 3 — Implement
Make the smallest correct change needed to satisfy the ticket.

Rules during implementation:
- preserve WooCommerce compatibility
- preserve desktop behavior unless the ticket requires changing it
- validate mobile/touch behavior when relevant
- keep code readable and scoped

### Step 4 — Self-check
Before finishing, verify:
- did the change actually solve the ticket?
- did you avoid unrelated modifications?
- did you preserve current flows?
- did you touch only necessary files?

### Step 5 — Final output
Return:

#### Changed files
List all modified files.

#### What changed
Short bullet list.

#### Acceptance criteria check
Explicitly map the implementation to the ticket acceptance criteria.

#### Manual QA
Provide practical manual QA cases.

#### Risks / follow-ups
Mention anything that still needs review.

#### Suggested commit
Use this exact format:
```text
<type>(<scope>): [<TICKET-ID>] <short imperative summary>
Quality rules
PHP
sanitize input
escape output
validate assumptions
reuse helpers when possible
JS
prevent duplicate click/touch triggers
handle loading / success / error states
avoid silent failures
CSS / UI
validate desktop + mobile + tablet when relevant
avoid quick-fix CSS without checking breakpoints
maintain BSC visual consistency
WooCommerce
do not duplicate cart/checkout logic
use WooCommerce as source of truth
preserve fragment refresh behavior
test qty=0 and edge cases for cart-related work
Anti-patterns

Do not:

rewrite large modules for a small ticket
invent new architecture without need
silently change business logic
hardcode environment-specific URLs
return incomplete implementation while sounding complete

If something is uncertain, state the uncertainty clearly and keep the implementation conservative.

Success definition

The ticket is complete only if:

the requested behavior is implemented
no critical existing flow is broken
the solution is scoped and maintainable
manual QA is clearly defined
the commit message is ready

