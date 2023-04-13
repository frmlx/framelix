/**
 * Media browser to surf in StorableFile and StorableFolder
 */
class FramelixFormFieldMediaBrowser extends FramelixFormField {

  /**
   * The signed url from the backend to point to the browser
   * @type {string}
   */
  jsCallUrl

  /**
   * The hidden submitting form field
   * @type {Cash}
   */
  input

  /**
   * The current opened browser modal
   * @type {FramelixModal}
   */
  browserModal

  /**
   * Multiple selection
   * @type {boolean}
   */
  multiple = false

  /**
   * Root folder
   * @type {number|null}
   */
  rootFolder = null

  /**
   * Current folder
   * @type {number|null}
   */
  currentFolder = null

  /**
   * The selection info div bellow open browser button
   * @type {Cash}
   */
  selectionInfo

  /**
   * Show max number of thumbnails for the user selected files
   * If more files are selected, it shows ... after the number of thumbs
   * @type {number}
   */
  selectionInfoMaxThumbs = 5

  /**
   * Internal id stored the modal instance id before the first time the browser has opened
   */
  prevModalInstanceId

  /**
   * Get metadata for given storable file id
   * @param {string|number} id
   * @return {Promise<Object>}
   */
  async getMetadataForId (id) {
    return this.apiRequest('metadata', { 'id': id }).getResponseData()
  }

  /**
   * Open browser modal
   * @returns {Promise<void>}
   */
  async openBrowser () {
    const self = this
    let replaceId = ''
    let lastSelectedEntry = null
    if (typeof this.prevModalInstanceId === 'undefined') {
      this.prevModalInstanceId = FramelixModal.instances.length
    }
    // delete all modals create by the browser
    for (let i = this.prevModalInstanceId; i < FramelixModal.instances.length; i++) {
      await FramelixModal.instances[i].destroy()
    }
    const request = this.apiRequest('browser')
    this.browserModal = FramelixModal.show({
      bodyContent: request
    })
    this.browserModal.created.then(async function () {
      const rightWindow = self.browserModal.container.find('.framelix-mediabrowser-right-window')
      const filesContainer = self.browserModal.container.find('.framelix-mediabrowser-files')
      const filesTableContainer = self.browserModal.container.find('.framelix-mediabrowser-files-table')
      const viewOptionContainer = self.browserModal.container.find('.framelix-mediabrowser-view')
      const viewOptionInput = viewOptionContainer.find('input')
      const storageKey = 'framelix_media_browser_view'
      const storedView = FramelixLocalStorage.get(storageKey)
      if (storedView) {
        viewOptionInput.val(storedView)
      }
      viewOptionInput.on('input', function () {
        const cw = rightWindow.width()
        let maxEntries = Math.floor(cw / 100)
        if (maxEntries < 1) maxEntries = 1
        const maxRange = parseFloat(viewOptionInput.attr('max'))
        const inputVal = parseFloat(viewOptionInput.val())
        filesTableContainer.toggleClass('hidden', !(inputVal <= 0.18))
        filesContainer.toggleClass('hidden', (inputVal <= 0.18))
        FramelixLocalStorage.set(storageKey, inputVal)
        let size = maxRange - inputVal + 0.1
        self.browserModal.container.css('--entries-per-row', Math.ceil(maxEntries * size))
      })
      viewOptionInput.trigger('input')
      self.updateSelectionInfo()
    })
    this.browserModal.destroyed.then(function () {
      self.browserModal = null
    })
    if (!this.browserModal.bodyContainer.hasClass('framelix-mediabrowser-modal')) {
      this.browserModal.bodyContainer.addClass('framelix-mediabrowser-modal')
      this.browserModal.container.on('click', '[data-action]', async function (ev) {
        ev.stopPropagation()
        const action = $(this).attr('data-action')
        const clickEl = $(this)
        switch (action) {
          case 'delete': {
            FramelixPopup.destroyAll()
            if (await FramelixModal.confirm(clickEl.attr('data-confirm-message')).confirmed) {
              await FramelixRequest.jsCall(clickEl.attr('data-delete-url')).checkHeaders()
              self.openBrowser()
              FramelixToast.success('__framelix_deleted__')
            }
          }
            break
          case 'rename': {
            FramelixPopup.destroyAll()
            const newName = await FramelixModal.prompt(await FramelixLang.get('__framelix_mediabrowser_rename__'), JSON.parse(clickEl.attr('data-name'))).promptResult
            if (newName) {
              await FramelixRequest.jsCall(clickEl.attr('data-store-url'), { 'value': newName }).checkHeaders()
              self.openBrowser()
              FramelixToast.success('__framelix_saved__')
            }
          }
            break
        }
      })
      this.browserModal.bodyContainer.on('click', '[data-action]', async function (ev) {
        ev.stopPropagation()
        const action = $(this).attr('data-action')
        const clickEl = $(this)
        switch (action) {
          case 'select': {
            const entry = $(this).closest('[data-id]')
            const entryId = entry.attr('data-id')
            const flag = self.toggleValue(entryId, true)
            if (ev.shiftKey && lastSelectedEntry && lastSelectedEntry !== entry) {
              let elA
              let elB
              if (lastSelectedEntry.nextAll(entry).length) {
                elA = lastSelectedEntry
                elB = entry
              } else if (lastSelectedEntry.prevAll(entry).length) {
                elA = entry
                elB = lastSelectedEntry
              }
              const values = []
              elA.nextUntil(elB).add(elA).add(elB).each(function () {
                values.push($(this).attr('data-id'))
              })
              if (flag) {
                self.addValue(values, true)
              } else {
                self.removeValue(values, true)
              }
              lastSelectedEntry = entry
            } else {
              lastSelectedEntry = entry
            }
          }
            break
          case 'edit-file':
          case 'edit-folder': {
            const entry = $(this).closest('[data-id]')
            const entryId = entry.attr('data-id')
            clickEl.removeAttr('data-tooltip')
            clickEl.removeAttr('title')
            FramelixPopup.destroyAll()
            FramelixPopup.show(this, self.apiRequest(action + '-list', { 'id': entryId }), {
              color: document.body,
              placement: 'right-start'
            })
          }
            break
          case 'edit-selection-info':
            self.showSelectionSortModal()
            break
          case 'unset-selection':
            self.setValue(null, true)
            break
          case 'openfolder':
            self.currentFolder = $(this).attr('data-id')
            self.openBrowser()
            break
          case 'createfolder':
            const result = await FramelixModal.prompt(await FramelixLang.get('__framelix_mediabrowser_foldername__')).promptResult
            if (result) {
              await self.apiRequest(action, {
                'value': result
              }).checkHeaders()
              self.openBrowser()
            }
            break
        }
      })
      this.browserModal.bodyContainer.on(FramelixFormField.EVENT_CHANGE_USER, 'input[type=\'file\']', async function (ev) {
        /** @type {FramelixFormFieldFile} */
        const field = FramelixFormField.getFieldByName(self.browserModal.bodyContainer, 'upload')
        const params = { 'currentFolder': self.currentFolder, 'replaceId': replaceId }
        const requests = await field.uploadFiles(self.jsCallUrl, params)
        for (const request of requests) {
          const result = await request.getResponseData()
          FramelixToast[result.type](result.message)
        }
        self.openBrowser()
      })
    }
  }

