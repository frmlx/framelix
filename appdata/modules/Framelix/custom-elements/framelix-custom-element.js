class FramelixCustomElement extends HTMLElement {
  /**
   * The original html before initialization
   * @type {string}
   */
  originalHtml

  constructor () {
    super()
  }

  async connectedCallback () {
    if (!await this.waitForConnect()) return
    this.updateDomContents()
  }

  setRootContainerProps () {
  }

  updateDomContents () {
    this.setRootContainerProps()
  }

  async waitForConnect () {
    // this prevents innerHTML from being empty
    await Framelix.wait(1)
    if (!this.isConnected) return false
    if (typeof this.originalHtml === 'undefined') this.originalHtml = this.innerHTML
    return true
  }
}