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
    if ($(this).children('article').length) {
      $(this).find('article .text').html(html)
    } else {
      $(this).html(html)
    }
    this.originalContents = $(this).contents()
  }

  async updateDomContents () {
    super.updateDomContents()
    const self = this
    const selfCash = $(this)
    const hidable = this.getAttribute('hidable')
    const bgcolor = this.getAttribute('bgcolor')
    const textcolor = this.getAttribute('textcolor')
    this.setAttribute('role', 'figure')

    const contents = this.originalContents
    const header = this.getAttribute('header')
    if (header !== null) {
      selfCash.prepend(`<header><div class="text">&nbsp;</div></header>`)
      FramelixLang.get(header).then((value) => {
        selfCash.find('header .text').html(value)
      })
    }
    // could be a translation string
    selfCash.append(`<article><div class="text">&nbsp;</div></article>`)
    if (contents.length === 1 && contents[0].nodeType === Node.TEXT_NODE && contents[0].nodeValue.trim().startsWith('__')) {
      const value = contents[0].nodeValue
      this.removeChild(contents[0])
      FramelixLang.get(value).then((value) => {
        selfCash.find('article .text').html(value)
      })
    } else {
      selfCash.find('article .text').append(contents)
    }
    if (hidable) {
      selfCash.children().first().append(`<framelix-button theme="transparent" icon="719" title="__framelix_alert_hide__" small></framelix-button>`)
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