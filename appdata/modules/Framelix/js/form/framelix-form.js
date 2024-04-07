/**
 * Framelix form generator
 */
class FramelixForm {

  /**
   * Triggered when form has been submitted
   * @type {string}
   */
  static EVENT_SUBMITTED = 'framelix-form-submitted'

  /**
   * All instances
   * @type {FramelixForm[]}
   */
  static instances = []

  /**
   * The whole container
   * @type {Cash}
   */
  container

  /**
   * The <form>
   * @type {Cash}
   */
  form

  /**
   * The hidden input with name of form
   * @type {Cash}
   */
  inputHiddenSubmitFormName

  /**
   * The hidden input with name of the clicked button
   * @type {Cash}
   */
  inputHiddenSubmitButtonName

  /**
   * The submit status container
   * @type {Cash}
   */
  submitStatusContainer

  /**
   * The id of the form
   * @type {string}
   */
  id

  /**
   * The label/title above the form if desired
   * @type {string|null}
   */
  label

  /**
   * Additional form html attributes
   * @type {FramelixHtmlAttributes|null}
   */
  htmlAttributes = null

  /**
   * The fields attached to this form
   * @type {Object<string, FramelixFormField>}
   */
  fields = {}

  /**
   * The buttons attached to the form
   * @type {Object}
   */
  buttons = {}

  /**
   * Submit method
   * post or get
   * @type {string}
   */
  submitMethod = 'post'

  /**
   * The url to submit to
   * If null then it is the current url
   * @type {string|null}
   */
  submitUrl = null

  /**
   * The target to render the submit response to
   * @type {FramelixTypeDefJsRequestOptions}
   */
  requestOptions = { url: '', renderTarget: 'currentcontext' }

  /**
   * Submit the form async
   * If false then the form will be submitted with native form submit features (new page load)
   * @type {boolean}
   */
  submitAsync = true

  /**
   * Submit the form async with raw data instead of POST/GET
   * Data can be retreived with Request::getBody()
   * This cannot be used when form contains file uploads
   * @type {boolean}
   */
  submitAsyncRaw = true

  /**
   * Execute the javascript code after form submit
   * @var {string|null}
   */
  executeAfterAsyncSubmit = null

  /**
   * Submit the form with enter key
   * @type {boolean}
   */
  submitWithEnter = true

  /**
   * Allow browser autocomplete in this form
   * @type {boolean}
   */
  autocomplete = false

  /**
   * Form buttons are sticked to the bottom of the screen and always visible
   * @var {boolean}
   */
  stickyFormButtons = false

  /**
   * Field groups
   * @type {Object|null}
   */
  fieldGroups = null

  /**
   * Make a form read only, effectively set all fields to disabled and remove action buttons row
   * @type {boolean}
   */
  readOnly = false

  /**
   * A function with custom validation rules
   * If set must return true on success, string on error
   * @type {function|null}
   */
  customValidation = null

  /**
   * The current shown validation message
   * @type {string|null}
   */
  validationMessage = null

  /**
   * A promise that is resolved when the form is completely rendered
   * @type {Promise}
   */
  rendered

  /**
   * Is the form currently in an ongoing submit request
   * @type {boolean}
   */
  isSubmitting = false

  /**
   * If form is submitting, then this holds the last async submit request
   * @type {FramelixRequest|null}
   */
  submitRequest = null

  /**
   * The resolve function to resolve the rendered promise
   * @type {function}
   * @private
   */
  _renderedResolve

  /**
   * Initialize forms
   */
  static init () {
    const searchStartMs = 400
    const inputSearchMap = new Map()
    const inputSearchValueMap = new Map()
    $(document).on('input keydown', 'input[type=\'search\']', function (ev) {
      if (ev.key === 'Tab') {
        return
      }
      clearTimeout(inputSearchMap.get(this))
      if (inputSearchValueMap.get(this) === this.value && ev.key !== 'Enter') {
        return
      }
      inputSearchValueMap.set(this, this.value)
      if (this.getAttribute('data-continuous-search') !== '1' && ev.key !== 'Enter') {
        return
      }
      if (ev.key === 'Escape') {
        if (this.value === '') {
          $(this).trigger('blur')
        }
        return
      }
      if (ev.key === 'Enter') {
        ev.preventDefault()
      }
      const el = $(this)
      inputSearchMap.set(this, setTimeout(function () {
        el.trigger('search-start')
        inputSearchMap.delete(this)
        inputSearchValueMap.delete(this)
      }, ev.key !== 'Enter' ? searchStartMs : 0))
    })
  }

