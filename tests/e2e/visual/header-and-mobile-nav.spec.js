const { expect, test } = require('@playwright/test');
const { expectsStorefront, fixture, routes } = require('../helpers/env');
const { gotoAndStabilize, loginToWpAdmin } = require('../helpers/ui');

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

    await gotoAndStabilize(page, routes.home, {
      primePage: false,
      waitForImages: false,
    });

    const menuToggle = page.locator('#mobileMenuToggle').first();
    const sidebar = page.locator('#mobileSidebar').first();

    await expect(menuToggle).toHaveAttribute('aria-expanded', 'false');
    await menuToggle.click();
    await expect(menuToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(sidebar).toHaveClass(/is-open/);

    const skinCareMenu = sidebar
      .locator('details.mobile-nav--catalog')
      .filter({ hasText: 'SKIN CARE' })
      .first();
    await skinCareMenu.locator('summary').first().click();
    await expect(skinCareMenu).toHaveAttribute('open', '');
    await expect(skinCareMenu.locator('.mobile-nav__image').first()).toBeVisible();

    await expect(sidebar).toHaveScreenshot('mobile-sidebar-open.png', {
      animations: 'disabled',
      maxDiffPixels: 20,
    });
  });

  test('mobile search stays below the header with the WordPress toolbar', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for header visual baselines');
    test.skip(testInfo.project.name === 'desktop', 'Mobile/tablet-only header interaction');

    const adminFixture =
      fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

    test.skip(
      !adminFixture?.username || !adminFixture?.password,
      'The admin fixture is required for the WordPress toolbar header check.'
    );

    const loggedIn = await loginToWpAdmin(
      page,
      adminFixture.username,
      adminFixture.password
    );
    expect(loggedIn).toBeTruthy();

    await gotoAndStabilize(page, routes.home, {
      primePage: false,
      waitForImages: false,
    });

    const adminBar = page.locator('#wpadminbar').first();
    const mobileHeader = page.locator('#mobileHeader').first();
    const searchToggle = page.locator('#mobile-search-btn').first();
    const searchPanel = page.locator('.bsc-mobile-search-panel').first();
    const searchInput = searchPanel.locator('.header-search-input').first();

    await expect(adminBar).toBeVisible();
    await expect(mobileHeader.locator('#mobileMenuToggle')).toBeVisible();
    await expect(mobileHeader.locator('.header-mobile__logo')).toBeVisible();

    await searchToggle.click();

    await expect(searchToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(searchPanel).toBeVisible();
    await expect(searchInput).toBeFocused();

    const positions = await page.evaluate(() => {
      const toolbar = document.querySelector('#wpadminbar');
      const header = document.querySelector('#mobileHeader');
      const panel = document.querySelector('.bsc-mobile-search-panel');

      return {
        toolbarBottom: toolbar?.getBoundingClientRect().bottom || 0,
        headerTop: header?.getBoundingClientRect().top || 0,
        headerBottom: header?.getBoundingClientRect().bottom || 0,
        panelTop: panel?.getBoundingClientRect().top || 0,
      };
    });

    expect(positions.headerTop).toBeGreaterThanOrEqual(positions.toolbarBottom - 1);
    expect(Math.abs(positions.panelTop - positions.headerBottom)).toBeLessThanOrEqual(1);
  });
});
