class FramelixCustomElementAlert extends FramelixCustomElement {
  initialized = false

  static resetAllAlerts () {
    FramelixLocalStorage.remove('framelix_alerts_hidden')
    $('framelix-alert').removeClass('hidden')
  }

  updateHeaderText (str) {
    this.setAttribute('header', str)
    this.updateDomContents()
  }

  updateBodyHtml (html) {
    if (this.originalHtml.trim().length) {
      this.originalHtml = html
      $(this).find('article .text').html(html)
    } else {
      this.originalHtml = html
      this.updateDomContents()
    }
  }

  async updateDomContents () {
    super.updateDomContents()
    const self = this
    const hidable = this.getAttribute('hidable')
    const bgcolor = this.getAttribute('bgcolor')
    const textcolor = this.getAttribute('textcolor')
    this.setAttribute('role', 'figure')

    let text = this.getAttribute('header') ? `
        <header><div class="text">${await FramelixLang.get(this.getAttribute('header'))}</div></header>
    ` : ''
    if (this.originalHtml.trim().length) {
      text += `<article><div class="text">${await FramelixLang.get(this.originalHtml)}</div></article>`
    }

    this.innerHTML = text
    if (hidable) {
      $(this).children().first().append(`<framelix-button theme="transparent" icon="719" title="__framelix_alert_hide__" small></framelix-button>`)
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
    if (hidable) {
      const entries = FramelixLocalStorage.get('framelix_alerts_hidden') || {}
      if (entries[hidable] === 1) {
        this.classList.add('hidden')
      }
    }
    if (!this.initialized) {
      this.initialized = true
      const self = this
      this.addEventListener('click', async function (ev) {
        if (!hidable) {
          return
        }
        const entries = FramelixLocalStorage.get('framelix_alerts_hidden') || {}
        entries[hidable] = 1
        FramelixLocalStorage.set('framelix_alerts_hidden', entries)
        FramelixToast.success($('.framelix-user-settings').length ? '__framelix_alert_hidden_backend__' : '__framelix_alert_hidden__')
        self.classList.add('hidden')
      })
    }
  }
}

window.customElements.define('framelix-alert', FramelixCustomElementAlert)