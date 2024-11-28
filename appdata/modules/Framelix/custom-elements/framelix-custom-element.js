class FramelixCustomElement extends HTMLElement {
  /**
   * The original contents
   * @type {Cash}
   */
  originalContents

  async connectedCallback () {
    if(this.originalContents === undefined){
      this.originalContents = $(this).contents()
    }
    this.updateDomContents()
  }

  setRootContainerProps () {
  }

  updateDomContents () {
    this.setRootContainerProps()
  }
}