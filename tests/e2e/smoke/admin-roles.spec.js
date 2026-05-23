const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { loginToWpAdmin, openWpAdminPage } = require('../helpers/ui');

async function loginAs(page, user) {
  const loggedIn = await loginToWpAdmin(page, user.username, user.password);
  expect(loggedIn).toBeTruthy();
}

async function expectAdminPage(page, user, pageSlug, headingPattern) {
  await openWpAdminPage(
    page,
    user,
    `/wp-admin/admin.php?page=${pageSlug}`,
    async (currentPage) => {
      await expect(currentPage.locator('.wrap h1').first()).toContainText(headingPattern);
    },
    {
      maxAttempts: 3,
    }
  );
}

async function expectRedirectedAwayFrom(page, user, deniedSlug) {
  await loginAs(page, user);
  await page.goto(`/wp-admin/admin.php?page=${deniedSlug}`, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('load');

  expect(page.url()).not.toContain('/wp-login.php');

  const bodyText = await page.locator('body').innerText().catch(() => '');
  if (bodyText.includes('not allowed') || bodyText.includes('No tienes acceso')) {
    expect(bodyText).not.toContain('Informes BSC');
    expect(bodyText).not.toContain('Productos BSC');
    return;
  }

  expect(page.url()).not.toContain(`page=${deniedSlug}`);
  await expect(page.locator('.wrap.bsc-admin-orders h1').first()).toContainText('Pedidos BSC');
}

test.describe('BSC admin role access smoke', () => {
  test.beforeEach(async ({ page }, testInfo) => {
    test.skip(
      testInfo.project.name !== 'desktop',
      'Role access smoke is desktop-only because WP admin is the target surface.'
    );
    test.skip(
      !fixture?.adminRoles?.operator || !fixture?.adminRoles?.employee,
      'BSC operational role fixtures are required.'
    );

    await page.context().clearCookies();
  });

  test('bsc_operator can use orders/showroom and cannot access products/reports', async ({ page }) => {
    const user = fixture.adminRoles.operator;

    await expectAdminPage(page, user, 'bsc-dashboard', 'BSC Dashboard');
    await expectAdminPage(page, user, 'bsc-orders', 'Pedidos BSC');
    await expectAdminPage(page, user, 'bsc-showroom', 'Venta Presencial');

    await expectRedirectedAwayFrom(page, user, 'bsc-products');
    await expectRedirectedAwayFrom(page, user, 'bsc-reports');
  });

  test('bsc_employee can use products/coupons and cannot access reports/access settings', async ({ page }) => {
    const user = fixture.adminRoles.employee;

    await expectAdminPage(page, user, 'bsc-dashboard', 'BSC Dashboard');
    await expectAdminPage(page, user, 'bsc-products', 'Productos BSC');
    await expectAdminPage(page, user, 'bsc-coupons', 'Cupones BSC');

    await expectRedirectedAwayFrom(page, user, 'bsc-reports');
    await expectRedirectedAwayFrom(page, user, 'bsc-access');
  });

  test('shop manager keeps full operational BSC access', async ({ page }) => {
    test.skip(!fixture?.adminRoles?.shopManager, 'Shop manager role fixture is required.');
    const user = fixture.adminRoles.shopManager;

    await expectAdminPage(page, user, 'bsc-dashboard', 'BSC Dashboard');
    await expectAdminPage(page, user, 'bsc-orders', 'Pedidos BSC');
    await expectAdminPage(page, user, 'bsc-products', 'Productos BSC');
    await expectAdminPage(page, user, 'bsc-reports', 'Informes');
    await expectAdminPage(page, user, 'bsc-creators', 'Bubble Creators');
  });
});
