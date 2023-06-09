/**
 * Framelix tabs
 */
class FramelixTabs {

  /**
   * Triggered when a tabs content have been rendered
   * @type {string}
   */
  static EVENT_TAB_CONTENT_RENDERED = 'framelix-tabs-rendered'

  /**
   * All instances
   * @type {FramelixTabs[]}
   */
  static instances = []

  /**
   * The whole container
   * @type {Cash}
   */
  container

  /**
   * The tabs id
   * @type {string}
   */
  id

  /**
   * The tabs data
   * @type {{}}
   */
  tabs = {}

  /**
   * The container for tab buttons
   * @type {Cash}
   */
  buttonContainer

  /**
   * The container for tab contents
   * @type {Cash}
   */
  contentContainer

  /**
   * Current active tab
   * @type {string|null}
   */
  activeTab = null

  /**
   * Init
   */
  static init () {
    $(window).on('hashchange', function () {
      const currentPath = location.hash.substr(1)
      for (let i = 0; i < FramelixTabs.instances.length; i++) {
        const instance = FramelixTabs.instances[i]
        const path = instance.getFullPath()
        if (currentPath.startsWith(path + ':')) {
          let tabId = currentPath.substr(path.length + 1).split(',')[0]
          instance.setActiveTab(tabId)
        }
      }
    })
  }

  /**
   * Constructor
   */
  constructor () {
    FramelixTabs.instances.push(this)
    this.container = $('<div>')
    this.container.addClass('framelix-tabs')
    this.container.attr('data-instance-id', FramelixTabs.instances.length - 1)
  }

  /**
   * Get full tabs path from the dom
   * @param {string=} addButtonId If set, than add this id to the path at the end
   */
  getFullPath (addButtonId) {
    let path = [this.id]
    let parent = this.container
    while (true) {
      parent = parent.parent().closest('.framelix-tab-content')
      if (!parent.length) break
      path.push(parent.closest('.framelix-tabs').attr('data-id') + ':' + parent.attr('data-id'))
    }
    path.reverse()
    return path.join(',') + (addButtonId ? ':' + addButtonId : '')
  }

  /**
   * Set active tab
   * @param {string} id
   */
  setActiveTab (id) {
    this.activeTab = id
    if (this.tabs[id] === undefined) return
    FramelixLocalStorage.set('tabs-active-' + location.pathname + '-' + this.id, this.getFullPath(id))
    const buttons = this.buttonContainer.children()
    const contents = this.contentContainer.children()
    buttons.attr('data-active', '0')
    contents.attr('data-active', '0')
    this.tabs[id].buttonContainer.attr('data-active', '1')
    this.tabs[id].contentContainer.attr('data-active', '1')
  }

  /**
   * Reload tab
   * @param {string} tabId
   */
  async reloadTab (tabId) {
    const row = this.tabs[tabId]
    if (!row) return
    const content = $(`<div class="framelix-tab-content"></div>`)
    content.attr('data-id', tabId)
    if (row.optionalContentAttributes) row.optionalContentAttributes.assignToElement(content)
    if (row.content instanceof FramelixView) {
      row.content.urlParameters = row.urlParameters
      content.html(row.content.container)
      row.content.load()
    } else if (row.url) {
      let request = FramelixRequest.request('get', row.url, row.urlParameters, null, content)
      if (await request.checkHeaders() === 0) {
        content.html((await request.getJson()).content)
      }
    } else if (typeof row.content === 'function') {
      content.html(await row.content())
    } else {
      content.html(row.content)
    }
    row.contentContainer = content
    this.contentContainer.children('[data-id=\'' + tabId + '\']').replaceWith(content)
    if (this.activeTab === tabId) {
      this.setActiveTab(tabId)
    }
    $(document).trigger(FramelixTabs.EVENT_TAB_CONTENT_RENDERED, [this, row])
  }

  /**
   * Add a tab
   * @param {string} id
   * @param {string} label
   * @param {string|function} content
   * @param {string=} tabColor
   */
  addTab (id, label, content, tabColor) {
    this.tabs[id] = {
      'id': id,
      'label': label,
      'content': content,
      'tabColor': tabColor
    }
  }

  /**
   * Render the tabs into the container
   * @return {Promise<void>} Resolved when tabs are fully functional
   */
  async render () {
    const self = this
    const basePath = this.getFullPath()
    let matchedHashActiveTabId = null
    let matchedStoredActiveTabId = null
    let storedActiveTabId = FramelixLocalStorage.get('tabs-active-' + location.pathname + '-' + this.id)
    let hashTabId = location.hash.substring(1)
    this.buttonContainer = $(`<div class="framelix-tab-buttons"></div>`)
    this.contentContainer = $(`<div class="framelix-tab-contents"></div>`)
    let firstTabId = null
    let count = 0
    for (let tabId in this.tabs) {
      const row = this.tabs[tabId]
      if (firstTabId === null) firstTabId = tabId
      const fullPath = basePath + ':' + tabId
      const btn = $(`<framelix-button class="framelix-tab-button" theme="light" icon="705"></framelix-button>`)
      btn.attr('data-id', tabId)
      if (row.tabColor) {
        btn.attr('bgcolor', row.tabColor)
      }
      btn.html(row.label)
      this.buttonContainer.append(btn)
      const content = $(`<div></div>`)
      content.addClass('framelix-tab-content')
      content.attr('data-id', tabId)
      FramelixIntersectionObserver.onGetVisible(content, async function () {
        self.reloadTab(tabId)
      })
      if (fullPath === storedActiveTabId) {
        matchedStoredActiveTabId = tabId
      }
      if (fullPath === hashTabId) {
        matchedHashActiveTabId = tabId
      }
      row.buttonContainer = btn
      row.contentContainer = content
      this.contentContainer.append(row.contentContainer)
      count++
    }
    this.container.attr('data-id', this.id)
    this.container.attr('data-count', count)
    this.container.append(this.buttonContainer)
    this.container.append(this.contentContainer)
    this.buttonContainer.on('click', '.framelix-tab-button', function () {
      location.hash = '#' + self.getFullPath($(this).attr('data-id'))
    })
    let activeTabId = matchedHashActiveTabId || matchedStoredActiveTabId || firstTabId || ''
    this.setActiveTab(activeTabId)
  }
}

FramelixInit.late.push(FramelixTabs.init)