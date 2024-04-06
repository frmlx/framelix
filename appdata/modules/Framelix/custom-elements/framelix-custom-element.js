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
    this.originalHtml = this.innerHTML
    this.updateDomContents()
  }

  setRootContainerProps () {
  }

  updateDomContents () {
    this.setRootContainerProps()
  }
}