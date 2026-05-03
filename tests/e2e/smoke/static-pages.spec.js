const { expect, test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const { gotoAndStabilize } = require('../helpers/ui');

const staticPages = [
  {
    name: 'FAQ',
    route: routes.faq,
    pageClass: '.page-faq',
    titleFragment: 'Preguntas frecuentes',
  },
  {
    name: 'Shipping Returns',
    route: routes.shippingReturns,
    pageClass: '.page-shipping-returns',
    titleFragment: 'Política de envíos',
  },
  {
    name: 'Claims',
    route: routes.claims,
    pageClass: '.page-claims',
    titleFragment: 'Reclamos',
  },
  {
    name: 'Cookies',
    route: routes.cookies,
    pageClass: '.page-cookies',
    titleFragment: 'Cookies',
  },
  {
    name: 'Copyrights',
    route: routes.copyrights,
    pageClass: '.page-copyrights',
    titleFragment: 'Derechos',
  },
  {
    name: 'Policies',
    route: routes.policies,
    pageClass: '.page-policies',
    titleFragment: 'Politicas',
  },
  {
    name: 'Privacy',
    route: routes.privacy,
    pageClass: '.page-privacy',
    titleFragment: 'Privacidad',
  },
];

test.describe('BSC smoke - static pages', () => {
  for (const staticPage of staticPages) {
    test(`${staticPage.name} page renders the correct hero shell`, async ({ page }) => {
      test.skip(!expectsStorefront(), 'Storefront mode is required for static page smoke');

      await gotoAndStabilize(page, staticPage.route);

      const notFound = await page.locator('.error-404').count();
      test.skip(notFound > 0, `${staticPage.route} is not published in this environment.`);

      await expect(page.locator(staticPage.pageClass).first()).toBeVisible();
      await expect(page.locator('.bsc__static-hero__title').first()).toContainText(staticPage.titleFragment);
      await expect(page.locator('main.bsc__page').first()).toBeVisible();
    });
  }
});
