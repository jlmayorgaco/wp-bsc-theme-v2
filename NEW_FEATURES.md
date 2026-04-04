# NEW_FEATURES.md - BSC Theme New Features Specification

**Document Version:** 1.0  
**Theme:** wp-bsc-theme-v2  
**Date:** April 2026  
**Status:** Planning

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [User Roles & Permissions System](#2-user-roles--permissions-system)
3. [Custom Admin Dashboard](#3-custom-admin-dashboard)
4. [BSC Orders Management (Operator View)](#4-bsc-orders-management-operator-view)
5. [BSC Products Management (Employee View)](#5-bsc-products-management-employee-view)
6. [Dual Stock System](#6-dual-stock-system)
7. [Custom Shipping Types](#7-custom-shipping-types)
8. [Automatic Email Notifications](#8-automatic-email-notifications)
9. [Tracking Link Integration](#9-tracking-link-integration)
10. [Bug Fixes Priority](#10-bug-fixes-priority)
11. [Performance Improvements](#11-performance-improvements)
12. [Technical Implementation Plan](#12-technical-implementation-plan)

---

## 1. Executive Summary

This document outlines the new features required for the Bubble Skin Care (BSC) WordPress/WooCommerce theme. The main objectives are:

1. **Simplified Admin Experience** - Role-based views for different team members
2. **Custom Order Management** - Simplified order workflow with custom statuses
3. **Dual Inventory System** - Warehouse stock vs. Showcase/Store stock
4. **Enhanced Shipping** - Two shipping methods based on stock type
5. **Bug Fixes** - Cart issues, coupon errors, UI/UX improvements
6. **Performance** - Significantly improve page load speed

---

## 2. User Roles & Permissions System

### 2.1 New Custom Roles

| Role | Capabilities | Access Level |
|------|-------------|--------------|
| **Admin (Administrator)** | Full access to WordPress + BSC custom features | All views |
| **Client/User** | Standard WooCommerce customer | Frontend only - My Account |
| **Operator** | Manage orders only | BSC → Orders view |
| **Employee** | Manage products + view orders | BSC → Orders, BSC → Products |

### 2.2 Role Implementation

```php
// Add custom roles on theme activation
function bsc_create_custom_roles() {
    // Operator role - Orders management only
    add_role(
        'bsc_operator',
        'BSC Operador',
        [
            'read' => true,
            'edit_orders' => true,
            'view_admin' => false,
        ]
    );

    // Employee role - Products + Orders view
    add_role(
        'bsc_employee',
        'BSC Empleado',
        [
            'read' => true,
            'edit_products' => true,
            'edit_orders' => true,
            'view_admin' => false,
        ]
    );
}
add_action('after_setup_theme', 'bsc_create_custom_roles');
```

### 2.3 Access Control Logic

```php
// Restrict access based on role
function bsc_restrict_admin_access() {
    $user = wp_get_current_user();
    
    if (in_array('bsc_operator', $user->roles)) {
        // Redirect to BSC Orders only
        if (is_admin() && !isset($_GET['page']) || $_GET['page'] !== 'bsc-orders') {
            wp_redirect(admin_url('admin.php?page=bsc-orders'));
            exit;
        }
    }
    
    if (in_array('bsc_employee', $user->roles)) {
        // Allow BSC Orders and BSC Products
        $allowed_pages = ['bsc-orders', 'bsc-products'];
        if (is_admin() && !in_array($_GET['page'] ?? '', $allowed_pages)) {
            wp_redirect(admin_url('admin.php?page=bsc-orders'));
            exit;
        }
    }
}
add_action('admin_init', 'bsc_restrict_admin_access');
```

---

## 3. Custom Admin Dashboard

### 3.1 BSC Admin Menu Structure

```
BSC (Custom Menu)
├── 📦 Pedidos (BSC Orders)
├── 📋 Productos (BSC Products) - Employee+
├── 📊 Informes (Reports) - Admin only
└️ ⚙️ Configuración (Settings) - Admin only
```

### 3.2 Menu Implementation

```php
// Add BSC admin menu
function bsc_add_admin_menu() {
    // Main menu - visible to all BSC roles
    add_menu_page(
        'BSC Dashboard',
        'BSC',
        'read',
        'bsc-dashboard',
        'bsc_render_dashboard',
        'dashicons-store',
        3
    );
    
    // Orders - Operator+
    add_submenu_page(
        'bsc-dashboard',
        'Pedidos BSC',
        'Pedidos',
        'edit_orders',
        'bsc-orders',
        'bsc_render_orders_page'
    );
    
    // Products - Employee+
    add_submenu_page(
        'bsc-dashboard',
        'Productos BSC',
        'Productos',
        'edit_products',
        'bsc-products',
        'bsc_render_products_page'
    );
}
add_action('admin_menu', 'bsc_add_admin_menu');
```

### 3.3 Dashboard Widgets

- **Today's Orders**: Count and revenue
- **Orders by Status**: Visual breakdown
- **Low Stock Alerts**: Products below threshold
- **Recent Activity**: Latest order changes

---

## 4. BSC Orders Management (Operator View)

### 4.1 Custom Order Statuses

| Status (Spanish) | Status (English) | WooCommerce Equivalent | Color |
|-----------------|------------------|----------------------|-------|
| Pago Recibido | Payment Received | `processing` | 🟢 Green |
| Orden en Preparación | Order Being Prepared | `pending` | 🟡 Yellow |
| Orden Enviada | Order Shipped | `shipped` custom | 🔵 Blue |
| Orden Recibida | Order Delivered | `completed` | ✅ Completed |
| Orden Cancelada | Order Cancelled | `cancelled` | 🔴 Red |
| Orden Fallida | Order Failed | `failed` | ⚫ Failed |

### 4.2 Orders Table Columns

```
| ID | Cliente | Fecha | Total | Estado | Guía de Envío | Código | Acciones |
```

- **ID**: Order number (custom format BSC-XXXX)
- **Cliente**: Customer name + email
- **Fecha**: Order date/time
- **Total**: Order total with currency
- **Estado**: Dropdown selector with status options
- **Guía de Envío**: Input field for tracking URL
- **Código**: Input field for tracking number
- **Acciones**: View, Edit, Email actions

### 4.3 Status Dropdown Implementation

```php
// Custom status dropdown in orders table
function bsc_render_order_status_dropdown($order_id, $current_status) {
    $statuses = [
        'wc-processing' => 'Pago Recibido',
        'wc-preparing' => 'Orden en Preparación',
        'wc-shipped' => 'Orden Enviada',
        'wc-completed' => 'Orden Recibida',
        'wc-cancelled' => 'Orden Cancelada',
        'wc-failed' => 'Orden Fallida',
    ];
    
    echo '<select class="bsc-order-status" data-order-id="' . $order_id . '">';
    foreach ($statuses as $key => $label) {
        $selected = ($key === $current_status) ? 'selected' : '';
        echo '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
    }
    echo '</select>';
}
```

### 4.4 Order Management Page Template

**File:** `admin/bsc-orders-page.php`

```php
// Custom orders list table
class BSC_Orders_List_Table extends WP_List_Table {
    // Override methods for custom columns
    // - Column for tracking link
    // - Column for tracking code
    // - Inline status editing
}
```

---

## 5. BSC Products Management (Employee View)

### 5.1 Product List View (Simplified)

Columns for product list:
```
| Imagen | SKU | Nombre | Stock Tienda | Stock Bodega | Precio | Estado | Acciones |
```

- **Stock Tienda**: Showcase/Store inventory
- **Stock Bodega**: Warehouse inventory
- **Estado**: Published/Draft/Private

### 5.2 Product Edit View

**File:** `admin/bsc-product-edit.php`

New fields per product:

```
📦 Inventario
├── [Number] Stock en Tienda (Showcase)
└── [Number] Stock en Bodega (Warehouse)

🚚 Envío
├── [Radio] Envío desde Tienda
├── [Radio] Envío desde Bodega
└── [Radio] Envío desde Ambos (depending on stock)
```

### 5.3 Product Admin Implementation

```php
// Add custom product fields
function bsc_add_product_meta_fields() {
    woocommerce_wp_number_input([
        'id' => '_stock_tienda',
        'label' => 'Stock en Tienda',
        'desc_tip' => true,
        'description' => 'Cantidad disponible en la tienda/showroom',
    ]);
    
    woocommerce_wp_number_input([
        'id' => '_stock_bodega',
        'label' => 'Stock en Bodega',
        'desc_tip' => true,
        'description' => 'Cantidad disponible en bodega',
    ]);
    
    woocommerce_wp_select([
        'id' => '_envio_tipo',
        'label' => 'Tipo de Envío',
        'options' => [
            'tienda' => 'Envío desde Tienda',
            'bodega' => 'Envío desde Bodega',
            'ambos' => 'Ambos (según disponibilidad)',
        ],
    ]);
}
add_action('woocommerce_product_options_inventory_product_data', 'bsc_add_product_meta_fields');
```

---

## 6. Dual Stock System

### 6.1 Stock Types

| Type | Spanish | Description | Used For |
|------|---------|-------------|----------|
| **Stock Tienda** | Stock in Store | Physical inventory at showroom/retail location | In-store purchases, showroom pickup |
| **Stock Bodega** | Stock in Warehouse | Warehouse/backroom inventory | Online orders, shipping |

### 6.2 Stock Logic

```
When order is placed:
├── Check shipping type selected by customer
├── If "Envío desde Tienda":
│   ├── Check stock in tienda
│   └── If not enough, check bodega
├── If "Envío desde Bodega":
│   └── Check stock in bodega
└── Calculate available stock = tienda + bodega (for display)
```

### 6.3 Stock Display on Frontend

```php
// Show stock based on shipping type
function bsc_get_product_stock_display($product_id) {
    $stock_tienda = get_post_meta($product_id, '_stock_tienda', true);
    $stock_bodega = get_post_meta($product_id, '_stock_bodega', true);
    $envio_tipo = get_post_meta($product_id, '_envio_tipo', true);
    
    $total = $stock_tienda + $stock_bodega;
    
    return [
        'tienda' => $stock_tienda,
        'bodega' => $stock_bodega,
        'total' => $total,
        'tipo_envio' => $envio_tipo,
    ];
}
```

### 6.4 Low Stock Alerts

```php
// Alert when stock is low
function bsc_check_low_stock($product_id) {
    $stock_tienda = get_post_meta($product_id, '_stock_tienda', true);
    $stock_bodega = get_post_meta($product_id, '_stock_bodega', true);
    $threshold = 5;
    
    if ($stock_tienda <= $threshold || $stock_bodega <= $threshold) {
        // Send notification to admin
        bsc_send_low_stock_alert($product_id, $stock_tienda, $stock_bodega);
    }
}
add_action('woocommerce_update_product', 'bsc_check_low_stock');
```

---

## 7. Custom Shipping Types

### 7.1 Shipping Methods Based on Stock

| Shipping Option | Description | Available When |
|-----------------|-------------|----------------|
| **Recoger en Tienda** | Pickup at store/showroom | Product has stock in tienda > 0 |
| **Envío Nacional** | National shipping from warehouse | Product has stock in bodega > 0 |
| **Envío Prioritario** | Priority shipping | Both stocks available |

### 7.2 Shipping Configuration

```php
// Add custom shipping options
function bsc_add_shipping_methods($methods) {
    $methods['bsc_tienda_pickup'] = 'BSC_Shipping_Tienda_Pickup';
    $methods['bsc_bodega_shipping'] = 'BSC_Shipping_Bodega';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'bsc_add_shipping_methods');

// Disable shipping method if no stock
function bsc_disable_shipping_no_stock($available_methods) {
    foreach ($available_methods as $method_id => $method) {
        if ($method_id === 'bsc_tienda_pickup' && !has_stock_tienda($product_id)) {
            unset($available_methods[$method_id]);
        }
        if ($method_id === 'bsc_bodega_shipping' && !has_stock_bodega($product_id)) {
            unset($available_methods[$method_id]);
        }
    }
    return $available_methods;
}
add_filter('woocommerce_available_shipping_methods', 'bsc_disable_shipping_no_stock');
```

### 7.3 Customer Selection

Customer selects shipping type at checkout:
- If product is from "tienda" type → Show "Recoger en Tienda" option
- If product is from "bodega" type → Show shipping options
- If product is "ambos" → Show both options

---

## 8. Automatic Email Notifications

### 8.1 Email Triggers

| Event | Recipient | Email Template |
|-------|-----------|----------------|
| Status → Pago Recibido | Customer | Order confirmed |
| Status → Orden en Preparación | Customer | Order being prepared |
| Status → Orden Enviada | Customer | Order shipped + tracking |
| Status → Orden Recibida | Customer | Order delivered |
| Status → Orden Cancelada | Customer | Order cancelled |
| Status → Orden Fallida | Customer | Payment failed |
| Tracking Added | Customer | Shipping update |

### 8.2 Email Implementation

```php
// Send email on status change
function bsc_send_order_status_email($order_id, $old_status, $new_status) {
    $order = wc_get_order($order_id);
    $customer_email = $order->get_billing_email();
    
    $email_templates = [
        'wc-processing' => 'order-confirmed',
        'wc-preparing' => 'order-preparing',
        'wc-shipped' => 'order-shipped',
        'wc-completed' => 'order-delivered',
        'wc-cancelled' => 'order-cancelled',
        'wc-failed' => 'order-failed',
    ];
    
    if (isset($email_templates[$new_status])) {
        $mailer = WC()->mailer();
        $email = $mailer->get_emails()['WC_Email_Customer_Processing_Order'];
        
        // Override with BSC template
        add_filter('woocommerce_email_template', function($template) use ($new_status) {
            return 'bsc-emails/' . $email_templates[$new_status] . '.php';
        });
        
        $email->trigger($order_id);
    }
}
add_action('woocommerce_order_status_changed', 'bsc_send_order_status_email', 10, 3);
```

### 8.3 Email Templates

**Template Files:**
- `emails/bsc-order-confirmed.php`
- `emails/bsc-order-preparing.php`
- `emails/bsc-order-shipped.php` (includes tracking info)
- `emails/bsc-order-delivered.php`
- `emails/bsc-order-cancelled.php`

---

## 9. Tracking Link Integration

### 9.1 Tracking Fields in Order Table

```php
// Add tracking fields to order
function bsc_add_tracking_fields_to_order($order_id) {
    update_post_meta($order_id, '_bsc_tracking_link', '');
    update_post_meta($order_id, '_bsc_tracking_code', '');
}
add_action('woocommerce_checkout_update_order_meta', 'bsc_add_tracking_fields_to_order');
```

### 9.2 Admin Interface

```php
// Tracking input in orders table
function bsc_render_tracking_inputs($order_id) {
    $tracking_link = get_post_meta($order_id, '_bsc_tracking_link', true);
    $tracking_code = get_post_meta($order_id, '_bsc_tracking_code', true);
    
    echo '<input type="text" 
            class="bsc-tracking-link" 
            placeholder="https://..." 
            value="' . esc_attr($tracking_link) . '" 
            data-order-id="' . $order_id . '">';
            
    echo '<input type="text" 
            class="bsc-tracking-code" 
            placeholder="Código de seguimiento" 
            value="' . esc_attr($tracking_code) . '" 
            data-order-id="' . $order_id . '">';
}
```

### 9.3 AJAX Save Handler

```php
// Save tracking via AJAX
add_action('wp_ajax_bsc_save_tracking', 'bsc_save_tracking');
add_action('wp_ajax_nopriv_bsc_save_tracking', 'bsc_save_tracking');

function bsc_save_tracking() {
    check_ajax_referer('bsc_ajax_action', 'nonce');
    
    $order_id = intval($_POST['order_id']);
    $tracking_link = sanitize_url($_POST['tracking_link']);
    $tracking_code = sanitize_text_field($_POST['tracking_code']);
    
    update_post_meta($order_id, '_bsc_tracking_link', $tracking_link);
    update_post_meta($order_id, '_bsc_tracking_code', $tracking_code);
    
    // Trigger tracking email
    bsc_send_tracking_email($order_id);
    
    wp_send_json_success(['message' => 'Tracking guardado correctamente']);
}
```

---

## 10. Bug Fixes Priority

### 10.1 Cart Issues (CRITICAL)

| Bug | Description | Priority |
|-----|-------------|----------|
| Cart Badge Not Updating | Cart count doesn't update everywhere | CRITICAL |
| Cart Cache Issues | Fragments not refreshing properly | CRITICAL |
| Cart Item Removal | Remove from mini-cart not working | HIGH |
| Cart Quantity Sync | Quantity changes not reflected | HIGH |

**Solution:** 
- Rewrite cart AJAX handlers
- Add proper fragment refresh
- Add cache busting
- Fix all cart templates

### 10.2 Coupon Errors

| Bug | Description | Priority |
|-----|-------------|----------|
| Coupon Validation | Wrong error messages | MEDIUM |
| Coupon Application | Sometimes doesn't apply | HIGH |
| Coupon Display | Shows wrong discount | MEDIUM |

**Solution:**
- Rewrite coupon AJAX handler
- Add proper validation
- Add error handling

### 10.3 UI/UX Bugs

| Bug | Description | Priority |
|-----|-------------|----------|
| Mobile Menu Z-Index | Menu behind content | HIGH |
| Filter Price Range | No visual feedback | MEDIUM |
| Loading States | No spinners on actions | MEDIUM |
| Error Messages | Sometimes hidden | HIGH |

---

## 11. Performance Improvements

### 11.1 Target Metrics

| Metric | Current | Target |
|--------|---------|--------|
| Page Load Time | 5+ seconds | < 2 seconds |
| Time to First Byte (TTFB) | 1+ seconds | < 500ms |
| First Contentful Paint | 3+ seconds | < 1.5s |
| Mobile Score (Lighthouse) | 40 | 80+ |

### 11.2 Performance Tasks

1. **Optimize Search**
   - Single query instead of 3
   - Add caching
   
2. **Optimize Images**
   - WebP format
   - Proper srcset
   - Lazy loading
   
3. **Optimize Queries**
   - Fix N+1 queries
   - Add transients
   - Use proper caching
   
4. **Optimize JavaScript**
   - Code splitting
   - Defer non-critical
   - Remove unused

5. **Optimize CSS**
   - Remove unused styles
   - Inline critical CSS
   - Proper media queries

---

## 12. Technical Implementation Plan

### Phase 1: Core Infrastructure (Week 1-2)

| Task | Duration | Files to Modify |
|------|----------|-----------------|
| Create custom roles | 2h | `functions.php`, new role file |
| Create admin menu | 4h | `functions.php`, new menu |
| Create BSC dashboard | 8h | New admin files |
| Access control logic | 4h | New permission file |

### Phase 2: Orders System (Week 2-3)

| Task | Duration | Files to Modify |
|------|----------|-----------------|
| Custom order statuses | 8h | `functions.php`, WooCommerce hooks |
| Orders table UI | 16h | New admin table class |
| Status change handler | 8h | AJAX handler |
| Email notifications | 12h | Email templates, hooks |

### Phase 3: Products System (Week 3-4)

| Task | Duration | Files to Modify |
|------|----------|-----------------|
| Product meta fields | 8h | `functions.php`, product data |
| Products list table | 8h | New admin table |
| Stock display logic | 8h | Product templates |
| Shipping methods | 16h | Shipping class files |

### Phase 4: Bug Fixes (Week 4-5)

| Task | Duration | Files to Modify |
|------|----------|-----------------|
| Cart fixes | 16h | `cart.js`, cart AJAX |
| Coupon fixes | 8h | Coupon AJAX, templates |
| UI/UX fixes | 16h | Various CSS/JS |
| Performance | 24h | Multiple files |

### Phase 5: Testing & Deploy (Week 5-6)

| Task | Duration |
|------|----------|
| Unit testing | 8h |
| Integration testing | 8h |
| User acceptance testing | 8h |
| Deploy to staging | 4h |
| Deploy to production | 4h |

---

## 13. File Structure (New Files)

```
wp-bsc-theme-v2/
├── admin/
│   ├── bsc-admin-menu.php        # Admin menu setup
│   ├── bsc-dashboard.php        # Main dashboard
│   ├── bsc-orders-page.php      # Orders management
│   ├── bsc-products-page.php    # Products management
│   ├── class-bsc-orders-table.php    # Orders list table
│   ├── class-bsc-products-table.php  # Products list table
│   └── bsc-ajax-handlers.php    # Admin AJAX
├── emails/
│   ├── bsc-emails.php           # Email configuration
│   ├── bsc-order-confirmed.php
│   ├── bsc-order-preparing.php
│   ├── bsc-order-shipped.php
│   ├── bsc-order-delivered.php
│   └── bsc-order-cancelled.php
├── includes/
│   ├── class-bsc-roles.php      # Role management
│   ├── class-bsc-permissions.php # Permission logic
│   ├── class-bsc-stock.php      # Stock management
│   ├── class-bsc-shipping.php   # Shipping methods
│   └── bsc-email-helper.php     # Email utilities
├── js/
│   ├── bsc-admin-orders.js      # Orders admin JS
│   ├── bsc-admin-products.js   # Products admin JS
│   ├── bsc-tracking.js          # Tracking input JS
│   └── bsc-cart-fix.js          # Cart fixes
├── css/
│   ├── bsc-admin.css            # Admin styles
│   └── bsc-cart-fix.css         # Cart fix styles
└── NEW_FEATURES.md             # This document
```

---

## 14. Testing Checklist

### Admin Testing
- [ ] Role creation works correctly
- [ ] Operator can only see Orders
- [ ] Employee can see Orders and Products
- [ ] Custom order statuses display
- [ ] Status change triggers email
- [ ] Tracking fields save correctly

### Frontend Testing
- [ ] Cart updates in all locations
- [ ] Coupon applies correctly
- [ ] Stock displays correctly
- [ ] Shipping options show based on stock
- [ ] Page load speed < 2 seconds

---

## 15. Rollback Plan

If issues arise:
1. Deactivate new plugin/files
2. Revert to standard WooCommerce order management
3. Keep dual stock as manual tracking initially

---

*Document prepared for Bubble Skin Care Theme v2*  
*For questions, refer to REVIEW.md and ISSUE.md for existing code documentation*