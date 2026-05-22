const { test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const { gotoAndStabilize, watchConsoleErrors } = require('../helpers/ui');

const publicPages = [
  ['home', routes.home],
  ['category', routes.categoryGrid || routes.category || routes.groupCategory],
  ['product detail', routes.product],
  ['cart', '/cart/'],
  ['contact', routes.contact],
  ['bubble creators', routes.bubbleCreators],
].filter(([, route]) => Boolean(route));

test.describe('BSC public console gate', () => {
  for (const [name, route] of publicPages) {
    test(`${name} route has no browser console errors`, async ({ page }) => {
      test.skip(!expectsStorefront(), 'Storefront mode is required for console smoke');

      const consoleWatcher = watchConsoleErrors(page, {
        allowed: [
          /favicon/i,
          /source map/i,
        ],
      });

      await gotoAndStabilize(page, route, {
        waitForImages: false,
      });

      consoleWatcher.assertNoErrors();
    });
  }
});
