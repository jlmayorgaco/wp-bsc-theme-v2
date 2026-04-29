const { expect, test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const {
  gotoAndStabilize,
  openFirstProductFromCategory,
} = require('../helpers/ui');

test.describe('BSC smoke', () => {
  test('home loads approved shell', async ({ page }) => {
    await gotoAndStabilize(page, routes.home);

    await expect(page.locator('body')).toBeVisible();

    if (expectsStorefront()) {
      await expect(
        page.locator('header, .bsc__header, .site-header').first()
      ).toBeVisible();
      return;
    }

    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('header shell renders for the active viewport', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for header smoke');

    await gotoAndStabilize(page, routes.home);

    if (testInfo.project.name === 'desktop') {
      await expect(page.locator('.bsc__header--desktop .header__image').first()).toBeVisible();
      await expect(page.locator('.bsc__header--desktop .btn-search-toggle').first()).toBeVisible();
      await expect(page.locator('.bsc__header--desktop .menu__icon.icon--profile').first()).toBeVisible();
      return;
    }

    await expect(page.locator('.bsc__header--mobile').first()).toBeVisible();
    await expect(page.locator('#mobileMenuToggle').first()).toBeVisible();
    await expect(page.locator('#mobile-search-btn').first()).toBeVisible();
    await expect(page.locator('#profile-button-mobile').first()).toBeVisible();
  });

  test('mobile menu toggles open and closed', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for mobile-nav smoke');
    test.skip(testInfo.project.name === 'desktop', 'Mobile/tablet-only smoke');

    await gotoAndStabilize(page, routes.home);

    const menuToggle = page.locator('#mobileMenuToggle').first();
    const mobileSidebar = page.locator('#mobileSidebar').first();
    const body = page.locator('body');

    await expect(menuToggle).toHaveAttribute('aria-expanded', 'false');

    await menuToggle.click();
    await expect(menuToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(mobileSidebar).toHaveClass(/is-open/);
    await expect(body).toHaveClass(/mobile-menu-open/);

    await menuToggle.click();
    await expect(menuToggle).toHaveAttribute('aria-expanded', 'false');
    await expect(mobileSidebar).not.toHaveClass(/is-open/);
  });

  test('category page renders product cards', async ({ page }) => {
    await gotoAndStabilize(page, routes.category);

    if (expectsStorefront()) {
      await expect(page.locator('.bsc__product-card').first()).toBeVisible();
      return;
    }

    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('first PDP opens from category', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for PDP smoke');

    await openFirstProductFromCategory(page, routes.category);

    await expect(
      page.locator(
        '.woocommerce-product-gallery, .product, .bsc__product-gallery'
      ).first()
    ).toBeVisible();
  });

  test('checkout page renders', async ({ page }) => {
    await gotoAndStabilize(page, routes.checkout);

    if (expectsStorefront()) {
      await expect(
        page.locator('form.checkout, .bsc__checkout, .woocommerce-checkout').first()
      ).toBeVisible();
      return;
    }

    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('checkout coupon toggle opens the coupon form', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for checkout coupon smoke');

    await gotoAndStabilize(page, routes.checkout);

    const $toggle = page.locator('.showcoupon').first();
    const $form = page.locator('#woocommerce-checkout-form-coupon').first();

    if (!(await $toggle.count()) || !(await $form.count())) {
      test.skip(true, 'Default Woo coupon toggle is not present in the current checkout variant.');
    }

    await expect($toggle).toBeVisible();
    await expect($form).toBeHidden();

    await $toggle.click();
    await expect($form).toBeVisible();
  });

  test('account route responds', async ({ page }) => {
    await gotoAndStabilize(page, routes.account);

    await expect(page.locator('body')).toBeVisible();
    await expect(page).toHaveURL(/mi-cuenta|my-account|login/i);
  });
});
