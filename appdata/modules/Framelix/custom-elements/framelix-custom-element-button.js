class FramelixCustomElementButton extends FramelixCustomElement {
  initialized = false

  async updateDomContents () {
    super.updateDomContents()
    const self = this
    const icon = this.getAttribute('icon')
    const disabled = this.hasAttribute('disabled')
    const bgcolor = this.getAttribute('bgcolor')
    const textcolor = this.getAttribute('textcolor')
    const href = this.getAttribute('href')
    const target = this.getAttribute('target') || '_self'
    const jscallUrl = this.getAttribute('jscall-url')
    const clickConfirmMessage = this.getAttribute('confirm-message')
    this.setAttribute('role', 'button')
    if (!disabled && this.getAttribute('tabindex') === null) {
      this.setAttribute('tabindex', '0')
    }
    this.toggleAttribute('haslabel', this.originalHtml.trim().length >= 1)
    if (!this.hasAttribute('raw')) {
      let html = ''
      if (icon) html += '<div class="icon"><div class="material-icons">' + icon + '</div></div>'
      const text = await FramelixLang.get(this.originalHtml.trim())
      if (text.length) html += '<label class="label">' + text + '</label>'
      this.innerHTML = html
    }
    if (bgcolor) {
      this.style.backgroundColor = bgcolor
      if (!textcolor) {
        setTimeout(function () {
          self.style.color = FramelixColorUtils.invertColor(FramelixColorUtils.cssColorToHex(self.style.backgroundColor), true)
        }, 10)
      }
    }
    if (textcolor) {
      self.style.color = textcolor
    }
    if (href) {
      this.setAttribute('role', 'link')
    }
    if (!this.initialized) {
      this.initialized = true
      this.addEventListener('click', async function (ev) {
        if (clickConfirmMessage) {
          if (!await FramelixModal.confirm(await FramelixLang.get(clickConfirmMessage)).confirmed) {
            return
          }
        }
        if (jscallUrl) {
          const request = FramelixRequest.jsCall(jscallUrl, { 'data': this.dataset })
          if (target === 'modal') {
            FramelixModal.show({ bodyContent: request })
          } else if (target === '_blank') {
            const result = await request.getResponseData()
            const w = window.open('about:blank')
            w.document.write(result)
          } else if (target === '_top') {
            const result = await request.getResponseData()
            window.top.document.write(result)
          } else if (target === '_self') {
            const result = await request.getResponseData()
            window.document.write(result)
          } else if (target === 'none') {
            await request.checkHeaders()
          }
        }
        if (href) {
          if (target === 'modal') {
            const request = FramelixRequest.request('get', href)
            FramelixModal.show({ bodyContent: request })
          } else if (target === 'none') {
            const request = FramelixRequest.request('get', href)
            await request.checkHeaders()
          } else {
            const link = $('<a>').attr('href', href).attr('target', target)
            link.css('display', 'hidden')
            $('body').append()
            link.trigger('click')
            setTimeout(function () {
              link.remove()
            }, 1000)
          }
        }
      })
      this.addEventListener('keydown', function (ev) {
        if (ev.key === ' ' || ev.key === 'Enter') {
          $(self).trigger('click')
        }
      })
    }
  }
}

window.customElements.define('framelix-button', FramelixCustomElementButton)