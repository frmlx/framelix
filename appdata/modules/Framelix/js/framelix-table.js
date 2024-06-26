/**
 * Framelix html table
 */
class FramelixTable {

  /**
   * Event is triggered when user has sorted table by clicking column headers
   * @type {string}
   */
  static EVENT_COLUMNSORT_SORT_CHANGED = 'framelix-table-columnsort-sort-changed'

  /**
   * Event is triggered when dragsort is enabled an the user has changed the row sort
   * @type {string}
   */
  static EVENT_DRAGSORT_SORT_CHANGED = 'framelix-table-dragsort-sort-changed'

  /**
   * Event is triggered when user has sorted table rows by any available sortoption
   * @type {string}
   */
  static EVENT_SORT_CHANGED = 'framelix-table-sort-changed'

  /**
   * No special behaviour
   * @type {string}
   */
  static COLUMNFLAG_DEFAULT = 'default'

  /**
   * An icon column
   * @type {string}
   */
  static COLUMNFLAG_ICON = 'icon'

  /**
   * Use smallest width possible
   * @type {string}
   */
  static COLUMNFLAG_SMALLWIDTH = 'smallwidth'

  /**
   * Use a smaller font
   * @type {string}
   */
  static COLUMNFLAG_SMALLFONT = 'smallfont'

  /**
   * Ignore sort for this column
   * @type {string}
   */
  static COLUMNFLAG_IGNORESORT = 'ignoresort'

  /**
   * Ignore editurl click on this column
   * @type {string}
   */
  static COLUMNFLAG_IGNOREURL = 'ignoreurl'

  /**
   * Remove the column if all cells in the tbody are empty
   * @type {string}
   */
  static COLUMNFLAG_REMOVE_IF_EMPTY = 'removeifempty'

  /**
   * All instances
   * @type {FramelixTable[]}
   */
  static instances = []

  /**
   * The sorter worker
   * @type {Worker|null}
   */
  static sorterWorker = null

  /**
   * The whole container
   * @type {Cash}
   */
  container

  /**
   * The <table>
   * @type {Cash}
   */
  table

  /**
   * Id for the table
   * Default is random generated in constructor
   * @type {string}
   */
  id

  /**
   * The rows internal data
   * Grouped by thead/tbody/tfoot
   * @type {*}
   */
  rows = {}

  /**
   * The column order in which order the columns are displayed
   * Automatically set by first added row
   * @var string[]
   */
  columnOrder = []

  /**
   * Column flags
   * Automatically set by added cell values
   * Key is column name, value is self::COLUMNFLAG_*
   * @var {Object<string, string[]>}
   */
  columnFlags = {}

  /**
   * Is the table sortable
   * @type {boolean}
   */
  sortable = true

  /**
   * The initial sort
   * @type {string[]|null}
   */
  initialSort = null

  /**
   * Remember the sort settings in client based on the tables id
   * @type {boolean}
   */
  rememberSort = true

  /**
   * Add a checkbox column at the beginning
   * @type {boolean}
   */
  checkboxColumn = false

  /**
   * Add a column at the beginning, where the user can sort the table rows by drag/drop
   * @type {boolean}
   */
  dragSort = false

  /**
   * Let user store the sorting in the database for the new sorted storables
   * @type {boolean}
   */
  storableSort = false

  /**
   * The js call url to sort the sorted storable data
   * @type {string|null}
   */
  storableSortJsCallUrl = null

  /**
   * General flag if the generated table has deletable button for a storable row
   * If true then it also depends on the storable getDeleteUrl return value
   * @var {boolean}
   */
  storableDeletable = true

  /**
   * If a row has an url attached, open in in a new tab instead of current tab
   * @var {boolean}
   */
  urlOpenInNewTab = false

  /**
   * The current sort
   * @type {string[]|null}
   */
  currentSort = null

  /**
   * Include some html before <table>
   * @type {string|null}
   */
  prependHtml = null

  /**
   * Include some html before <table>
   * @type {string|null}
   */
  appendHtml = null

