/**
 * Inline popups
 */
class FramelixPopup {

  /**
   * All instances
   * @type {Object<number, FramelixPopup>}
   */
  static instances = {}

  /**
   * The internal id
   * @type {string}
   * @private
   */
  id

  /**
   * The target on which the element is bound to
   * @type {Cash|null}
   */
  target = null

  /**
   * The popper instance
   * @type {popper|null}
   */
  popperInstance = null

  /**
   * The popper element
   * @type {Cash|null}
   */
  popperEl = null

  /**
   * The content el to write to
   * @type {Cash|null}
   */
  content = null

  /**
   * The options with what the popup was created with
   * @type {FramelixTypeDefPopupShowOptions|Object|null}
   */
  options = {}

  /**
   * The promise that is resolved when the popup is created, attached and content is written into
   * @type {Promise<FramelixPopup>}
   */
  created

  /**
   * The promise that is resolved when the popup is destroyed (closed)
   * @type {Promise<FramelixPopup>}
   */
  destroyed

  /**
   * Internal cached bounding rect to compare after dom change
   * Only update position when rect has changed of target
   * @type {string|null}
   * @private
   */
  boundingRect = null

  /**
   * Internal promise resolver
   * @type {Object<string, function>|null}
   * @private
   */
  resolvers

  /**
   * Init
   */
  static init () {
    $(document).on('mouseenter touchstart', '[data-tooltip],[title]', async function (ev) {
      const title = $(this).attr('title')
      if (title !== undefined) {
        $(this).attr('data-tooltip', $(this).attr('title'))
        $(this).removeAttr('title')
      }
      const text = await FramelixLang.get($(this).attr('data-tooltip'))
      if (!text.trim().length) {
        return
      }
      const instance = FramelixPopup.show(this, text, {
        closeMethods: 'mouseleave-target',
        color: 'dark',
        group: 'tooltip',
        closeButton: false,
        offsetByMouseEvent: ev,
        data: { tooltip: true }
      })
      // a tooltip is above everything
      instance.popperEl.css('z-index', 999)
    })
    $(document).on('click', function (ev) {
      for (let id in FramelixPopup.instances) {
        const instance = FramelixPopup.instances[id]
        if (!instance.popperEl) continue
        const popperEl = instance.popperEl[0]
        const contains = popperEl.contains(ev.target)
        if (instance.options.closeMethods.indexOf('click-outside') > -1 && !contains) {
          instance.destroy()
        }
        if (instance.options.closeMethods.indexOf('click-inside') > -1 && contains) {
          instance.destroy()
        }
        if (instance.options.closeMethods.indexOf('click') > -1) {
          instance.destroy()
        }
      }
    })
    // listen to dom changes to auto hide popups when the target element isn't visible in the dom anymore
    FramelixDom.addChangeListener('framelix-popup', function () {
      if (!FramelixObjectUtils.hasKeys(FramelixPopup.instances)) return
      for (let id in FramelixPopup.instances) {
        const instance = FramelixPopup.instances[id]
        if (!instance.popperEl) continue
        if (!FramelixDom.isVisible(instance.target) || !FramelixDom.isVisible(instance.popperEl)) {
          instance.destroy()
        } else {
          const boundingRect = JSON.stringify(instance.target[0].getBoundingClientRect())
          if (boundingRect !== instance.boundingRect) {
            instance.boundingRect = boundingRect
            instance.popperInstance.update()
          }
        }
      }
    })
  }

