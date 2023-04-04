/**
 * Framelix modal window
 */
class FramelixModal {

  /**
   * The container containing all modals
   * @type {Cash}
   */
  static modalsContainer

  /**
   * All instances
   * @type {FramelixModal[]}
   */
  static instances = []

  /**
   * The current active instance
   * @type {FramelixModal|null}
   */
  static currentInstance = null

  /**
   * The options with what the modal was created with
   * @type {FramelixModalShowOptions}
   */
  options = {}

  /**
   * The whole modal container
   * @type {Cash}
   */
  container

  /**
   * The content container
   * Append actual content to header, body and footer container, this is the outer of the two
   * @type {Cash}
   */
  contentContainer

  /**
   * The body content container
   * @type {Cash}
   */
  bodyContainer

  /**
   * The header content  (for titles, etc...) which is always visible even when body is scrolling
   * @type {Cash}
   */
  headerContainer

  /**
   * The footer content  (for buttons, inputs, etc...) which is always visible even when body is scrolling
   * @type {Cash}
   */
  footerContainer

  /**
   * The close button
   * @type {Cash}
   */
  closeButton

  /**
   * The promise that is resolved when the window is created (all contents has been loaded/written)
   * @type {Promise<FramelixModal>}
   */
  created

  /**
   * The promise that is resolved when the window is destroyed(closed)
   * @type {Promise<FramelixModal>}
   */
  destroyed

  /**
   * Confirm window was confirmed
   * @type {Promise<boolean>}
   */
  confirmed

  /**
   * Prompt result
   * @type {Promise<string|null>}
   */
  promptResult

  /**
   * Internal promise resolver
   * @type {Object<string, function>|null}
   * @private
   */
  resolvers

  /**
   * Destroy all modals at once
   * @return {Promise} Resolved when all modals are really closed
   */
  static async destroyAll () {
    let promises = []
    for (let i = 0; i < FramelixModal.instances.length; i++) {
      const instance = FramelixModal.instances[i]
      promises.push(instance.destroy())
    }
    return Promise.all(promises)
  }

  /**
   * Init
   */
  static init () {
    FramelixModal.modalsContainer = $(`<div class="framelix-modals"></div>`)
    $('body').append(FramelixModal.modalsContainer)
  }

  /**
   * Display a nice alert box (instead of a native alert() function)
   * @param {string|Cash} content
   * @param {FramelixModalShowOptions=} options
   * @return {FramelixModal}
   */
  static alert (content, options) {
    if (!options) options = {}
    const html = $(`<div style="text-align: center;">`)
    html.append(content)
    if (!options.maxWidth) options.maxWidth = 600
    options.bodyContent = html
    options.footerContent = '<framelix-button icon="check">__framelix_ok__</framelix-button>'
    const modal = FramelixModal.show(options)
    const buttons = modal.footerContainer.find('framelix-button')
    buttons.on('click', function () {
      modal.destroy()
    })
    setTimeout(function () {
      buttons.trigger('focus')
    }, 10)
    return modal
  }

  /**
   * Display a nice prompt box (instead of a native prompt() function)
   * @param {string|Cash} content
   * @param {string=} defaultText
   * @param {FramelixModalShowOptions=} options
   * @return {FramelixModal}
   */
  static prompt (content, defaultText, options) {
    if (!options) options = {}
    const html = $(`<div style="text-align: center;"></div>`)
    if (content) {
      html.append(content)
      html.append('<div class="framelix-spacer"></div>')
    }
    const input = $('<input type="text" class="framelix-form-field-input">')
    if (defaultText !== undefined) input.val(defaultText)

    html.append($('<div>').append(input))
    let footerContainer = `
        <framelix-button icon="clear" theme="light">__framelix_cancel__</framelix-button>
        <framelix-button data-success="1" icon="check" theme="success" style="flex-grow: 4">__framelix_ok__</framelix-button>
    `

    const close = function (success) {
      if (modal.resolvers['prompt']) {
        modal.resolvers['prompt'](success ? input.val() : null)
        delete modal.resolvers['prompt']
      }
      modal.destroy()
    }

    input.on('keydown', function (ev) {
      if (ev.key === 'Enter') {
        close(true)
      }
    })

    if (!options.maxWidth) options.maxWidth = 600
    options.bodyContent = html
    options.footerContent = footerContainer
    const modal = FramelixModal.show(options)
    const buttons = modal.footerContainer.find('framelix-button')
    buttons.on('click', function () {
      close($(this).attr('data-success') === '1')
    })
    setTimeout(function () {
      input.trigger('focus')
    }, 10)
    return modal
  }

