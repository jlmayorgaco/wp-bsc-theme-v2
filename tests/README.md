# Playwright Test Baseline

This directory contains the first `MVP2` safety net for functional smoke flows
and pixel-perfect visual regression.

## Default base URL

- `http://bsc.local`

Override it with:

- `PLAYWRIGHT_BASE_URL`

## Public mode

By default, the suite assumes guest traffic may be gated by the current
`coming soon` mode.

- default: `PW_PUBLIC_MODE=coming-soon`
- storefront baseline mode: `PW_PUBLIC_MODE=storefront`

Equivalent shortcut:

- `PW_EXPECT_STOREFRONT=1`

## Default routes

- Home: `/`
- Category: `/product-category/group-skin-care/`
- Checkout: `/checkout/`
- Account: `/mi-cuenta/`
- Bubble Points: `/mi-cuenta/bubble-points/`

Override them with:

- `PW_ROUTE_HOME`
- `PW_ROUTE_CATEGORY`
- `PW_ROUTE_CHECKOUT`
- `PW_ROUTE_ACCOUNT`
- `PW_ROUTE_BUBBLE_POINTS`
- `PW_ROUTE_THANK_YOU`

## Authenticated fixture bootstrap

The visual suite can auto-bootstrap a deterministic QA customer and a reusable
WooCommerce order by calling WordPress through the Local PHP runtime.

These authenticated baselines are intended to run with:

- `PW_PUBLIC_MODE=storefront`

Auto-discovered defaults:

- PHP binary: latest `php-*` under `%APPDATA%\\Local\\lightning-services`
- `php.ini`: latest `php.ini` under `%APPDATA%\\Local\\run\\*\\conf\\php`
- QA account route: WooCommerce `myaccount`
- Bubble Points route: `/mi-cuenta/bubble-points/`
- Thank-you route: fixture order `order-received` URL when `PW_PUBLIC_MODE=storefront`

Optional overrides:

- `PW_PHP_BIN`
- `PW_PHP_INI`
- `PW_ACCOUNT_EMAIL`
- `PW_ACCOUNT_PASSWORD`
- `PW_ROUTE_ACCOUNT`
- `PW_ROUTE_BUBBLE_POINTS`
- `PW_ROUTE_THANK_YOU`
- `PW_DISABLE_WP_FIXTURE=1`

Without the fixture or explicit auth variables:

- account visual baseline is skipped
- bubble points visual baseline is skipped
- thank-you visual baseline is skipped unless the suite runs in storefront mode or `PW_ROUTE_THANK_YOU` is set

## Official viewports

- Mobile: `390x844`
- Tablet: `768x1024`
- Desktop: `1440x900`

## Commands

```bash
npm run test:e2e:smoke
npm run test:e2e:visual
npm run test:e2e:visual:update
npm run test:e2e:report
```

## First baseline workflow

1. Point the suite at the approved environment.
2. If the approved storefront is visible, run with `PW_PUBLIC_MODE=storefront`.
2. Ensure the expected products/pages exist.
3. Run `npm run test:e2e:visual:update`.
4. Review generated snapshots before committing them.

## Data assumptions

- the category route contains product cards
- the first product card opens a valid PDP
- checkout is reachable with the current catalog/cart state
- public visual baselines require a non-`coming soon` storefront response
- account, bubble points, and thank-you routes use a reusable QA fixture when the Local PHP runtime is available
