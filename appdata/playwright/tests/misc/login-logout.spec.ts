import { expect, test } from '../../includes/framelix-fixture'

test('Login/Logout', async ({ page, utils }) => {

  // clear browser session/cookie to not affect global sso token
  await page.context().clearCookies()

  // login page
  await utils.goto('backend/login')

  // now user must be logged out as we cleared cookies
  await expect(page.locator('html[data-user]')).toHaveCount(0)

  await page.fill('input[name="email"]', 'test@test.local')
  await page.fill('input[name="password"]', 'test@test.local')
  await utils.submitFormAndWaitForFormSubmitFinished('login', 'login')

  // goto start page
  await utils.goto('')

  // now user must be logged in
  await expect(page.locator('html[data-user]')).toHaveCount(1)

  // logout
  await utils.goto('backend/logout')

  // goto start page
  await utils.goto('')

  // now user must be logged out correctly
  await expect(page.locator('html[data-user]')).toHaveCount(0)
})
