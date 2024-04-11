/**
 * A captcha field to provide captcha validation
 */
class FramelixFormFieldCaptcha extends FramelixFormField {

  static TYPE_RECAPTCHA_V2 = 'recaptchav2'
  static TYPE_RECAPTCHA_V3 = 'recaptchav3'

  /**
   * The captcha type
   * @type {string}
   */
  type

  /**
   * The public keys for the captcha types
   * @type {Object<string, string>}
   */
  publicKeys

  /**
   * Some captcha solutions (recaptcha) does allow setting a category for action tracking
   * @type {string}
   */
  trackingAction = 'framelix'

  /**
   * If true, the captcha will only be rendered after used has changed any value in the form that this
   * captcha is in
   * If this captcha is not part of a form, it will be rendered right away (same as false)
   * @type {boolean}
   */
  renderAfterUserInput = true

  /**
   * Signed url for the php call to verify the token
   * @type {string}
   */
  signedUrlVerifyToken

  /**
   * The current recaptcha widget id
   * @type {number|null}
   */
  recaptchaWidgetId = null

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    // not possible
  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.field.find('input').val() || ''
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    // disabled doesn't render anything at all
    if (this.disabled) {
      return
    }

    if (!this.renderAfterUserInput || !this.form) {
      await render()
      return
    }

    const self = this

    this.form.container.one('change input paste', function () {
      render()
    }).one('mouseenter', '.framelix-form-buttons framelix-button[data-type="submit"]', function () {
      render()
    })

    const messageContainer = $(`<framelix-alert style="visibility: hidden">&nbsp;</framelix-alert>`)
    self.field.append(messageContainer)
    /** @type {FramelixCustomElementAlert} */
    const alert = messageContainer[0]
    let rendered = false

    async function render () {
      if (rendered) return
      rendered = true
      alert.style.visibility = ''
      alert.updateBodyHtml(`<div class="framelix-loading"></div>&nbsp;&nbsp;${await FramelixLang.get('__framelix_form_validation_captcha_loading__')}`)
      if (self.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V2 || self.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V3) {
        await FramelixDom.includeResource('https://www.google.com/recaptcha/api.js?render=' + (self.publicKeys[FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V3] || 'explicit'), function () {
          return typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function'
        })
      }
      if (self.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V2) {
        grecaptcha.ready(function () {
          let el = document.createElement('div')
          self.field.html(el)
          self.recaptchaWidgetId = grecaptcha.render(el, {
            'sitekey': self.publicKeys[self.type],
            'theme': $('html').attr('data-color-scheme'),
            'callback': async function () {
              const token = self.field.find('textarea').val()
              let apiResponse = await FramelixRequest.jsCall(self.signedUrlVerifyToken, {
                'token': token,
                'type': self.type
              }).getResponseData()
              if (!apiResponse || !apiResponse.hash) {
                grecaptcha.reset(self.recaptchaWidgetId)
                self.showValidationMessage('__framelix_form_validation_captcha_invalid__')
                return
              }
              self.hideValidationMessage()
              self.field.append($(`<input type="hidden" name="${self.name}">`).val(token + ':' + apiResponse.hash))
              self.triggerChange(self.field, false)
            }
          })
        })
      }
      if (self.type === FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V3) {
        grecaptcha.ready(async function () {
          let token = await grecaptcha.execute(self.publicKeys[self.type], { action: self.trackingAction })
          let apiResponse = await FramelixRequest.jsCall(self.signedUrlVerifyToken, {
            'token': token,
            'type': self.type
          }).getResponseData()
          if (!apiResponse || !apiResponse.hash) {
            // if not validated than render a v2 visible captcha
            self.type = FramelixFormFieldCaptcha.TYPE_RECAPTCHA_V2
            self.render()
          } else {
            self.hideValidationMessage()
            alert.setAttribute('theme', 'success')
            alert.updateBodyHtml(await FramelixLang.get('__framelix_form_validation_captcha_verified__'))
            self.field.append($(`<input type="hidden" name="${self.name}">`).val(token + ':' + apiResponse.hash))
            self.triggerChange(self.field, false)
          }
        })
      }
    }
  }
}

FramelixFormField.classReferences['FramelixFormFieldCaptcha'] = FramelixFormFieldCaptcha