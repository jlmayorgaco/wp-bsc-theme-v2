Examples:
- `BSC-025`
- search lag issues
- excessive network calls
- irrelevant results

---

## Core Principles

### 1. Speed is critical
Search must feel instant (<300ms perceived response when possible).

### 2. Minimize queries
Avoid unnecessary or duplicated queries.

### 3. Minimize payload
Return only what is needed:
- product title
- image
- price
- link

Not full product objects.

### 4. Debounce input
Never query on every keystroke without control.

### 5. Mobile-first UX
Search must:
- not block typing
- not freeze UI
- not cause layout shifts

---

## Typical Search Flow in BSC

```text
User types → JS input handler → debounce → AJAX → PHP search query → JSON results → render dropdown

Likely files:

js/search.js
inc/ajax/search-actions.php
product query logic (WP_Query / WC_Query)
search UI component
Step-by-Step Optimization Workflow
Step 1 — Identify the problem

Be specific:

slow response?
too many requests?
irrelevant results?
UI lag?
dropdown flicker?
mobile unusable?
Step 2 — Inspect frontend behavior

In js/search.js:

Check:

event binding (input, keyup, etc.)
debounce implementation
request frequency
request cancellation (if any)
response handling
loading states

Key questions:

how many requests per second?
are requests overlapping?
are old results overwriting new ones?
Step 3 — Add / fix debounce

If missing or weak, implement debounce.

Recommended:

250–400 ms delay

Example logic:

user types → wait → send request only after pause

Do not:

fire request on every keystroke
stack multiple parallel requests unnecessarily
Step 4 — Handle request concurrency

Ensure:

only latest query result is used
previous requests are ignored or aborted

Fix patterns:

track last query string
discard outdated responses
use abort controller (if implemented)
Step 5 — Optimize backend query

In inc/ajax/search-actions.php:

Check:

query arguments
number of posts returned
fields retrieved
Use optimized query:
limit results (e.g., 5–10 items)
restrict post type (product)
avoid loading full meta if not needed
use fields => 'ids' when possible (then fetch minimal data)
Avoid:
full product object load if unnecessary
multiple nested queries
unbounded queries
Step 6 — Improve relevance

Adjust:

search fields (title, SKU, maybe meta)
ordering (relevance, popularity, recent)

Optional improvements:

prioritize exact matches
boost product titles over descriptions
handle partial matches intelligently
Step 7 — Minimize response payload

Return only:

{
  "id": 123,
  "title": "Product Name",
  "price": "$50",
  "image": "url",
  "url": "link"
}

Do not return:

full product HTML
unnecessary meta fields
large descriptions
Step 8 — Optimize UI rendering

In js/search.js:

Check:

rendering loop
DOM updates

Fix patterns:

batch DOM updates
avoid re-rendering entire list unnecessarily
show loading indicator while waiting
Step 9 — Improve UX behavior

Ensure:

dropdown appears quickly
results update smoothly
no flicker between queries
empty state handled:
“No results found”
loading state visible but not intrusive
Step 10 — Validate mobile behavior

Check:

typing is smooth
dropdown does not block input
results are readable
tap targets are usable
Common Root Cause Patterns
1. No debounce

Symptoms:

too many requests
server overload
laggy UI

Fix:

implement debounce
2. Overlapping requests

Symptoms:

results appear out of order
flickering results

Fix:

cancel or ignore outdated requests
3. Heavy backend queries

Symptoms:

slow response time

Fix:

limit fields
reduce query complexity
reduce result count
4. Large payloads

Symptoms:

slow network transfer
slow rendering

Fix:

return minimal data
5. Inefficient DOM updates

Symptoms:

UI stutters
lag when rendering results

Fix:

batch updates
reduce reflows
File-Level Checklist
js/search.js

Check:

debounce
request frequency
concurrency handling
rendering logic
loading state
empty state
inc/ajax/search-actions.php

Check:

query arguments
result limit
data returned
sanitization
JSON structure
Templates / Components

Check:

dropdown structure
result item layout
mobile usability
Safe Optimization Rules
Allowed
add debounce
reduce result count
optimize query arguments
minimize payload
improve rendering logic
improve loading/empty states
Do not
rewrite search system entirely
add heavy search libraries unnecessarily
break existing search behavior expectations
change search semantics drastically without requirement
introduce inconsistent result formats
Performance Targets

Aim for:

≤ 300–500 ms perceived response
≤ 1 request per input pause
≤ 10 results per query
minimal DOM updates
Manual QA Checklist

Test:

typing fast
typing slowly
deleting input
empty query
short query (1–2 chars)
long query
mobile typing
result click
no results state
Recommended Output Format
Problem summary
what was slow or incorrect
Root cause
debounce / query / payload / UI / concurrency
Files reviewed
file/a
file/b
Fix strategy
concise explanation
Performance improvement
what improved (requests, speed, payload)
Risks
relevance changes
edge query cases
Manual QA
typing scenarios
mobile validation
Suggested commit
perf(search): [<TICKET-ID>] <short imperative summary>

Examples:

perf(search): [BSC-025] add debounce and reduce query frequency
perf(search): [BSC-025] optimize product query and payload size
perf(search): [BSC-025] prevent outdated AJAX responses from overriding results
Anti-Patterns

Do not:

query on every keystroke
return full product HTML
load full product objects unnecessarily
allow multiple overlapping requests without control
block input during search
ignore mobile UX
Golden Rule

A search optimization is correct only if:

results appear quickly
queries are minimal and controlled
payload is lightweight
UI is smooth and responsive
behavior remains predictable and consistent