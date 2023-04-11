import { test } from '../../includes/framelix-fixture'

test('Traversing all docs pages', async ({ page, utils }) => {

  // clear browser session/cookie to not affect global sso token
  await page.context().clearCookies()
  await utils.goto('/')

  // find all navigation links and go through them
  let elements = page.locator('.framelix-sidebar a.framelix-sidebar-link')
  let elementsCount = await elements.count()
  let urls = []

  for (let index = 0; index < elementsCount; index++) {
    const element = await elements.nth(index)
    urls.push(await element.getAttribute('href'))
  }

  for (let i = 0; i < urls.length; i++) {
    const url = urls[i]
    await utils.goto(url)
  }
})