  /**
   * Get instance by id
   * If multiple forms with the same id exist, return last
   * @param {string} id
   * @return {FramelixForm|null}
   */
  static getById (id) {
    for (let i = FramelixForm.instances.length - 1; i >= 0; i--) {
      if (FramelixForm.instances[i].id === id) {
        return FramelixForm.instances[i]
      }
    }
    return null
  }

  /**
   * Constructor
   */
  constructor () {
    const self = this
    this.rendered = new Promise(function (resolve) {
      self._renderedResolve = resolve
    })
    this.id = FramelixRandom.getRandomHtmlId()
    FramelixForm.instances.push(this)
    this.container = $('<div>')
    this.container.addClass('framelix-form')
    this.container.attr('data-instance-id', FramelixForm.instances.length - 1)
  }

  /**
   * Add a field
   * @param {FramelixFormField} field
   */
  addField (field) {
    field.form = this
    this.fields[field.name] = field
  }

  /**
   * Remove a field by name
   * @param {string} name
   */
  removeField (name) {
    if (this.fields[name]) {
      this.fields[name].form = null
      delete this.fields[name]
    }
  }

  /**
   * Set values for this form
   * @param {Object|null} values
   */
  setValues (values) {
    for (let name in this.fields) {
      this.fields[name].setValue(values[name] ? values[name] || null : null)
    }
  }

  /**
   * Get values for this form
   * @return {Object}
   */
  getValues () {
    let values = {}
    for (let name in this.fields) {
      values[name] = this.fields[name].getValue()
    }
    return values
  }

  /**
   * Add a button where you later can bind custom actions
   * @param {string} actionId
   * @param {string} buttonText
   * @param {string|null} buttonIcon
   * @param {string} buttonColor
   * @param {string|null} buttonTooltip
   */
  addButton (
    actionId,
    buttonText,
    buttonIcon = '70c',
    buttonColor = 'dark',
    buttonTooltip = null
  ) {
    this.buttons['action-' + actionId] = {
      'type': 'action',
      'action': actionId,
      'color': buttonColor,
      'buttonText': buttonText,
      'buttonIcon': buttonIcon,
      'buttonTooltip': buttonTooltip
    }
  }

  /**
   * Add a button to load a url
   * @param url
   * @param {string} buttonText
   * @param {string|null} buttonIcon
   * @param {string} buttonColor
   * @param {string|null} buttonTooltip
   */
  addLoadUrlButton (
    url,
    buttonText,
    buttonIcon = '70c',
    buttonColor = 'dark',
    buttonTooltip = null
  ) {
    this.buttons['url-' + url] = {
      'type': 'url',
      'url': url,
      'color': buttonColor,
      'buttonText': buttonText,
      'buttonIcon': buttonIcon,
      'buttonTooltip': buttonTooltip
    }
  }

  /**
   * Add submit button
   * @param {string} submitFieldName
   * @param {string} buttonText
   * @param {string|null} buttonIcon
   * @param {string} buttonColor
   * @param {string|null} buttonTooltip
   */
  addSubmitButton (
    submitFieldName,
    buttonText,
    buttonIcon = null,
    buttonColor = 'success',
    buttonTooltip = null
  ) {
    this.buttons['submit-' + submitFieldName] = {
      'type': 'submit',
      'submitFieldName': submitFieldName,
      'color': buttonColor,
      'buttonText': buttonText,
      'buttonIcon': buttonIcon,
      'buttonTooltip': buttonTooltip
    }
  }

  /**
   * Set submit status
   * @param {boolean} flag
   */
  setSubmitStatus (flag) {
    this.isSubmitting = flag
    this.container.toggleClass('framelix-form-submitting', flag)
  }

  /**
   * Show validation message
   * Does append message if already visible
   * @param {string} message
   */
  async showValidationMessage (message) {
    message = await FramelixLang.get(message)
    this.validationMessage = message
    FramelixToast.error(message)
  }

  /**
   * Hide validation message
   */
  hideValidationMessage () {
    this.validationMessage = null
    FramelixPopup.destroyInstancesOnTarget(this.submitStatusContainer)
  }

