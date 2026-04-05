# BSC-043 — Security Checklist

Audit date: 2026-04-04  
Audited by: Claude Code (BSC-043)

## Legend
- ✅ Secure — control in place
- ⚠️ Partial — present but could be stronger
- ❌ Missing — fix required
- N/A — not applicable

---

## AJAX Handlers (`inc/ajax/`)

| File | ABSPATH guard | Nonce check | Input sanitization | Capability check | Rate limiting | Output escaping |
|------|:---:|:---:|:---:|:---:|:---:|:---:|
| `cart-actions.php` | ✅ | ✅ | ✅ `absint`, `sanitize_text_field` | N/A (cart is public) | N/A | ✅ |
| `checkout-actions.php` | ✅ | ✅ | ✅ `sanitize_text_field` | N/A (checkout is public) | N/A | ✅ |
| `coupons-actions.php` | ✅ | ✅ | ✅ `sanitize_text_field` | N/A | N/A | ✅ |
| `filters-actions.php` | ✅ | ✅ | ✅ `sanitize_text_field`, `intval` | N/A | N/A (read-only) | ✅ |
| `review-summary-actions.php` | ✅ | ✅ | N/A (no user input) | N/A | N/A | ✅ |
| `search-actions.php` | ✅ | ✅ | ✅ `sanitize_text_field`, `wp_unslash` | N/A (read-only) | N/A (cached TTL 15min) | ✅ |
| `newsletter-actions.php` | ✅ | ✅ | ✅ `sanitize_email`, `is_email` | N/A | ✅ 60s per IP | ✅ |
| `contact-actions.php` | ✅ | ✅ | ✅ `sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field` | N/A | ✅ 60s per IP | ✅ |
| `creator-actions.php` | ✅ | ✅ | ✅ `sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field` | N/A | ✅ 300s per IP | ✅ |

---

## Admin AJAX Handlers (`admin/bsc-orders-page.php`, `admin/bsc-showroom-page.php`)

| Handler | Nonce | Capability | Sanitization |
|---------|:---:|:---:|:---:|
| `bsc_update_order_status` | ✅ `bsc_orders_nonce` | ✅ `edit_orders` | ✅ `absint`, `sanitize_text_field` |
| `bsc_save_tracking` | ✅ `bsc_orders_nonce` | ✅ `edit_orders` | ✅ `absint`, `sanitize_text_field`, `esc_url_raw` |
| `bsc_export_orders_csv` | ✅ WP nonce field | ✅ `edit_orders` | ✅ `absint` date params |
| `bsc_register_showroom_sale` | ✅ `bsc_showroom_nonce` | ✅ `edit_orders` | ✅ `absint`, `sanitize_text_field`, `sanitize_email` |
| `bsc_ajax_showroom_search` | ✅ `bsc_showroom_nonce` | ✅ `edit_orders` | ✅ `sanitize_text_field` |

---

## Forms (non-AJAX)

| File | CSRF nonce | Input sanitization | Output escaping |
|------|:---:|:---:|:---:|
| `page-login.php` | ✅ (WP native `wp_login_url` form includes nonce) | ✅ handled by WP core | ✅ |
| `page-register.php` | ✅ `bsc_register_nonce` (added BSC-043) | ✅ `sanitize_text_field`, `sanitize_email` | ✅ `esc_html` on errors |

---

## Rate Limiting Summary

| Action | Limit | Key |
|--------|-------|-----|
| Contact form | 1 req / 60s | IP hash |
| Newsletter subscribe | 1 req / 60s | IP hash |
| Bubble Creators apply | 1 req / 300s | IP hash |

Search and filter endpoints are read-only and rate-limited by the 15-minute transient cache (search) — no per-IP limiting required.

---

## Known Gaps / Follow-ups

- `page-register.php` — password strength is not enforced server-side (only client-side JS). Consider `wp_check_password_strength()` or a minimum length check.
- `filters-actions.php` — `posts_per_page: 48` hard limit prevents unbounded queries but no explicit pagination guard. Acceptable for current scale.
- AJAX handlers expose standard WP error format on nonce failure — this is expected WP behavior and does not leak sensitive data.

---

## How to run a quick audit

```bash
# Check all ajax handlers have check_ajax_referer
grep -r "check_ajax_referer" inc/ajax/ admin/

# Check for unescaped output
grep -rn "echo \$_" inc/ajax/ admin/

# Check for missing ABSPATH guard
grep -rL "defined.*ABSPATH" inc/ajax/
```
