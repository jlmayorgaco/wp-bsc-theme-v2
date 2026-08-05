const { expect, test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const { gotoAndStabilize } = require('../helpers/ui');

test.describe('BSC visual baseline - header and mobile nav', () => {
  test('desktop header shell', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for header visual baselines');
    test.skip(testInfo.project.name !== 'desktop', 'Desktop-only visual baseline');

    await gotoAndStabilize(page, routes.home);

    const desktopHeader = page.locator('.bsc__header--desktop .header__container').first();
    const searchButton = desktopHeader.locator('.icon--search > button');
    const profileLink = desktopHeader.locator('.icon--profile > a');

    await expect(desktopHeader).toBeVisible();
    await expect(searchButton).toHaveCSS('color', 'rgb(51, 51, 51)');
    await expect(profileLink).toHaveCSS('color', 'rgb(51, 51, 51)');
    await expect(desktopHeader).toHaveScreenshot('header-desktop-shell.png', {
      animations: 'disabled',
      maxDiffPixelRatio: 0.001,
    });
  });

  test('mobile header shell', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for header visual baselines');
    test.skip(testInfo.project.name === 'desktop', 'Mobile/tablet-only visual baseline');

    await gotoAndStabilize(page, routes.home);

    const mobileHeader = page.locator('.bsc__header--mobile .header-mobile__nav').first();

    await expect(mobileHeader).toBeVisible();
    await expect(mobileHeader).toHaveScreenshot('header-mobile-shell.png', {
      animations: 'disabled',
    });
  });

  test('mobile sidebar open state', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for header visual baselines');
    test.skip(testInfo.project.name === 'desktop', 'Mobile/tablet-only visual baseline');

    await gotoAndStabilize(page, routes.home);

    const menuToggle = page.locator('#mobileMenuToggle').first();
    const sidebar = page.locator('#mobileSidebar').first();

    await expect(menuToggle).toHaveAttribute('aria-expanded', 'false');
    await menuToggle.click();
    await expect(menuToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(sidebar).toHaveClass(/is-open/);
    await expect(sidebar).toHaveScreenshot('mobile-sidebar-open.png', {
      animations: 'disabled',
      maxDiffPixels: 20,
    });
  });
});
