/**
 * Request helper to do any kind of request with ajax
 */
class FramelixRequest {

  /**
   * The request options if created with renderFromRequestOptions()
   * @type {FramelixTypeDefJsRequestOptions|null}
   */
  requestOptions = null

  /**
   * A function to be fired during submit progress
   * The first parameter is submit status in percent from 0 to 1
   * @type {function(number, Event)|null}
   */
  progressCallback = null

  /**
   * The submit request object
   * @type {XMLHttpRequest}
   */
  submitRequest

  /**
   * The resolved promise when request is finished
   * @type {Promise}
   */
  finished

  /**
   * The response json
   * @type {*}
   * @private
   */
  _responseJson

  /**
   * Create a request, execute it and render to data depending on the options given
   * @param {FramelixTypeDefJsRequestOptions} requestOptions
   * @param {Cash|HTMLElement|null} initiatorElement
   * @param {Object|FormData|string=} postData If set, request will be a POST request, no matter what isset in requestOptions
   * @param {function(number, Event)=} progressCallback A function that is called everytime request progress is updated with a number between 0-1
   * @return {FramelixRequest|null} The crafted request, is null in case of newTab or selfTab
   */
  static renderFromRequestOptions (requestOptions, initiatorElement, postData, progressCallback) {
    if (requestOptions.renderTarget && (requestOptions.renderTarget.newTab || requestOptions.renderTarget.selfTab)) {
      const link = $('<a>').attr('href', requestOptions.url).attr('target', requestOptions.renderTarget.newTab ? '_blank' : '_self')
      link.css('display', 'hidden')
      $('body').append()
      link.trigger('click')
      if (progressCallback && requestOptions.renderTarget.newTab) {
        progressCallback(1, null)
      }
      setTimeout(function () {
        link.remove()
      }, 1000)
      return null
    }
    let method = 'get'
    // with post data or a jscall request we use POST
    if (postData || requestOptions.url.includes('/jscv?method=')) {
      method = 'post'
    }
    const request = FramelixRequest.request(method, requestOptions.url, null, postData)
    request.requestOptions = requestOptions
    request.progressCallback = progressCallback
    FramelixRequest.renderResponse(request, requestOptions, initiatorElement)
    return request
  }

  /**
   * Create a jscall request
   * @param {string} signedUrl The signed call url generated from backend JsCall::getUrl
   * @param {Object=} parameters Parameters to pass by
   * @param {boolean|Cash=} showProgressBar Show progress bar at top of page or in given container
   * @return {FramelixRequest}
   */
  static jsCall (signedUrl, parameters, showProgressBar) {
    const postData = parameters instanceof FormData ? parameters : JSON.stringify(parameters)
    let request
    request = FramelixRequest.request('post', signedUrl, null, postData, showProgressBar)
    return request
  }

