const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { gotoAndStabilize, loginToWpAdmin } = require('../helpers/ui');

const previewCases = [
  { slug: 'welcome', expected: 'Bienvenido Bubble Lover' },
  { slug: 'password-reset', expected: 'Recupera tu contrase' },
  { slug: 'birthday', expected: 'Feliz cumple' },
  { slug: 'order-confirmed', expected: 'Gracias por tu compra' },
  { slug: 'order-preparing', expected: 'Estamos preparando tu pedido' },
  { slug: 'order-shipped', expected: 'Tu pedido est' },
  { slug: 'order-delivered', expected: 'Tu pedido fue entregado' },
  { slug: 'order-cancelled', expected: 'Tu pedido fue cancelado' },
  { slug: 'followup-inactive', expected: 'Te extra' },
  { slug: 'followup-repurchase', expected: 'Tu rutina puede estar por acabarse' },
];

async function ensureEmailPreviewLoaded(page, adminFixture, previewCase) {
  let lastError = null;

  for (let attempt = 1; attempt <= 5; attempt += 1) {
    try {
      const loggedIn = await loginToWpAdmin(
        page,
        adminFixture.username,
        adminFixture.password
      );

      expect(loggedIn).toBeTruthy();

      await gotoAndStabilize(page, fixture.previewRoutes[previewCase.slug], {
        maxAttempts: 7,
        primePage: false,
      });

      const bodyText = await page.locator('body').first().innerText().catch(() => '');
      if (bodyText.includes(previewCase.expected)) {
        return;
      }

      lastError = new Error(`Preview content for ${previewCase.slug} did not render expected text.`);
    } catch (error) {
      lastError = error;
    }

    await page.goto('about:blank').catch(() => null);
    await page.waitForTimeout(500 * attempt);
  }

  throw lastError || new Error(`Could not load preview ${previewCase.slug}.`);
}

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

      await ensureEmailPreviewLoaded(page, adminFixture, previewCase);

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
