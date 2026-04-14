# BSC-070 Checkout multi-word department cities

## Objective

Fix checkout city loading for Colombian departments whose names contain multiple words, starting with Norte de Santander.

## Context

The Colombia departments/cities plugin includes cities for Norte de Santander, but its server-side city field renderer looks up places with `ucfirst(strtolower($current_sc))`. That changes `Norte de Santander` to `Norte de santander`, which does not match the plugin's exact data key.

The BSC checkout uses a custom AJAX endpoint (`bsc_reload_city_fields`) that re-renders the city field through that plugin server path. This makes some departments appear to have no cities even though the plugin data is present.

## Files To Inspect

- `inc/ajax/checkout-actions.php`
- `inc/woocommerce.php`
- `js/checkout.js`
- `wp-content/plugins/wc-departamentos-y-ciudades-colombia/includes/DYCDCPWC_Woo_Places_Class.php`
- `wp-content/plugins/wc-departamentos-y-ciudades-colombia/assets/places/CO-cities.php`

## Implementation Plan

1. Keep the existing BSC city reload endpoint.
2. Load the Colombia plugin place data through the existing theme helper.
3. Match the selected department by exact key first, then by normalized text as fallback.
4. Render the `billing_city` select directly when matching cities are found.
5. Re-trigger the plugin's city select enhancement after replacing the field.
6. Fall back to WooCommerce/plugin rendering if no city list is available.

## Acceptance Criteria

- Norte de Santander loads its cities in checkout.
- Other multi-word departments such as La Guajira and San Andres y Providencia also load cities.
- Existing single-word departments continue to load cities.
- The city field keeps the same `billing_city` name/id and `city_select` class.
- The replacement city select can still receive the plugin select enhancement.
- Shipping recalculation still receives the selected city code.

## Manual QA

1. Open `/checkout`.
2. Select `Norte de Santander` and confirm cities appear.
3. Select a city, for example `CUCUTA (N/STDER)`, and confirm the summary can update shipping.
4. Repeat with `La Guajira`.
5. Repeat with `Boyaca / CHIQUINQUIRA` to confirm the previous shipping fix still works.

## Rollback Notes

Revert this ticket's commit to return the city reload endpoint to WooCommerce/plugin rendering. The visible regression would be the return of empty city selects for affected multi-word departments.
