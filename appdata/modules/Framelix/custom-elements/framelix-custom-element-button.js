class FramelixCustomElementButton extends FramelixCustomElement {
  initialized = false

  async updateDomContents () {
    super.updateDomContents()
    const self = this
    const selfCash = $(this)
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
    if (!this.hasAttribute('raw')) {
      selfCash.empty()
      const contents = this.originalContents
      if (icon) {
        selfCash.prepend('<div class="icon"><framelix-icon icon="' + icon + '"></framelix-icon></div>')
      }
      // could be a translation string
      if (contents.length === 1 && contents[0].nodeType === Node.TEXT_NODE && contents[0].nodeValue.trim().startsWith('__')) {
        const value = contents[0].nodeValue.trim()
        selfCash.append('<label class="label">&nbsp;</label>')
        FramelixLang.get(value).then((value) => {
          selfCash.find('label.label').html(value)
          this.toggleAttribute('haslabel', value.length > 0)
        })
      } else {
        selfCash.append($('<label class="label"></label>').append(contents))
        this.toggleAttribute('haslabel', selfCash.find('label.label').text().trim().length > 0)
      }
    }
    if (bgcolor) {
      self.style.backgroundColor = bgcolor
      if (!textcolor) {
        self.style.color = FramelixColorUtils.invertColor(bgcolor)
      }
    }
    if (textcolor) {
      self.style.color = textcolor === 'invert' ? FramelixColorUtils.invertColor(getComputedStyle(self).backgroundColor, true) : textcolor
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