const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { gotoAndStabilize, loginToWpAdmin } = require('../helpers/ui');

const previewCases = [
  { slug: 'welcome', expected: 'Bienvenido Bubble Lover' },
  { slug: 'password-reset', expected: 'Recupera tu contrase' },
  { slug: 'password-changed', expected: 'Tu contraseña fue actualizada' },
  { slug: 'birthday', expected: 'Feliz cumple' },
  { slug: 'order-confirmed', expected: 'Gracias por tu compra' },
  { slug: 'order-preparing', expected: 'Estamos preparando tu pedido' },
  { slug: 'order-shipped', expected: 'Tu pedido est' },
  { slug: 'order-delivered', expected: 'Tu pedido fue entregado' },
  { slug: 'order-cancelled', expected: 'Tu pedido fue cancelado' },
  { slug: 'followup-inactive', expected: 'Te extra' },
  { slug: 'followup-repurchase', expected: 'Tu rutina puede estar por acabarse' },
  { slug: 'abandoned-cart', expected: 'Tu carrito BSC te espera' },
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

async function expectSharedTypography(page, projectName) {
  const displayTypography = projectName === 'mobile'
    ? { fontSize: '28px', fontWeight: '900', lineHeight: '34px' }
    : { fontSize: '32px', fontWeight: '900', lineHeight: '38px' };
  const roles = [
    {
      selector: '.bsc-email-title',
      expected: { fontSize: '24px', fontWeight: '900', lineHeight: '30px' },
      required: true,
    },
    {
      selector: '.bsc-email-body-copy',
      expected: {
        fontSize: '12px',
        lineHeight: '17.76px',
      },
      required: true,
    },
    {
      selector: '.bsc-email-section-title',
      expected: { fontSize: '18px', fontWeight: '900', lineHeight: '24px' },
      required: false,
    },
    {
      selector: '.bsc-email-button',
      expected: { fontSize: '16px', fontWeight: '800', lineHeight: '20px' },
      required: true,
    },
    {
      selector: '.bsc-email-display',
      expected: displayTypography,
      required: false,
    },
    {
      selector: '.bsc-email-caption',
      expected: { fontSize: '11px', fontWeight: '400', lineHeight: '13px' },
      required: true,
    },
    {
      selector: '.bsc-email-caption-strong',
      expected: { fontSize: '12px', fontWeight: '900', lineHeight: '16px' },
      required: true,
    },
    {
      selector: '.bsc-email-decoration',
      expected: { fontSize: '22px', fontWeight: '400', lineHeight: '22px' },
      required: true,
    },
  ];

  for (const role of roles) {
    const styles = await page.locator(role.selector).evaluateAll((nodes) =>
      nodes.map((node) => {
        const computed = window.getComputedStyle(node);
        return {
          fontSize: computed.fontSize,
          fontWeight: computed.fontWeight,
          lineHeight: computed.lineHeight,
        };
      })
    );

    if (role.required) {
      expect(styles.length, `${role.selector} must exist`).toBeGreaterThan(0);
    }

    for (const style of styles) {
      expect(style, `${role.selector} must use the shared typography`).toEqual(
        expect.objectContaining(role.expected)
      );
    }
  }

  expect(await page.locator('h1.bsc-email-title').count()).toBe(1);
  expect(await page.locator('h1:not(.bsc-email-title)').count()).toBe(0);

  const allowedTypographyClasses = [
    'bsc-email-title',
    'bsc-email-body-copy',
    'bsc-email-section-title',
    'bsc-email-button',
    'bsc-email-display',
    'bsc-email-caption',
    'bsc-email-caption-strong',
    'bsc-email-decoration',
    'bsc-email-preheader',
    'bsc-email-spacer',
  ];
  const unclassifiedTypography = await page
    .locator('[style*="font-size"]')
    .evaluateAll((nodes, allowedClasses) =>
      nodes
        .filter((node) => !allowedClasses.some((className) => node.classList.contains(className)))
        .map((node) => ({
          className: node.className,
          style: node.getAttribute('style'),
          tag: node.tagName,
          text: (node.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80),
        })),
      allowedTypographyClasses
    );

  expect(
    unclassifiedTypography,
    'every explicit font size must use a shared typography role'
  ).toEqual([]);
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

      expect(hrefs.some((href) => /(?:bsc\.local|localhost|127\.0\.0\.1)/i.test(href))).toBeFalsy();
      expect(hrefs).toContain(
        'https://www.instagram.com/bubbles.skincare?igsi=em1zNmw0Z2pjMDlu'
      );
      expect(hrefs).toContain(
        'https://www.tiktok.com/@bubblesskincare?_r=1&_t=ZS-99BnmyXTB7C'
      );

      await expectSharedTypography(page, testInfo.project.name);

      if (previewCase.slug === 'password-changed') {
        const passwordCard = page.locator('.bsc-email-password-card');
        const passwordCardIcon = page.locator('.bsc-email-password-card-icon img');
        const cardMetrics = await passwordCard.evaluate((node) => ({
          borderWidth: getComputedStyle(node).borderTopWidth,
          width: Math.round(node.getBoundingClientRect().width),
        }));
        const iconWidth = await passwordCardIcon.evaluate((node) =>
          Math.round(node.getBoundingClientRect().width)
        );

        expect(cardMetrics.borderWidth, 'password card border must stay subtle').toBe('1px');
        expect(cardMetrics.width, 'password card must use the available email width').toBeGreaterThanOrEqual(
          testInfo.project.name === 'mobile' ? 320 : 468
        );
        expect(iconWidth, 'password card icon must stay prominent').toBe(53);
      }

      const horizontalOverflow = await page.evaluate(
        () => document.documentElement.scrollWidth - document.documentElement.clientWidth
      );
      expect(horizontalOverflow, 'email content must not overflow horizontally').toBeLessThanOrEqual(1);

      await expect(page).toHaveScreenshot(`email-${previewCase.slug}.png`, {
        animations: 'disabled',
        fullPage: true,
      });
    });
  }
});
