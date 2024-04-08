class FramelixCustomElement extends HTMLElement {
  /**
   * The original html before initialization
   * @type {string}
   */
  originalHtml

  async connectedCallback () {
    if (this.originalHtml === undefined) {
      this.originalHtml = this.innerHTML
    }
    this.updateDomContents()
  }

  setRootContainerProps () {
  }

  updateDomContents () {
    this.setRootContainerProps()
  }
}