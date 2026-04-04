# WordPress & WooCommerce Rules

## 1. Purpose

This document defines how to safely work with:

- WordPress core
- WooCommerce core
- Theme-level overrides and custom logic

Goal:
- preserve ecommerce integrity
- avoid breaking cart, checkout, orders, or sessions
- ensure compatibility with WooCommerce updates

---

## 2. Core Principle

WooCommerce is the **source of truth** for:

- cart state
- totals
- shipping
- coupons
- order data
- customer session

Never duplicate or override this logic without strong justification.

---

## 3. WordPress Rules

### 3.1 Use WordPress APIs

Always prefer built-in WP functions over custom logic.

Use:
- `get_option()`, `update_option()`
- `get_post_meta()`, `update_post_meta()`
- `wp_insert_post()`
- `wp_mail()`
- `wp_enqueue_script()`, `wp_enqueue_style()`

Do not:
- manually query DB unless necessary
- hardcode URLs or paths
- bypass WP helpers

---

### 3.2 Hooks-first approach

Always try:
- `add_action`
- `add_filter`

Before:
- overriding templates
- modifying core behavior

Hooks are safer, more maintainable, and update-friendly.

---

### 3.3 Template overrides

WooCommerce templates can be overridden in:

```text
theme/woocommerce/
Rules:

override only what is necessary
do not copy entire templates unnecessarily
keep overrides minimal
track overrides in documentation

Risk:

outdated overrides break silently after Woo updates
4. WooCommerce Cart Rules
4.1 Source of truth

The cart is stored in:

WooCommerce session
server-side state

UI is NOT the source of truth.

4.2 Cart updates

Correct flow:

UI → JS → AJAX → PHP → WooCommerce cart → fragments → UI

Rules:

always update cart via WooCommerce APIs
always refresh fragments after change
always handle qty = 0
4.3 Required functions

Use WooCommerce APIs:

WC()->cart->add_to_cart()
WC()->cart->remove_cart_item()
WC()->cart->set_quantity()
WC()->cart->calculate_totals()

Do not:

manually compute totals
manipulate session directly
bypass WooCommerce cart methods
4.4 Fragment rules

Fragments are used to sync UI:

header cart
mini-cart
totals

Rules:

refresh fragments after any cart change
avoid triggering fragments unnecessarily
ensure consistency across UI
4.5 Edge cases

Always test:

adding first item
adding multiple items
removing last item
qty → 0
guest vs logged user
5. WooCommerce Checkout Rules
5.1 Checkout flow must remain intact

Do not break:

validation
totals calculation
shipping logic
payment flow
5.2 Shipping logic

Rules:

shipping must update automatically when location changes
do not hardcode shipping values in JS
use WooCommerce recalculation mechanisms
5.3 Coupons

Rules:

use WooCommerce coupon system
always show success/error messages
never fake coupon application on frontend
5.4 Totals

Rules:

totals must always come from WooCommerce
never calculate totals manually in JS
UI must reflect server state
6. Orders Rules
6.1 Order data

Orders are stored as:

WooCommerce order post type

Use:

wc_get_order()
order meta
6.2 Status transitions

Rules:

use WooCommerce order status system
do not invent parallel state systems

Example statuses:

pending
processing
completed
cancelled
failed
custom states if needed (but controlled)
6.3 Tracking integration

When adding tracking:

store in order meta
update status consistently
trigger email properly

Do not:

store tracking only in frontend
skip status transitions
7. User / Account Rules
7.1 User data

Use:

wp_get_current_user()
get_user_meta()

Do not:

store duplicate user data unnecessarily
7.2 My Account pages

Located in:

woocommerce/myaccount/

Rules:

keep Woo structure
extend, don’t break
preserve navigation and endpoints
8. AJAX Rules (WordPress + Woo)
8.1 Endpoints

Use:

wp_ajax_*
wp_ajax_nopriv_*
8.2 Security

Always:

validate nonce when needed
sanitize input
validate required fields
8.3 Response format

Return structured JSON:

{
  "success": true,
  "data": {}
}

or

{
  "success": false,
  "message": "Error message"
}
8.4 Do not
echo raw HTML blindly
return inconsistent formats
fail silently
9. Data Integrity Rules
9.1 Single source of truth

Avoid duplication of:

cart data
order totals
stock
user info
9.2 Meta usage

Use post meta for:

tracking codes
custom fields
admin extensions
9.3 Do not
store business-critical data only in frontend
rely on JS-only state
10. Stock & Inventory Rules
10.1 WooCommerce stock

Use WooCommerce stock system as base.

10.2 Dual stock (planned)

If implementing:

separate:
web stock
showroom stock

Rules:

define clear source of truth
avoid inconsistent decrements
document behavior clearly
11. Email Rules

Use:

wp_mail() or Woo email system

Rules:

emails must be triggered server-side
never rely only on frontend triggers
ensure correct data (order, tracking, totals)
12. Performance Rules (WP/Woo specific)

Avoid:

repeated get_posts() in loops
unbounded queries
loading all products when not needed

Prefer:

proper query arguments
caching where safe
limiting fields
13. Update Compatibility Rules

When changing:

templates
hooks
WooCommerce behavior

Always consider:

future Woo updates
template version mismatches

Document:

overridden templates
custom hooks
14. Anti-Patterns

Do not:

override WooCommerce core files
duplicate checkout logic
compute totals manually
bypass cart/session logic
hardcode shipping or pricing logic
mix frontend state with backend state inconsistently
skip Woo hooks when they exist
create parallel systems for orders or cart
15. Testing Requirements

For any WooCommerce-related change:

Must test:
add to cart
remove item
qty change
qty = 0
coupon apply/remove
shipping update
checkout submit
order creation
order visibility in account
Must test:
guest user
logged-in user
mobile
desktop
16. Final Rule

WooCommerce is not optional logic — it is the core system.

All changes must:

respect WooCommerce lifecycle
preserve data integrity
avoid duplicating logic
remain compatible with updates

