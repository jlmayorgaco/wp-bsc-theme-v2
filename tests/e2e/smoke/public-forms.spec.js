const { expect, test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const { gotoAndStabilize } = require('../helpers/ui');

async function dispatchFormSubmit(locator) {
  await locator.evaluate((form) => {
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
  });
}

test.describe('BSC public forms smoke', () => {
  test('newsletter validates email and preserves ajax success flow', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public form smoke');

    await page.route('**/wp-admin/admin-ajax.php*', async (route) => {
      const request = route.request();
      const postData = request.postData() || '';

      if (request.method() === 'POST' && postData.includes('action=bsc_newsletter_subscribe')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: { message: 'Newsletter QA OK' },
          }),
        });
        return;
      }

      await route.continue();
    });

    await gotoAndStabilize(page, routes.home);

    const form = page.locator('#bsc-newsletter-form').first();
    const email = page.locator('#bsc-newsletter-email').first();
    const error = page.locator('#bsc-newsletter-error').first();

    await expect(form).toBeVisible();
    await email.fill('correo-invalido');
    await dispatchFormSubmit(form);
    await expect(error).toContainText(/correo.*v.lido/i);

    await email.fill('qa.newsletter@bsc.local');
    await page.locator('#bsc-newsletter-submit').click();

    await expect(page.locator('#bsc-newsletter-success').first()).toContainText('Newsletter QA OK');
    await expect(form).toBeHidden();
  });

  test('contact form shows ajax validation errors before success', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public form smoke');

    await page.route('**/wp-admin/admin-ajax.php*', async (route) => {
      const request = route.request();
      const postData = request.postData() || '';

      if (request.method() === 'POST' && postData.includes('action=bsc_contact_form_submit')) {
        const params = new URLSearchParams(postData);
        const message = params.get('bsc_message') || '';
        const isValid =
          (params.get('bsc_name') || '').length >= 2 &&
          /.+@.+\..+/.test(params.get('bsc_email') || '') &&
          message.length >= 10;

        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(
            isValid
              ? { success: true, data: { message: 'Contacto QA OK' } }
              : { success: false, data: { message: 'Contacto QA invalido' } }
          ),
        });
        return;
      }

      await route.continue();
    });

    await gotoAndStabilize(page, routes.contact);

    const form = page.locator('#bsc-contact-form').first();
    const notice = page.locator('#bsc-contact-notice').first();

    await page.locator('#bsc-contact-name').fill('Q');
    await page.locator('#bsc-contact-email').fill('correo-invalido');
    await page.locator('#bsc-contact-message').fill('corto');
    await dispatchFormSubmit(form);

    await expect(notice).toContainText('Contacto QA invalido');

    await page.locator('#bsc-contact-name').fill('QA Contacto');
    await page.locator('#bsc-contact-email').fill('qa.contacto@bsc.local');
    await page.locator('#bsc-contact-message').fill('Mensaje valido para validar el flujo AJAX.');
    await page.locator('#bsc-contact-submit').click();

    await expect(notice).toContainText('Contacto QA OK');
    await expect(page.locator('#bsc-contact-name')).toHaveValue('');
  });

  test('bubble creators requires platform-specific social links', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public form smoke');

    await gotoAndStabilize(page, routes.bubbleCreators);

    const error = page.locator('#bc-form-error').first();

    await page.locator('#bc-name').fill('QA Creator');
    await page.locator('#bc-email').fill('qa.creator@bsc.local');
    await page.locator('#bc-instagram').fill('https://example.com/qa.creator');
    await page.locator('#bc-tiktok').fill('https://www.tiktok.com/@qa.creator');
    await page.locator('#bc-message').fill('Validando los links sociales obligatorios.');
    await page.locator('#bc-form-submit').click();

    await expect(error).toContainText('Instagram');

    await page.locator('#bc-instagram').fill('https://www.instagram.com/qa.creator/');
    await page.locator('#bc-tiktok').fill('https://example.com/@qa.creator');
    await page.locator('#bc-form-submit').click();

    await expect(error).toContainText('TikTok');
  });
});
