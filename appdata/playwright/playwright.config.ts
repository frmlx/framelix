import { devices, PlaywrightTestConfig } from '@playwright/test'

/**
 * See https://playwright.dev/docs/test-configuration.
 */
const config: PlaywrightTestConfig = {

  globalSetup: require.resolve('./includes/global-setup'),
  testDir: './tests',

  /* Maximum time one test can run for. */
  timeout: 30 * 1000,

  expect: {

    /**
     * Maximum time expect() should wait for the condition to be met.
     * For example in `await expect(locator).toHaveText();`
     */
    timeout: 5000
  },

  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,

  retries: 1,

  /* Opt out of parallel tests because our testserver is not so beefy. */
  workers: 1,

  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['./includes/framelix-reporter.ts'],
    ['html', { open: 'never', outputFolder: '/framelix/userdata/playwright/results' }],
    ['json', { outputFile: '/framelix/userdata/playwright/results/results.json' }]
  ],

  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {

    // fixed viewport for screenshot comparision
    viewport: { width: 1280, height: 720 },

    // ignore any ssl errors as we have self signed certs on testserver and local
    ignoreHTTPSErrors: true,

    // Maximum time each action such as `click()` can take. Defaults to 0 (no limit).
    actionTimeout: 0,

    // Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer
    trace: 'retain-on-failure',
    video: 'retain-on-failure',

    // use the storage state for each test as it contains SSO tokens
    // is generated in includes/global-setup.ts
    // storageState: '../../userdata/playwright/browserStorageState.json',

    // headless is default
    headless: true
  },

  // basically this are browser specs
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
      },
    },
    // firefox hang forever in docker runners as of dez 2022
    // should later revalidate this issue if it has resolved
    // {
    //   name: 'firefox',
    //   use: {
    //     ...devices['Desktop Firefox'],
    //   },
    // },

    // {
    //  name: 'webkit',
    //  use: {
    //    ...devices['Desktop Safari'],
    //  },
    // },

    /* Test against mobile viewports. */
    // {
    //   name: 'Mobile Chrome',
    //   use: {
    //     ...devices['Pixel 5'],
    //   },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: {
    //     ...devices['iPhone 12'],
    //   },
    // },

    /* Test against branded browsers. */
    // {
    //   name: 'Microsoft Edge',
    //   use: {
    //     channel: 'msedge',
    //   },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: {
    //     channel: 'chrome',
    //   },
    // },
  ],

  /* Folder for test artifacts such as screenshots, videos, traces, etc. */
  // outputDir: 'test-results/',

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   port: 3000,
  // },
}
export default config
