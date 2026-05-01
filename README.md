# Bubble Skin Care Theme v2

Custom WordPress + WooCommerce theme for Bubble Skin Care.

## Stack

- WordPress 6+
- WooCommerce 7+
- PHP 8+
- Sass
- Playwright
- Swiper 11 local assets
- Font Awesome local assets
- Internal modules under `plugins/`

## Main Commands

```bash
npm run lint
npm run lint:php
npm run lint:js
npm run lint:scss
npm run test:e2e:smoke
npm run test:e2e:visual
npm run compile:css
```

## Working Rules

- New work starts with a ticket in `.Codex/tickets/`.
- Completed tickets are removed from `.Codex/tickets/` after their delivery commit is accepted.
- Durable history lives in git commits, `CHANGELOG.md`, and release notes.
- Keep checkout, cart, account, admin orders, navigation, and SEO safe.
- Avoid heavy plugins or page builders for theme-level work.

## Important Files

- `AGENTS.md`: active agent workflow.
- `CLAUDE.md`: Claude workflow mirror.
- `CHANGELOG.md`: durable change history.
- `MVP2_RELEASE_STATUS.md`: latest release gate status.
- `MVP2_GO_LIVE_CHECKLIST.md`: deploy and rollback checklist.
- `docs/architecture.md`: current system map.

## Theme Areas

- `front-page.php`: home.
- `components/`: shared frontend components.
- `components/checkout/`: custom checkout views.
- `inc/ajax/`: AJAX handlers.
- `inc/woocommerce.php`: WooCommerce hooks and behavior.
- `admin/`: BSC admin pages and operational workflows.
- `includes/class-bsc-stock.php`: dual stock model.
- `emails/`: transactional and follow-up email templates.
- `tests/e2e/`: smoke and visual tests.
- `tools/`: local lint helpers.

## Release Validation

Minimum validation for code changes:

```bash
npm run lint
npm run test:e2e:smoke
```

Run visual tests for UI changes:

```bash
npm run test:e2e:visual
```

Before production, complete `MVP2_GO_LIVE_CHECKLIST.md`.
