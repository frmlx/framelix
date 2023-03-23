import { Page, Response } from '@playwright/test'

type FramelixConfig = { rootUrl: string; }

export class FramelixUtils {
  readonly page: Page
  readonly framelixConfig: FramelixConfig

  /**
   * Log info message
   * @param {string} msg
   */
  static logInfo (msg: string): void {
    console.log(msg)
  }

  /**
   * Log success message
   * @param {string} msg
   */
  static logSuccess (msg: string): void {
    console.log(`\x1b[32m${msg}\x1b[0m`)
  }

  /**
   * Log warning message
   * @param {string} msg
   */
  static logWarn (msg: string): void {
    console.log(`\x1b[33m${msg}\x1b[0m`)
  }

  /**
   * Log error message
   * @param {string} msg
   */
  static logError (msg: string): void {
    console.log(`\x1b[31m${msg}\x1b[0m`)
  }

  /**
   * Constructor
   * @param {Page} page
   */
  constructor (page: Page) {
    this.page = page
    this.framelixConfig = {
      rootUrl: 'https://127.0.0.1'
    }
    // stop on any uncaught page error
    this.page.on('pageerror', exception => {
      throw exception
    })
    // stop on any console error
    page.on('console', msg => {
      if (msg.type() === 'error')
        throw new Error(`Console error on page: ${msg.text()}`)
    })
    // log navigation requests for easier debugging
    page.on('framenavigated', function (request) {
      FramelixUtils.logInfo('- Navigated to: ' + request.url())
    })
  }

  /**
   * Goto framework page, name relative from root url
   * @param {string} relativePath
   * @return {Promise<Response>}
   */
  async goto (relativePath: string): Promise<Response> {
    return this.page.goto(this.framelixConfig.rootUrl + '/' + relativePath.replace(/^\//, ''))
  }

  /**
   * Wait for a form submit to be finally finished
   * @return {Promise<void>}
   */
  async waitForFormSubmitFinished (): Promise<void> {
    const self = this
    let bind = function (resolve) {
      self.page.once('response', async response => {
        // wait for page to be fully loaded
        await self.page.waitForLoadState('networkidle')
        resolve()
      })
    }
    return new Promise<void>(function (resolve) {
      bind(resolve)
    })
  }

  /**
   * Submit a form and wait submit to be finally finished
   * @param {string} formId
   * @param {string} buttonName
   */
  async submitFormAndWaitForFormSubmitFinished (formId: string, buttonName: string): Promise<void> {
    await Promise.all([
      this.waitForFormSubmitFinished(),
      this.page.click('#framelix-form-row-bottom-' + formId + ' framelix-button[data-submit-field-name="' + buttonName + '"]')
    ])
  }
}