  /**
   * Display a nice confirmation box (instead of a native confirm() function)
   * @param {string|Cash} content
   * @param {FramelixModalShowOptions=} options
   * @return {FramelixModal}
   */
  static confirm (content, options) {
    if (!options) options = {}
    const html = $(`<div style="text-align: center;"></div>`)
    html.html(content)
    const bottom = $(`
      <framelix-button theme="light" icon="clear">__framelix_cancel__</framelix-button>
      <framelix-button theme="success" data-success="1" icon="check" style="flex-grow: 4">__framelix_ok__</framelix-button>
    `)
    if (!options.maxWidth) options.maxWidth = 600
    options.bodyContent = html
    options.footerContent = bottom
    const modal = FramelixModal.show(options)
    const buttons = modal.footerContainer.find('framelix-button')
    buttons.on('click', function () {
      if (modal.resolvers['confirmed']) {
        modal.resolvers['confirmed']($(this).attr('data-success') === '1')
        delete modal.resolvers['confirmed']
      }
      modal.destroy()
    })
    setTimeout(function () {
      buttons.last().trigger('focus')
    }, 10)
    return modal
  }

  /**
   * Make a request
   * @param {string} method post|get|put|delete
   * @param {string} urlPath The url path with or without url parameters
   * @param {Object=} urlParams Additional url parameters to append to urlPath
   * @param {Object|FormData|string=} postData Post data to send
   * @param {boolean|Cash=} showProgressBar Show progress bar at top of page or in given container
   * @param {Object=} fetchOptions Additonal options to directly pass to the fetch() call
   * @param {FramelixModalShowOptions=} modalOptions Modal options
   * @return {Promise<FramelixModal>} Resolved when content is loaded
   */
  static async request (method, urlPath, urlParams, postData, showProgressBar, fetchOptions, modalOptions) {
    if (!modalOptions) modalOptions = {}
    modalOptions.bodyContent = '<div class="framelix-loading"></div>'
    const modal = FramelixModal.show(modalOptions)
    modal.request = FramelixRequest.request(method, urlPath, urlParams, postData, showProgressBar, fetchOptions)
    if (await modal.request.checkHeaders() === 0) {
      const json = await modal.request.getJson()
      modal.bodyContainer.html(json?.content)
    }
    return modal
  }

  // docs-id-start: FramelixModalShowOptions
  /**
   * @typedef {Object} FramelixModalShowOptions
   * @property {string|Cash|FramelixRequest} bodyContent The body content
   * @property {string|Cash|FramelixRequest|null=} headerContent The fixed header content
   * @property {string|Cash|FramelixRequest|null=} footerContent The fixed footer content
   * @property {number|string=} maxWidth Max width
   * @property {string=} color The modal color, success, warning, error, primary
   * @property {FramelixModal=} instance Reuse the given instance instead of creating a new
   * @property {Object=} data Any data to pass to the instance for later reference
   */

  // docs-id-end: FramelixModalShowOptions