  /**
   * Add a field group
   * Each field in $fieldNames will be grouped under a collapsable container with $label
   * The group collapsable will be inserted before the first field in $fieldNames
   * @param {string} id
   * @param {string} label
   * @param {string[]} fieldNames
   * @param {boolean} defaultState
   * @param {boolean} rememberState
   */
  addFieldGroup (
    id,
    label,
    fieldNames,
    defaultState = true,
    rememberState = true
  ) {
    this.fieldGroups[id] = {
      'label': label,
      'fieldNames': fieldNames,
      'defaultState': defaultState,
      'rememberState': rememberState
    }
  }

  /**
   * Remove field groupby given id
   * @param {string} id
   */
  removeFieldGroup (id) {
    if (this.fieldGroups) {
      delete this.fieldGroups[id]
    }
  }

  /**
   * Update field visibility
   */
  updateFieldVisibility () {
    const formValues = FormDataJson.toJson(this.form[0], { 'flatList': true, 'includeDisabled': true })
    let formValuesFlatIndexed = {}
    for (let i = 0; i < formValues.length; i++) {
      formValuesFlatIndexed[formValues[i][0]] = formValues[i][1]
    }
    let fieldsWithConditionFlat = []
    for (let fieldName in this.fields) {
      const field = this.fields[fieldName]
      if (!field.visibilityCondition) {
        field.container.toggleClass('hidden', false)
      } else {
        fieldsWithConditionFlat.push(field)
      }
    }
    for (let i = 0; i < fieldsWithConditionFlat.length; i++) {
      const field = fieldsWithConditionFlat[i]
      let conditionData = field.visibilityCondition.data
      let isVisible = false
      conditionLoop: for (let j = 0; j < conditionData.length; j++) {
        const conditionRow = conditionData[j]
        if (conditionRow.type === 'or') {
          if (isVisible) {
            break
          }
          continue
        }
        if (conditionRow.type === 'and') {
          if (!isVisible) {
            break
          }
          continue
        }
        let conditionFieldValue = typeof formValuesFlatIndexed[conditionRow.field] === 'undefined' ? null : formValuesFlatIndexed[conditionRow.field]
        let requiredValue = conditionRow.value
        switch (conditionRow.type) {
          case 'equal':
          case 'notEqual':
          case 'like':
          case 'notLike':
            if (requiredValue !== null && typeof requiredValue !== 'object') {
              requiredValue = [requiredValue + '']
            }
            if (conditionFieldValue !== null && typeof conditionFieldValue !== 'object') {
              conditionFieldValue = [conditionFieldValue + '']
            }
            for (let requiredValueKey in requiredValue) {
              if (conditionRow.type === 'equal' || conditionRow.type === 'like') {
                for (let conditionFieldValueKey in conditionFieldValue) {
                  const val = conditionFieldValue[conditionFieldValueKey]
                  isVisible = conditionRow.type === 'equal' ? val === requiredValue[requiredValueKey] : val.match(FramelixStringUtils.escapeRegex(requiredValue[requiredValueKey]), 'i')

                  if (isVisible) {
                    continue conditionLoop
                  }
                }
              } else {
                for (let conditionFieldValueKey in conditionFieldValue) {
                  const val = conditionFieldValue[conditionFieldValueKey]
                  isVisible = conditionRow.type === 'notEqual' ? val !== requiredValue[requiredValueKey] : !val.match(FramelixStringUtils.escapeRegex(requiredValue[requiredValueKey]), 'i')
                  if (isVisible) {
                    continue conditionLoop
                  }
                }
              }
            }
            break
          case 'greatherThan':
          case 'greatherThanEqual':
          case 'lowerThan':
          case 'lowerThanEqual':
            if (typeof conditionFieldValue === 'object') {
              conditionFieldValue = FramelixObjectUtils.countKeys(conditionFieldValue)
            } else {
              conditionFieldValue = parseFloat(conditionFieldValue)
            }
            if (conditionRow.type === 'greatherThan') {
              isVisible = conditionFieldValue > requiredValue
            } else if (conditionRow.type === 'greatherThanEqual') {
              isVisible = conditionFieldValue >= requiredValue
            } else if (conditionRow.type === 'lowerThan') {
              isVisible = conditionFieldValue < requiredValue
            } else if (conditionRow.type === 'lowerThanEqual') {
              isVisible = conditionFieldValue <= requiredValue
            }
            break
          case 'empty':
          case 'notEmpty':
            isVisible = conditionFieldValue === null || conditionFieldValue === '' || (typeof conditionFieldValue === 'object' && !FramelixObjectUtils.countKeys(conditionFieldValue))
            if (conditionRow.type === 'notEmpty') {
              isVisible = !isVisible
            }
            break
        }
      }
      field.setVisibilityConditionHiddenStatus(isVisible)
    }
  }