  /**
   * Create a request
   * @param {string} method post|get|put|delete
   * @param {string} urlPath The url path with or without url parameters
   * @param {Object=} urlParams Additional url parameters to append to urlPath
   * @param {Object|FormData|string=} postData Post data to send
   * @param {boolean|Cash} showProgressBar Show progress bar at top of page or in given container
   * @param {Object|null} fetchOptions Additonal options to directly pass to the fetch() call
   * @return {FramelixRequest}
   */
  static request (method, urlPath, urlParams, postData, showProgressBar = false, fetchOptions = null) {
    let instance = new FramelixRequest()

    if (typeof urlParams !== 'undefined' && urlParams !== null) {
      if (!urlPath.match(/\?/)) {
        urlPath += '?'
      } else {
        urlPath += '&'
      }
      urlPath += FramelixObjectUtils.toUrlencodedString(urlParams)
    }

    let body = postData
    if (typeof postData !== 'undefined' && postData !== null) {
      if (typeof postData === 'object' && !(postData instanceof FormData)) {
        body = FramelixRequest.objectToFormData(postData)
      }
    }
    if (!fetchOptions) {
      fetchOptions = {}
    }

    instance.finished = new Promise(function (resolve) {
      instance.submitRequest = new XMLHttpRequest()
      instance.submitRequest.open(method.toUpperCase(), urlPath, true, fetchOptions.username || null, fetchOptions.password || null)
      instance.submitRequest.setRequestHeader('x-requested-with', 'xmlhttprequest')
      instance.submitRequest.setRequestHeader('Cache-Control', 'no-store')
      instance.submitRequest.setRequestHeader('x-browser-url', window.location.href)
      if (typeof body === 'string') {
        instance.submitRequest.setRequestHeader('content-type', 'application/json')
      }
      instance.submitRequest.responseType = 'blob'
      if (fetchOptions.headers) {
        for (let k in fetchOptions.headers) {
          instance.submitRequest.setRequestHeader(k, fetchOptions.headers[k])
        }
      }
      instance.submitRequest.upload.addEventListener('progress', function (ev) {
        const loaded = 1 / ev.total * ev.loaded
        if (showProgressBar) {
          Framelix.showProgressBar(loaded, showProgressBar !== true ? showProgressBar : null)
        }
        if (instance.progressCallback) {
          instance.progressCallback(loaded, ev)
        }
      })
      instance.submitRequest.addEventListener('load', async function (ev) {
        if (instance.progressCallback) {
          instance.progressCallback(1, ev)
        }
        resolve()
      })
      instance.submitRequest.addEventListener('error', function (ev) {
        console.error(ev)
        instance.progressCallback(1, ev)
        resolve()
      })
      instance.submitRequest.send(body)
    })
    instance.finished.then(function () {
      if (showProgressBar) {
        Framelix.showProgressBar(null, showProgressBar !== true ? showProgressBar : null)
      }
    })
    return instance
  }

  /**
   * Convert an object to form data
   * @param {Object} obj
   * @param {FormData=} formData
   * @param {string=} parentKey
   * @return {FormData}
   */
  static objectToFormData (obj, formData, parentKey) {
    if (!formData) {
      formData = new FormData()
    }
    if (obj) {
      for (let i in obj) {
        let v = obj[i]
        let k = parentKey ? parentKey + '[' + i + ']' : i
        if (v !== null && v !== undefined) {
          if (typeof v === 'object') {
            FramelixRequest.objectToFormData(v, formData, parentKey)
          } else {
            formData.append(k, v)
          }
        }
      }
    }
    return formData
  }

  /**
   * Render request response into target based on requestOptions
   * @param {FramelixRequest|string} request
   * @param {FramelixTypeDefJsRequestOptions} requestOptions
   * @param {HTMLElement|Cash|null} initiatorElement
   * @param {string=} overrideResponse Override the response result with given string
   * @return {Promise<void>}
   */
  static async renderResponse (request, requestOptions, initiatorElement, overrideResponse) {
    // quick target options
    if (typeof requestOptions.renderTarget === 'string') {
      if (requestOptions.renderTarget === FramelixTypeDefJsRequestOptions.RENDER_TARGET_MODAL_NEW) {
        requestOptions.renderTarget = { modalOptions: {} }
      } else if (requestOptions.renderTarget === FramelixTypeDefJsRequestOptions.RENDER_TARGET_POPUP) {
        requestOptions.renderTarget = { popupOptions: { closeMethods: 'click-outside' } }
      } else if (requestOptions.renderTarget === FramelixTypeDefJsRequestOptions.RENDER_TARGET_CURRENT_CONTEXT) {
        requestOptions.renderTarget = requestOptions.renderTarget = { modalOptions: {} }
        if (!initiatorElement) {
          console.error('Missing initiatorElement for render to current context')
          return null
        }
        initiatorElement = $(initiatorElement)
        let parentCell = initiatorElement.closest('td')
        let parentPopup = initiatorElement.closest('.framelix-popup')
        let parentModal = initiatorElement.closest('.framelix-modal')
        let parentTab = initiatorElement.closest('.framelix-tab-content')

        if (parentCell.length) {
          requestOptions.renderTarget = { elementSelector: parentCell }
        } else if (parentPopup.length) {
          requestOptions.renderTarget = { popupOptions: parentPopup[0].framelixPopupInstance.options }
          initiatorElement = parentPopup[0].framelixPopupInstance.target
        } else if (parentModal.length) {
          const modal = FramelixModal.instances[parentModal.attr('data-instance-id')]
          if (modal) {
            requestOptions.renderTarget = { modalOptions: { instance: modal } }
          }
        } else if (parentTab.length) {
          const modal = FramelixModal.instances[parentModal.attr('data-instance-id')]
          if (modal) {
            requestOptions.renderTarget = { modalOptions: { instance: modal } }
          }
        }
      }
    }
    const isFixedResponse = !(request instanceof FramelixRequest)
    if (!requestOptions.renderTarget) {
      if (!isFixedResponse) {
        Framelix.showProgressBar(1)
        request.checkHeaders().then(function () {
          Framelix.showProgressBar(null)
        })
      }
    } else if (requestOptions.renderTarget.modalOptions) {
      let options = requestOptions.renderTarget.modalOptions
      options.bodyContent = request
      await FramelixModal.show(options).created
    } else if (requestOptions.renderTarget.popupOptions) {
      let options = requestOptions.renderTarget.popupOptions
      await FramelixPopup.show(initiatorElement, request, options).created
    } else if (requestOptions.renderTarget.elementSelector) {
      const el = $(requestOptions.renderTarget.elementSelector)
      el.html(`<div class="framelix-loading"></div>`)
      el.html(isFixedResponse ? request : await request.getJson())
    }
  }

