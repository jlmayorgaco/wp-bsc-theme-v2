const { expect, test } = require('@playwright/test');
const { routes } = require('../helpers/env');

test.describe('Responsive product images', () => {
  test.use({
    deviceScaleFactor: 2,
    hasTouch: true,
    isMobile: true,
    viewport: { width: 390, height: 844 },
  });

  test('cards and the product gallery provide a sharp Retina source', async ({ page }) => {
    const pageErrors = [];
    page.on('pageerror', (error) => pageErrors.push(error.message));

    await page.goto(routes.groupCategory, { waitUntil: 'domcontentloaded' });

    const cards = page.locator('.bsc__product-card');
    await expect(cards.first()).toBeVisible();

    const priorityImage = cards.first().locator('.card__image');
    await expect(priorityImage).toHaveAttribute('src', /-400x400\./);
    await expect(priorityImage).toHaveAttribute(
      'sizes',
      '(max-width: 767px) calc((100vw - 64px) / 2), 200px'
    );
    await expect(priorityImage).toHaveAttribute('loading', 'eager');
    await expect(priorityImage).toHaveAttribute('fetchpriority', 'high');

    // The second product is part of the stable local media fixture. At DPR 2 its
    // 156px rendered slot requires more than 300px and must resolve to 400px.
    const retinaImage = cards.nth(1).locator('.card__image');
    await retinaImage.scrollIntoViewIfNeeded();
    await expect(retinaImage).toBeVisible();
    await expect
      .poll(() => retinaImage.evaluate((img) => img.currentSrc), {
        message: 'The Retina card must resolve to the 400px srcset candidate.',
      })
      .toMatch(/-400x400\./);
    expect(await retinaImage.evaluate((img) => img.naturalWidth)).toBeGreaterThan(0);

    await cards.nth(1).locator('.card__images').click();
    await page.waitForLoadState('domcontentloaded');

    const mainGalleryImage = page.locator(
      '.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image img'
    ).first();
    await expect(mainGalleryImage).toBeVisible();
    await expect(mainGalleryImage).toHaveAttribute(
      'sizes',
      '(max-width: 768px) calc(100vw - 48px), (max-width: 1376px) calc((100vw - 96px) / 2), 640px'
    );
    await expect(mainGalleryImage).toHaveAttribute('loading', 'eager');
    await expect(mainGalleryImage).toHaveAttribute('fetchpriority', 'high');
    expect(await mainGalleryImage.evaluate((img) => img.naturalWidth)).toBeGreaterThan(0);

    expect(pageErrors).toEqual([]);
  });
});
