# Review Ticket Prompt

You are reviewing a single implemented ticket in the BSC WordPress + WooCommerce project.

## Goal
Review the implementation for correctness, scope control, maintainability, and regression risk.

This is a **code review task**, not a QA execution task by default.
Focus on whether the implementation is technically sound and aligned with the ticket.

Do not rewrite the code unless explicitly asked.

## Mandatory context
Before doing anything, read:
1. `CLAUDE.md`
2. the active ticket file in `.Codex/tickets/`
3. the implementation diff / changed files
4. any directly relevant docs only:
   - `.claude/context/architecture-summary.md`
   - `.claude/context/coding-rules.md`
   - `.claude/context/known-issues.md`
   - `.claude/context/ui-ux-rules.md`
   - `.claude/context/wordpress-woocommerce-rules.md`
   - module docs if needed

Do **not** inspect unrelated modules unless necessary to understand risk.

---

## Execution rules

### 1. Scope discipline
- Review **one ticket only**
- Focus on the actual implementation against the ticket
- Do not drift into unrelated feature ideas
- Do not recommend broad rewrites unless the implementation is unsafe

### 2. Review mindset
Your job is to assess:
- whether the ticket was implemented correctly
- whether the scope stayed controlled
- whether the solution is maintainable
- whether it introduces regression risk
- whether it follows project rules

---

## Required workflow

### Step 1 — Ticket understanding
Start by outputting:

#### Ticket summary
- ticket ID
- title
- objective
- affected module(s)

#### Review scope
- what files are under review
- what change appears to have been made

### Step 2 — Implementation assessment
Summarize:
- whether the implementation matches the ticket
- whether the change is appropriately scoped
- whether the code follows repository rules

### Step 3 — Code quality review
Inspect:
- clarity
- structure
- use of correct layer (template / JS / AJAX / helper / Woo hook)
- duplication
- unsafe shortcuts
- hidden side effects

### Step 4 — Risk review
Identify:
- regression risks
- WooCommerce risks
- mobile/touch risks
- state synchronization risks
- missing defensive handling

### Step 5 — Final review verdict
Return one of:
- **APPROVE**
- **APPROVE WITH CHANGES REQUESTED**
- **REQUEST CHANGES**

Then explain why.

---

## Review checklist categories

### A. Ticket alignment
- Does the implementation solve the requested problem?
- Does it stay within ticket scope?
- Does it avoid unrelated behavior changes?

### B. Layer correctness
Was the change made in the right place?
- template
- JS interaction layer
- AJAX handler
- Woo hook
- helper

Flag if logic is placed in the wrong layer.

### C. Maintainability
- Is the code readable?
- Is the change understandable by the next developer?
- Is duplication introduced unnecessarily?
- Is this solution scalable enough for the current scope?

### D. Safety
- Are inputs sanitized where relevant?
- Are outputs escaped where relevant?
- Are failure states handled?
- Are null / missing / edge cases considered?

### E. WooCommerce / WordPress integrity
For WP/Woo-related tickets, verify:
- WooCommerce remains the source of truth
- no duplicated totals/state logic
- no unsafe bypass of cart/session/order logic
- hooks/APIs are used appropriately
- fragment refresh/state sync is preserved if relevant

### F. UI/UX integrity
If UI-related:
- does the implementation preserve consistency?
- does it consider mobile/tablet?
- does it avoid CSS hacks or fragile layout fixes?
- does it provide feedback where needed?

---

## Review severity levels

Use these when reporting issues:

### Critical
Must be fixed before merge.
Examples:
- breaks WooCommerce flow
- unsafe cart state handling
- obvious regression
- wrong business logic
- missing security validation

### Major
Should be fixed before merge.
Examples:
- incomplete acceptance coverage
- fragile implementation
- wrong module/layer choice
- missing edge-case handling

### Minor
Can be fixed now or in immediate follow-up.
Examples:
- naming clarity
- small duplication
- slight code organization issue

### Nit
Optional polish only.

---

## Final output format

### Ticket
- ID:
- Title:

### Verdict
- APPROVE / APPROVE WITH CHANGES REQUESTED / REQUEST CHANGES

### Summary
2–6 lines summarizing the review result.

### What Was Reviewed
- file/a
- file/b
- file/c

### Strengths
- strength 1
- strength 2
- strength 3

### Findings
For each finding use:
- **Severity:** Critical / Major / Minor / Nit
- **Location:** file + function/block if possible
- **Issue:** what is wrong
- **Why it matters:** impact
- **Recommended fix:** concise suggestion

### Acceptance Criteria Coverage
- Criterion 1 — covered / partially covered / not covered
- Criterion 2 — covered / partially covered / not covered
- Criterion 3 — covered / partially covered / not covered

### Risk Notes
- risk 1
- risk 2
- risk 3

### Recommended Action
One of:
- merge as-is
- merge after small fixes
- send back for revision

---

## Quality rules

### Be specific
Do not say:
- “looks good”
- “probably fine”
- “seems okay”

Always tie review comments to:
- ticket objective
- code behavior
- repository rules
- system risk

### Be strict but useful
- do not approve weak implementations
- do not request unnecessary rewrites
- prioritize correctness and safety over elegance

### Be conservative
If something is unclear in a high-risk area, flag it.

---

## Anti-patterns
Do not:
- rewrite the ticket during review
- ask for unrelated enhancements
- drift into a full architecture redesign
- ignore mobile or WooCommerce implications
- approve code that only partially solves the ticket

---

## Success definition
A review is complete only if:
- the ticket objective was assessed
- code quality was reviewed
- risks were identified clearly
- acceptance coverage was checked
- verdict is explicit and actionable
