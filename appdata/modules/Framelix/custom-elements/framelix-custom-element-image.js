class FramelixCustomElementImage extends FramelixCustomElement {
  async updateDomContents () {
    super.updateDomContents()
    await Framelix.wait(FramelixRandom.getRandomInt(100, 200))
    const lazy = !this.getAttribute('nolazy')
    const self = this

    function show () {
      const parent = self.parentElement
      let parentWidth = parent.getBoundingClientRect().width
      if (self.getAttribute('minwidth')) {
        parentWidth = Math.max(parseInt(self.getAttribute('minwidth')), parentWidth)
      }
      if (self.getAttribute('maxwidth')) {
        parentWidth = Math.min(parseInt(self.getAttribute('maxwidth')), parentWidth)
      }
      if (parentWidth <= 0) {
        return
      }
      let useUrl = ''
      const sizeAttr = []
      for (const attr of self.attributes) {
        if (attr.name.startsWith('size-')) {
          const size = parseInt(attr.name.substring(5))
          sizeAttr.push({ 'size': size, 'url': attr.value })
          if (parentWidth > size || useUrl === '') {
            useUrl = attr.value
          }
        }
      }
      sizeAttr.sort(function (a, b) {
        if (a.size > b.size) return 1
        if (a.size < b.size) return -1
        return 0
      })
      for (const row of sizeAttr) {
        if (parentWidth > row.size || useUrl === '') {
          useUrl = row.url
        } else if (parentWidth < row.size) {
          useUrl = row.url
          break
        }
      }
      if (useUrl === '') useUrl = self.getAttribute('src')

      function checkResize (el) {
        if (self.getAttribute('recalculate')) {
          setTimeout(function () {
            FramelixResizeObserver.observe(el, function () {
              FramelixResizeObserver.unobserve(el)
              show()
            })
          }, 500)
        }
      }

      if (self.getAttribute('setparent')) {
        parent.style.backgroundImage = 'url(' + useUrl + ')'
        checkResize(parent)
      } else {
        const img = document.createElement('img')
        self.innerHTML = ``
        self.appendChild(img)
        img.src = useUrl
        checkResize(img)
      }
    }

    if (!lazy) {
      show()
    } else {
      FramelixIntersectionObserver.onGetVisible(this, show)
    }
  }
}

window.customElements.define('framelix-image', FramelixCustomElementImage)