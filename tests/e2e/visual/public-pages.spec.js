const { expect, test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const {
  ensureCheckoutReadyFromCategory,
  gotoAndStabilize,
  gotoProductGridCategory,
  openFirstProductFromCategory,
} = require('../helpers/ui');

async function stabilizeHomeHero(page) {
  await page.evaluate(() => {
    const hero = document.querySelector('.bsc__home-swiper');
    const swiper = hero?.swiper || null;
    const stableSlide = window.innerWidth < 768 ? 1 : 0;

    if (swiper) {
      if (swiper.autoplay && typeof swiper.autoplay.stop === 'function') {
        swiper.autoplay.stop();
      }

      if (typeof swiper.slideTo === 'function') {
        swiper.slideTo(stableSlide, 0);
      }
    }

    document
      .querySelectorAll('.bsc__home-swiper .slide__hero')
      .forEach((node) => node.classList.remove('fade-in'));

    const activeHero = document.querySelector(
      '.bsc__home-swiper .swiper-slide-active .slide__hero'
    );
    if (activeHero) {
      activeHero.classList.add('fade-in');
    }
  });
}

test.describe('BSC visual baseline - public pages', () => {
  test('home', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.home);
    await stabilizeHomeHero(page);

    await expect(page).toHaveScreenshot('home.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('category', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoProductGridCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ]);

    await expect(page).toHaveScreenshot('category.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('product detail', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    if (routes.product) {
      await gotoAndStabilize(page, routes.product);
    } else {
      await openFirstProductFromCategory(page, [
        routes.categoryGrid,
        routes.category,
        routes.groupCategory,
      ]);
    }

    await page.addStyleTag({
      content: `
        .bsc__product-recommendations {
          display: none !important;
        }
      `,
    });

    await expect(page).toHaveScreenshot('product-detail.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('checkout', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await ensureCheckoutReadyFromCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ], testInfo.project.name);
    await gotoAndStabilize(page, routes.checkout);

    await expect(page).toHaveScreenshot('checkout.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('contact us', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.contact);

    await expect(page).toHaveScreenshot('contact-us.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('login', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.login);

    await expect(page).toHaveScreenshot('login.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('register', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.register);

    await expect(page).toHaveScreenshot('register.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('bubble creators', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.bubbleCreators);

    await expect(page).toHaveScreenshot('bubble-creators.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });
});