  /**
   * Show modal
   * @param {FramelixModalShowOptions} options
   * @return {FramelixModal}
   */
  static show (options) {
    const instance = options.instance || new FramelixModal()
    instance.container.append(FramelixToast.container)
    FramelixModal.currentInstance = instance
    instance.options = options
    instance.resolvers = {}
    instance.created = new Promise(function (resolve) {
      instance.resolvers['created'] = resolve
    })
    instance.confirmed = new Promise(function (resolve) {
      instance.resolvers['confirmed'] = resolve
    })
    instance.promptResult = new Promise(function (resolve) {
      instance.resolvers['prompt'] = resolve
    })
    instance.destroyed = new Promise(function (resolve) {
      instance.resolvers['destroyed'] = resolve
    })
    // on new instance set properties and events
    if (!options.instance) {
      FramelixModal.modalsContainer.append(instance.container)
      instance.container[0].showModal()
      instance.closeButton = instance.container.find('.framelix-modal-close')
      instance.contentContainer = instance.container.find('.framelix-modal-content')
      instance.headerContainer = instance.container.find('.framelix-modal-header')
      instance.bodyContainer = instance.container.find('.framelix-modal-body')
      instance.footerContainer = instance.container.find('.framelix-modal-footer')
      instance.closeButton.on('click', function () {
        instance.destroy()
      })
      instance.container.on('cancel close', function () {
        instance.destroy()
      })
    }
    instance.container.find('.framelix-modal-inner').attr('class', 'framelix-modal-inner framelix-modal-inner-' + options.color)
    if (typeof options.maxWidth === 'undefined') {
      const content = $('.framelix-content-inner-inner')
      options.maxWidth = content.length ? content.width() : 1800
    }
    const inner = instance.container.find('.framelix-modal-inner')
    inner.css('max-width', (typeof options.maxWidth === 'number' ? options.maxWidth + 'px' : options.maxWidth))
    const writeContainers = {
      'headerContainer': options.headerContent,
      'bodyContainer': options.bodyContent,
      'footerContainer': options.footerContent,
    }
    const writePromises = []
    for (let containerName in writeContainers) {
      const content = writeContainers[containerName]
      if (content === null || content === undefined) {
        continue
      }
      instance[containerName].removeClass('hidden')
      if (content instanceof FramelixRequest) {
        writePromises.push(content.writeToContainer(instance[containerName]))
      } else {
        instance[containerName].html(content)
      }
    }
    instance.container.trigger('focus')
    FramelixPopup.destroyTooltips()
    Promise.all(writePromises).then(function () {
      if (instance.resolvers['created']) {
        instance.resolvers['created']()
        delete instance.resolvers['created']
      }
    })
    return instance
  }

  /**
   * Constructor
   */
  constructor () {
    FramelixModal.instances.push(this)
    this.container = $(`<dialog class="framelix-modal">
        <div class="framelix-modal-inner">
            <div class="framelix-modal-close">
              <framelix-button icon="clear" title="__framelix_close__"></framelix-button>
            </div>
            <div class="framelix-modal-content" role="document">
                <div class="framelix-modal-header hidden"></div>
                <div class="framelix-modal-body"></div>
                <div class="framelix-modal-footer hidden"></div>
            </div>
        </div>
    </dialog>`)
    this.container.attr('data-instance-id', FramelixModal.instances.length - 1)
  }

  /**
   * Destroy modal
   * @return {Promise} Resolved when modal is destroyed(closed) but elements are still accessable
   */
  async destroy () {
    // already destroyed
    if (!this.resolvers) return
    for (let key in this.resolvers) {
      this.resolvers[key]()
    }
    this.resolvers = null
    const prevModal = this.container.prev('dialog')
    this.container[0].close()
    if (!prevModal.length) {
      FramelixModal.currentInstance = null
      $('body').append(FramelixToast.container)
    } else {
      FramelixModal.currentInstance = FramelixModal.instances[prevModal.attr('data-instance-id')]
      FramelixModal.currentInstance.container.append(FramelixToast.container)
    }
    this.container.remove()
    this.container = null
  }
}

FramelixInit.late.push(FramelixModal.init)