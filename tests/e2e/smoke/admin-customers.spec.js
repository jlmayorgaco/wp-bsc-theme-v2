const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openCustomersAdmin(page, testInfo) {
  test.skip(
    testInfo.project.name === 'tablet',
    'Customer admin smoke runs at desktop and mobile widths.'
  );

  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for Customers admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-customers',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-customers h1').first()).toContainText(
        'Directorio de clientes'
      );
    }
  );
}

test.describe('BSC Customers admin smoke', () => {
  test('directory filters and profile validation work without losing input', async ({ page }, testInfo) => {
    await openCustomersAdmin(page, testInfo);

    const search = page.getByLabel('Buscar cliente');
    await expect(search).toBeVisible();
    await expect(page.getByLabel('Rol')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Cuentas encontradas' })).toBeVisible();

    await search.fill('__bsc_customer_that_does_not_exist__');
    await page.getByRole('button', { name: 'Buscar' }).click();
    await expect(page).toHaveURL(/customer_query=__bsc_customer_that_does_not_exist__/);
    await expect(page.getByText('No encontramos usuarios con esos filtros.')).toBeVisible();

    await page.getByRole('link', { name: 'Limpiar' }).click();
    const editLink = page.getByRole('link', { name: 'Ver y editar' }).first();
    await expect(editLink).toBeVisible();
    await editLink.click();

    await expect(page.getByRole('heading', { name: 'Datos de cuenta' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Perfil de piel BSC' })).toBeVisible();
    await expect(page.getByText(/Programada cada día a las 2:15 a\. m\./)).toBeVisible();

    await page.getByLabel('Nombres').fill('Valor temporal QA');
    await page.getByLabel('Correo electrónico').fill('correo-invalido');
    await page.locator('#bsc-customer-editor-form').evaluate((form) => {
      form.noValidate = true;
    });
    await page.getByRole('button', { name: 'Guardar datos del cliente' }).click();

    await expect(page.getByRole('alert')).toContainText('Revisa el correo electrónico');
    await expect(page.getByLabel('Nombres')).toHaveValue('Valor temporal QA');
    await expect(page.getByLabel('Correo electrónico')).toHaveAttribute('aria-invalid', 'true');
  });

  test('birthday changes persist for the disposable QA customer', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'desktop', 'Persistence coverage runs once on desktop.');
    test.skip(!fixture?.auth?.email, 'The disposable QA customer fixture is required.');

    await openCustomersAdmin(page, testInfo);
    await page.getByLabel('Buscar cliente').fill(fixture.auth.email);
    await page.getByRole('button', { name: 'Buscar' }).click();

    const editLink = page.getByRole('link', { name: 'Ver y editar' }).first();
    await expect(editLink).toBeVisible();
    await editLink.click();

    const birthdayInput = page.getByLabel('Cumpleaños');
    const originalBirthday = await birthdayInput.inputValue();
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
    const day = String(tomorrow.getDate()).padStart(2, '0');
    let testBirthday = `2000-${month}-${day}`;

    if (testBirthday === originalBirthday) {
      testBirthday = `1996-${month}-${day}`;
    }

    try {
      await birthdayInput.fill(testBirthday);
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        page.getByRole('button', { name: 'Guardar datos del cliente' }).click(),
      ]);

      await expect(page.getByRole('status')).toContainText('actualizados correctamente');
      await expect(page.getByLabel('Cumpleaños')).toHaveValue(testBirthday);
    } finally {
      const currentBirthday = page.getByLabel('Cumpleaños');
      if ((await currentBirthday.count()) > 0) {
        await currentBirthday.fill(originalBirthday);
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          page.getByRole('button', { name: 'Guardar datos del cliente' }).click(),
        ]);
      }
    }
  });
});