  /**
   * Validate the form
   * @return {Promise<boolean>} True on success, false on any error
   */
  async validate () {
    let success = true

    // hide all validation messages
    this.hideValidationMessage()
    for (let fieldName in this.fields) {
      const field = this.fields[fieldName]
      field.hideValidationMessage()
    }

    for (let fieldName in this.fields) {
      const field = this.fields[fieldName]
      const validation = await field.validate()
      if (validation !== true) {
        success = false
        field.showValidationMessage(validation)
      }
    }
    if (success && this.customValidation) {
      const validation = await this.customValidation()
      if (validation !== true) {
        success = false
        this.showValidationMessage(validation)
      }
    }
    return success
  }

  /**
   * Render the form into the container
   * @return {Promise<void>}
   */
  async render () {
    const self = this
    this.form = $(`<form>`)
    if (!this.autocomplete) {
      this.form.attr('autocomplete', 'off')
    }
    this.form.attr('novalidate', true)
    this.container.empty()
    this.container.toggleClass('framelix-form-sticky-form-buttons', this.stickyFormButtons)
    if (this.label) {
      this.container.append($(`<div class="framelix-form-label"></div>`).html(await FramelixLang.get(this.label)))
    }
    this.container.append(this.form)
    this.container.css('display', 'none')
    $(document.body).append(this.container)

    this.form.attr('id', 'framelix-form-' + this.id)
    this.form.attr('name', 'framelix-form-' + this.id)
    this.form.attr('onsubmit', 'return false')
    if (this.htmlAttributes) {
      this.htmlAttributes.assignToElement(this.form)
    }

    this.inputHiddenSubmitFormName = $('<input type="hidden" value="1">')
    this.inputHiddenSubmitButtonName = $('<input type="hidden" value="1">')
    this.form.append(this.inputHiddenSubmitFormName)
    this.form.append(this.inputHiddenSubmitButtonName)

    const fieldRenderPromises = []
    /** @type {FramelixFormField[]} */
    const positionedFields = []

    for (let name in this.fields) {
      const field = this.fields[name]
      field.form = this
      if (this.readOnly) {
        field.disabled = true
      }
      const row = $('<div class="framelix-form-field-row"></div>').append(field.container)
      row.attr('data-types', field.container.attr('data-types'))
      this.form.append(row)
      field.render()
      fieldRenderPromises.push(field.rendered)
      if (field.positionInForm) {
        positionedFields.push(field)
      }
    }

    if (this.fieldGroups) {
      for (let id in this.fieldGroups) {
        const row = this.fieldGroups[id]
        const storageKey = this.id + '_' + id
        let state = row.defaultState
        if (row.rememberState) {
          state = FramelixLocalStorage.get(storageKey)
          if (state === null) {
            state = row.defaultState
          }
        }
        let groupStartField = null
        let prevGroupField = null
        for (let i = 0; i < row.fieldNames.length; i++) {
          const fieldName = row.fieldNames[i]
          const field = this.fields[fieldName]
          if (field) {
            const rowContainer = field.container.parent()
            if (!groupStartField) {
              groupStartField = field
              rowContainer.before(`<div class="framelix-form-field-group" data-id="${id}" data-storage-key="${storageKey}" data-state="${state ? 1 : 0}" data-remember="${row.rememberState ? '1' : '0'}"><framelix-button theme="light" icon="705">${row.label}</framelix-button></div>`)
            }
            rowContainer.toggleClass('framelix-form-field-group-hidden', !state)
            rowContainer.attr('data-field-group-id', id)
            if (prevGroupField) {
              prevGroupField.container.parent().after(rowContainer)
            }
            prevGroupField = field
          }
        }
      }
    }

    const bottomRow = $(`<div class="framelix-form-row framelix-form-row-bottom"></div>`)
    bottomRow.attr('id', 'framelix-form-row-bottom-' + this.id)
    this.container.append(bottomRow)

    const buttonsCount = FramelixObjectUtils.countKeys(this.buttons)
    const buttonsRow = $(`<div class="framelix-form-buttons" data-buttons="${buttonsCount}"></div>`)
    bottomRow.append(buttonsRow)
    if (buttonsCount) {
      for (let i in this.buttons) {
        const buttonData = this.buttons[i]
        if (this.readOnly && !buttonData.ignoreReadOnly) {
          continue
        }

        const button = $(`<framelix-button>`)
        button.attr('theme', buttonData.color)
        button.attr('data-type', buttonData.type)
        button.attr('data-submit-field-name', buttonData.submitFieldName)
        button.html(buttonData.buttonText)
        if (buttonData.buttonIcon) {
          button.attr('icon', buttonData.buttonIcon)
        }
        if (buttonData.buttonTooltip) {
          button.attr('title', buttonData.buttonTooltip)
        }
        if (buttonData.type === 'submit') {
          button.on('click', function () {
            self.submit($(this).attr('data-submit-field-name'))
          })
        } else if (buttonData.type === 'url') {
          button.on('click', function () {
            if (self.submitRequest) {
              self.submitRequest.abort()
            }
            window.location.href = buttonData.url
          })
        } else if (buttonData.type === 'action') {
          button.attr('data-action', buttonData.action)
        }
        buttonsRow.append(button)
      }
      this.form.on('keydown', function (ev) {
        if ((ev.key === 'Enter' && self.submitWithEnter) || (ev.key.toLowerCase() === 's' && ev.ctrlKey)) {
          buttonsRow.find('[data-type=\'submit\']').first().trigger('click')
          if (ev.ctrlKey) {
            ev.preventDefault()
          }
        }
      })
    }
    this.submitStatusContainer = $(`<div class="framelix-form-submit-status"></div>`)
    bottomRow.append(this.submitStatusContainer)
    this.container.css('display', '')
    if (this.validationMessage !== null) {
      this.showValidationMessage(this.validationMessage)
    }
    this.form.on('focusin', function () {
      self.hideValidationMessage()
    })
    this.form.on('click', '.framelix-form-field-group framelix-button', function () {
      const el = $(this).parent()
      const newState = el.attr('data-state') !== '1'
      const id = el.attr('data-id')
      el.attr('data-state', newState ? '1' : '0')
      self.form.find('.framelix-form-field-row').filter('[data-field-group-id=\'' + id + '\']').toggleClass('framelix-form-field-group-hidden', !newState)
      if (el.attr('data-remember') === '1') {
        FramelixLocalStorage.set(el.attr('data-storage-key'), newState)
      }
    })

    for (let i = 0; i < positionedFields.length; i++) {
      const field = positionedFields[i]
      if (!field.positionInForm || !field.positionInForm.after) {
        continue
      }
      const afterField = self.fields[field.positionInForm.after]
      if (!afterField) {
        continue
      }
      const rowToAttach = afterField.container.parent()
      const oldRow = field.container.parent()
      rowToAttach.attr('data-sizing', field.positionInForm.sizing)
      if (field.positionInForm.columnGrowMe) {
        field.container.css('flex-grow', field.positionInForm.columnGrowMe)
      }
      if (field.positionInForm.columnGrowOther) {
        afterField.container.css('flex-grow', field.positionInForm.columnGrowOther)
      }
      rowToAttach.append(field.container)
      if (!oldRow.children('.framelix-form-field').length) {
        oldRow.remove()
      }
    }

    Promise.all(fieldRenderPromises).then(function () {
      if (self._renderedResolve) {
        self._renderedResolve()
      }
      self._renderedResolve = null
      self.updateFieldVisibility()
      self.form.on(FramelixFormField.EVENT_CHANGE, function () {
        self.updateFieldVisibility()
      })
    })
  }

