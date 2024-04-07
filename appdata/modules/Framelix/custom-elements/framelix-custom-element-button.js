class FramelixCustomElementButton extends FramelixCustomElement {
  initialized = false

  async updateDomContents () {
    super.updateDomContents()
    const self = this
    const icon = this.getAttribute('icon')
    let disabled = this.hasAttribute('disabled')
    const bgcolor = this.getAttribute('bgcolor')
    const textcolor = this.getAttribute('textcolor')
    this.setAttribute('role', 'button')
    if (!disabled) {
      if (this.getAttribute('tabindex') === null) {
        this.setAttribute('tabindex', '0')
      }
    } else if (disabled) {
      this.removeAttribute('tabindex')
      this.removeAttribute('role')
    }
    this.toggleAttribute('haslabel', this.originalHtml.trim().length >= 1)
    if (!this.hasAttribute('raw')) {
      let html = ''
      if (icon) {
        html += '<div class="icon"><framelix-icon icon="' + icon + '"></framelix-icon></div>'
      }
      const text = await FramelixLang.get(this.originalHtml.trim())
      if (text.length) {
        html += '<label class="label">' + text + '</label>'
      }
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
    if (this.hasAttribute('href')) {
      this.setAttribute('role', 'link')
    }
    if (!this.initialized) {
      this.initialized = true
      this.addEventListener('click', async function (ev) {

        disabled = disabled = this.hasAttribute('disabled')
        if (disabled) {
          return
        }

        const clickConfirmMessage = this.getAttribute('confirm-message')
        if (clickConfirmMessage) {
          if (!await FramelixModal.confirm(await FramelixLang.get(clickConfirmMessage)).confirmed) {
            return
          }
        }
        const requestOptions = FramelixTypeDefJsRequestOptions.fromAttrValue(this.getAttribute('request-options'))
        if (requestOptions) {
          ev.stopPropagation()
          ev.preventDefault()
          self.setAttribute('disabled', '1')
          self.updateDomContents()
          FramelixRequest.renderFromRequestOptions(requestOptions, this, null, function (loaded) {
            if (loaded >= 1) {
              self.removeAttribute('disabled')
              self.updateDomContents()
            }
          })
          return
        }
        const href = this.getAttribute('href')
        if (href) {
          ev.stopPropagation()
          ev.preventDefault()
          const target = this.getAttribute('target') || '_self'
          const link = $('<a>').attr('href', href).attr('target', target)
          link.css('display', 'hidden')
          $('body').append()
          link.trigger('click')
          setTimeout(function () {
            link.remove()
          }, 1000)
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