  /**
   * Show a popup on given element
   * @param {HTMLElement|Cash} target The target to bind to
   * @param {string|Cash|FramelixRequest} content The content
   * @param {FramelixTypeDefPopupShowOptions|Object} options
   * @return {FramelixPopup}
   */
  static show (target, content, options) {
    const lastModal = FramelixModal.modalsContainer.children().last()
    if (!options) options = {}
    if (options.group === undefined) options.group = 'popup'
    if (options.offset === undefined) options.offset = [0, 5]
    if (options.color === undefined) options.color = 'dark'
    if (options.appendTo === undefined) options.appendTo = lastModal.length ? lastModal : 'body'
    if (options.padding === undefined) options.padding = '5px 10px'
    if (options.closeMethods === undefined) options.closeMethods = 'click-outside'
    if (typeof options.closeMethods === 'string') options.closeMethods = options.closeMethods.replace(/\s/g, '').split(',')
    if (target instanceof cash) target = target[0]

    const instance = new FramelixPopup()
    instance.options = options
    instance.resolvers = {}
    instance.created = new Promise(function (resolve) {
      instance.resolvers['created'] = resolve
    })
    instance.destroyed = new Promise(function (resolve) {
      instance.resolvers['destroyed'] = resolve
    })
    if (options.offsetByMouseEvent) {
      const rect = target.getBoundingClientRect()
      const elCenter = rect.left + rect.width / 2
      options.offset = [options.offsetByMouseEvent.pageX - elCenter, 5]
    }
    let popperOptions = {}
    popperOptions.placement = options.placement || 'top'
    if (!popperOptions.modifiers) popperOptions.modifiers = []
    popperOptions.modifiers.push({
      name: 'offset',
      options: {
        offset: options.offset,
      }
    })
    popperOptions.modifiers.push({
      name: 'preventOverflow',
      options: {
        padding: 10,
        altAxis: true,
        tether: !options.stickInViewport
      },
    })
    popperOptions.modifiers.push({
      name: 'arrow',
      options: {
        padding: 5,
      },
    })
    if (!target.popperInstances) target.popperInstances = {}
    if (target.popperInstances[options.group]) {
      target.popperInstances[options.group].destroy()
    }
    let popperEl = $(`<div class="framelix-popup" data-theme><div data-popper-arrow></div><div class="framelix-popup-inner" style="padding:${options.padding}"></div></div>`)
    $(options.appendTo).append(popperEl)
    FramelixColorUtils.setColorsFromElementColorDef(options.color, popperEl)
    const contentEl = popperEl.children('.framelix-popup-inner')
    const writePromises = []
    if (content instanceof FramelixRequest) {
      writePromises.push(new Promise(async function (resolve) {
        contentEl.html(`<div class="framelix-loading"></div>`)
        // on any response handling other then default response, destroy the popup as it will hang in a undefined state without any proper returning response
        if (await content.checkHeaders() > 0) {
          instance.destroy()
          return
        }
        await content.writeToContainer(contentEl)
        resolve()
      }))
    } else {
      contentEl.html(content)
    }
    popperEl[0].framelixPopupInstance = instance

    instance.content = contentEl
    instance.popperInstance = Popper.createPopper(target, popperEl[0], popperOptions)
    instance.popperEl = popperEl
    instance.target = $(target)
    instance.id = FramelixRandom.getRandomHtmlId()
    target.popperInstances[options.group] = instance
    // a slight delay before adding the instance, to prevent closing it directly when invoked by a click event
    setTimeout(async function () {
      FramelixPopup.instances[instance.id] = instance
      instance.popperInstance?.update()
      popperEl.attr('data-show-arrow', '1')
      if (instance.resolvers?.created) {
        Promise.all(writePromises).then(function () {
          instance.resolvers.created()
          delete instance.resolvers.created
        })
      }
    }, 100)
    if (options.closeMethods.indexOf('mouseleave-target') > -1) {
      $(target).one('mouseleave touchend mousedown', function () {
        // mouseleave could happen faster than the instance exists, so add it to allow destroy() to work properly
        FramelixPopup.instances[instance.id] = instance
        instance.destroy()
      })
    }
    if (options.closeMethods.indexOf('focusout-popup') > -1) {
      instance.popperEl.one('focusin', function () {
        instance.popperEl.on('focusout', function () {
          setTimeout(function () {
            if (!instance.popperEl || !instance.popperEl.has(document.activeElement).length) {
              instance.destroy()
            }
          }, 100)
        })
      })
    }
    // on any swipe left/right we close as well
    $(document).one('swiped-left swiped-right', function () {
      instance.destroy()
    })
    Framelix.addEscapeAction(function () {
      if (!instance.resolvers) return false
      instance.destroy()
      return true
    })
    return instance
  }

  /**
   * Hide all instances on a given target element
   * @param {HTMLElement|Cash} el
   */
  static destroyInstancesOnTarget (el) {
    if (el instanceof cash) {
      el = el[0]
    }
    if (el.popperInstances) {
      for (let group in el.popperInstances) {
        el.popperInstances[group].destroy()
      }
    }
  }

  /**
   * Destroy all tooltips only
   */
  static destroyTooltips () {
    for (let id in FramelixPopup.instances) {
      if (!FramelixPopup.instances[id].target) {
        continue
      }
      if (FramelixPopup.instances[id]?.options?.data?.tooltip) {
        FramelixPopup.instances[id].destroy()
      }
    }
  }

  /**
   * Destroy all popups
   * @return {Promise<void>}
   */
  static async destroyAll () {
    for (let id in FramelixPopup.instances) {
      await FramelixPopup.instances[id].destroy()
    }
  }

  /**
   * Destroy self
   * @return {Promise<void>}
   */
  async destroy () {
    // already destroyed
    if (!this.resolvers) return
    for (let key in this.resolvers) await this.resolvers[key]()
    this.resolvers = null
    delete FramelixPopup.instances[this.id]
    if (this.popperEl) {
      this.popperEl.remove()
      this.popperEl = null
    }
    if (this.popperInstance) {
      this.popperInstance.destroy()
      this.popperInstance = null
    }
  }
}

FramelixInit.late.push(FramelixPopup.init)