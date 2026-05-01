# Bubble Skin Care (BSC) - Project Overview

## What This Project Is

BSC is a custom WordPress + WooCommerce ecommerce theme. The theme acts as the application layer for storefront UI, checkout/cart behavior, admin operations, stock workflows, AJAX handlers, and email templates.

## Current Phase

The project is in MVP2 release hardening.

Current release references:

- `MVP2_RELEASE_STATUS.md`
- `MVP2_GO_LIVE_CHECKLIST.md`
- `CHANGELOG.md`
- `docs/architecture.md`

## Work Model

All new work is ticket-driven.

Active tickets live in:

```text
.Codex/tickets/BSC-###-short-title.md
```

Completed ticket files are removed from active queues after delivery commits are accepted. Use git history and release docs for completed work.

Each active ticket should define:

- objective
- context
- files to inspect
- implementation steps
- acceptance criteria
- QA cases
- rollback notes

## Main System Areas

- Storefront: home, header, product cards, shop/category pages, PDP, footer, WhatsApp.
- Cart and checkout: custom cart page, checkout components, coupons, shipping destination, review summary.
- Account: login/register, account dashboard, orders, view order.
- Admin operations: orders, packing, labels, products, reports, showroom, Bubble Points.
- Stock: dual stock model for bodega and showroom.
- Emails: order lifecycle, welcome, password reset, birthday, follow-up emails.

## Core Flow

```text
UI -> JS -> AJAX -> PHP -> WooCommerce/theme operation -> JSON response -> UI
```

## High-Risk Surfaces

- Checkout and payment.
- Cart quantity updates and badge sync.
- Admin packing stock deductions.
- Product stock edits.
- Account/order routes.
- Navigation and SEO-visible URLs.
