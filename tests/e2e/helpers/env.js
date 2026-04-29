const { loadAuthFixture } = require('./wp-fixture');

const fixture = loadAuthFixture();
const publicMode =
  process.env.PW_PUBLIC_MODE ||
  (process.env.PW_EXPECT_STOREFRONT === '1' ? 'storefront' : 'coming-soon');

const routes = {
  home: process.env.PW_ROUTE_HOME || '/',
  category:
    process.env.PW_ROUTE_CATEGORY ||
    fixture?.routes?.category ||
    '/product-category/group-skin-care/',
  product:
    process.env.PW_ROUTE_PRODUCT ||
    fixture?.routes?.product ||
    '',
  checkout: process.env.PW_ROUTE_CHECKOUT || '/checkout/',
  contact: process.env.PW_ROUTE_CONTACT || '/contact-us/',
  login: process.env.PW_ROUTE_LOGIN || '/login/',
  register: process.env.PW_ROUTE_REGISTER || '/register/',
  bubbleCreators: process.env.PW_ROUTE_BUBBLE_CREATORS || '/bubble-creators/',
  account:
    process.env.PW_ROUTE_ACCOUNT ||
    fixture?.routes?.account ||
    '/mi-cuenta/',
  accountAddresses:
    process.env.PW_ROUTE_ACCOUNT_ADDRESSES ||
    fixture?.routes?.accountAddresses ||
    '/mi-cuenta/edit-address/',
  bubblePoints:
    process.env.PW_ROUTE_BUBBLE_POINTS ||
    fixture?.routes?.bubblePoints ||
    '/mi-cuenta/bubble-points/',
  thankYou:
    process.env.PW_ROUTE_THANK_YOU ||
    (publicMode === 'storefront' ? fixture?.routes?.thankYou : '') ||
    '',
};

const auth = {
  email: process.env.PW_ACCOUNT_EMAIL || fixture?.auth?.email || '',
  password: process.env.PW_ACCOUNT_PASSWORD || fixture?.auth?.password || '',
};

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
  fixture,
  hasAccountAuth,
  hasThankYouRoute,
  publicMode,
  routes,
};
