const routes = {
  home: process.env.PW_ROUTE_HOME || '/',
  category:
    process.env.PW_ROUTE_CATEGORY || '/product-category/group-skin-care/',
  checkout: process.env.PW_ROUTE_CHECKOUT || '/checkout/',
  account: process.env.PW_ROUTE_ACCOUNT || '/mi-cuenta/',
  bubblePoints: process.env.PW_ROUTE_BUBBLE_POINTS || '/bubble-points/',
  thankYou: process.env.PW_ROUTE_THANK_YOU || '',
};

const auth = {
  email: process.env.PW_ACCOUNT_EMAIL || '',
  password: process.env.PW_ACCOUNT_PASSWORD || '',
};

const publicMode =
  process.env.PW_PUBLIC_MODE ||
  (process.env.PW_EXPECT_STOREFRONT === '1' ? 'storefront' : 'coming-soon');

function hasAccountAuth() {
  return Boolean(auth.email && auth.password);
}

function hasThankYouRoute() {
  return Boolean(routes.thankYou);
}

function expectsStorefront() {
  return publicMode === 'storefront';
}

module.exports = {
  auth,
  expectsStorefront,
  hasAccountAuth,
  hasThankYouRoute,
  publicMode,
  routes,
};
