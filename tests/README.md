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
- `PW_ROUTE_PRODUCT`

## Fixture bootstrap

The visual suite can auto-bootstrap deterministic QA data by calling WordPress
through the Local PHP runtime.

It seeds:

- QA customer credentials
- QA administrator credentials for preview pages
- reusable WooCommerce order for thank-you baselines
- deterministic public visual category
- deterministic public visual PDP
- email preview routes

These authenticated baselines are intended to run with:

- `PW_PUBLIC_MODE=storefront`

Auto-discovered defaults:

- PHP binary: latest `php-*` under `%APPDATA%\\Local\\lightning-services`
- `php.ini`: latest `php.ini` under `%APPDATA%\\Local\\run\\*\\conf\\php`
- QA account route: WooCommerce `myaccount`
- Bubble Points route: `/mi-cuenta/bubble-points/`
- QA category route: fixture `product_cat` archive
- QA product route: fixture product permalink
- Thank-you route: fixture order `order-received` URL when `PW_PUBLIC_MODE=storefront`

Optional overrides:

- `PW_PHP_BIN`
- `PW_PHP_INI`
- `PW_ACCOUNT_EMAIL`
- `PW_ACCOUNT_PASSWORD`
- `PW_ROUTE_ACCOUNT`
- `PW_ROUTE_BUBBLE_POINTS`
- `PW_ROUTE_THANK_YOU`
- `PW_ROUTE_PRODUCT`
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
npm run test:e2e:visual:auth
npm run test:e2e:visual:public-auth
npm run test:e2e:visual:emails
npm run test:e2e:visual:update
npm run test:e2e:report
```

## First baseline workflow

1. Point the suite at the approved environment.
2. If the approved storefront is visible, run with `PW_PUBLIC_MODE=storefront`.
3. Prefer fixture-backed routes for category and PDP baselines.
4. Run `npm run test:e2e:visual:public-auth`.
5. Run `npm run test:e2e:visual:auth` when validating only authenticated gates.
6. Run `npm run test:e2e:visual:emails`.
7. Run `npm run test:e2e:visual:update` only when intentionally refreshing snapshots.
4. Review generated snapshots before committing them.

## Data assumptions

- public category and PDP baselines should prefer fixture routes over live catalog order
- checkout is reachable with the current catalog/cart state
- public visual baselines require a non-`coming soon` storefront response
- account, bubble points, and thank-you routes use a reusable QA fixture when the Local PHP runtime is available
- email preview baselines use reusable QA admin accounts and preview routes from the fixture bootstrap
- authenticated visual gates are expected to run with `--workers=1` to avoid local WordPress session/routing flake across parallel projects
