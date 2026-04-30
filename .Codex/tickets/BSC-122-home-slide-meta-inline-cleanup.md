# BSC-122 — Home Slide Meta Inline Cleanup

## Objective
Remove the last non-email inline width styles from the `home_slide` custom post type settings fields without changing the data model or admin workflow.

## Context
- `scripts/script_custom_types.php` still renders meta inputs with `style="width:100%"`.
- This is a low-risk cleanup because WordPress admin already provides standard field sizing classes.
- No behavior or save logic needs to change.

## Files To Inspect
- `scripts/script_custom_types.php`

## Exact Implementation Plan
1. Replace inline width styles in the slide meta fields with `class="regular-text"`.
2. Replace the same inline width style in the home favorites settings fields with `class="regular-text"`.
3. Leave save handlers and field names untouched.

## Acceptance Criteria
- `scripts/script_custom_types.php` contains no inline `style=` attributes.
- The `home_slide` editor fields remain full-width enough for normal admin use.
- The home favorites settings inputs keep their current behavior.

## Manual QA
1. Open a `home_slide` post in admin and verify subtitle/button inputs render correctly.
2. Open the BSC home favorites settings screen and verify the SKU inputs still render and save.

## Rollback Notes
- Revert this commit to restore the inline width styling.
