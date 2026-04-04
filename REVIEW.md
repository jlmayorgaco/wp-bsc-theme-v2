# Bubble Skin Care (BSC) WordPress Theme - Complete Technical Review

## Table of Contents
1. [File Tree Structure](#a-file-tree-structure)
2. [Architecture Overview](#b-architecture)
3. [Main Features](#c-main-features)
4. [Development Pipeline](#d-pipeline)
5. [Detailed Class/Method/Function Reference](#e-detailed-reference)
6. [Guidelines for AI/LLM Agents](#f-guidelines-for-aiagents)

---

## A) File Tree Structure

```
wp-bsc-theme-v2/
├── CLAUDE.md                          # Project guidelines for AI agents
├── composer.json                      # PHP dependencies
├── composer.lock
├── style.css                          # Main theme stylesheet
├── style-rtl.css                      # RTL stylesheet
├── woocommerce.css                    # WooCommerce overrides
├── package.json                       # Node.js dependencies
├── package-lock.json
├── index.php                          # Main entry point
├── functions.php                      # Theme core functions (116 lines)
├── front-page.php                     # Homepage template (418 lines)
├── header.php                         # Header template
├── footer.php                         # Footer template
├── page.php                           # Generic page template
├── single.php                         # Single post template
├── search.php                         # Search results template
├── sidebar.php                        # Sidebar template
├── archive.php                        # Archive template
├── 404.php                           # Error page template

# Page Templates (Custom)
├── page-login.php                     # Login page
├── page-register.php                  # Registration page
├── page-mi-cuenta.php                 # My Account page
├── page-bubble-points.php             # Bubble Points page
├── page-bubble-creators.php           # Bubble Creators page
├── page-contact-us.php                # Contact page
├── page-faq.php                       # FAQ page
├── page-shipping-returns.php          # Shipping & Returns page
├── page-privacy.php                   # Privacy policy
├── page-policies.php                  # Policies page
├── page-copyrights.php                # Copyrights page
├── page-cookies.php                   # Cookies policy
├── page-claims.php                    # Claims page
├── page-checkout.php                  # Checkout page (custom)
├── page-cart.php                      # Cart page (custom)
├── template-bsc-shop-landing.php      # K-Beauty shop landing

# Components (Reusable Template Parts)
├── components/
│   ├── header.php                     # Main header (630 lines)
│   ├── footer.php                     # Main footer (124 lines)
│   ├── shop.php                       # Main shop view
│   ├── whatsapp.php                   # WhatsApp floating button
│   ├── swiper.php                     # Hero slider component
│   ├── product-category.php           # Category page (522 lines)
│   ├── header/
│   │   ├── BSC_HeaderNav.class.php    # Header navigation class (55 lines)
│   │   └── BSC_MenuNav.class.php      # Menu navigation class (105 lines)
│   ├── products/
│   │   ├── card.php                   # Product card class (176 lines)
│   │   ├── slider.php                 # Product slider class (143 lines)
│   │   ├── filters.php                # Product filters sidebar (108 lines)
│   │   └── categories-meta.php        # Categories metadata
│   ├── orders/
│   │   ├── orders-table.php           # Orders table
│   │   ├── orders-zero-state.php      # Empty orders state
│   │   └── order-progress-bar.php     # Order tracking progress
│   └── my-account/
│       └── my-account-header.php      # My Account header

# WooCommerce Templates (Overridden)
├── woocommerce/
│   ├── archive-product.php            # Product archive
│   ├── single-product.php             # Single product
│   ├── cart/
│   │   ├── cart.php                   # Cart page
│   │   ├── mini-cart.php              # Mini cart widget
│   │   ├── cart-totals.php            # Cart totals
│   │   ├── cart-empty.php            # Empty cart
│   │   ├── cross-sells.php           # Cross-sells
│   │   └── shipping-calculator.php   # Shipping calculator
│   ├── checkout/
│   │   ├── form-checkout.php         # Checkout form
│   │   ├── form-billing.php          # Billing form
│   │   ├── form-shipping.php         # Shipping form
│   │   ├── form-pay.php              # Payment form
│   │   ├── payment.php               # Payment methods
│   │   ├── thankyou.php              # Thank you page
│   │   └── order-received.php        # Order received
│   ├── myaccount/
│   │   ├── my-account.php            # My Account dashboard
│   │   ├── dashboard.php             # Dashboard
│   │   ├── orders.php                # Orders list
│   │   ├── view-order.php            # Order details
│   │   ├── downloads.php             # Downloads
│   │   ├── edit-account.php          # Edit account
│   │   ├── edit-address.php         # Edit address
│   │   ├── form-login.php            # Login form
│   │   ├── form-lost-password.php    # Lost password
│   │   ├── form-reset-password.php   # Reset password
│   │   └── navigation.php            # Account navigation
│   └── coming-soon.php               # Coming soon mode

# Template Parts
├── template-parts/
│   ├── content.php                    # Default content
│   ├── content-page.php              # Page content
│   ├── content-search.php            # Search content
│   └── content-none.php              # No content found

# Theme Includes (inc/)
├── inc/
│   ├── setup/
│   │   ├── theme-setup.php            # Theme setup (202 lines)
│   │   └── widgets-setup.php           # Widgets setup
│   ├── scripts/
│   │   └── enqueue-scripts.php        # Script enqueue (148 lines)
│   ├── ajax/
│   │   ├── cart-actions.php           # Cart AJAX (126 lines)
│   │   ├── checkout-actions.php       # Checkout AJAX (33 lines)
│   │   ├── filters-actions.php        # Filters AJAX (118 lines)
│   │   ├── coupons-actions.php        # Coupons AJAX
│   │   ├── newsletter-actions.php     # Newsletter AJAX (48 lines)
│   │   ├── creator-actions.php        # Bubble Creators AJAX
│   │   ├── review-summary-actions.php # Reviews AJAX
│   │   └── search-actions.php         # Search AJAX
│   ├── woocommerce.php               # WooCommerce compatibility (304 lines)
│   ├── functions_bsc.php             # BSC custom functions (21 lines)
│   ├── template-tags.php              # Template tags
│   ├── template-functions.php         # Template functions
│   ├── custom-header.php             # Custom header support
│   ├── customizer.php                # Theme customizer
│   ├── jetpack.php                   # Jetpack compatibility
│   └── admin/
│       └── product-covers.php         # Admin product fields

# Theme Plugins (Built-in)
├── plugins/
│   └── bubble-points/
│       ├── index.php                  # Plugin loader
│       ├── classes/
│       │   ├── class-bsc-bubble-points.php       # Points class (51 lines)
│       │   └── class-bsc-bubble-points-coupon.php # Coupon class
│       ├── hooks/
│       │   ├── hooks-front.php        # Frontend hooks
│       │   └── hooks-admin.php        # Admin hooks
│       ├── helpers/
│       │   └── helpers.php            # Helper functions
│       ├── includes/
│       │   ├── store.php              # Store functions
│       │   ├── redeem.php             # Redeem functions
│       │   └── install.php            # Installation
│       ├── views/
│       │   ├── profile-bubble-points.php
│       │   ├── modal-bubble-points.php
│       │   └── coupons-bubble-points.php
│       ├── config/
│       │   └── coupons.php            # Coupon config
│       ├── seeds/
│       │   └── demo1.php              # Demo data
│       └── admin/
│           └── user-history.php       # User history admin

# Scripts (JavaScript)
├── js/
│   ├── navigation.js                  # Desktop navigation
│   ├── mobile-menu.js                 # Mobile menu
│   ├── search.js                      # Search functionality
│   ├── cart.js                        # Add to cart AJAX
│   ├── filters.js                     # Product filters
│   ├── category-filter.js             # Category tab filter
│   ├── checkout.js                    # Checkout logic
│   ├── coupons.js                     # Coupon handling
│   ├── newsletter.js                  # Newsletter form
│   ├── tabs.js                        # Tab switching (skin types)
│   ├── swiper-init.js                 # Swiper slider init
│   ├── bsc-slider-mobile-hint.js      # Mobile slider hint
│   └── customizer.js                  # Customizer JS

# Styles (SASS)
├── sass/
│   ├── style.scss                    # Main styles
│   ├── woocommerce.scss              # WooCommerce styles
│   └── tokens/
│       ├── _colors.scss              # Color tokens
│       ├── _typography.scss           # Typography tokens
│       ├── _structure.scss           # Structure tokens
│       ├── _widths.scss              # Width tokens
│       └── _columns.scss             # Column tokens

# Images & Assets
├── images/
│   ├── header_menus/                  # Header menu images
│   ├── home_brands/                  # Brand logos
│   ├── shop/                         # Shop section images
│   ├── bsc_logo_*.png               # Logo variants
│   ├── bsc__placeholder_product.jpg  # Placeholder image
│   └── ...
├── fonts/
│   ├── dlicon.woff2                  # Custom icon font
│   ├── dlicon.woff
│   ├── dlicon.ttf
│   └── dlicon.json                   # Icon definitions
├── vendor/
│   ├── fontawesome/                  # Font Awesome
│   │   └── all.min.css
│   └── webfonts/                     # Web fonts
│       ├── fa-solid-900.woff2
│       ├── fa-regular-400.woff2
│       ├── fa-brands-400.woff2
│       └── ...

# CI/CD
├── cicd/
│   └── deploy.php                    # Deployment script

# Helpers
└── helpers/
    └── recommended_products.php       # Recommended products
```

---

## B) Architecture

### B.1) Overall Architecture Pattern

The theme follows a **modular component-based architecture** with strong separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Core                           │
├─────────────────────────────────────────────────────────────┤
│                   WooCommerce Integration                   │
├─────────────────────────────────────────────────────────────┤
│                      Theme Core (functions.php)             │
│  ├── Theme Setup → Widget Setup → Scripts Enqueue          │
│  ├── AJAX Handlers → WooCommerce Hooks → Custom Pages      │
├─────────────────────────────────────────────────────────────┤
│                  Components (OOP Classes)                   │
│  ├── BSC_HeaderNav / BSC_MenuNav (Navigation)              │
│  ├── BSC_Products_Card (Product Display)                  │
│  ├── BSC_Products_Sliders (Homepage Sliders)              │
│  ├── BSCShopPage (Category Rendering)                     │
│  └── BSC_Bubble_Points (Loyalty Program)                  │
├─────────────────────────────────────────────────────────────┤
│                    Template Layer                           │
│  ├── Page Templates (page-*.php)                           │
│  ├── WooCommerce Overrides (woocommerce/)                 │
│  ├── Component Templates (components/)                    │
│  └── Template Parts (template-parts/)                     │
├─────────────────────────────────────────────────────────────┤
│                     JavaScript Layer                        │
│  ├── AJAX Operations (cart.js, filters.js)                 │
│  ├── UI Interactions (tabs.js, mobile-menu.js)            │
│  └── Third-party Integration (swiper-init.js)             │
├─────────────────────────────────────────────────────────────┤
│                      CSS/SASS Layer                         │
│  ├── Design Tokens (_colors, _typography, etc.)           │
│  ├── Core Styles (style.scss)                             │
│  └── WooCommerce Overrides (woocommerce.scss)             │
└─────────────────────────────────────────────────────────────┘
```

### B.2) Key Architectural Decisions

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| **Plugin Distribution** | Built-in plugin system in `/plugins/` folder | Custom loyalty program without external plugin dependency |
| **AJAX Pattern** | WordPress admin-ajax.php with nonce verification | Secure, standard WordPress approach |
| **Navigation** | OOP classes (BSC_HeaderNav, BSC_MenuNav) | Reusable, maintainable, programmatically configurable |
| **Product Rendering** | BSC_Products_Card class | Single responsibility for product display |
| **Category Pages** | BSCShopPage class with URL depth detection | Dynamic rendering based on URL structure |
| **Cart Management** | AJAX with WooCommerce fragments | Real-time cart updates without page reload |
| **Performance** | Conditional script loading per page type | Minimize HTTP requests |

### B.3) Database & Data Flow

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Browser    │────▶│   WordPress │────▶│  WooCommerce │
│              │◀────│   AJAX      │◀────│   Database   │
└──────────────┘     └──────────────┘     └──────────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │   Options   │
                    │  (wp_options)│
                    │ • bsc_home_favorites
                    │ • bsc_newsletter_subscribers
                    │ • bsc_default_pages_created
                    └──────────────┘
```

### B.4) Key WordPress/WooCommerce Hooks Used

**Theme Setup Hooks:**
- `after_setup_theme` - Theme initialization, nav menus, support
- `wp_enqueue_scripts` - Script/stylesheet enqueueing
- `template_redirect` - Page routing (guest redirects, coming soon)
- `wp_head` - Preconnect hints, meta tags

**WooCommerce Hooks:**
- `woocommerce_save_account_details` - Custom user meta save
- `woocommerce_checkout_fields` - Custom checkout fields
- `woocommerce_before_calculate_totals` - Shipping calculation
- `woocommerce_package_rates` - Free shipping threshold logic
- `woocommerce_add_to_cart_fragments` - Cart fragment refresh
- `woocommerce_output_related_products_args` - Related products config

**AJAX Hooks:**
- `wp_ajax_bsc_*` - Authenticated AJAX actions
- `wp_ajax_nopriv_bsc_*` - Guest AJAX actions

---

## C) Main Features

### C.1) E-Commerce Features

| Feature | Implementation | File(s) |
|---------|----------------|---------|
| **Product Catalog** | WooCommerce + custom category rendering | `components/product-category.php`, `BSCShopPage` class |
| **Product Cards** | Custom card with AJAX add-to-cart | `components/products/card.php` |
| **Shopping Cart** | AJAX cart management | `inc/ajax/cart-actions.php`, `js/cart.js` |
| **Checkout** | Custom checkout with Colombian fields | `inc/woocommerce.php` (lines 238-304) |
| **My Account** | Full WooCommerce account override | `woocommerce/myaccount/*` |
| **Order Tracking** | Custom order progress bar | `components/orders/order-progress-bar.php` |
| **Product Filters** | AJAX sidebar filters by group | `inc/ajax/filters-actions.php`, `components/products/filters.php` |

### C.2) Loyalty Program (Bubble Points)

| Feature | Implementation | File(s) |
|---------|----------------|---------|
| **Points System** | User meta-based points | `plugins/bubble-points/classes/class-bsc-bubble-points.php` |
| **Points Display** | Profile & modal views | `plugins/bubble-points/views/profile-bubble-points.php` |
| **Points Redemption** | Coupon generation | `plugins/bubble-points/includes/redeem.php` |
| **Admin Panel** | User history tracking | `plugins/bubble-points/admin/user-history.php` |

### C.3) User Account Features

| Feature | Implementation | File(s) |
|---------|----------------|---------|
| **Custom Registration** | Custom page template | `page-register.php` |
| **Custom Login** | Custom page template | `page-login.php` |
| **Guest Redirect** | Force login for /mi-cuenta | `functions.php` (lines 91-97) |
| **Profile Custom Fields** | Birthday, skin type, sensitivity | `inc/functions_bsc.php`, `inc/setup/theme-setup.php` |
| **Bubble Creators** | Influencer program page | `page-bubble-creators.php` |

### C.4) Homepage Features

| Feature | Implementation | File(s) |
|---------|----------------|---------|
| **Hero Slider** | Swiper.js integration | `components/swiper.php`, `js/swiper-init.js` |
| **Latest Launches** | SKU-based product slider | `components/products/slider.php` |
| **Favorites by Skin Type** | Tabbed product sections | `front-page.php` (lines 35-131), `js/tabs.js` |
| **Category Showcase** | Skin Care / Hair Care / Make Up grid | `front-page.php` (lines 142-214) |
| **Brands Section** | Brand logos with term links | `front-page.php` (lines 216-290) |
| **Featured Products** | Default product slider | `front-page.php` (lines 292-302) |
| **About/Benefits** | Institutional section | `front-page.php` (lines 304-342) |
| **Newsletter** | Email subscription with admin notification | `inc/ajax/newsletter-actions.php`, `js/newsletter.js` |
| **Blog Section** | Mockup with "Próximamente" | `front-page.php` (lines 359-377) |

### C.5) Navigation & Header Features

| Feature | Implementation | File(s) |
|---------|----------------|---------|
| **Desktop Navigation** | OOP-based mega menu | `components/header/BSC_HeaderNav.class.php`, `BSC_MenuNav.class.php` |
| **Mobile Menu** | Sidebar with details/summary | `components/header.php` (lines 509-627), `js/mobile-menu.js` |
| **Search** | AJAX live search | `inc/ajax/search-actions.php`, `js/search.js` |
| **User Profile Dropdown** | Conditional logged-in/guest | `components/header.php` (lines 374-405) |
| **Cart Icon** | With items count | `components/footer.php` (lines 102-105) |

### C.6) Footer Features

| Feature | Implementation | File(s) |
|---------|----------------|---------|
| **Link Columns** | Shipping, FAQ, Contact, Account | `components/footer.php` |
| **Social Icons** | Instagram, TikTok | `components/footer.php` (lines 77-94) |
| **Copyright** | Dynamic year (2020-2026) | `components/footer.php` (line 97) |
| **WhatsApp Float** | Fixed position with pre-filled message | `components/footer.php` (lines 107-110) |
| **Shopping Cart** | Quick access to checkout | `components/footer.php` (lines 102-105) |

### C.7) Newsletter System

| Feature | Implementation | File(s) |
|---------|----------------|---------|
| **Subscription Form** | AJAX with rate limiting | `inc/ajax/newsletter-actions.php` |
| **Duplicate Check** | Option-based email list | Same as above |
| **Admin Notification** | Email on new subscriber | Same as above |
| **Frontend UI** | Success/error feedback | `front-page.php` (lines 392-399), `js/newsletter.js` |

### C.8) Performance Features

| Feature | Implementation | File(s) |
|---------|----------------|---------|
| **Emoji Removal** | Disable WP emoji scripts | `functions.php` (lines 59-66) |
| **REST API Cleanup** | Remove head links | `functions.php` (lines 68-73) |
| **Gutenberg CSS Removal** | Dequeue block library | `functions.php` (lines 76-80) |
| **Version Management** | File-based versioning | `functions.php` (line 11) |
| **Google Fonts Preconnect** | Performance hint | `functions.php` (lines 86-89) |
| **Conditional Script Loading** | Only load needed JS | `inc/scripts/enqueue-scripts.php` |
| **Optimized Queries** | `no_found_rows`, `cache_results` | Various files |

### C.9) Checkout Customization (Colombia-Specific)

| Feature | Implementation | File(s) |
|---------|----------------|---------|
| **Cédula Field** | Required ID field | `inc/woocommerce.php` (lines 238-250) |
| **Default Country** | Colombia (CO) | `inc/woocommerce.php` (lines 299-304) |
| **Custom Placeholders** | Spanish labels | `inc/woocommerce.php` (lines 261-269) |
| **Custom Button Text** | "¡Hacer Compra!" | `inc/woocommerce.php` (lines 271-274) |
| **Free Shipping Threshold** | Hide when under $300k | `inc/woocommerce.php` (lines 277-293) |

---

## D) Pipeline

### D.1) Development Environment

```bash
# Local Development (likely Local by Flywheel or similar)
# Domain: bsc.local (per component/header.php line 266)

# Stack:
# - WordPress 6.x
# - WooCommerce 8.x
# - PHP 8.x
# - MySQL/MariaDB
```

### D.2) Build Process (Node.js/SASS)

```bash
# Install dependencies
npm install

# Development (watch SASS)
npm run dev  # or similar (check package.json)

# Production build
npm run build  # or similar (check package.json)
```

### D.3) Deployment Pipeline

The theme includes a CI/CD deployment script:

```php
// cicd/deploy.php
// Custom deployment logic (needs review)
```

### D.4) Theme Activation Flow

```
1. Theme Activation (after_switch_theme)
   ↓
2. Clear transient (bsc_pages_checked)
   ↓
3. Flush rewrite rules
   ↓
4. Create default pages (bsc_create_default_pages_once)
   ↓
5. Load theme plugins (bsc_load_theme_plugins)
```

### D.5) Page Creation System

The theme automatically creates these pages on activation:

| Page | Slug | Template |
|------|------|----------|
| Login | `/login` | page-login.php |
| Register | `/register` | page-register.php |
| My Account | `/mi-cuenta` | [woocommerce_my_account] |
| Bubble Points | `/mi-cuenta/bubble-points` | page-bubble-points.php |
| K-Beauty | `/product-category` | template-bsc-shop-landing.php |
| Bubble Creators | `/bubble-creators` | page-bubble-creators.php |
| Shipping & Returns | `/shipping-returns` | page-shipping-returns.php |
| FAQ | `/faq` | page-faq.php |
| Contact | `/contact-us` | page-contact-us.php |
| Welcome | `/registro-familia-bubbles` | Default |

---

## E) Detailed Reference

### E.1) Core Functions Reference

#### functions.php - Main Entry Point

| Function/Action | Lines | Purpose |
|-----------------|-------|---------|
| `_S_VERSION` constant | 10-12 | File-based cache busting |
| `bsc_2_0_setup()` | 15 | Theme setup (deprecated, now in theme-setup.php) |
| `bsc_2_0_scripts()` | 19 | Enqueue scripts (deprecated, now in enqueue-scripts.php) |
| Theme includes | 22-36 | Load all required files |
| Emoji removal | 59-66 | Performance optimization |
| REST cleanup | 68-73 | Security/hide info |
| Gutenberg dequeue | 76-80 | Performance |
| Preconnect hints | 86-89 | Performance |
| `bsc_redirect_my_account_guests()` | 91-97 | Force login |
| Coming soon mode | 101-116 | WooCommerce visibility |

### E.2) Theme Setup Reference

#### inc/setup/theme-setup.php

| Function | Purpose |
|-----------|---------|
| `bsc_2_0_setup()` | Register nav menus, theme support, content width |
| `bsc_create_default_pages()` | Create all required pages |
| `bsc_create_default_pages_once()` | With transient caching |
| `bsc_load_theme_plugins()` | Load bundled plugins |
| User meta save | `account_birthday`, `account_skin_type`, `account_sensitivity`, `bsc_needs1-4` |

### E.3) AJAX Handlers Reference

#### inc/ajax/cart-actions.php

| AJAX Action | Handler Function | Purpose |
|-------------|------------------|---------|
| `bsc_add_to_cart` | `bsc_ajax_add_to_cart_handler()` | Add product to cart |
| `update_cart_quantity` | `bsc_update_cart_quantity()` | Update item quantity |
| `bsc_get_cart_quantities` | `bsc_get_cart_quantities()` | Get all cart quantities |
| `bsc_remove_cart_item` | `bsc_remove_cart_item()` | Remove item from cart |

#### inc/ajax/filters-actions.php

| AJAX Action | Handler Function | Purpose |
|-------------|------------------|---------|
| `bsc_filter_products` | `bsc_filter_products()` | Filter products by category/price |

#### inc/ajax/newsletter-actions.php

| AJAX Action | Handler Function | Purpose |
|-------------|------------------|---------|
| `bsc_newsletter_subscribe` | `bsc_newsletter_subscribe()` | Subscribe to newsletter |

#### inc/ajax/checkout-actions.php

| AJAX Action | Handler Function | Purpose |
|-------------|------------------|---------|
| `bsc_reload_city_fields` | `bsc_reload_city_fields()` | Dynamic city field reload |

### E.4) Class Reference

#### BSC_HeaderNav (components/header/BSC_HeaderNav.class.php)

```php
class BSC_HeaderNav {
    private string $id;
    private string $class;
    private array $menus;
    
    public function setId(string $id): void
    public function setClass(string $class): void
    public function setMenus(array $menus): void
    public function getMenus(): array
    public function addMenu(BSC_MenuNav $menu): void
    public function renderNavButtons(): string      // Render header nav buttons
    public function renderNavContent(): string      // Render mega menu content
}
```

#### BSC_MenuNav (components/header/BSC_MenuNav.class.php)

```php
class BSC_MenuNav {
    private string $name;
    private string $slug;
    private string $image;
    private string $link;
    private array $menus;
    
    public function setName(string $name): void
    public function getName(): string
    public function setImage(string $image): void
    public function setLink(string $link): void
    public function getLink(): string
    public function setSlug(string $slug): void
    public function setCover(array $payload): void
    public function setMenus(array $menus): void
    public function getMenus(): array
    public function appendMenu(array $menu): void
    public function renderButton(): string        // Render menu button
    public function renderContent(): string       // Render menu dropdown content
}
```

#### BSC_Products_Card (components/products/card.php)

```php
class BSC_Products_Card {
    private $id;
    private $title;
    private $price;
    private $regular_price;
    private $sale_price;
    private $stock_status;
    private $image;
    private $categories = [];
    private $link;
    private $rating = 0;
    private $brand = '';
    private $type = 'simple';
    
    public function setProduct(WC_Product $product): void
    private function getProductBrand(array $terms): string
    public function render_images(): void
    public function render_rating(): void
    public function render_title(): void
    public function render_brand(): void
    public function render_price(): void
    public function render_button(string $label = '¡Lo quiero!'): void
    public function render(): void                  // Full card render
}
```

#### BSC_Products_Sliders (components/products/slider.php)

```php
class BSC_Products_Sliders {
    private $skus = [];
    private $label = '';
    private $slug = '';
    private $max_products = 5;
    
    public function setMax($max_products): void
    public function setSkus(array $skus): void
    public function setLabel(string $label): void
    public function setSlug(string $slug): void
    public function render(): void
    private function getFallbackProductIds(int $limit, array $exclude_ids = []): array
}
```

#### BSCShopPage (components/product-category.php)

```php
class BSCShopPage {
    private ?WP_Term $category;
    private ?WP_Term $parent;
    private ?WP_Term $grandparent;
    private bool $showFilters;
    private int $urlDepth;
    
    public function __construct()
    private function getDefaultChildTermForGroup(WP_Term $cat): ?WP_Term
    private function computeUrlDepth(): int
    private function computeShowFilters(): bool
    private function getImageUrl(WP_Term $term): string
    private function renderBreadcrumbs(?WP_Term $grandparent, ?WP_Term $parent, ?WP_Term $current): void
    private function renderCategoryGrid(array $terms): void
    private function renderLevel1(): void        // /product-category/
    private function renderLevel2(): void        // /product-category/group-*/
    private function renderLevel3(): void        // /product-category/*/* (with filters)
    private function renderProducts(WP_Term $category): WP_Query
    public function render(): void               // Main entry point
}
```

#### BSC_Bubble_Points (plugins/bubble-points/classes/class-bsc-bubble-points.php)

```php
class BSC_Bubble_Points {
    protected const META_KEY = 'bsc_bubble_points';
    
    public static function get($user_id): int
    public static function add($user_id, $points): void
    public static function deduct($user_id, $points): void
    public static function set($user_id, $points): void
    public static function has_enough($user_id, $required_points): bool
    public static function get_current(): int
}
```

### E.5) WooCommerce Hooks Reference

| Hook | Function | Purpose |
|------|----------|---------|
| `woocommerce_save_account_details` | Custom field save | Save birthday, skin type, sensitivity |
| `woocommerce_checkout_fields` | Add billing_cedula | Colombian ID field |
| `woocommerce_package_rates` | Hide free shipping | Threshold logic |
| `default_checkout_billing_country` | Default CO | Colombia |
| `woocommerce_order_button_text` | Custom text | "¡Hacer Compra!" |

### E.6) JavaScript Reference

| File | Purpose | Key Functions |
|------|---------|----------------|
| `cart.js` | Add to cart AJAX | `bsc_ajax_add_to_cart_handler` |
| `filters.js` | Product filtering | `bsc_filter_products` |
| `search.js` | Live search | AJAX search with results |
| `tabs.js` | Skin type tabs | Tab switching for favorites |
| `mobile-menu.js` | Mobile nav | Toggle sidebar |
| `newsletter.js` | Newsletter form | Subscribe AJAX |
| `category-filter.js` | Subcategory tabs | Client-side filter |

### E.7) Options Reference

The theme stores data in `wp_options`:

| Option Name | Type | Purpose |
|-------------|------|---------|
| `bsc_home_favorites` | Array | SKU lists for homepage sections |
| `bsc_newsletter_subscribers` | Array | Email list with dates |
| `bsc_default_pages_created` | Bool | Page creation flag |
| `bsc_pages_checked` | Transient | Daily check transient |

---

## F) Guidelines for AI/LLM Agents

### F.1) Before Making Changes

1. **Identify the correct file** - Use this document to locate the right template
2. **Check CLAUDE.md** - Follow project-specific guidelines
3. **Understand dependencies** - Many components depend on each other
4. **Check for nonces** - All AJAX calls must verify `bsc_ajax_action`
5. **Verify WooCommerce is active** - Many features depend on it

### F.2) Common Tasks & Where to Find Them

| Task | Location |
|------|----------|
| Add new homepage section | `front-page.php` + `components/products/slider.php` |
| Modify product card | `components/products/card.php` |
| Change header menu items | `components/header.php` (menu config) |
| Add checkout field | `inc/woocommerce.php` (check `woocommerce_checkout_fields`) |
| Modify cart AJAX | `inc/ajax/cart-actions.php` + `js/cart.js` |
| Add footer link | `components/footer.php` |
| Modify filters | `inc/ajax/filters-actions.php` + `components/products/filters.php` |
| Add page template | Create new `page-*.php` |
| Modify Bubble Points | `plugins/bubble-points/*` |

### F.3) Important Patterns

**AJAX Handler Pattern:**
```php
add_action('wp_ajax_bsc_action_name', 'handler_function');
add_action('wp_ajax_nopriv_bsc_action_name', 'handler_function');

function handler_function() {
    check_ajax_referer('bsc_ajax_action', 'nonce');
    // Process request
    wp_send_json_success([/* data */]);
}
```

**Product Card Rendering:**
```php
$card = new BSC_Products_Card();
$card->setProduct($product);
$card->render();
```

**Menu Creation:**
```php
$menu = new BSC_MenuNav();
$menu->setName('Menu Name');
$menu->setSlug('BSC_MENU_NAV_SLUG');
$menu->appendMenu([
    'slug' => 'section-slug',
    'title' => 'Section Title',
    'items' => [
        ['slug' => 'item-slug', 'title' => 'Item', 'link' => '/path/']
    ]
]);
$headerNav->addMenu($menu);
```

### F.4) Performance Considerations

- Use conditional script loading (`is_front_page()`, `is_shop()`, etc.)
- Always use `no_found_rows => true` for non-paginated queries
- Use `cache_results => true` for repeated queries
- Prefer `get_posts()` over `WP_Query` when only IDs needed
- Lazy load images with `loading="lazy"`
- Use file-based versioning (`_S_VERSION`)

### F.5) Testing Checklist

When modifying this theme, verify:
- [ ] Cart updates correctly (add/remove/update quantity)
- [ ] Filters return correct products
- [ ] Mobile menu opens/closes
- [ ] Search returns relevant results
- [ ] Checkout fields work correctly
- [ ] Login/logout works
- [ ] Newsletter subscription works
- [ ] Pages load without PHP errors
- [ ] WooCommerce fragments refresh properly

---

## G) Quick Reference Card

| Category | Key Files |
|----------|-----------|
| **Core** | `functions.php`, `inc/setup/theme-setup.php` |
| **Header** | `components/header.php`, `BSC_HeaderNav.class.php`, `BSC_MenuNav.class.php` |
| **Footer** | `components/footer.php` |
| **Homepage** | `front-page.php`, `components/swiper.php` |
| **Shop** | `woocommerce/archive-product.php`, `components/product-category.php`, `BSCShopPage` |
| **Product** | `components/products/card.php`, `components/products/slider.php` |
| **Cart** | `inc/ajax/cart-actions.php`, `js/cart.js` |
| **Filters** | `inc/ajax/filters-actions.php`, `components/products/filters.php`, `js/filters.js` |
| **WooCommerce** | `inc/woocommerce.php` |
| **My Account** | `woocommerce/myaccount/*` |
| **Bubble Points** | `plugins/bubble-points/*` |
| **Scripts** | `inc/scripts/enqueue-scripts.php`, `js/*` |
| **Styles** | `sass/*`, `style.css`, `woocommerce.css` |

---

*Document generated for Bubble Skin Care WordPress Theme v2*
*Last Updated: April 2026*