  /**
   * Check if a given value is selected
   * @param {*} value
   * @return {boolean}
   */
  hasValue (value) {
    if (typeof value === 'string') value = parseInt(value)
    const v = this.getValueObject()
    return v.selection.indexOf(value) > -1
  }

  /**
   * Toggle a value, remove it when exists, add it when not exists
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   * @return {boolean} Return true when added, return false when removed
   */
  toggleValue (value, isUserChange = false) {
    if (!this.hasValue(value)) {
      this.addValue(value, isUserChange)
      return true
    } else {
      this.removeValue(value, isUserChange)
      return false
    }
  }

  /**
   * Add a selected value
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  addValue (value, isUserChange = false) {
    if (!Array.isArray(value)) value = [value]
    const v = this.getValueObject()
    for (let addValue of value) {
      if (typeof addValue === 'string') addValue = parseInt(addValue)
      if (!this.hasValue(addValue)) {
        v.selection.push(addValue)
      }
    }
    this.setValue(v, isUserChange)
  }

  /**
   * Remove a selected value
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  removeValue (value, isUserChange = false) {
    if (!Array.isArray(value)) value = [value]
    const v = this.getValueObject()
    for (let removeValue of value) {
      if (typeof removeValue === 'string') removeValue = parseInt(removeValue)
      const index = v.selection.indexOf(removeValue)
      if (index > -1) {
        v.selection.splice(index, 1)
      }
    }
    this.setValue(v, isUserChange)
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    const oldVal = this.input.val()
    if (!this.multiple && value && FramelixObjectUtils.countKeys(value.selection) > 1) {
      value.selection = [value.selection.pop()]
    }
    if (!FramelixObjectUtils.hasKeys(value) || !FramelixObjectUtils.hasKeys(value.selection)) {
      this.input.val('')
    } else {
      this.input.val(JSON.stringify(value))
    }
    const newVal = this.input.val()
    if (!this.input.data('media-browser-first-value-set') || oldVal !== newVal) {
      this.input.data('media-browser-first-value-set', 1)
      this.updateSelectionInfo()
    }
    if (oldVal !== newVal) {
      this.triggerChange(this.input, isUserChange)
    }
  }

  async getSelectionInfo () {
    const val = this.input.val()
    const self = this
    return self.apiRequest('selectioninfo', {
      'value': val ? JSON.parse(val) : null,
      'selectionInfoMaxThumbs': this.selectionInfoMaxThumbs
    }).getResponseData()
  }

  async updateSelectionInfo () {
    const self = this
    const selectionInfo = await this.getSelectionInfo()
    if (self.browserModal) {
      self.browserModal.container.find('.framelix-mediabrowser-entry, .framelix-mediabrowser-files-table tr[data-id]').each(function () {
        this.toggleAttribute('data-selected', self.hasValue($(this).attr('data-id')))
      })
      self.browserModal.container.find('.framelix-mediabrowser-selected-info').html(selectionInfo)
    }
    this.selectionInfo.html(selectionInfo)
  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.input.val()
  }

  /**
   * Get value as a js object instead of json str
   * @returns {any|{sortedFiles: *[], selection: *[]}}
   */
  getValueObject () {
    let v = this.input.val()
    v = v.length ? JSON.parse(v) : { 'selection': [], 'sortedFiles': [] }
    return v
  }

