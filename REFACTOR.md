# Clean Architecture Refactoring Plan

## 1. Goal
Transition the entire `wp-bsc-theme-v2` codebase from its procedural / hybrid origins into a Modern, Strongly-Typed, Object-Oriented **Clean Architecture**. This setup will improve scalability, testing, and debugging.

## 2. Core Concepts
* **Autoloading (`src/`)**: Move away from hundreds of `require_once` statements. Use PSR-4 autoloading via Composer (mapping `BSC\` to `src/`).
* **Features / Controllers**: Classes dedicated to registering hooks (`add_action`, `add_filter`) and routing AJAX boundaries.
* **Services**: Contain pure business logic (e.g., Order Processing, Stock Management, Emailing). They do not directly interact with HTML or direct `$_POST`/`$_GET` data.
* **Repositories**: The *only* layer responsible for interacting with the database (`$wpdb`) or native WordPress data functions (`get_post_meta`, `get_option`). Maps raw WP data into DTOs.
* **DTOs (Data Transfer Objects)**: Strictly typed classes used to pass data between Repositories, Services, and Features (e.g., passing a `UserContextDTO` or `CartSummaryDTO`).
* **Views & Renders**: Moving away from inline `echo '<div...'`. View classes prepare data, and dumb template files (in `template-parts/`) render the HTML.
* **Helpers**: Static, pure utility functions that contain no state.

## 3. Proposed Directory Structure & File Tree
We consolidate `admin2`, `inc`, `includes`, and `scripts` into a unified `src/` directory for PHP code, and `template-parts/` for HTML.

```text
wp-bsc-theme-v2/
├── src/                          # All Object-Oriented PHP logic (PSR-4 Autoloaded)
│   ├── Core/                     # Abstractions & Bootstrap
│   │   ├── Boot.php              # Initializes the Autoloader & Theme Config
│   │   ├── BaseView.php          # Base View class enforcing ->render()
│   │   ├── BaseFeature.php       # Base Feature class for module initialization
│   │   └── BaseRepository.php    # Base Repository for DB abstractions
│   │
│   ├── DTOs/                     # Data Transfer Objects
│   │   ├── ProductDTO.php
│   │   ├── OrderDTO.php
│   │   └── CartStateDTO.php
│   │
│   ├── Features/                 # Controllers - Entrypoints & Hook mapping
│   │   ├── Admin/                # Contains code migrated from `admin2/`
│   │   │   ├── DashboardFeature.php
│   │   │   └── ReportsFeature.php
│   │   ├── Checkout/
│   │   │   └── CheckoutAjaxController.php
│   │   ├── Shop/
│   │   │   └── ShopHooksFeature.php
│   │   └── Cart/
│   │       └── CartAjaxController.php
│   │
│   ├── Services/                 # Business Rules (No WP HTTP Context)
│   │   ├── StockService.php
│   │   ├── OrderProcessorService.php
│   │   └── MailingService.php
│   │
│   ├── Repositories/             # Database Access & Meta interactions
│   │   ├── ProductRepository.php
│   │   ├── OrderRepository.php
│   │   └── UserRepository.php
│   │
│   ├── Views/                    # Logic to prepare data for templates
│   │   ├── Admin/
│   │   │   └── DashboardView.php
│   │   ├── Checkout/
│   │   │   └── CheckoutFormView.php
│   │   └── Components/
│   │       └── GlobalNoticeView.php
│   │
│   └── Helpers/                  # Static utilities 
│       ├── CurrencyHelper.php
│       └── SecurityNonceHelper.php
│
├── template-parts/               # Dumb HTML/PHP templates (No deep logic)
│   ├── admin/
│   │   └── dashboard-table.php
│   ├── checkout/
│   │   └── main-form.php
│   └── components/
│       └── product-card.php
│
├── functions.php                 # Minimal. Only requires `vendor/autoload.php` and calls `BSC\Core\Boot::init()`
├── composer.json                 # Used for PSR-4 Autoloading configuration
└── index.php, single.php, etc.   # Standard WP Hierarchy files that utilize Views & Services
```

## 4. Design Patterns by Layer

### 4.1. The Entrypoint (`functions.php`)
Instead of loading 20+ files manually, the new `functions.php` will be drastically simplified:
```php
<?php
// Let Composer handle autoloading
require_once __DIR__ . '/vendor/autoload.php';

// Boot the application
\BSC\Core\Boot::init();
```

### 4.2. DTO (Data Transfer Object)
Stop using generic `WP_Post` objects or associative arrays which lack auto-completion and type safety.
```php
namespace BSC\DTOs;

class ProductDTO {
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly float $price,
        public readonly bool $inStock
    ) {}
}
```

### 4.3. Repositories
Repositories hide WordPress's legacy architecture. They return cleanly structured DTOs.
```php
namespace BSC\Repositories;
use BSC\DTOs\ProductDTO;

class ProductRepository extends \BSC\Core\BaseRepository {
    public function getProductStock(int $productId): ProductDTO {
        $post = get_post($productId);
        $stock = get_post_meta($productId, '_stock', true);
        return new ProductDTO($productId, $post->post_title, /* price */, $stock > 0);
    }
}
```

### 4.4. Services
Services process data independently of where the data comes from (AJAX, Admin page, CRON).
```php
namespace BSC\Services;

class StockService {
    public function __construct(
        private \BSC\Repositories\ProductRepository $productRepo
    ) {}

    public function processStockDeduction(int $productId, int $qty): bool {
        // Business logic here, calls $this->productRepo
        return true;
    }
}
```

### 4.5. Features (Controllers)
Features act as the glue. They register hooks and handle HTTP/AJAX requests.
```php
namespace BSC\Features\Cart;

class CartAjaxController extends \BSC\Core\BaseFeature {
    public function registerHooks(): void {
        add_action('wp_ajax_bsc_add_to_cart', [$this, 'handleAddToCart']);
    }

    public function handleAddToCart(): void {
        // 1. Verify Nonce (Helper)
        // 2. Read $_POST
        // 3. Call Service layer
        // 4. Return JSON response
    }
}
```

### 4.6. Views & Renders
A complete separation of concerns for HTML. 
```php
namespace BSC\Views\Checkout;

class CheckoutFormView extends \BSC\Core\BaseView {
    public function render(array $data = []): void {
        // Prepare complex view logic
        $data['formatted_total'] = \BSC\Helpers\CurrencyHelper::format($data['total']);
        
        // Render dumb template
        $this->loadTemplate('checkout/main-form', $data);
    }
}
```

## 5. Phased Migration Strategy

To avoid breaking the production website, the refactoring should happen in phases:

1. **Phase 1: Foundation.** Setup `composer.json` for PSR-4 autoloading (`"BSC\\": "src/"`). Create core abstractions in `src/Core/`.
2. **Phase 2: DTOs & Repositories.** Begin wrapping existing WP functions with Repository classes for Products, Users, and Orders. 
3. **Phase 3: Clean up AJAX.** Move all scripts from `inc/ajax/*.php` into Feature and Service classes. Direct POST logic in procedural scripts will convert to strictly typed Controller methods.
4. **Phase 4: Extraction of Views.** Identify all random HTML echos from `functions.php` and `inc/` files. Move the logic to View classes and the HTML to `template-parts/`.
5. **Phase 5: Consolidation of Admin.** Migrate the existing OOP efforts in `admin2/` directly into `src/Features/Admin/` to have one single unified `src/` backend.