  /**
   * Submit the form
   * @param {string=} submitButtonName This key will be 1 on submit, which normally indicates the button that is clicked
   * @return {Promise<boolean>} Resolved when submit is done - True indicates form has been submitted, false if not submitted for any reason
   */
  async submit (submitButtonName) {

    // already submitting, skip submit
    if (this.isSubmitting) {
      return false
    }

    // validate the form before submit
    if ((await this.validate()) !== true) {
      return false
    }

    const self = this

    this.inputHiddenSubmitFormName.attr('name', 'framelix-form-' + this.id)
    this.inputHiddenSubmitButtonName.attr('name', 'framelix-form-button-' + (submitButtonName || this.id))

    if (!this.submitAsync) {
      this.setSubmitStatus(true)
      this.form.removeAttr('onsubmit')
      this.form.attr('method', this.submitMethod)
      this.form.attr('target', this.requestOptions.renderTarget && this.requestOptions.renderTarget.newTab ? '_blank' : '_self')
      this.form.attr('action', this.submitUrl || window.location.href)
      this.form[0].submit()
      this.form.attr('onsubmit', 'return false')
      if (this.form.attr('target') === '_blank') {
        setTimeout(function () {
          self.setSubmitStatus(false)
          self.form.trigger(FramelixForm.EVENT_SUBMITTED, { 'submitButtonName': submitButtonName })
        }, 1000)
      }
      return true
    }

    self.setSubmitStatus(true)
    let formData
    if (this.submitAsyncRaw) {
      formData = JSON.stringify(FormDataJson.toJson(this.form[0], { 'includeDisabled': true }))
    } else {
      let values = FormDataJson.toJson(this.form[0], { 'flatList': true, 'includeDisabled': true })
      formData = new FormData()
      for (let i = 0; i < values.length; i++) {
        formData.append(values[i][0], values[i][1])
      }
      for (let fieldName in this.fields) {
        const field = this.fields[fieldName]
        if (field instanceof FramelixFormFieldFile) {
          const files = field.getValue()
          if (files) {
            for (let i = 0; i < files.length; i++) {
              formData.append(fieldName + '[]', files[i])
            }
          }
        }
      }
    }
    this.hideValidationMessage()
    let submitUrl = this.submitUrl
    if (!submitUrl) {
      const tabContent = this.form.closest('.framelix-tab-content')
      if (tabContent.length) {
        const tabData = FramelixTabs.instances[tabContent.closest('.framelix-tabs').attr('data-instance-id')].tabs[tabContent.attr('data-id')]
        if (tabData && tabData.content instanceof FramelixView) {
          submitUrl = tabData.content.getMergedUrl()
        }
      }
    }
    if (!submitUrl) {
      submitUrl = location.href
    }
    this.submitRequest = FramelixRequest.request('post', submitUrl, null, formData, this.submitStatusContainer)
    const request = self.submitRequest
    await request.finished
    self.setSubmitStatus(false)
    self.form.trigger(FramelixForm.EVENT_SUBMITTED, { 'submitButtonName': submitButtonName })

    for (let fieldName in self.fields) {
      const field = self.fields[fieldName]
      field.hideValidationMessage()
    }
    self.hideValidationMessage()

    // if request does handle anything itself, do not proceed handling the request
    const responseCheckHeadersStatus = await request.checkHeaders()
    if (responseCheckHeadersStatus !== 0) {
      return true
    }

    // got no response data, just end here
    const responseData = await request.getJson()
    if (!responseData) {
      return true
    }

    // got error messages, display them
    if (responseData.errorMessages) {
      if (typeof responseData.errorMessages === 'string') {
        // form error message
        this.showValidationMessage(responseData.errorMessages)
      } else {
        // field specific errors
        for (let fieldName in self.fields) {
          const field = self.fields[fieldName] || this
          if (!responseData.errorMessages[fieldName]) {
            continue
          }
          if (field) {
            field.showValidationMessage(responseData.errorMessages[fieldName])
          } else {
            this.showValidationMessage(responseData.errorMessages[fieldName])
          }
        }
      }
    }
    // toast messages
    if (FramelixObjectUtils.hasKeys(responseData.toastMessages)) {
      for (let i = 0; i < responseData.toastMessages.length; i++) {
        FramelixToast.queue.push(responseData.toastMessages[i])
      }
      FramelixToast.showNext()
    }

    if (typeof responseData.buffer === 'string' && responseData.buffer.length) {
      // override response data json to rest of the buffer to use default renderer
      request.requestOptions = this.requestOptions
      request['_responseJson'] = responseData.buffer
      await request.render(this.container[0])
    }

    if (this.executeAfterAsyncSubmit) {
      await new Promise(function (resolve) {
        eval('(async function(){' + self.executeAfterAsyncSubmit + '; resolve();})()')
      })
    }
    return true
  }
}

FramelixInit.late.push(FramelixForm.init)