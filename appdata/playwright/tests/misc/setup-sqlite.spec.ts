import { test } from '../../includes/framelix-fixture'
import * as fs from 'fs'
import { expect } from '@playwright/test'

test('App Setup Page with SQLite on Starter Module', async ({ page, utils }) => {
  // remove some config data before starting to make sure the setup is required
  const modulePrivateFolder = '/framelix/userdata/FramelixStarter/private'
  const files = ['config/01-app.php', 'FramelixStarter.db']
  for (let file of files) {
    if (fs.existsSync(modulePrivateFolder + '/' + file)) {
      fs.unlinkSync(modulePrivateFolder + '/' + file)
    }
  }
  await utils.goto(utils.framelixConfig.rootUrlStarter)

  await page.fill('input[name="email"]', 'test@test.local')
  await page.fill('input[name="password"]', 'test@test.local')
  await page.fill('input[name="password2"]', 'test@test.local')
  await utils.submitFormAndWaitForFormSubmitFinished('setup', 'setup')

  // now user can login, but isn't yet
  await expect(page.locator('html[data-user]')).toHaveCount(0)

  await page.fill('input[name="email"]', 'test@test.local')
  await page.fill('input[name="password"]', 'test@test.local')
  await utils.submitFormAndWaitForFormSubmitFinished('login', 'login')

  // now user must be logged in
  await expect(page.locator('html[data-user="1"]')).toHaveCount(1)
})
