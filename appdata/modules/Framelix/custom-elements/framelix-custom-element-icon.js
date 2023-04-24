class FramelixCustomElementIcon extends FramelixCustomElement {
  initialized = false

  async updateDomContents () {
    super.updateDomContents()
    const icon = this.getAttribute('icon')
    const size = this.getAttribute('size')
    this.setAttribute('role', 'img')
    if (size) {
      this.style.fontSize = size.match(/[^0-9]/) ? size : size + 'px'
    }
    this.innerHTML = '&#xe' + icon + ';'
  }
}

window.customElements.define('framelix-icon', FramelixCustomElementIcon)