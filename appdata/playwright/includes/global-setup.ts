import { chromium } from '@playwright/test'
import { FramelixUtils } from './framelix-utils'

async function globalSetup () {
  FramelixUtils.logInfo('')
  FramelixUtils.logInfo('# Warming up Framelix Playwright Tests - Run App Setups')
  const browser = await chromium.launch()
  const context = await browser.newContext({ ignoreHTTPSErrors: true })
  const page = await context.newPage()
  const utils = new FramelixUtils(page)

  // running framelix setup pages
  let apps = [utils.framelixConfig.rootUrlStarter, utils.framelixConfig.rootUrlDemo]

  for (let i = 0; i < apps.length; i++) {
    const appUrl = apps[i]
    await utils.goto(appUrl)
    await page.fill('input[name="email"]', 'test@test.local')
    await page.fill('input[name="password"]', 'test@test.local')
    await page.fill('input[name="password2"]', 'test@test.local')
    await utils.submitFormAndWaitForFormSubmitFinished('setup', 'setup')

    await utils.goto(appUrl + '/backend/login')
    await page.fill('input[name="email"]', 'test@test.local')
    await page.fill('input[name="password"]', 'test@test.local')
    await utils.submitFormAndWaitForFormSubmitFinished('login', 'login')

    // run warmup directly after to restore a proper app state
    await utils.goto(appUrl + '/appwarmup')
  }

  await browser.close()

  FramelixUtils.logSuccess('# Prepare done')
  FramelixUtils.logInfo('')
}

export default globalSetup