  /**
   * Abort
   */
  abort () {
    if (this.submitRequest.readyState !== this.submitRequest.DONE) {
      this.submitRequest.abort()
    }
  }

  /**
   * Check if response has some headers that need special handling
   * Such as file download or redirect
   * Download a file if required, return 1 in this case
   * Redirect if required, return 2 in this case
   * Error if happened, return 3 in this case
   * Return 0 for no special handling
   * @return {Promise<number>}
   */
  async checkHeaders () {
    // download if required
    let dispositionHeader = await this.getHeader('content-disposition')
    if (dispositionHeader) {
      let attachmentMatch = dispositionHeader.match(/(attachment|inline)\s*;\s*filename\s*=["'](.*?)["']/)
      if (attachmentMatch) {
        Framelix.downloadBlobAsFile(await this.getBlob(), attachmentMatch[2])
        return 1
      }
    }
    // redirect if required
    let redirectHeader = await this.getHeader('x-redirect')
    if (redirectHeader) {
      Framelix.redirect(redirectHeader)
      return 2
    }
    if (this.submitRequest.status >= 400) {
      FramelixModal.show({ bodyContent: await this.getText() })
      return 3
    }
    return 0
  }

  /**
   * Write response data to given container
   * @param {Cash} container
   * @param {boolean} showLoadingBar
   * @param {boolean} checkHeaders If true checkHeaders returns any other than 0, this promise never resolves
   * @return {Promise<void>}
   */
  async writeToContainer (container, showLoadingBar = true, checkHeaders = true) {
    if (showLoadingBar) {
      container.html(`<div class="framelix-loading"></div>`)
    }
    const responseData = await this.getResponseData(checkHeaders)
    container.html(responseData)
  }

  /**
   * Get response data
   * @param {boolean} checkHeaders If true checkHeaders returns any other than 0, this promise never resolves
   * @return {Promise<string|*>}
   */
  async getResponseData (checkHeaders = true) {
    await this.finished
    const self = this
    return new Promise(async function (resolve) {
      if (checkHeaders && await self.checkHeaders() !== 0) {
        return
      }
      if (await self.getHeader('content-type') === 'application/json') {
        resolve(self.getJson())
      }
      return resolve(self.getText())
    })
  }

  /**
   * Just get raw response blob
   * @return {Promise<Blob>}
   */
  async getBlob () {
    await this.finished
    return this.submitRequest.response
  }

  /**
   * Just get raw response text
   * @return {Promise<string>}
   */
  async getText () {
    await this.finished
    return this.submitRequest.response.text()
  }

  /**
   * Get response json
   * Return undefined on any error
   * @return {Promise<*|undefined>}
   */
  async getJson () {
    await this.finished
    if (typeof this._responseJson === 'undefined') {
      try {
        this._responseJson = JSON.parse(await this.getText())
      } catch (e) {

      }
    }
    return this._responseJson
  }

  /**
   * Get response header
   * @param {string} key
   * @return {Promise<string|null>}
   */
  async getHeader (key) {
    await this.finished
    return this.submitRequest.getResponseHeader(key)
  }
}