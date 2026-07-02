const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openAdminProductsPage(page, testInfo) {
  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for product admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-products',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-products h1').first()).toContainText(
        'Productos BSC'
      );
    }
  );

  return adminFixture;
}

test.describe('BSC admin products smoke', () => {
  test('products list loads and stock history modal opens', async ({ page }, testInfo) => {
    await openAdminProductsPage(page, testInfo);

    await expect(page.locator('.bsc-price-input[data-field="regular_price"]').first()).toBeVisible();
    await expect(page.locator('.bsc-price-input[data-field="sale_price"]')).toHaveCount(0);

    const discountToggle = page.locator('#bsc-discount-mode-toggle').first();
    await expect(discountToggle).toBeVisible();
    await discountToggle.click();
    await expect(page.locator('#bsc-discount-controls').first()).toBeVisible();
    await expect(page.locator('#bsc-discount-percent').first()).toBeEnabled();
    const discountCheckbox = page.locator('.bsc-product-discount-checkbox').first();
    await expect(discountCheckbox).toBeEnabled();
    await discountCheckbox.check();
    await page.locator('#bsc-discount-percent').fill('0');
    await expect(page.locator('#bsc-apply-discount').first()).toBeEnabled();

    const firstMoreActions = page.locator('.bsc-admin-products__more-actions').first();
    await expect(firstMoreActions.locator('summary')).toBeVisible();
    await firstMoreActions.locator('summary').click();

    const historyButton = page.locator('.bsc-stock-history-btn').first();
    await expect(historyButton).toBeVisible();
    await expect(page.locator('.bsc-product-delete-btn').first()).toBeVisible();
    await historyButton.click({ force: true });

    const modal = page.locator('#bsc-stock-modal').first();
    await expect(modal).toBeVisible();
    await expect(page.locator('#bsc-stock-modal-title').first()).toContainText('Historial');
    await expect(page.locator('#bsc-stock-modal-body').first()).not.toBeEmpty();

    await page.locator('#bsc-stock-modal-close').click({ force: true });
    await expect(modal).toBeHidden();
  });

  test('product edit page loads media and category controls', async ({ page }, testInfo) => {
    const adminFixture = await openAdminProductsPage(page, testInfo);

    const editLink = page.locator('a.button.button-small', { hasText: 'Editar' }).first();
    await expect(editLink).toBeVisible();
    const editHref = await editLink.getAttribute('href');
    expect(editHref).toBeTruthy();
    await openWpAdminPage(page, adminFixture, editHref, async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-product-edit h1').first()).toContainText(
        'Editar Producto'
      );
      await expect(currentPage.locator('#bsc-select-main-image').first()).toBeVisible();
      await expect(currentPage.locator('input[name="_regular_price"]').first()).toBeVisible();
      await expect(currentPage.locator('input[name="_discount_percent"]').first()).toBeVisible();
      await expect(currentPage.locator('input[name="_sale_price"]')).toHaveCount(0);
      await expect(currentPage.locator('select[name="post_status"]').first()).toBeVisible();
      await expect(currentPage.locator('select[name="post_status"] option[value="hidden"]')).toHaveText('Oculto');
      await expect(currentPage.locator('select[name="post_status"] option[value="archive"]')).toHaveText('Archivado');
      await expect(currentPage.locator('select[name="post_status"] option[value="delete"]')).toHaveText('Borrar');
      await expect(currentPage.locator('#bsc-select-gallery').first()).toBeVisible();
      await expect(currentPage.locator('#bsc-root-tabs .button').first()).toBeVisible();
      await expect(currentPage.locator('#bsc-cat-branches').first()).not.toBeEmpty();
    });
  });
});
