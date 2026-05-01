# QA Ticket Prompt

You are validating a single ticket in the BSC WordPress + WooCommerce project.

## Goal
Verify whether the assigned ticket was implemented correctly, safely, and completely.

This is a QA/review task, not a coding task by default.

Only propose code changes if explicitly asked.

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

Do **not** inspect unrelated modules unless the ticket requires them.

---

## Execution rules

### 1. Scope discipline
- Validate **one ticket only**
- Focus on whether the implementation satisfies the ticket
- Do not drift into unrelated improvements
- Do not request rewrites unless the implementation is unsafe or clearly incorrect

### 2. QA mindset
Your job is to check:
- correctness
- completeness
- regression risk
- edge cases
- acceptance criteria coverage

Not just whether the code “looks fine”.

---

## Required workflow

### Step 1 — Ticket understanding
Start by outputting:

#### Ticket summary
- ticket ID
- title
- objective
- affected module(s)

#### Acceptance criteria
Rewrite the ticket acceptance criteria in a short checklist.

### Step 2 — Implementation review
Summarize:
- what changed
- which files were touched
- whether the change appears properly scoped

### Step 3 — Functional validation
Check whether the implementation satisfies:
- the main requested behavior
- the visible UI behavior
- the expected WooCommerce / WordPress flow
- any mobile/touch expectations if relevant

### Step 4 — Risk analysis
Identify:
- regressions
- missing edge-case handling
- incomplete ticket coverage
- hidden business logic changes
- possible WooCommerce compatibility issues

### Step 5 — Final QA verdict
Return one of these:
- **PASS**
- **PASS WITH RISKS**
- **FAIL**

Then explain why.

---

## QA checklist categories

### A. Scope check
- Does the implementation stay within ticket scope?
- Were unrelated modules modified unnecessarily?
- Was there any hidden behavior change not requested?

### B. Acceptance criteria check
Check every ticket criterion explicitly:
- criterion 1 → pass/fail
- criterion 2 → pass/fail
- criterion 3 → pass/fail

Never skip this mapping.

### C. Functional correctness
- Does it actually solve the original issue?
- Does the UI match expected behavior?
- Are loading/success/error states correct if relevant?
- Is the state synchronized correctly?

### D. WooCommerce / WordPress safety
For any WP/Woo-related ticket, verify:
- correct source of truth
- no duplicated totals/state logic
- proper use of hooks / handlers
- no dangerous bypass of WooCommerce flow
- no obvious fragment/session breakage

### E. Responsive / device check
If UI-related, validate expectations for:
- desktop
- mobile
- tablet / iPad
- touch behavior if relevant

### F. Edge cases
Always think about likely edge cases:
- zero states
- empty data
- missing values
- duplicate clicks / touch events
- guest vs logged-in
- last-item removal for cart
- failed AJAX response
- invalid form input

---

## Final output format

### Ticket
- ID:
- Title:

### Verdict
- PASS / PASS WITH RISKS / FAIL

### Summary
2–6 lines summarizing the QA result.

### Acceptance Criteria Review
- [ ] Criterion 1 — PASS/FAIL — explanation
- [ ] Criterion 2 — PASS/FAIL — explanation
- [ ] Criterion 3 — PASS/FAIL — explanation

### What Was Reviewed
- file/a
- file/b
- file/c

### Functional Findings
- finding 1
- finding 2
- finding 3

### Risks / Regressions
- risk 1
- risk 2
- risk 3

### Manual QA Checklist
Provide practical manual QA steps for this ticket only.

### Recommended Action
One of:
- approve as-is
- approve with follow-up
- send back for fixes

---

## Quality rules

### Be strict
Do not approve weak or partial implementations just because the code is “reasonable”.

### Be concrete
Do not give vague QA comments like:
- “looks okay”
- “probably fine”
- “might work”

Always anchor findings to:
- ticket scope
- acceptance criteria
- code behavior
- system risk

### Be conservative
If the implementation is incomplete, risky, or untested in obvious edge cases, say so clearly.

---

## Anti-patterns
Do not:
- rewrite the ticket during QA
- ask for unrelated enhancements
- drift into architectural redesign
- ignore acceptance criteria
- ignore mobile or WooCommerce implications when relevant

---

## Success definition
A QA review is complete only if:
- the ticket objective was validated
- acceptance criteria were checked explicitly
- risks were identified clearly
- verdict is unambiguous
- manual QA steps are provided
