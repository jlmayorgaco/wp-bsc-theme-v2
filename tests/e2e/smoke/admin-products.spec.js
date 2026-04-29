const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { loginToWpAdmin } = require('../helpers/ui');

async function openAdminProductsPage(page, testInfo) {
  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for product admin smoke coverage.'
  );

  const loggedIn = await loginToWpAdmin(
    page,
    adminFixture.username,
    adminFixture.password
  );

  expect(loggedIn).toBeTruthy();

  await page.goto('/wp-admin/admin.php?page=bsc-products', {
    waitUntil: 'domcontentloaded',
  });
  await page.waitForLoadState('load');

  await expect(page.locator('.wrap.bsc-admin-products h1').first()).toContainText(
    'Productos BSC'
  );
}

test.describe('BSC admin products smoke', () => {
  test('products list loads and stock history modal opens', async ({ page }, testInfo) => {
    await openAdminProductsPage(page, testInfo);

    const historyButton = page.locator('.bsc-stock-history-btn').first();
    await expect(historyButton).toBeVisible();
    await historyButton.click({ force: true });

    const modal = page.locator('#bsc-stock-modal').first();
    await expect(modal).toBeVisible();
    await expect(page.locator('#bsc-stock-modal-title').first()).toContainText('Historial');
    await expect(page.locator('#bsc-stock-modal-body').first()).not.toBeEmpty();

    await page.locator('#bsc-stock-modal-close').click({ force: true });
    await expect(modal).toBeHidden();
  });

  test('product edit page loads media and category controls', async ({ page }, testInfo) => {
    await openAdminProductsPage(page, testInfo);

    const editLink = page.locator('a.button.button-small', { hasText: 'Editar' }).first();
    await expect(editLink).toBeVisible();
    const editHref = await editLink.getAttribute('href');
    expect(editHref).toBeTruthy();
    await page.goto(editHref, { waitUntil: 'domcontentloaded' });

    await page.waitForLoadState('load');

    await expect(page.locator('.wrap.bsc-admin-product-edit h1').first()).toContainText('Editar Producto');
    await expect(page.locator('#bsc-select-main-image').first()).toBeVisible();
    await expect(page.locator('#bsc-select-gallery').first()).toBeVisible();
    await expect(page.locator('#bsc-root-tabs .button').first()).toBeVisible();
    await expect(page.locator('#bsc-cat-branches').first()).not.toBeEmpty();
  });
});