  async showSelectionSortModal () {
    const self = this
    const modal = FramelixModal.show({
      bodyContent: this.apiRequest('edit-selection-info', { 'value': this.getValue() }),
      maxWidth: 400
    })
    await modal.created
    FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable').then(function () {
      function saveSort () {
        const ids = []
        const entries = sortContainer.children()
        entries.each(function () {
          ids.push(parseInt($(this).attr('data-id')))
          $(this).find('input').val(ids.length)
        })
        const v = self.getValueObject()
        v.sortedFiles = ids
        self.setValue(v)
      }

      const sortContainer = modal.bodyContainer.find('.framelix-mediabrowser-edit-sort-container')
      sortContainer.on('blur keydown', 'input', function (ev) {
        if (ev.key && ev.key !== 'Enter') return
        const entries = sortContainer.children()
        const entryNow = $(ev.target).closest('[data-id]')
        const v = parseInt($(ev.target).val())
        if (!isNaN(v)) {
          const replaceWith = v <= 0 ? null : entries.eq(v - 1)
          if (!replaceWith || !replaceWith.length) {
            if (v <= 0) {
              sortContainer.prepend(entryNow)
            } else {
              sortContainer.append(entryNow)
            }
          } else {
            replaceWith.after(entryNow)
          }
          saveSort()
        }
      })
      new Sortable(sortContainer[0], {
        'onSort': function () {
          saveSort()
        }
      })
    })
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    const self = this
    await super.renderInternal()
    const btn = $('<framelix-button theme="primary" icon="perm_media">__framelix_mediabrowser_open__</framelix-button>')
    this.field.append(btn)
    this.selectionInfo = $('<div class="framelix-mediabrowser-selected-info"></div>')
    this.field.append(this.selectionInfo)
    btn.on('click', function () {
      self.openBrowser()
    })
    this.selectionInfo.on('click', '[data-action]', function () {
      const action = $(this).attr('data-action')
      switch (action) {
        case 'unset-selection':
          self.setValue(null, true)
          break
        case 'edit-selection-info':
          self.showSelectionSortModal()
          break
      }

    })
    this.input = $('<input type="hidden">')
    this.input.attr('name', this.name)
    this.field.append(this.input)
    await this.setValue(this.defaultValue)
  }

  /**
   * Api request
   * @param {string} action
   * @param {Object=} data
   * @param {boolean|Cash=} showProgressBar Show progress bar at top of page or in given container
   * @return {FramelixRequest}
   */
  apiRequest (action, data, showProgressBar) {
    data = data || {}
    if (data instanceof FormData) {
      data.append('action', action)
      data.append('currentFolder', this.currentFolder ?? '')
    } else {
      data.currentFolder = this.currentFolder
      data.action = action
    }
    return FramelixRequest.jsCall(this.jsCallUrl, data, showProgressBar)
  }
}

FramelixFormField.classReferences['FramelixFormFieldMediaBrowser'] = FramelixFormFieldMediaBrowser