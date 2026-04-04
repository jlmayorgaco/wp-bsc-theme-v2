# Skill: Build Admin Module

## Purpose

This skill defines how to safely design and implement a **custom admin module** for the BSC WordPress + WooCommerce project.

Use this when building or extending internal operational tools such as:
- custom admin menus
- operator dashboards
- simplified orders views
- reports
- tracking workflows
- role-specific product views
- stock/admin utilities

Goal:
- create focused internal tools
- reduce wp-admin complexity for non-technical staff
- preserve security and permissions
- keep WooCommerce as the source of truth

---

## When to Use This Skill

Use this skill for tickets such as:
- custom admin menu
- operator-only orders screen
- employee product management screen
- reports dashboard
- tracking code workflow
- custom inventory panels
- showroom sales/admin flow

Examples:
- `BSC-029` roles
- `BSC-030` custom BSC admin menu
- `BSC-031` orders module
- `BSC-033` tracking flow
- `BSC-035` reports
- `BSC-036` dual stock admin
- `BSC-037` showroom operational flow

---

## Core Principles

### 1. Admin modules must be task-driven
Do not expose raw WordPress/WooCommerce complexity if the operational user only needs:
- view orders
- change status
- add tracking
- export packing lists
- update stock fields

Design for the real task, not for platform completeness.

### 2. WooCommerce remains the source of truth
Admin modules are a **UI layer** over WooCommerce and WordPress data.

Do not create parallel systems for:
- cart
- orders
- totals
- payments
- stock
- customer identity

### 3. Permissions must be narrow
Operational users should see only what they need.

Never casually expose:
- payments config
- WooCommerce settings
- users/settings screens
- plugin/theme settings
- security/admin tools

### 4. Prefer simple internal UX
Admin tools should be:
- fast
- clear
- table-based when useful
- low-friction
- consistent

Internal admin does not need marketing-heavy design. It needs clarity and speed.

---

## Recommended Admin Module Architecture

Use a modular structure like:

```text
admin/
├── bsc-admin-menu.php
├── bsc-dashboard.php
├── bsc-orders-page.php
├── bsc-products-page.php
├── bsc-reports-page.php
├── class-bsc-orders-table.php
├── class-bsc-products-table.php
├── bsc-admin-assets.php
└── bsc-admin-ajax.php

includes/
├── class-bsc-roles.php
├── class-bsc-permissions.php
├── class-bsc-orders-service.php
├── class-bsc-tracking-service.php
├── class-bsc-reports-service.php
└── class-bsc-stock-service.php