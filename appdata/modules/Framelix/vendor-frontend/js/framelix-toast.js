/**
 * Framelix toast
 */
class FramelixToast {

  /**
   * The toast container
   * @type {Cash}
   */
  static container

  /**
   * The toast inner container
   * @type {Cash}
   */
  static innerContainer

  /**
   * The loader container
   * @type {Cash}
   */
  static loaderContainer

  /**
   * The count container
   * @type {Cash}
   */
  static countContainer

  /**
   * The message container
   * @type {Cash}
   */
  static messageContainer

  /**
   * The close button
   * @type {Cash}
   */
  static closeButton

  /**
   * The queue of all upcoming messages
   * @type {[]}
   */
  static queue = []

  /**
   * Timeout for showing next
   * @type {*}
   */
  static showNextTo = null

  /**
   * Init
   */
  static async init () {
    FramelixToast.container = $(`<div class="framelix-toast hidden" aria-atomic="true" aria-hidden="true">
        <div class="framelix-toast-inner">
          <div class="framelix-toast-loader"></div>
          <div class="framelix-toast-counter"><span class="framelix-toast-count" title="__framelix_toast_count__"></span></div>
          <div class="framelix-toast-message"></div>
          <div class="framelix-toast-close">
            <framelix-button theme="transparent" icon="719"></framelix-button>
          </div>
        </div>
    </div>`)
    $('body').append(FramelixToast.container)
    FramelixToast.innerContainer = FramelixToast.container.children()
    FramelixToast.loaderContainer = FramelixToast.container.find('.framelix-toast-loader')
    FramelixToast.countContainer = FramelixToast.container.find('.framelix-toast-count')
    FramelixToast.messageContainer = FramelixToast.container.find('.framelix-toast-message')
    FramelixToast.closeButton = FramelixToast.container.find('.framelix-toast-close framelix-button')
    FramelixToast.closeButton.on('click', function () {
      FramelixToast.showNext(true)
    })
    $(document).on('keydown', function (ev) {
      if (ev.key === 'Escape') {
        FramelixToast.hideAll()
      }
    })
    FramelixToast.showNext()
  }

  /**
   * Show info toast
   * @param {string|Cash|FramelixRequest} message
   * @param {number|string=} delaySeconds
   */
  static info (message, delaySeconds = 'auto') {
    FramelixToast.queue.push({ 'message': message, 'type': 'info', 'delay': delaySeconds })
    FramelixToast.showNext()
  }

  /**
   * Show success toast
   * @param {string|Cash|FramelixRequest} message
   * @param {number|string=} delaySeconds
   */
  static success (message, delaySeconds = 'auto') {
    FramelixToast.queue.push({ 'message': message, 'type': 'success', 'delay': delaySeconds })
    FramelixToast.showNext()
  }

  /**
   * Show warning toast
   * @param {string|Cash|FramelixRequest} message
   * @param {number|string=} delaySeconds
   */
  static warning (message, delaySeconds = 'auto') {
    FramelixToast.queue.push({ 'message': message, 'type': 'warning', 'delay': delaySeconds })
    FramelixToast.showNext()
  }

  /**
   * Show error toast
   * @param {string|Cash|FramelixRequest} message
   * @param {number|string=} delaySeconds
   */
  static error (message, delaySeconds = 'auto') {
    FramelixToast.queue.push({ 'message': message, 'type': 'error', 'delay': delaySeconds })
    FramelixToast.showNext()
  }

  /**
   * Show next toast from the queue
   * @param {boolean=} force If true then show next, doesn't matter if current timeout is active
   */
  static async showNext (force) {
    FramelixToast.updateQueueCount()
    if (force) {
      clearTimeout(FramelixToast.showNextTo)
      FramelixToast.showNextTo = null
    }
    if (FramelixToast.showNextTo) {
      return
    }
    if (!FramelixToast.queue.length) {
      FramelixToast.hideAll()
      return
    }
    const row = FramelixToast.queue.shift()
    let colorClass = ' framelix-toast-' + row.type
    if (row.type === 'info') colorClass = ''

    let nextMessageDelay = typeof row.delay === 'number' && row.delay > 0 ? row.delay * 1000 : 1000 * 300
    let messagePromise
    if (row.message instanceof FramelixRequest) {
      FramelixToast.messageContainer.html('<div class="framelix-loading"></div>')
      // on any response handling other then default response, skip the toast as it will hang in a undefined state without any proper returning response
      if (await row.message.checkHeaders() > 0) {
        FramelixToast.showNextTo = null
        FramelixToast.showNext()
        return
      }
      messagePromise = row.message.getResponseData()
    } else {
      messagePromise = FramelixLang.get(row.message)
      if (!messagePromise) {
        messagePromise = new Promise(function (resolve) {
          resolve('')
        })
      }
    }
    if (row.delay === 'auto') {
      nextMessageDelay = 5000
    }
    FramelixToast.loaderContainer.css({
      'width': '0',
      'transition': 'none'
    })
    setTimeout(function () {
      FramelixToast.container.removeClass('hidden')
      FramelixToast.loaderContainer.css({
        'transition': nextMessageDelay + 'ms linear'
      })
      setTimeout(function () {
        FramelixToast.container.addClass('framelix-toast-visible')
        FramelixToast.loaderContainer.css('width', '100%')
      }, 10)
    }, 10)
    FramelixToast.container.attr('role', row.type === 'error' ? 'alert' : 'status').attr('aria-live', row.type === 'error' ? 'assertive' : 'polite')
    FramelixToast.innerContainer.attr('class', 'framelix-toast-inner ' + colorClass)
    messagePromise.then(function (message) {
      FramelixToast.messageContainer.html(message)
    })
    FramelixToast.updateQueueCount()
    FramelixToast.showNextTo = setTimeout(function () {
      FramelixToast.showNextTo = null
      // only when document is currently active then show next
      // otherwise the user must manually close the message
      if (document.visibilityState === 'visible') FramelixToast.showNext()
    }, nextMessageDelay)
  }

  /**
   * Hide all toasts
   */
  static hideAll () {
    FramelixToast.queue = []
    FramelixToast.updateQueueCount()
    setTimeout(function () {
      FramelixToast.container.removeClass('framelix-toast-visible')
      setTimeout(function () {
        FramelixToast.container.addClass('hidden')
      }, 200)
    }, 10)
  }

  /**
   * Update queue count
   * @private
   */
  static updateQueueCount () {
    let queueCount = FramelixToast.queue.length
    FramelixToast.container.attr('data-count', queueCount)
    FramelixToast.countContainer.text('+' + queueCount)
    FramelixToast.closeButton
      .attr('title', queueCount > 0 ? '__framelix_toast_next__' : '__framelix_close__')
      .attr('icon', queueCount > 0 ? '713' : '719')
  }
}

FramelixInit.late.push(FramelixToast.init)