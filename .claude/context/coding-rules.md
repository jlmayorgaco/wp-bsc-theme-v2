coding-rules.md
# BSC Coding Rules

## 1. General Philosophy

Code in this repository must be:
- minimal
- safe
- traceable
- maintainable
- compatible with WordPress + WooCommerce
- easy to review ticket by ticket

Prefer the **smallest correct change** over a broad rewrite.

---

## 2. Ticket Discipline

- Work on **one ticket at a time**
- Do not mix multiple tickets in one implementation unless explicitly approved
- Every code change must map to a ticket ID
- Every ticket should result in one clean main commit when possible

Before editing:
1. summarize the current behavior
2. identify exact files to touch
3. propose the minimum viable implementation
4. identify risks

After editing:
1. list changed files
2. explain what changed
3. confirm acceptance criteria
4. provide manual QA
5. mention follow-ups if needed

---

## 3. Commit and Branch Rules

### Branch format
```text
<type>/<TICKET-ID>-short-name

Examples:

fix/BSC-003-cart-qty-zero
style/BSC-009-mobile-menu-redesign
feat/BSC-031-admin-orders-module
Commit format
<type>(<scope>): [<TICKET-ID>] <short imperative summary>

Examples:

fix(cart): [BSC-003] sync quantity controls when product returns to zero
fix(checkout): [BSC-016] auto refresh shipping price by city
style(menu-mobile): [BSC-009] redesign mobile navigation layout
feat(admin-orders): [BSC-031] add simplified operator orders dashboard

Do not use vague messages such as:

fix stuff
update
final
misc
more changes
4. PHP Rules
4.1 Style
Follow WordPress coding style where practical
Keep functions focused and small
Prefer explicit, readable logic over clever code
Use early returns to reduce nesting when appropriate
Avoid long mixed PHP/HTML blocks when logic can be extracted cleanly
4.2 Safety
Always sanitize input
Always escape output
Validate assumptions before using values
Check objects before calling methods on them
Do not trust $_POST, $_GET, $_REQUEST
4.3 WordPress conventions

Prefer:

sanitize_text_field()
sanitize_email()
sanitize_textarea_field()
absint()
esc_html()
esc_attr()
esc_url()
wp_kses_post() when rich text is required
4.4 Structure
Put setup/bootstrap logic in functions.php only when necessary
Put feature logic in appropriate inc/, components/, admin/, or helper files
Avoid turning functions.php into a dumping ground
Reuse helpers instead of duplicating business logic
4.5 Do not
hardcode production URLs
hardcode repeated contact data in multiple places
duplicate WooCommerce logic without reason
add dead debug code
leave commented-out experimental code in production files
5. JavaScript Rules
5.1 Style
Keep JS modular per feature
Prefer feature-scoped files over one giant script
Use event delegation where appropriate
Keep logic readable and predictable
Avoid global pollution
5.2 Interaction safety
Prevent double triggers on click/touch/pointer events
Handle loading, success, and error states
Do not assume desktop-only interactions
Test touch behavior for mobile and iPad-sensitive flows
5.3 AJAX rules
Use existing AJAX architecture when available
Keep request payloads explicit
Expect structured JSON responses
Handle failure states visibly
Never fail silently
5.4 Do not
put significant JS inline in templates unless unavoidable
add new JS libraries for small UI fixes
duplicate AJAX handlers
break fragment refresh behavior in cart/checkout
6. CSS / SCSS Rules
6.1 Style
Keep styles scoped to the component/module
Prefer extending existing structure over adding random one-off overrides
Respect current BSC visual language
Validate responsive behavior on desktop, tablet, and mobile
6.2 Responsive discipline

Every visual change must be checked for:

desktop
tablet / iPad
mobile
6.3 Do not
add “quick fix” CSS without checking breakpoints
use excessive !important
create style collisions in unrelated modules
solve layout bugs with fragile magic numbers unless clearly justified
7. WooCommerce Rules

WooCommerce compatibility is critical.

7.1 Before changing cart/checkout/account

Always inspect:

related template override
related JS file
related AJAX handler
inc/woocommerce.php
existing hooks already in use
7.2 Preferred approach
use WooCommerce hooks where possible
keep cart/session/order state inside WooCommerce flow
refresh UI from correct source of truth
preserve fragments or replace them only deliberately and safely
7.3 High-risk areas
cart quantity changes
qty = 0 edge case
shipping recalculation
coupons
mini-cart refresh
my account orders
order status UI
7.4 Do not
bypass WooCommerce session/state without a reason
compute duplicate totals in custom JS unless unavoidable
rewrite checkout logic broadly for a small UX task
break guest checkout or logged-in checkout assumptions
8. AJAX / Form Handling Rules
8.1 Required practices
validate nonce when applicable
sanitize all incoming fields
validate required values
return structured success/error responses
show visible feedback in UI
8.2 Error handling
errors should be actionable and readable
do not silently swallow exceptions
do not expose sensitive internals to users
logs are acceptable, but not as a substitute for user feedback
8.3 Rate limiting / abuse awareness

For forms like:

newsletter
contact
creators
login-like interactions

Prefer:

nonce
basic validation
deduplication where needed
simple anti-spam/rate-limiting patterns
9. File Placement Rules

Put code where it logically belongs:

PHP
theme bootstrapping → functions.php, inc/setup/
WooCommerce integration → inc/woocommerce.php
AJAX handlers → inc/ajax/
reusable UI blocks → components/
admin logic → admin/ or includes/
helper logic → dedicated helper/class files
JS
cart interactions → js/cart.js
checkout → js/checkout.js
coupons → js/coupons.js
mobile menu → js/mobile-menu.js
search → js/search.js
Docs / AI context
ticket files -> .Codex/tickets/
reusable instructions → .claude/skills/
checklists → .claude/checklists/
module docs → docs/

Do not place unrelated logic in arbitrary files just because they are already loaded.

10. Refactor Rules

Refactor only when:

needed to complete the ticket safely
duplication is directly blocking the task
current structure would create a bug if left as-is
Allowed refactor
extracting repeated helper logic
improving naming within touched scope
reducing obvious duplication in touched module
isolating logic from templates when needed
Not allowed without explicit approval
broad architecture rewrites
changing unrelated modules
renaming many files for aesthetics only
moving large chunks of code across the repo without necessity
11. Performance Rules

Performance is a first-class concern.

When writing code:

avoid repeated expensive queries
avoid unnecessary DOM work
avoid unnecessary network calls
load only what is needed where possible
prefer existing assets and patterns over new dependencies

When touching product loops, search, sliders, cart, or checkout:

consider query count
consider JS cost
consider image size
consider fragment refresh cost
12. Accessibility Rules

At minimum, new or modified UI should preserve:

readable text
visible focus states
usable tap targets
labels for relevant forms
accessible button/link semantics

Do not use non-semantic clickable containers if a real button or link is more appropriate.

13. Admin / Role Rules

When working on admin/operator flows:

keep permissions narrow
expose only the screens the role needs
do not leak payment/config/security access to operational roles
prefer task-focused custom screens over raw wp-admin complexity
14. Documentation Rules

If a ticket changes behavior significantly:

update relevant docs
update checklist if needed
note assumptions and follow-ups

If a ticket introduces:

a new flow
a new role
a new stock rule
a new restore/backup procedure

then document it in the correct place:

ROADMAP.md
ARCHITECTURE.md
OPERATIONS.md
docs/...
docs/decisions/ADR-*.md
15. Testing Rules

Minimum expected validation depends on the ticket, but always think in terms of:

desktop
mobile
guest
logged-in
happy path
edge case

For WooCommerce-related changes, consider:

add item
remove item
quantity to zero
coupon apply/remove
shipping recalculation
order visibility
state consistency
16. Anti-Patterns

Do not:

mix tickets
hardcode environment-specific values
add random CSS overrides without understanding layout
rely on visual rendering alone to assume a feature works
duplicate logic across templates and AJAX handlers
leave TODOs without context
add libraries for convenience when native/theme patterns are enough
make hidden behavior changes not requested by the ticket
