import { FullConfig, Reporter, Suite, TestCase, TestResult } from '@playwright/test/reporter'
import { FramelixUtils } from './framelix-utils'

class FramelixReporter implements Reporter {

  public testCount: number = 0
  public allTestsCount: number = 0

  getCurrentTestNumbers (): string {
    return `[${this.testCount}/${this.allTestsCount}]`
  }

  onBegin (config: FullConfig, suite: Suite) {
    this.allTestsCount = suite.allTests().length

    FramelixUtils.logInfo(`# Framelix Testrunner with a total of ${suite.allTests().length} tests`)
    FramelixUtils.logInfo(`# Playwright Version: ${config.version}`)
    for (let project of config.projects) {
      FramelixUtils.logInfo(`# Browser: ${project.name} | ${project.use.userAgent}`)
    }
  }

  onTestBegin (test: TestCase, result: TestResult) {
    const project = test.parent.project()
    if (result.retry) {
      FramelixUtils.logWarn(`# ${this.getCurrentTestNumbers()} RETRY #${result.retry} Test "${test.title.trim()}" with browser "${project.name}"`)
    } else {
      this.testCount++
      FramelixUtils.logInfo(`# ${this.getCurrentTestNumbers()} Test "${test.title.trim()}" with browser "${project.name}"`)
    }
  }

  onTestEnd (test, result) {
    if (Array.isArray(result.stdout)) {
      for (let i = 0; i < result.stdout.length; i++) {
        FramelixUtils.logInfo(result.stdout[i].trim())
      }
    }
    if (Array.isArray(result.stderr)) {
      for (let i = 0; i < result.stderr.length; i++) {
        FramelixUtils.logError(result.stderr[i].trim())
      }
    }
    let title = `# ${this.getCurrentTestNumbers()} Test "${test.title.trim()}" finished in ${(result.duration / 1000).toFixed(1)}s`
    if (result.status !== 'passed') {
      FramelixUtils.logError(`${title} but with errors`)
    } else {
      FramelixUtils.logSuccess(`${title} successfully`)
    }
    FramelixUtils.logInfo('')
  }

  onEnd (result) {
    if (result.status !== 'passed') {
      FramelixUtils.logError(`# Finished all tests but with errors`)
    } else {
      FramelixUtils.logSuccess(`# Finished all tests successfully`)
    }
  }
}

export default FramelixReporter