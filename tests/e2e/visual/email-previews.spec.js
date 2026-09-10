const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { gotoAndStabilize, loginToWpAdmin, watchConsoleErrors } = require('../helpers/ui');

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
  { slug: 'followup-repurchase', expected: 'Tenemos nuevas opciones para tu rutina' },
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
      const consoleWatcher = previewCase.slug === 'order-shipped'
        ? watchConsoleErrors(page)
        : null;
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
        const copyFrames = page.locator('.bsc-email-copy-frame');
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

        await expect(copyFrames, 'password messages must use constrained copy frames').toHaveCount(2);
        const copyFrameMetrics = await copyFrames.evaluateAll((nodes) =>
          nodes.map((node) => {
            const bounds = node.getBoundingClientRect();
            const parentBounds = node.parentElement.getBoundingClientRect();

            return {
              centerDelta: Math.abs(
                bounds.left + bounds.width / 2 - (parentBounds.left + parentBounds.width / 2)
              ),
              width: Math.round(bounds.width),
            };
          })
        );

        for (const metrics of copyFrameMetrics) {
          expect(metrics.width, 'password message width must remain email-client safe').toBe(283);
          expect(metrics.centerDelta, 'password message must remain centered').toBeLessThanOrEqual(1);
        }
      }

      if (previewCase.slug === 'birthday') {
        const copyFrame = page.locator('.bsc-email-copy-frame');
        const couponCard = page.locator('.bsc-email-coupon-card');
        const couponCheck = page.locator('.bsc-email-coupon-check img');
        const couponHeadline = page.locator('.bsc-email-coupon-headline');
        const couponMeta = page.locator('.bsc-email-coupon-meta');
        const cakeHero = page.locator('img[src*="bsc-email-hero-cake-complete.png"]');

        await expect(copyFrame, 'birthday message must use a constrained copy frame').toHaveCount(1);
        await expect(couponCard, 'birthday coupon card must render').toHaveCount(1);
        await expect(couponCheck, 'birthday coupon check must render').toHaveCount(1);
        await expect(couponHeadline, 'birthday coupon headline must render').toHaveCount(1);
        await expect(couponMeta, 'birthday coupon secondary copy must render').toHaveCount(1);
        await expect(cakeHero, 'complete birthday hero must render').toHaveCount(1);
        const copyMetrics = await copyFrame.evaluate((node) => {
          const bounds = node.getBoundingClientRect();
          const parentBounds = node.parentElement.getBoundingClientRect();

          return {
            centerDelta: Math.abs(
              bounds.left + bounds.width / 2 - (parentBounds.left + parentBounds.width / 2)
            ),
            width: Math.round(bounds.width),
          };
        });
        const birthdayMetrics = await page.evaluate(() => {
          const card = document.querySelector('.bsc-email-coupon-card');
          const check = document.querySelector('.bsc-email-coupon-check img');
          const hero = document.querySelector('img[src*="bsc-email-hero-cake-complete.png"]');

          return {
            borderWidth: getComputedStyle(card).borderTopWidth,
            cardCenterDelta: Math.abs(
              card.getBoundingClientRect().left + card.getBoundingClientRect().width / 2
                - (card.parentElement.getBoundingClientRect().left + card.parentElement.getBoundingClientRect().width / 2)
            ),
            cardWidth: Math.round(card.getBoundingClientRect().width),
            cardWidthPriority: card.style.getPropertyPriority('width'),
            tableLayout: getComputedStyle(card).tableLayout,
            checkWidth: Math.round(check.getBoundingClientRect().width),
            heroNaturalHeight: hero.naturalHeight,
            heroNaturalWidth: hero.naturalWidth,
            heroWidth: Math.round(hero.getBoundingClientRect().width),
            headlineFontSize: getComputedStyle(document.querySelector('.bsc-email-coupon-headline')).fontSize,
            headlineFontWeight: getComputedStyle(document.querySelector('.bsc-email-coupon-headline')).fontWeight,
            metaFontSize: getComputedStyle(document.querySelector('.bsc-email-coupon-meta')).fontSize,
            metaFontWeight: getComputedStyle(document.querySelector('.bsc-email-coupon-meta')).fontWeight,
          };
        });

        expect(copyMetrics.width, 'birthday message width must remain email-client safe').toBe(306);
        expect(copyMetrics.centerDelta, 'birthday message must remain centered').toBeLessThanOrEqual(1);
        expect(birthdayMetrics.borderWidth, 'birthday coupon border must match password card').toBe('1px');
        expect(birthdayMetrics.cardWidth, 'birthday coupon card must keep its fixed width').toBe(345);
        expect(birthdayMetrics.cardWidthPriority, 'birthday coupon width must override mobile fluid styles').toBe('important');
        expect(birthdayMetrics.tableLayout, 'birthday coupon table must honor its fixed width').toBe('fixed');
        expect(birthdayMetrics.cardCenterDelta, 'birthday coupon card must remain centered').toBeLessThanOrEqual(1);
        expect(birthdayMetrics.checkWidth, 'birthday coupon check must match password card').toBe(53);
        expect(birthdayMetrics.heroNaturalWidth, 'complete birthday hero must load').toBe(550);
        expect(birthdayMetrics.heroNaturalHeight, 'complete birthday hero must load').toBe(305);
        expect(birthdayMetrics.heroWidth, 'birthday hero must keep its intended email size').toBe(275);
        expect(birthdayMetrics.headlineFontSize, 'birthday coupon title must use section-title size').toBe('18px');
        expect(birthdayMetrics.headlineFontWeight, 'birthday coupon title must stay bold').toBe('900');
        expect(birthdayMetrics.metaFontSize, 'birthday coupon secondary copy must use body size').toBe('12px');
        expect(birthdayMetrics.metaFontWeight, 'birthday coupon secondary copy must stay regular').toBe('400');
      }

      if (previewCase.slug === 'order-shipped') {
        const previewUrl = new URL(page.url());
        expect(previewUrl.pathname, 'tracking preview must use the admin preview endpoint').toBe(
          '/wp-admin/admin-post.php'
        );
        expect(previewUrl.searchParams.get('action')).toBe('bsc_preview_email');
        expect(previewUrl.searchParams.get('template')).toBe('order-shipped');
        await expect(page).toHaveTitle(/Tu pedido esta en camino/i);
        await expect(page.locator('body')).toContainText('Copia y pega tu track:');
        await expect(
          page.locator('#vite-error-overlay, [data-nextjs-dialog-overlay], nextjs-portal')
        ).toHaveCount(0);

        const trackingMetrics = await page.evaluate(() => {
          const card = document.querySelector('.bsc-email-tracking-card');
          const check = document.querySelector('.bsc-email-tracking-card .bsc-email-coupon-check img');
          const content = document.querySelector('.bsc-email-tracking-content');
          const headline = document.querySelector('.bsc-email-tracking-card .bsc-email-section-title');
          const button = document.querySelector('.bsc-email-tracking-button');
          const cardBounds = card.getBoundingClientRect();
          const buttonBounds = button.getBoundingClientRect();
          const contentStyle = getComputedStyle(content);

          return {
            borderWidth: getComputedStyle(card).borderTopWidth,
            buttonLeftGap: Math.round(buttonBounds.left - cardBounds.left),
            buttonRightGap: Math.round(cardBounds.right - buttonBounds.right),
            checkFile: new URL(check.src).pathname.split('/').pop(),
            checkWidth: Math.round(check.getBoundingClientRect().width),
            headline: headline.textContent.trim(),
            paddingLeft: contentStyle.paddingLeft,
            paddingRight: contentStyle.paddingRight,
            tableLayout: getComputedStyle(card).tableLayout,
          };
        });

        expect(trackingMetrics.borderWidth, 'tracking card border must stay subtle').toBe('1px');
        expect(trackingMetrics.checkFile, 'tracking must use the shared pink coupon check').toBe(
          'bsc-email-coupon-check-pink.png'
        );
        expect(trackingMetrics.checkWidth, 'tracking check must match the birthday card').toBe(53);
        expect(trackingMetrics.headline, 'tracking instructions must use the requested copy').toBe(
          'Copia y pega tu track:'
        );
        expect(trackingMetrics.paddingLeft, 'tracking content must keep left padding').toBe('18px');
        expect(trackingMetrics.paddingRight, 'tracking content must keep right padding').toBe('18px');
        expect(trackingMetrics.tableLayout, 'tracking card must not expand past the mobile shell').toBe(
          testInfo.project.name === 'mobile' ? 'fixed' : 'auto'
        );
        expect(trackingMetrics.buttonLeftGap, 'tracking button must stay inside the left edge').toBeGreaterThanOrEqual(18);
        expect(trackingMetrics.buttonRightGap, 'tracking button must stay inside the right edge').toBeGreaterThanOrEqual(18);
      }

      if (previewCase.slug === 'followup-inactive') {
        const inactiveMetrics = await page.evaluate(() => {
          const card = document.querySelector('.bsc-email-coupon-card');
          const check = document.querySelector('.bsc-email-coupon-check img');
          const code = document.querySelector('.bsc-email-coupon-check').nextElementSibling;
          const headline = document.querySelector('.bsc-email-coupon-headline');
          const meta = document.querySelector('.bsc-email-coupon-meta');
          const badge = meta.querySelector('span');
          const graphic = document.querySelector('.bsc-email-coupon-graphic--mobile');
          const graphicImage = graphic.querySelector('img');
          const typography = (node) => {
            const computed = getComputedStyle(node);
            return {
              fontSize: computed.fontSize,
              fontWeight: computed.fontWeight,
            };
          };

          return {
            badge: typography(badge),
            borderWidth: getComputedStyle(card).borderTopWidth,
            cardWidth: Math.round(card.getBoundingClientRect().width),
            checkWidth: Math.round(check.getBoundingClientRect().width),
            code: typography(code),
            graphicBottom: Math.round(graphic.getBoundingClientRect().bottom),
            graphicDisplay: getComputedStyle(graphic).display,
            graphicWidth: Math.round(graphicImage.getBoundingClientRect().width),
            headline: typography(headline),
            meta: typography(meta),
            metaTop: Math.round(meta.getBoundingClientRect().top),
          };
        });

        expect(inactiveMetrics.borderWidth, 'inactive coupon border must stay subtle').toBe('1px');
        if (testInfo.project.name === 'mobile') {
          expect(inactiveMetrics.cardWidth, 'inactive coupon card must fit the mobile shell').toBe(342);
        }
        expect(inactiveMetrics.checkWidth, 'inactive coupon check must match other emails').toBe(53);
        expect(inactiveMetrics.graphicDisplay, 'inactive coupon heart must stay visible').not.toBe('none');
        expect(inactiveMetrics.graphicWidth, 'inactive coupon heart must use the responsive size').toBe(
          testInfo.project.name === 'mobile' ? 92 : 136
        );
        expect(inactiveMetrics.metaTop, 'inactive coupon details must sit below the heart rows').toBeGreaterThanOrEqual(
          inactiveMetrics.graphicBottom - 1
        );
        expect(inactiveMetrics.code, 'inactive coupon code must use title typography').toEqual({
          fontSize: '18px',
          fontWeight: '900',
        });
        expect(inactiveMetrics.headline, 'inactive coupon headline must use title typography').toEqual({
          fontSize: '18px',
          fontWeight: '900',
        });
        expect(inactiveMetrics.meta, 'inactive coupon details must use secondary typography').toEqual({
          fontSize: '12px',
          fontWeight: '400',
        });
        expect(inactiveMetrics.badge, 'inactive coupon badge must use secondary typography').toEqual({
          fontSize: '12px',
          fontWeight: '400',
        });
      }

      const horizontalOverflow = await page.evaluate(
        () => document.documentElement.scrollWidth - document.documentElement.clientWidth
      );
      expect(horizontalOverflow, 'email content must not overflow horizontally').toBeLessThanOrEqual(1);

      await expect(page).toHaveScreenshot(`email-${previewCase.slug}.png`, {
        animations: 'disabled',
        fullPage: true,
      });

      if (previewCase.slug === 'order-shipped') {
        const trackingButton = page.locator('.bsc-email-tracking-button');
        await trackingButton.focus();
        await expect(trackingButton, 'tracking control must accept keyboard focus').toBeFocused();
        consoleWatcher.assertNoErrors();
      }
    });
  }
});
