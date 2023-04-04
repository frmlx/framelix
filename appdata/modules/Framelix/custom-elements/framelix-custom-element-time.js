class FramelixCustomElementTime extends FramelixCustomElement {
  initialized = false

  async updateDomContents () {
    super.updateDomContents()
    const self = this
    const datetime = this.getAttribute('datetime')
    const format = this.getAttribute('format')

    this.innerHTML = '<time datetime="' + datetime + '">' + (dayjs(datetime).format(format)) + '</time>'

    if (!this.initialized) {
      this.initialized = true
    }
  }
}

window.customElements.define('framelix-time', FramelixCustomElementTime)