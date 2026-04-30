# BSC-147 — Remove legacy `wp-bsc-plugin-v1` source after theme absorption

## Objective

Remove the copied legacy plugin source from the theme workspace after validating that the active theme runtime no longer depends on it.

## Context

- `BSC-146` absorbed the useful catalog/filter runtime into `plugins/bsc-catalog`.
- The copied folder `wp-bsc-plugin-v1` is not an active WordPress plugin in this repo layout.
- Keeping the folder around adds confusion, nested git history, and the risk of future accidental runtime coupling.

## Files To Inspect

- [plugins/bsc-catalog/docs/plugin-integration.md](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\plugins\bsc-catalog\docs\plugin-integration.md)
- [plugins/bsc-catalog](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\plugins\bsc-catalog)
- [wp-bsc-plugin-v1](C:\Users\walla\Local Sites\bsc\app\public\wp-content\themes\wp-bsc-theme-v2\wp-bsc-plugin-v1)

## Implementation Plan

1. Confirm there are no active runtime references to `wp-bsc-plugin-v1`.
2. Keep the copied JSON seed contracts inside `plugins/bsc-catalog/seed/legacy-plugin`.
3. Delete the `wp-bsc-plugin-v1` folder from the workspace.
4. Run smoke/visual checks on the catalog storefront to confirm nothing regressed.

## Acceptance Criteria

- `wp-bsc-plugin-v1` is removed from the workspace.
- The theme runtime still works with only `plugins/bsc-catalog`.
- Smoke tests covering category/product-card filters still pass.

## Manual QA

1. Open a level-3 category and apply filters.
2. Open a PDP from the category grid.
3. Add a product to cart from the category grid and reduce quantity back to zero.

## Rollback Notes

- Restore the folder from backup if some deferred legacy feature still needs source review.
- The active runtime does not require the folder to be restored.
