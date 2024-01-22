/**
 * A field to enter and validate a TOTP two-factor code
 */
class FramelixFormFieldTwoFactorCode extends FramelixFormField {

  /**
   * The hidden input to submit
   * @type {Cash}
   */
  input

  /**
   * Auto submit the form containing this field after user has entered 6-digits
   * @type {boolean}
   */
  formAutoSubmit = true

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    let i = 1
    this.field.find('[type=\'text\']').each(function () {
      if (typeof value === 'string' && value.length >= i) {
        this.value = value[i]
      }
      i++
    })
  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.input.val()
  }

  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */
  async validate () {
    if (!this.isVisible()) return true

    const parentValidation = await super.validate()
    if (parentValidation !== true) return parentValidation

    return true
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.input = $(`<input type="text" inputmode="decimal" autocomplete="one-time-code" class="framelix-form-field-input">`)
    this.input.attr('name', this.name)
    this.container.append(this.input)

    this.field.append(`<div class="framelix-form-field-twofactorcode-label">${await FramelixLang.get('__framelix_form_2fa_enter__')}</div>`)
    this.field.append(this.input)
    this.field.append(`<div class="framelix-form-field-twofactorcode-backup"><framelix-button theme="light">__framelix_form_2fa_usebackup__</framelix-button></div>`)
    this.field.find('.framelix-form-field-twofactorcode-backup framelix-button').on('click', async function () {
      self.field.find('.framelix-form-field-twofactorcode-backup').remove()
      self.field.find('.framelix-form-field-twofactorcode-label').text(await FramelixLang.get('__framelix_form_2fa_enter_backup__'))
      self.input.attr('type', 'text')
      self.input.addClass('framelix-form-field-twofactorcode-backup-input')
      self.input.val('')
    })
    this.field.on('focusin', 'input', function () {
      this.select()
    })
    this.field.on('input', '.framelix-form-field-twofactorcode-backup-input', function (ev) {
      ev.stopPropagation()
      ev.stopImmediatePropagation()
      self.input.val(self.input.val().replace(/[^0-9A-Z]/ig, ''))
      if (self.input.val().length === 10 && self.formAutoSubmit && self.form) {
        self.form.submit()
      }
    })
    this.field.on('input', function () {
      self.input.val(self.input.val().replace(/[^0-9]/ig, ''))
      if (self.input.val().length === 6 && self.formAutoSubmit && self.form) {
        self.form.submit()
      }
    })
  }
}

FramelixFormField.classReferences['FramelixFormFieldTwoFactorCode'] = FramelixFormFieldTwoFactorCode