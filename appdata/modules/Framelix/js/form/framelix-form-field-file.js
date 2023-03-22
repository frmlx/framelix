/**
 * A file upload field
 */
class FramelixFormFieldFile extends FramelixFormField {

  /**
   * The file input
   * @type {Cash}
   */
  inputFile

  /**
   * Is multiple
   * @type {boolean}
   */
  multiple = false

  /**
   * Allowed file types
   * Example: Only allow images, use image/*
   * @type {string|null}
   */
  allowedFileTypes

  /**
   * Files
   * @type {Object<string, {file:File, container:Cash}>}
   */
  files = {}

  /**
   * Files container
   * @type {Cash}
   */
  filesContainer

  /**
   * Min selected files for submitted value
   * @type {number|null}
   */
  minSelectedFiles = null

  /**
   * Max selected files for submitted value
   * @type {number|null}
   */
  maxSelectedFiles = null

  /**
   * Upload btn label
   * @type {string}
   */
  buttonLabel = '__framelix_form_file_pick__'

  /**
   * Instant delete the existing file when user click the delete button
   * Otherwise you must implement a delete functionality
   * @type {boolean}
   */
  instantDelete = false

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    for (let filename in this.files) {
      this.removeFile(filename, false, isUserChange)
    }
    if (FramelixObjectUtils.hasKeys(value)) {
      for (let i in value) this.addFile(value[i], false, isUserChange)
    }
    this.triggerChange(this.inputFile, isUserChange)
  }

  /**
   * Get value for this field
   * Return all newly added files (not from default value)
   * @return {File[]|null}
   */
  getValue () {
    let arr = []
    for (let fileId in this.files) {
      if (!(this.files[fileId].file instanceof File)) continue
      arr.push(this.files[fileId].file)
    }
    return arr.length ? arr : null
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

    const value = FramelixObjectUtils.countKeys(this.getValue())
    if (this.minSelectedFiles !== null) {
      if (value < this.minSelectedFiles) {
        return await FramelixLang.get('__framelix_form_validation_minselectedfiles__', { 'number': this.minSelectedFiles })
      }
    }
    if (this.maxSelectedFiles !== null) {
      if (value > this.maxSelectedFiles) {
        return await FramelixLang.get('__framelix_form_validation_maxselectedfiles__', { 'number': this.maxSelectedFiles })
      }
    }
    return true
  }

  /**
   * Add a file
   * @param {File|Object} file
   * @param {boolean} triggerChange Trigger change event
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  addFile (file, triggerChange, isUserChange = false) {
    if (!this.multiple) {
      for (let filename in this.files) {
        this.removeFile(filename, false)
      }
    }
    const filename = file.name
    let fileId = file.name
    const container = $(`<div class="framelix-form-field-file-file">
        <div class="framelix-form-field-file-file-label">
            <framelix-button theme="light" class="framelix-form-field-file-file-remove" title="__framelix_form_file_delete_queue__" icon="clear"></framelix-button>
            <div class="framelix-form-field-file-file-label-text">
                ${file.url ? '<a href="' + file.url + '">' + filename + '</a>' : filename}
            </div>
            <div class="framelix-form-field-file-file-label-size">${FramelixNumberUtils.filesizeToUnit(file.size, 'mb')}</div>
        </div>    
      </div>`)
    // we have an id, so file already exists
    if (file.id) {
      fileId = file.id
      container.find('.framelix-form-field-file-file-remove').attr('title', '__framelix_form_file_delete_existing__')
      container.attr('data-id', file.id)
      container.attr('data-delete-url', file.deleteUrl)
      container.append(`<input type="hidden" name="${this.name}[${file.id}]" value="1">`)
    }
    container.attr('data-file-internal-id', fileId)
    this.files[fileId] = {
      'file': file,
      'container': container
    }
    this.filesContainer.append(container)
    if (triggerChange) this.triggerChange(this.inputFile, isUserChange)
  }

  /**
   * Remove a file
   * @param {string} fileId
   * @param {boolean} triggerChange Trigger change event
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  removeFile (fileId, triggerChange, isUserChange = false) {
    const fileRow = this.files[fileId]
    if (!fileRow) return
    if (fileRow.uploadRequest) fileRow.uploadRequest.abort()
    fileRow.container.remove()
    delete this.files[fileId]
    if (triggerChange) this.triggerChange(this.inputFile, isUserChange)
  }

  /**
   * Upload all added files per ajax
   * @param {string} jsCallUrl The jscall url to send data to
   * @param {Object=} parameters Additional parameters to send along with the request
   * @return {Promise<FramelixRequest[]>} Resolved when all uploads are done
   */
  async uploadFiles (jsCallUrl, parameters) {
    const arr = []
    for (let fileId in this.files) {
      const req = this.uploadFile(jsCallUrl, fileId, parameters)
      if (req) {
        await req.finished
        arr.push(req)
      }
    }
    return arr
  }

  /**
   * Upload a single fileId
   * @param {string} jsCallUrl The jscall url to send data to
   * @param {string} fileId
   * @param {Object=} parameters Additional parameters to send along with the request
   * @return {FramelixRequest|null}
   */
  uploadFile (jsCallUrl, fileId, parameters) {
    const row = this.files[fileId]
    if (!row.file) return null
    const formData = new FormData()
    formData.append('file', row.file)
    if (parameters) {
      formData.append('parameters', JSON.stringify(parameters))
    }
    const req = FramelixRequest.jsCall(jsCallUrl, formData, row.container)
    row.uploadRequest = req
    req.finished.then(function () {
      row.file = null
      row.container.attr('data-uploaded', '1')
    })
    return req
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.field.html(`      
        <framelix-button block class="framelix-form-field-file-button" icon="file_upload">${this.buttonLabel}</framelix-button>
        <label style="display: none"><input type="file" ${this.disabled ? 'disabled' : ''}></label>
        <div class="framelix-form-field-file-files"></div>
    `)
    if (this.disabled) {
      this.field.children().first().addClass('hidden')
    }
    this.filesContainer = this.field.find('.framelix-form-field-file-files')
    this.inputFile = this.field.find('input[type=\'file\']')
    if (this.allowedFileTypes) this.inputFile.attr('accept', this.allowedFileTypes)
    if (this.multiple) this.inputFile.attr('multiple', true)
    this.inputFile.on('change', function (ev) {
      if (!ev.target.files) return
      ev.stopPropagation()
      for (let i = 0; i < ev.target.files.length; i++) {
        self.addFile(ev.target.files[i], false)
      }
      self.triggerChange(self.inputFile, true)
    })
    this.filesContainer.on('click', '.framelix-form-field-file-file-remove', async function () {
      const fileEntry = $(this).closest('.framelix-form-field-file-file')
      if (fileEntry.attr('data-id')) {
        const deleteUrl = fileEntry.attr('data-delete-url')
        if (self.instantDelete && deleteUrl) {
          if (!await FramelixModal.confirm(await FramelixLang.get('__framelix_delete_sure__')).confirmed) {
            return
          }
          const response = await FramelixRequest.jsCall(deleteUrl).getResponseData()
          if (response === true) {
            fileEntry.remove()
            FramelixToast.success('__framelix_deleted__')
          } else {
            FramelixToast.error(await FramelixLang.get('__framelix_error__', [response]))
          }
          return
        }
        fileEntry.toggleClass('framelix-form-field-file-file-strikethrough')
        const deleteFlag = fileEntry.hasClass('framelix-form-field-file-file-strikethrough')
        fileEntry.find('input').val(!deleteFlag ? '1' : '0')
      } else {
        self.removeFile(fileEntry.attr('data-file-internal-id'), true)
      }
    })
    this.container.on('click', '.framelix-form-field-file-button', function () {
      $(this).next().trigger('click')
    })
    this.container.on('dragover', function (ev) {
      ev.preventDefault()
    })
    this.container.on('drop', function (ev) {
      ev.preventDefault()
      for (let i = 0; i < ev.dataTransfer.files.length; i++) {
        self.addFile(ev.dataTransfer.files[i], false)
      }
      self.triggerChange(self.inputFile, true)
    })
    this.setValue(this.defaultValue)
  }
}

FramelixFormField.classReferences['FramelixFormFieldFile'] = FramelixFormFieldFile