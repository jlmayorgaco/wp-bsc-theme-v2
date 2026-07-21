const { expect, test } = require('@playwright/test');
const { coupons, expectsStorefront, routes } = require('../helpers/env');
const {
  applyCheckoutCouponDirect,
  ensureCheckoutReadyFromCategory,
  gotoAndStabilize,
  gotoProductGridCategory,
  interactWithPrimaryCardAddToCart,
  openFirstProductFromCategory,
  removeCheckoutCouponDirect,
  selectCheckoutBillingDestination,
} = require('../helpers/ui');

test.describe('BSC smoke', () => {
  test('home loads approved shell', async ({ page }, testInfo) => {
    await gotoAndStabilize(page, routes.home);

    await expect(page.locator('body')).toBeVisible();

    if (expectsStorefront()) {
      if (testInfo.project.name === 'desktop') {
        await expect(page.locator('.bsc__header--desktop').first()).toBeVisible();
      } else {
        await expect(page.locator('.bsc__header--mobile').first()).toBeVisible();
      }
      return;
    }

    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('parking mode allows WordPress and custom auth routes', async ({ page }) => {
    test.skip(expectsStorefront(), 'Coming-soon mode is required for parking auth smoke');

    const authRoutes = [
      { path: routes.login, selector: '#loginform' },
      { path: routes.register, selector: '#registerform' },
      { path: '/wp-login.php', selector: '#loginform' },
      { path: '/wp-login.php?action=register', selector: '#login' },
    ];

    for (const { path, selector } of authRoutes) {
      await gotoAndStabilize(page, path, { primePage: false });
      await expect(page.locator('.coming-soon-container')).toHaveCount(0);
      await expect(page.locator(selector).first()).toBeVisible();
    }
  });

  test('header shell renders for the active viewport', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for header smoke');

    await gotoAndStabilize(page, routes.home);

    if (testInfo.project.name === 'desktop') {
      await expect(page.locator('.bsc__header--desktop .header__image').first()).toBeVisible();
      await expect(page.locator('.bsc__header--desktop .btn-search-toggle').first()).toBeVisible();
      await expect(page.locator('.bsc__header--desktop .menu__icon.icon--profile').first()).toBeVisible();
      await expect(page.locator('.bsc__menu-nav-image:has(img[alt="SKIN CARE"])')).toHaveAttribute('href', /\/product-category\/group-skin-care\/$/);
      await expect(page.locator('.bsc__menu-nav-image:has(img[alt="HAIR CARE"])')).toHaveAttribute('href', /\/product-category\/group-hair-care\/$/);
      await expect(page.locator('.bsc__menu-nav-image:has(img[alt="MAKE UP"])')).toHaveAttribute('href', /\/product-category\/group-make-up\/$/);
      return;
    }

    await expect(page.locator('.bsc__header--mobile').first()).toBeVisible();
    await expect(page.locator('#mobileMenuToggle').first()).toBeVisible();
    await expect(page.locator('#mobile-search-btn').first()).toBeVisible();
    await expect(page.locator('#profile-button-mobile').first()).toBeVisible();
  });

  test('header search hides suggestion meta labels', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for header search smoke');

    await page.route('**/wp-admin/admin-ajax.php*', async (route) => {
      const request = route.request();

      if (request.method() === 'GET' && request.url().includes('action=bsc_search_products')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: {
              products: [],
              suggestions: [{ label: 'TOCOBO', meta: 'Marca', url: '/marca/tocobo/' }],
            },
          }),
        });
        return;
      }

      await route.continue();
    });

    await gotoAndStabilize(page, routes.home);

    const isDesktop = testInfo.project.name === 'desktop';
    const toggle = page.locator(isDesktop ? '.bsc__header--desktop .btn-search-toggle' : '#mobile-search-btn').first();
    const input = page.locator(
      isDesktop
        ? '.bsc__header--desktop .header-search-input'
        : '.bsc-mobile-search-panel .header-search-input'
    ).first();
    const results = page.locator(
      isDesktop
        ? '.bsc__header--desktop .search-results'
        : '.bsc-mobile-search-panel .search-results'
    ).first();

    await toggle.click();
    await input.fill('tocobo');

    await expect(results.locator('.search-result-item--suggestion')).toHaveCount(1);
    await expect(results.locator('.search-result-item--suggestion .search-result-name')).toHaveText('TOCOBO');
    await expect(results.locator('.search-result-item--suggestion .search-result-meta')).toBeHidden();
  });

  test('header product results show name and price without brand', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for header search smoke');

    await page.route('**/wp-admin/admin-ajax.php*', async (route) => {
      const request = route.request();

      if (request.method() === 'GET' && request.url().includes('action=bsc_search_products')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: {
              products: [{
                name: 'Protector solar',
                brand: 'TOCOBO',
                price: '$85.000',
                permalink: '/producto/protector-solar/',
              }],
              suggestions: [],
            },
          }),
        });
        return;
      }

      await route.continue();
    });

    await gotoAndStabilize(page, routes.home);

    const isDesktop = testInfo.project.name === 'desktop';
    const toggle = page.locator(isDesktop ? '.bsc__header--desktop .btn-search-toggle' : '#mobile-search-btn').first();
    const input = page.locator(
      isDesktop
        ? '.bsc__header--desktop .header-search-input'
        : '.bsc-mobile-search-panel .header-search-input'
    ).first();
    const results = page.locator(
      isDesktop
        ? '.bsc__header--desktop .search-results'
        : '.bsc-mobile-search-panel .search-results'
    ).first();

    await toggle.click();
    await input.fill('tocobo');

    const productResult = results.locator('.search-result-item:not(.search-result-item--all)').first();
    await expect(productResult.locator('.search-result-name')).toHaveText('Protector solar');
    await expect(productResult.locator('.search-result-price')).toHaveText('$85.000');
    await expect(productResult.locator('.search-result-brand')).toHaveCount(0);
    await expect(productResult).not.toContainText('TOCOBO');
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
    if (expectsStorefront()) {
      await gotoProductGridCategory(page, [
        routes.categoryGrid,
        routes.category,
        routes.groupCategory,
      ]);
      await expect(page.locator('.bsc__product-card').first()).toBeVisible();
      return;
    }

    await gotoAndStabilize(page, routes.groupCategory);
    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('category price ranges sync their visible outputs', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for category filter smoke');

    await gotoProductGridCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ]);

    const minInput = page.locator('#min_price').first();
    const maxInput = page.locator('#max_price').first();
    const minOutput = page.locator('#min_price_output').first();
    const maxOutput = page.locator('#max_price_output').first();

    await expect(minInput).toBeVisible();
    await expect(maxInput).toBeVisible();

    await minInput.fill('45000');
    await minInput.dispatchEvent('input');
    await expect(minOutput).toHaveText('45000');

    await maxInput.fill('120000');
    await maxInput.dispatchEvent('input');
    await expect(maxOutput).toHaveText('120000');
  });

  test('first PDP opens from category', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for PDP smoke');

    await openFirstProductFromCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ]);

    await expect(
      page.locator(
        '.woocommerce-product-gallery, .product, .bsc__product-gallery'
      ).first()
    ).toBeVisible();
  });

  test('product card add-to-cart toggles quantity controls and restores CTA at zero', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for cart smoke');

    await gotoProductGridCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ]);
    const { addButton, controls } = await interactWithPrimaryCardAddToCart(page, testInfo.project.name);
    const minusButton = controls.locator('.bsc__qty-minus').first();

    await minusButton.click();

    await expect(controls).toBeHidden();
    await expect(addButton).toBeVisible();
    await expect(page.locator('.footer__cart-count').first()).toHaveText('0');
  });

  test('checkout page renders', async ({ page }, testInfo) => {
    if (expectsStorefront()) {
      await ensureCheckoutReadyFromCategory(page, [
        routes.categoryGrid,
        routes.category,
        routes.groupCategory,
      ], testInfo.project.name);
    }

    await gotoAndStabilize(page, routes.checkout);

    if (expectsStorefront()) {
      await expect(
        page.locator('form.checkout, .bsc__checkout, .woocommerce-checkout').first()
      ).toBeVisible();
      return;
    }

    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('checkout coupon toggle opens the coupon form', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for checkout coupon smoke');

    await ensureCheckoutReadyFromCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ], testInfo.project.name);
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

  test('checkout coupon apply/remove keeps the review summary in sync', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for checkout coupon smoke');
    test.skip(!coupons.fixed, 'Checkout coupon fixture is required.');

    await ensureCheckoutReadyFromCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ], testInfo.project.name);
    await gotoAndStabilize(page, routes.checkout);

    const summaryTotal = page.locator('#review-summary__total').first();
    const initialTotal = ((await summaryTotal.textContent()) || '').trim();

    await applyCheckoutCouponDirect(page, coupons.fixed);
    await expect.poll(async () => ((await summaryTotal.textContent()) || '').trim()).not.toBe(initialTotal);

    await removeCheckoutCouponDirect(page, coupons.fixed);

    await expect.poll(async () => ((await summaryTotal.textContent()) || '').trim()).toBe(initialTotal);
  });

  test('checkout shipping summary reacts to destination and free-shipping coupons', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for checkout shipping smoke');
    test.skip(!coupons.freeShipping, 'Free-shipping coupon fixture is required.');

    await ensureCheckoutReadyFromCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ], testInfo.project.name);
    await gotoAndStabilize(page, routes.checkout);

    const shippingValue = page.locator('#review-summary__shipping').first();
    const shippingRow = shippingValue.locator('xpath=ancestor::div[contains(@class,"review-summary__row")][1]');
    const pendingNotice = page.locator('#bsc-shipping-pending-msg').first();

    await expect(pendingNotice).toContainText('Selecciona tu departamento para ver el valor del envio.');
    await expect(shippingRow).toBeHidden();

    await selectCheckoutBillingDestination(page);

    await expect(pendingNotice).toHaveCount(0);
    await expect(shippingRow).toBeVisible();
    await expect.poll(async () => ((await shippingValue.textContent()) || '').trim()).not.toBe('');
    await expect.poll(async () => ((await shippingValue.textContent()) || '').trim()).not.toContain('Gratis');

    await applyCheckoutCouponDirect(page, coupons.freeShipping);

    await expect.poll(async () => ((await shippingValue.textContent()) || '').trim()).toContain('Gratis');

    await removeCheckoutCouponDirect(page, coupons.freeShipping);
    await expect.poll(async () => ((await shippingValue.textContent()) || '').trim()).not.toContain('Gratis');
  });

  test('shop landing renders grouped category cards', async ({ page }) => {
    await gotoAndStabilize(page, routes.shop);

    if (expectsStorefront()) {
      await expect(page.locator('.bsc-kb-grid--shop').first()).toBeVisible();
      await expect(page.locator('.bsc-kb-card').first()).toBeVisible();
      return;
    }

    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('account route responds', async ({ page }) => {
    await gotoAndStabilize(page, routes.account);

    await expect(page.locator('body')).toBeVisible();
    await expect(page).toHaveURL(/mi-cuenta|my-account|login/i);
  });

  test('footer account heading links directly to orders', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for footer navigation smoke');

    await gotoAndStabilize(page, routes.home);

    const footerAccountLink = page.locator('.footer__heading a', { hasText: 'Mi cuenta' }).first();
    await expect(footerAccountLink).toBeVisible();

    const href = await footerAccountLink.getAttribute('href');
    const actualPath = new URL(href, page.url()).pathname;

    expect(actualPath).toMatch(/\/(?:my-account|mi-cuenta)\/orders\/$/);
  });

  test('contact page exposes dynamic contact CTAs and ajax feedback shell', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for contact smoke');

    await page.route('**/wp-admin/admin-ajax.php*', async (route) => {
      const request = route.request();
      const postData = request.postData() || '';

      if (request.method() === 'POST' && postData.includes('action=bsc_contact_form_submit')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: { message: 'Contacto QA OK' },
          }),
        });
        return;
      }

      await route.continue();
    });

    await gotoAndStabilize(page, routes.contact);

    const whatsappLink = page.locator('.bsc__contact-item[href*="api.whatsapp.com"]').first();
    const shopButton = page.locator('.bsc__contact-btn').first();
    const notice = page.locator('#bsc-contact-notice').first();

    await expect(whatsappLink).toBeVisible();
    await expect(shopButton).toBeVisible();

    await page.locator('#bsc-contact-name').fill('QA Visual');
    await page.locator('#bsc-contact-email').fill('qa.visual@bsc.local');
    await page.locator('#bsc-contact-message').fill('Mensaje de prueba para validar el flujo de contacto.');
    await page.locator('#bsc-contact-submit').click();

    await expect(notice).toContainText('Contacto QA OK');
    await expect(page.locator('#bsc-contact-name')).toHaveValue('');
  });

  test('bubble creators page preserves ajax success flow', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for Bubble Creators smoke');

    await page.route('**/wp-admin/admin-ajax.php*', async (route) => {
      const request = route.request();
      const postData = request.postData() || '';

      if (request.method() === 'POST' && postData.includes('action=bsc_creator_apply')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: { message: 'Creator QA OK' },
          }),
        });
        return;
      }

      await route.continue();
    });

    await gotoAndStabilize(page, routes.bubbleCreators);

    await expect(page.locator('#bc-creator-form').first()).toBeVisible();
    await expect(page.locator('#bc-name')).toHaveAttribute('required', '');
    await expect(page.locator('#bc-email')).toHaveAttribute('required', '');
    await expect(page.locator('#bc-instagram')).toHaveAttribute('required', '');
    await expect(page.locator('#bc-instagram')).toHaveAttribute('type', 'url');
    await expect(page.locator('#bc-tiktok')).toHaveAttribute('required', '');
    await expect(page.locator('#bc-tiktok')).toHaveAttribute('type', 'url');
    await expect(page.locator('#bc-message')).toHaveAttribute('required', '');

    await page.locator('#bc-name').fill('QA Creator');
    await page.locator('#bc-email').fill('qa.creator@bsc.local');
    await page.locator('#bc-instagram').fill('https://www.instagram.com/qa.creator/');
    await page.locator('#bc-tiktok').fill('https://www.tiktok.com/@qa.creator');
    await page.locator('#bc-message').fill('Validando el flujo AJAX del formulario Bubble Creators.');
    await page.locator('#bc-form-submit').click();

    await expect(page.locator('#bc-form-success').first()).toContainText('Creator QA OK');
    await expect(page.locator('#bc-creator-form').first()).toBeHidden();
  });
});
