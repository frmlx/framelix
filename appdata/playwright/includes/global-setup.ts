import { chromium } from '@playwright/test'
import { FramelixUtils } from './framelix-utils'

async function globalSetup () {
  FramelixUtils.logInfo('')
  FramelixUtils.logInfo('# Prepare and Reset App | Login into and store credentials for future runs')
  const browser = await chromium.launch()
  const context = await browser.newContext({ ignoreHTTPSErrors: true })
  const page = await context.newPage()
  const utils = new FramelixUtils(page)

  // first init app if already initialized
  await utils.goto('initializeplaywright')

  // second running official setup page so it is also tested as well
  await utils.goto('/setup')
  await page.fill('input[name="email"]', 'test@test.local')
  await page.fill('input[name="password"]', 'test@test.local')
  await page.fill('input[name="password2"]', 'test@test.local')
  await utils.submitFormAndWaitForFormSubmitFinished('setup', 'setup')

  // storing browser cookies and session to re-use later
  // after setup, the user is automatically logged in as admin
  await page.context().storageState({ path: __dirname + '/../../../userdata/playwright/browserStorageState.json' })
  await browser.close()
  FramelixUtils.logSuccess('# Prepare done')
  FramelixUtils.logInfo('')
}

export default globalSetup