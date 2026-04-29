const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { gotoAndStabilize, loginToWpAdmin } = require('../helpers/ui');

const previewCases = [
  { slug: 'welcome', expected: 'Bienvenida a Bubble Skin Care' },
  { slug: 'password-reset', expected: 'Recupera tu contrase' },
  { slug: 'birthday', expected: 'Feliz cumple' },
  { slug: 'order-confirmed', expected: 'Tu pago fue recibido' },
  { slug: 'order-preparing', expected: 'Estamos preparando tu pedido' },
  { slug: 'order-shipped', expected: 'Tu pedido est' },
  { slug: 'order-delivered', expected: 'Tu pedido fue entregado' },
  { slug: 'order-cancelled', expected: 'Tu pedido fue cancelado' },
  { slug: 'followup-inactive', expected: 'Hace rato no te vemos por aqu' },
  { slug: 'followup-repurchase', expected: 'Tu rutina puede estar por acabarse' },
];

test.describe('BSC visual baseline - email previews', () => {
  test.describe.configure({ mode: 'serial' });

  for (const previewCase of previewCases) {
    test(`${previewCase.slug} preview`, async ({ page }, testInfo) => {
      const adminFixture =
        fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

      test.skip(
        !adminFixture?.username || !adminFixture?.password,
        'The admin fixture is required for email preview baselines.'
      );
      test.skip(
        !fixture?.previewRoutes?.[previewCase.slug],
        `The preview route for ${previewCase.slug} is not available.`
      );

      const loggedIn = await loginToWpAdmin(
        page,
        adminFixture.username,
        adminFixture.password
      );

      expect(loggedIn).toBeTruthy();

      await gotoAndStabilize(page, fixture.previewRoutes[previewCase.slug]);

      await expect(page.locator('body')).toContainText(previewCase.expected);

      const bodyHtml = await page.locator('body').evaluate((node) => node.innerHTML);
      expect(bodyHtml.includes('{{')).toBeFalsy();
      expect(bodyHtml.includes('}}')).toBeFalsy();

      const hrefs = await page.locator('a[href]').evaluateAll((links) =>
        links
          .map((link) => link.getAttribute('href'))
          .filter((href) => typeof href === 'string' && href.trim() !== '')
      );

      expect(hrefs.length).toBeGreaterThan(0);

      await expect(page).toHaveScreenshot(`email-${previewCase.slug}.png`, {
        animations: 'disabled',
        fullPage: true,
      });
    });
  }
});
