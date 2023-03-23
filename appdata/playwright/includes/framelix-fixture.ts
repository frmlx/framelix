import { expect, test as base } from '@playwright/test'
import { FramelixUtils } from './framelix-utils'

type FramelixFixture = {
  utils: FramelixUtils;
};

export const test = base.extend<FramelixFixture>({
  utils: async ({ page }, use) => {
    await use(new FramelixUtils(page))
  }
})
export { expect } from '@playwright/test'