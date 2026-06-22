# BSC Sass Architecture

This theme keeps visual changes pixel-stable by separating CSS responsibilities and moving debt in small verified slices.

## Layers

- `tokens/`: Sass variables, CSS custom properties, breakpoints, spacing, color, radii, typography values. Token files must not emit component selectors, except the `.bsc` custom-property scope in `tokens/_css-vars.scss`.
- `generic/`: resets, normalize, box sizing, base accessibility helpers, and vendor-like foundations.
- `components/primitives/`: reusable public UI primitives such as typography, buttons, forms, layout helpers, and quantity controls.
- `components/`: product, checkout, header, footer, profile, orders, tabs, motion, and other reusable UI blocks.
- `pages/`: page-level composition only. A page file can arrange components, but should not become the only source of a reusable component.
- `admin/`: WordPress admin UI foundations and admin-only screens.
- `plugins/`: plugin-specific styles that intentionally follow WordPress/plugin constraints.

## Pixel-Stable Refactor Rules

- Preserve computed styles first. Remove invalid CSS only when browsers already ignore it, or verify the visual snapshot after changing it.
- Move rules between layers with the same selector, values, and cascade position unless the visual change is intentional.
- Prefer `@use` and `@forward`; active `@import` is legacy-only.
- Prefer values from `tokens/public` and `tokens/css-vars` for new public CSS.
- Keep raw hex, `rgba()`, and `!important` out of new component work unless there is a third-party or WooCommerce override reason.
- Use token breakpoints or local mixins instead of new ad-hoc breakpoint values.
- Add shared primitives when a pattern appears in two or more screens.

## Quality Gates

- `npm run compile:css` builds all Sass entrypoints.
- `npm run lint:scss` verifies Sass entrypoints compile independently.
- `npm run lint:scss-quality` blocks known high-risk SCSS patterns.
- `npm run lint:stylelint` checks general SCSS syntax/style rules.
- Visual changes should be paired with Playwright smoke or visual tests for the affected pages.
