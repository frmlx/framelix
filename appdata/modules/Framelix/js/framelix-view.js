/**
 * Framelix view - To display a view async (In tabs for example)
 */
class FramelixView {

  /**
   * All instances
   * @type {FramelixView[]}
   */
  static instances = []

  /**
   * The whole container
   * @type {Cash}
   */
  container

  /**
   * The php class
   * @type {string}
   */
  phpClass

  /**
   * The url to this view
   * @type {string}
   */
  url

  /**
   * Additional url parameters to append to the view url
   * @type {Object|null}
   */
  urlParameters

  /**
   * Is this view already loaded
   * @type {boolean}
   */
  loaded = false

  /**
   * Constructor
   */
  constructor () {
    FramelixView.instances.push(this)
    this.container = $('<div>')
    this.container.addClass('framelix-view')
  }

  /**
   * Get the url to the view + the current search params + additional url parameters attached
   * @return {string}
   */
  getMergedUrl () {
    let url = this.url
    if (location.search.length) {
      if (!url.includes('?')) {
        url += '?'
      } else {
        url += '&'
      }
      url += location.search.substring(1)
    }
    if (this.urlParameters) {
      if (!url.match(/\?/)) {
        url += '?'
      } else {
        url += '&'
      }
      url += FramelixObjectUtils.toUrlencodedString(this.urlParameters)
    }
    return url
  }

  /**
   * Load the view into the container
   * @return {Promise} Resolved when content is fully loaded
   */
  async load () {
    this.loaded = true
    this.container.html('<div class="framelix-loading"></div> ' + (await FramelixLang.get('__framelix_view_loading__')))
    const result = await FramelixRequest.request('get', this.getMergedUrl(), null, null, false, { 'headers': { 'x-tab-id': this.container.closest('.framelix-tab-content').attr('data-id') } }).getResponseData()
    if (result === undefined) {
      FramelixToast.error(await FramelixLang.get('__framelix_error__', ['Request error']))
      return
    }
    if (typeof result === 'string') {
      this.container.html(result)
    } else {
      this.container.html(result.content)
    }
  }

  /**
   * Render the view into the container
   */
  render () {
    const self = this
    this.container.attr('data-view', this.phpClass)
    FramelixIntersectionObserver.onGetVisible(this.container, function () {
      self.load()
    })
  }
}