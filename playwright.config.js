const { defineConfig, devices } = require('@playwright/test');

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL ||
  process.env.BASE_URL ||
  'http://bsc.local';
const includeWebKitGate = process.env.PW_ENABLE_WEBKIT_GATE === '1';
const webKitGatePattern = /@webkit/;

const baseProjects = [
  {
    name: 'mobile',
    grepInvert: includeWebKitGate ? webKitGatePattern : undefined,
    use: {
      browserName: 'chromium',
      viewport: { width: 390, height: 844 },
      isMobile: true,
      hasTouch: true,
    },
  },
  {
    name: 'tablet',
    grepInvert: includeWebKitGate ? webKitGatePattern : undefined,
    use: {
      browserName: 'chromium',
      viewport: { width: 768, height: 1024 },
      isMobile: true,
      hasTouch: true,
    },
  },
  {
    name: 'desktop',
    grepInvert: includeWebKitGate ? webKitGatePattern : undefined,
    use: {
      browserName: 'chromium',
      viewport: { width: 1440, height: 900 },
    },
  },
];

const webKitProjects = [
  {
    name: 'webkit-iphone',
    grep: webKitGatePattern,
    use: {
      ...devices['iPhone 13'],
      browserName: 'webkit',
      locale: 'es-CO',
      timezoneId: 'America/Bogota',
    },
  },
  {
    name: 'webkit-desktop',
    grep: webKitGatePattern,
    use: {
      browserName: 'webkit',
      viewport: { width: 1440, height: 900 },
    },
  },
];

module.exports = defineConfig({
  testDir: './tests/e2e',
  timeout: 45_000,
  expect: {
    timeout: 10_000,
  },
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 1,
  workers: process.env.CI ? 1 : 3,
  reporter: [['list'], ['html', { open: 'never' }]],
  outputDir: 'test-results',
  use: {
    baseURL,
    headless: true,
    locale: 'es-CO',
    timezoneId: 'America/Bogota',
    colorScheme: 'light',
    reducedMotion: 'reduce',
    ignoreHTTPSErrors: true,
    actionTimeout: 15_000,
    navigationTimeout: 45_000,
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'off',
  },
  projects: includeWebKitGate ? [...baseProjects, ...webKitProjects] : baseProjects,
});
