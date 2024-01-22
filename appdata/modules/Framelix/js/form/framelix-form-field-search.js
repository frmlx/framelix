/**
 * A search field
 */
class FramelixFormFieldSearch extends FramelixFormField {

  maxWidth = 400

  /**
   * Is multiple
   * @type {boolean}
   */
  multiple = false

  /**
   * Signed url for the php call for the search
   * @type {string}
   */
  signedUrlSearch

  /**
   * Continuous search when user input
   * If false, user must hit enter to start search
   * @type {boolean}
   */
  continuousSearch = true

  /**
   * The options that are selected when the field is rendered the first time
   * @type {Object|null}
   */
  initialSelectedOptions = null

  /**
   * The result popup
   * @type {FramelixPopup|null}
   */
  resultPopup = null

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    this.triggerChange(this.field, isUserChange)
  }

  /**
   * Get value for this field
   * @return {string[]|string|null}
   */
  getValue () {
    const values = FormDataJson.toJson(this.field.find('.framelix-form-field-search-selected-options')[0], {
      'includeDisabled': true,
      'flatList': true
    })
    let arr = []
    for (let i = 0; i < values.length; i++) {
      arr.push(values[i][1])
    }
    if (!arr.length) return null
    return this.multiple ? arr : arr[0]
  }

  /**
   * Get option html
   * @param {string} value
   * @param {string} label
   * @param {boolean} checked
   * @return {Cash}
   */
  getOptionHtml (value, label, checked) {
    const option = $(`
        <label class="framelix-form-field-select-option">
            <div class="framelix-form-field-select-option-checkbox">
                <input type="checkbox" name="${this.name + (this.multiple ? '[]' : '')}" ${this.disabled ? 'disabled' : ''}>
            </div>
            <div class="framelix-form-field-select-option-label"></div>
        </label>
      `)
    const input = option.find('input')
    option.find('.framelix-form-field-select-option-label').html(label)
    input.attr('value', value)
    input.prop('checked', checked)
    return option
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.container.attr('data-multiple', this.multiple ? '1' : '0')
    this.field.html(`
      <div class="framelix-form-field-search-container">
        <div class="framelix-form-field-search-input"><div class="framelix-form-field-container" data-field-with-button="1"><input type="search" placeholder="${await FramelixLang.get('__framelix_form_select_search__')}" class="framelix-form-field-input" spellcheck="false" data-continuous-search="${this.continuousSearch ? '1' : '0'}" ${this.disabled ? 'disabled' : ''}><framelix-button theme="primary" icon="744"></framelix-button></div></div>
        <div class="framelix-form-field-search-selected-options framelix-form-field-input"></div>
      </div>
    `)
    const searchInputContainer = this.field.find('.framelix-form-field-search-input')
    const searchInput = searchInputContainer.find('input')
    const searchButton = searchInputContainer.find('framelix-button')
    const selectedOptionsContainer = this.field.find('.framelix-form-field-search-selected-options')

    if (this.initialSelectedOptions && this.initialSelectedOptions.keys.length) {
      for (let i = 0; i < this.initialSelectedOptions.keys.length; i++) {
        const value = this.initialSelectedOptions.keys[i]
        const label = this.initialSelectedOptions.values[i]
        selectedOptionsContainer.append(this.getOptionHtml(value, label, true))
        if (!this.multiple) break
      }
    }

    if (!this.disabled) {
      searchButton.on('click', function () {
        searchInput.trigger('search-start')
      })
      searchInput.on('search-start', async function () {
        let query = this.value.trim()
        if (query.length) {
          const currentValue = self.getValue()
          if (!self.resultPopup) {
            function updateValue () {
              let existingOptions = {}
              selectedOptionsContainer.find('input').each(function () {
                existingOptions[this.value] = this
              })
              if (self.resultPopup && self.resultPopup.popperEl) {
                self.resultPopup.popperEl.find('input').each(function () {
                  if (existingOptions[this.value]) {
                    existingOptions[this.value].checked = this.checked
                    return
                  }
                  if (!this.checked) return true
                  let optionEl = $(this).closest('.framelix-form-field-select-option')
                  optionEl.find('input').prop('checked', this.checked)
                  if (!self.multiple) {
                    selectedOptionsContainer.empty()
                    selectedOptionsContainer.append(optionEl)
                    return false
                  }
                  selectedOptionsContainer.append(optionEl)
                })
              }
            }

            self.resultPopup = FramelixPopup.show(
              searchInput,
              `<div class="framelix-form-field-search-popup framelix-form-field-input" data-multiple="${self.multiple ? '1' : '0'}"></div>`,
              {
                closeMethods: 'click-outside',
                placement: 'bottom-start',
                appendTo: searchInputContainer,
                padding: '',
                offset: [0, 0]
              }
            )
            self.resultPopup.destroyed.then(function () {
              updateValue()
              self.resultPopup = null
            })
            self.resultPopup.popperEl.on('change', function () {
              updateValue()
              if (!self.multiple) {
                self.resultPopup?.destroy()
              }
            })
          }
          searchButton.attr('disabled', '').addClass('framelix-pulse')
          let options = await FramelixRequest.jsCall(self.signedUrlSearch, { 'query': this.value }).getResponseData()
          searchButton.attr('disabled', '').removeClass('framelix-pulse')
          const content = self.resultPopup.popperEl.find('.framelix-popup-inner > .framelix-form-field-input')
          content.html('')
          if (!options.keys.length) {
            content.html(`<div class="framelix-form-field-select-option">${await FramelixLang.get('__framelix_form_search_noresult__')}</div>`)
          } else {
            if (options.keys) {
              for (let i = 0; i < options.keys.length; i++) {
                const value = self.stringifyValue(options.keys[i])
                content.append(self.getOptionHtml(value, options.values[i], value === currentValue || FramelixObjectUtils.hasValue(currentValue, value)))
              }
            }
          }
        } else {
          if (self.resultPopup) {
            self.resultPopup.destroy()
          }
        }
      })
      searchInput.on('keydown', function (ev) {
        if (self.resultPopup && ev.key === 'Tab' && !ev.shiftKey) {
          ev.preventDefault()
          self.resultPopup.popperEl.find('label').first().trigger('focus')
        }
      })
    }
  }
}

FramelixFormField.classReferences['FramelixFormFieldSearch'] = FramelixFormFieldSearch