  /**
   * Escape html inside table cells
   * true = escape every cell
   * false = escape no cell
   * array = escape only given cell names
   * @type {string[]|boolean}
   */
  escapeHtml = true

  /**
   * Row url open method
   * default = Same browser window or new window when user click with middle mouse button
   * newwindow = New browser window (tab)
   * currenttab = If table is in a FramelixTab, then load it into this tab - If is no tab, it falls back to default
   * currentmodal = If table is in a FramelixModal, then load it into this modal - If is no modal, it falls back to default
   * newmodal = Opens the row url in a new FramelixModal
   * @type {string}
   */
  rowUrlOpenMethod = 'default'

  /**
   * A promise that is resolved when the table is completely rendered
   * @type {Promise}
   */
  rendered

  /**
   * The resolve function to resolve the rendered promise
   * @type {function}
   * @private
   */
  _renderedResolve

  /**
   * Get instance by id
   * @param {string} id
   * @return {FramelixTable|null}
   */
  static getById (id) {
    for (let i = 0; i < FramelixTable.instances.length; i++) {
      if (FramelixTable.instances[i].id === id) {
        return FramelixTable.instances[i]
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

    this.id = 'table-' + FramelixRandom.getRandomHtmlId()
    FramelixTable.instances.push(this)
    this.container = $('<div>')
    this.container.addClass('framelix-table')
    this.container.attr('data-instance-id', FramelixTable.instances.length - 1)
  }

  /**
   * Sort the table
   * @return {Promise<void>} When sorting is finished
   */
  async sort () {
    const self = this
    self.updateHeaderSortingInfoLabels()
    self.currentSort = self.currentSort || FramelixLocalStorage.get('framelix-table-user-sort') || self.initialSort
    if (!self.currentSort) return

    const thead = self.table ? self.table.children('thead') : null
    if (thead) {
      thead.addClass('framelix-pulse')
    }

    return new Promise(function (resolve) {
      if (!FramelixTable.sorterWorker) {
        FramelixTable.sorterWorker = new Worker(FramelixConfig.compiledFileUrls['Framelix']['js']['table-sorter-serviceworker'])
      }
      FramelixTable.sorterWorker.onmessage = async function (e) {
        if (self.rows.tbody) {
          const newRows = []
          for (let i = 0; i < e.data.length; i++) {
            let rowIndex = e.data[i]
            newRows.push(self.rows.tbody[rowIndex])
          }
          self.rows.tbody = newRows
          self.updateTbodyDomSort()
          // it is possible that this function is called before the table is rendered, in this case we cannot sort the dom
          if (self.table) {
            const tbody = self.table.children('tbody')[0]
            for (let i = 0; i < self.rows.tbody.length; i++) {
              const el = self.rows.tbody[i].el
              if (el) tbody.appendChild(el)
            }
          }
        }
        if (thead) {
          thead.removeClass('framelix-pulse')
        }
        resolve()
      }

      let rows = []
      if (self.rows.tbody) {
        for (let j = 0; j < self.rows.tbody.length; j++) {
          let sortValues = []
          const row = self.rows.tbody[j]
          for (let i = 0; i < self.currentSort.length; i++) {
            let sortCellName = self.currentSort[i].substr(1)
            let sortValue = row['sortValues'][sortCellName]
            if (sortValue === null || sortValue === undefined) {
              sortValue = row['cellValues'][sortCellName]
            }
            // table cells
            if (sortValue instanceof FramelixTableCell) {
              sortValue = sortValue.sortValue
            }
            sortValues.push(sortValue)
          }
          rows.push({ 'rowIndex': j, 'sortValues': sortValues })
        }
      }
      FramelixTable.sorterWorker.postMessage({ 'sortSettings': self.currentSort, 'rows': rows })
    })
  }

  /**
   * Update header cells dom depending on current sort
   */
  updateHeaderSortingInfoLabels () {
    const theadCells = this.table ? this.table.children('thead').children().first().children('th') : null
    if (theadCells) {
      theadCells.find('.framelix-table-header-sort-info-number, .framelix-table-header-sort-info-text').empty()
      if (this.currentSort) {
        for (let i = 0; i < this.currentSort.length; i++) {
          const dir = this.currentSort[i].substr(0, 1)
          const cellName = this.currentSort[i].substr(1)
          const cell = theadCells.filter('[data-column-name=\'' + CSS.escape(cellName) + '\']')
          cell.addClass('framelix-table-header-sort')
          cell.html(`<div class="framelix-table-header-sort-info-number">${i + 1}</div><div class="framelix-table-header-sort-info-text">${dir === '+' ? 'A-Z' : 'Z-A'}</div>`)
        }
      }
    }
  }

  /**
   * Just resort tbody dom based on rows data sort
   * @return {boolean} True when sort has been changed
   */
  updateTbodyDomSort () {
    // it is possible that this function is called before the table is rendered, in this case we cannot sort the dom
    if (!this.table || !this.rows.tbody) return false
    let sortHasChanged = false
    const tbody = this.table.children('tbody')[0]
    const childs = Array.from(tbody.children)
    for (let i = 0; i < this.rows.tbody.length; i++) {
      const el = this.rows.tbody[i].el
      if (!sortHasChanged && i !== childs.indexOf(el)) {
        sortHasChanged = true
      }
      if (el) tbody.appendChild(el)
    }
    if (sortHasChanged) {
      this.table.trigger(FramelixTable.EVENT_COLUMNSORT_SORT_CHANGED)
    }
  }

  /**
   * Open row url depending on the table settings
   * @param {string} url
   * @param {string=} forceMethod Force open method
   * @return {Promise}
   */
  async openRowUrl (url, forceMethod) {
    let method = forceMethod || this.rowUrlOpenMethod
    switch (method) {
      case 'newmodal':
      case 'currentmodal': {
        let modal
        if (method === 'currentmodal') {
          const modalContainer = this.container.closest('.framelix-modal')
          if (!modalContainer.length) {
            window.location.href = url
            return
          }
          modal = FramelixModal.instances[modalContainer.attr('data-instance-id')]
        }
        const result = await FramelixRequest.request('get', url, null, null, true)
        if (modal) {
          modal.bodyContainer.html((await result.getJson()).content || '')
        } else {
          FramelixModal.request('get', url, null, null, true)
        }
      }
        break
      case 'currenttab': {
        const tab = this.container.closest('.framelix-tab-content')
        if (!tab.length) {
          window.location.href = url
          return
        }
        const tabData = FramelixTabs.instances[tab.closest('.framelix-tabs').attr('data-instance-id')].tabs[tab.attr('data-id')]
        if (tabData && tabData.content instanceof FramelixView) {
          tabData.content.url = url
        }
        const result = await FramelixRequest.request('get', url, null, null, true)
        tab.html((await result.getJson()).content || '')
      }
        break
      case 'default':
        window.location.href = url
        break
      case 'newwindow':
        window.open(url)
        break
    }
  }

  /**
   * Render the table into the container
   * @return {Promise<void>}
   */
  async render () {
    const self = this

    // initially sort tbody data before creating the table for performance boost
    if (this.sortable) {
      if (this.rememberSort) {
        const rememberedSort = FramelixLocalStorage.get(this.id + '-table-sort')
        if (rememberedSort) {
          this.initialSort = rememberedSort
        }
      }
      this.sort()
    }

    // completely building the table by hand, because this is the most performant way
    // so that we can handle big tables with ease
    let tableHtml = ''
    if (this.prependHtml) tableHtml = this.prependHtml
    tableHtml += `<table id="${this.id}">`
    let canDragSort = this.dragSort && FramelixObjectUtils.hasKeys(this.rows.tbody, 2)

    let removeEmptyCells = {}
    for (let i in this.columnFlags) {
      for (let j in this.columnFlags[i]) {
        if (this.columnFlags[i][j] === FramelixTable.COLUMNFLAG_REMOVE_IF_EMPTY) {
          removeEmptyCells[i] = true
        }
      }
    }
    removeEmptyCells['_deletable'] = true

    if (this.sortable && this.rows['thead'] && this.rows['thead'][0]) {
      // add a row for sorting information
      const row = Object.assign({}, this.rows['thead'][0])
      row.cellValues = {}
      row.htmlAttributes = new FramelixHtmlAttributes()
      row.htmlAttributes.addClass('framelix-table-row-sort-info')
      this.rows['thead'].unshift(row)
    }

    const convertValue = async function (level, cellValue, rowGroup, columnName, cellAttributes, removeEmptyCells) {
      if (cellValue === '' || cellValue === null || cellValue === undefined) {
        cellValue = ''
      }
      if (cellValue instanceof FramelixTableCell) {
        cellValue = cellValue.stringValue
      } else if (typeof cellValue === 'object') {
        let str = ''
        for (let i in cellValue) {
          str += '<div class="array-value" data-key="' + FramelixStringUtils.htmlEscape(i) + '">' + await convertValue(level + 1, cellValue[i], rowGroup, columnName, cellAttributes, removeEmptyCells) + '</div>'
        }
        cellValue = str
      } else {
        cellValue = rowGroup === 'thead' ? await FramelixLang.get(cellValue) : cellValue
        if (typeof cellValue !== 'string') cellValue = (cellValue ?? '').toString()
        if (self.escapeHtml === true || (Array.isArray(self.escapeHtml) && self.escapeHtml.indexOf(columnName) > -1)) {
          cellValue = FramelixStringUtils.htmlEscape(cellValue).replace(/\n/g, '<br/>')
        }
      }
      if (level === 0) {
        if (rowGroup === 'thead') cellValue = `<div class="framelix-tableheader-text">${cellValue}</div>`
        if (self.sortable && rowGroup === 'thead' && !cellAttributes.get('data-flag-ignoresort')) {
          cellAttributes.set('tabindex', '0')
        }
        if (rowGroup === 'thead') cellValue = `<div class="framelix-table-cell-header">${cellValue}</div>`
        if (removeEmptyCells[columnName] && cellValue !== '' && rowGroup === 'tbody') {
          removeEmptyCells[columnName] = false
        }
      }
      return cellValue
    }

    for (let rowGroup in this.rows) {
      tableHtml += `<${rowGroup}>`
      const cellType = rowGroup === 'thead' ? 'th' : 'td'
      for (let i = 0; i < this.rows[rowGroup].length; i++) {
        const row = this.rows[rowGroup][i]
        if (!row.cellValues) row.cellValues = {}
        let rowAttributes = row.htmlAttributes || new FramelixHtmlAttributes()
        rowAttributes.set('data-row-key', i)
        if (rowAttributes.get('data-url')) {
          rowAttributes.set('tabindex', '0')
          if (rowAttributes.get('data-url').replace(/\#.*/g, '') === window.location.href.replace(/\#.*/g, '')) {
            rowAttributes.addClass('framelix-table-row-highlight')
          }
        }
        tableHtml += '<tr ' + rowAttributes.toString() + '>'
        if (canDragSort) {
          let cellAttributes = new FramelixHtmlAttributes()
          cellAttributes.setStyle('width', '0%')
          cellAttributes.set('data-column-name', '_dragsort')
          cellAttributes.set('data-flag-ignoresort', '1')
          cellAttributes.set('data-flag-ignoreurl', '1')
          cellAttributes.set('data-flag-icon', '1')
          let cellValue = ''
          if (rowGroup === 'tbody') {
            cellValue = '<framelix-button icon="70f" title="__framelix_table_dragsort__"></framelix-button>'
          }
          if (rowGroup === 'thead') cellValue = `<div class="framelix-table-cell-header">${cellValue}</div>`
          tableHtml += '<' + cellType + ' ' + cellAttributes.toString() + '>'
          tableHtml += cellValue
          tableHtml += '</' + cellType + '>'
        }
        if (this.checkboxColumn) {
          let cellAttributes = new FramelixHtmlAttributes()
          cellAttributes.set('data-column-name', '_checkbox')
          cellAttributes.set('data-flag-ignoresort', '1')
          cellAttributes.set('data-flag-ignoreurl', '1')
          cellAttributes.set('data-flag-icon', '1')

          let cellValue = '<framelix-button theme="transparent"><input type="checkbox" name="_checkbox" value="' + i + '"></framelix-button>'
          if (rowGroup === 'thead') cellValue = `<div class="framelix-table-cell-header">${cellValue}</div>`
          if (rowGroup === 'thead' && i !== 0) cellValue = ''
          tableHtml += '<' + cellType + ' ' + cellAttributes.toString() + '>'
          tableHtml += cellValue
          tableHtml += '</' + cellType + '>'
        }
        for (let j = 0; j < this.columnOrder.length; j++) {
          const columnName = this.columnOrder[j]
          let cellAttributes = row.cellAttributes ? row.cellAttributes[columnName] : null
          if (!cellAttributes) cellAttributes = new FramelixHtmlAttributes()
          cellAttributes.set('data-column-name', columnName)
          if (this.columnFlags[columnName]) {
            for (let i in this.columnFlags[columnName]) {
              const flag = this.columnFlags[columnName][i]
              cellAttributes.set('data-flag-' + flag, '1')
            }
          }
          let cellValue = row.cellValues[columnName] || ''
          if (typeof cellValue === 'string' && cellValue.startsWith('<framelix-button')) {
            cellAttributes.set('data-flag-ignoresort', '1')
            cellAttributes.set('data-flag-ignoreurl', '1')
            cellAttributes.set('data-flag-icon', '1')
          }
          cellValue = await convertValue(0, cellValue, rowGroup, columnName, cellAttributes, removeEmptyCells)
          tableHtml += '<' + cellType + ' ' + cellAttributes.toString() + '>'
          tableHtml += cellValue
          tableHtml += '</' + cellType + '>'
        }
        tableHtml += '</tr>'
      }
      tableHtml += `</${rowGroup}>`
    }
    if (this.appendHtml) tableHtml += this.appendHtml
    self.container[0].innerHTML = tableHtml
    // attach elements to internal rows data
    self.table = self.container.children('table')
    for (let columnName in removeEmptyCells) {
      if (removeEmptyCells[columnName]) {
        self.table.children().children('tr').children('td,th').filter('[data-column-name=\'' + columnName + '\']').remove()
      }
    }
    const tbody = this.table.children('tbody')[0]
    if (tbody) {
      for (let i = 0; i < tbody.childNodes.length; i++) {
        this.rows.tbody[i].el = tbody.childNodes[i]
      }
    }
    // update header cells dom
    this.updateHeaderSortingInfoLabels()

    // bind checkbox clicks
    if (this.checkboxColumn) {
      self.table.on('change', 'thead th[data-column-name=\'_checkbox\'] input', function () {
        $(tbody).children().children('td[data-column-name=\'_checkbox\']').find('input').prop('checked', this.checked)
      })
    }
    // bind open url    
    let mouseDownRow = null
    self.table.on('keydown', 'tr[data-url]', function (ev) {
      if (ev.key === 'Enter') {
        ev.preventDefault()
        let url = $(this).attr('data-url')
        if (ev.ctrlKey && self.rowUrlOpenMethod === 'default') {
          self.openRowUrl(url, 'newwindow')
        } else {
          self.openRowUrl(url)
        }
      }
    })
    self.table.on('mousedown', 'tr[data-url]', function (ev) {
      mouseDownRow = this
      // middle mouse button, stop browser default behaviour (scrolling)
      if (ev.which === 2) {
        ev.preventDefault()
      }
    })
    self.table.on('mouseup', 'tr[data-url]', function (ev) {
      if (mouseDownRow !== this) return
      // ignore any other mouse button than left and middle mouse click
      if (ev.which && ev.which > 2) return
      const newTab = ev.which === 2 || self.urlOpenInNewTab
      // clicking inside some specific elements, than we ignore edit url
      let target = $(ev.target)
      let url = $(this).attr('data-url')
      if (
        target.is('a') ||
        target.is('input,select,textarea') ||
        target.attr('data-flag-ignoreurl') === '1' ||
        target.attr('onclick') ||
        target.closest('a').length ||
        target.closest('td').attr('data-flag-ignoreurl') === '1'
      ) {
        return
      }
      // a text has been selected, do not open edit url
      if (!ev.touches && !newTab && window.getSelection().toString().length > 0) {
        return
      }
      if (newTab && self.rowUrlOpenMethod === 'default') {
        self.openRowUrl(url, 'newwindow')
        return
      }
      self.openRowUrl(url)
    })
    // bind all available sort events to one catching event
    this.table.on(FramelixTable.EVENT_DRAGSORT_SORT_CHANGED + ' ' + FramelixTable.EVENT_COLUMNSORT_SORT_CHANGED, async function () {
      self.table.trigger(FramelixTable.EVENT_SORT_CHANGED)
      if (self.storableSort) {
        if (self.container.children('.framelix-table-savesort').length) return
        FramelixToast.info(await FramelixLang.get('__framelix_table_savesort_tooltip__'))
        const btn = $(`<framelix-button theme="primary" class="framelix-table-savesort" icon="718">__framelix_table_savesort__</framelix-button>`)
        self.container.append(btn)
        btn.on('click', async function () {
          Framelix.showProgressBar(1)
          btn.addClass('framelix-pulse').attr('disabled', true)
          let data = []
          self.table.children('tbody').children().each(function () {
            const row = [this.getAttribute('data-id')]
            const connId = this.getAttribute('data-connection-id')
            if (connId) row.push(connId)
            data.push(row)
          })
          const apiResult = await FramelixRequest.jsCall(self.storableSortJsCallUrl, {
            'data': data
          }).getResponseData()
          Framelix.showProgressBar(null)
          if (apiResult === true) {
            btn.remove()
            FramelixToast.success('__framelix_table_savesort_saved__')
          }
        })
      }
    })
    // bind dragsort actions
    if (canDragSort) {
      FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable').then(function () {
        new Sortable(self.table.children('tbody')[0], {
          'handle': 'td[data-column-name=\'_dragsort\']',
          'onSort': function () {
            self.table.trigger(FramelixTable.EVENT_DRAGSORT_SORT_CHANGED)
          }
        })
      })
    }
    // bind sortable actions
    if (this.sortable) {
      self.container.addClass('framelix-table-sortable')
      self.table.on('click keydown', 'th:not([data-flag-ignoresort])', async function (ev) {
        if (ev.type === 'keydown') {
          if (ev.key !== ' ') {
            return
          }
          ev.preventDefault()
        }
        // reset the sort with pressed ctrl key
        if (ev.ctrlKey) {
          self.currentSort = null
          FramelixLocalStorage.remove(self.id + '-table-sort')
          if (self.rows.tbody) {
            self.rows.tbody.sort(function (a, b) {
              return a.rowKeyInitial > b.rowKeyInitial ? 1 : -1
            })
            self.updateHeaderSortingInfoLabels()
            self.updateTbodyDomSort()
          }
          return
        }
        if (!self.currentSort) self.currentSort = []
        const cellName = $(this).attr('data-column-name')
        let flippedCell = null
        for (let i = 0; i < self.currentSort?.length; i++) {
          // just flip if the cell is already sorted
          const sortCellName = self.currentSort[i].substr(1)
          if (sortCellName === cellName) {
            flippedCell = (self.currentSort[i].substr(0, 1) === '+' ? '-' : '+') + sortCellName
            self.currentSort[i] = flippedCell
            break
          }
        }
        if (!ev.shiftKey) {
          self.currentSort = [flippedCell || '+' + cellName]
        } else if (!flippedCell) {
          self.currentSort.push('+' + cellName)
        }
        FramelixLocalStorage.set(self.id + '-table-sort', self.currentSort)
        self.sort()
      })
    }
    if (this._renderedResolve) {
      this._renderedResolve()
      this._renderedResolve = null
    }
  }
}