/**
 * Framelix html attributes
 * Work nicely with backend json serialized data
 */
class FramelixHtmlAttributes {

  /**
   * @type {Object<string, string>|null}
   */
  styles = null

  /**
   * @type {string[]}
   */
  classes = null

  /**
   * @type {Object<string, string>|null}
   */
  other = null

  /**
   * Assign all properties to given element
   * @param  {Cash} el
   */
  assignToElement (el) {
    if (FramelixObjectUtils.hasKeys(this.styles)) el.css(this.styles)
    if (FramelixObjectUtils.hasKeys(this.classes)) el.addClass(this.classes)
    if (FramelixObjectUtils.hasKeys(this.other)) el.attr(this.other)
  }

  /**
   * To string
   * Will output the HTML for the given attributes
   * @return {string}
   */
  toString () {
    let out = {}
    if (this.styles) {
      let arr = []
      for (let key in this.styles) {
        arr.push(key + ':' + this.styles[key] + ';')
      }
      out['style'] = arr.join(' ')
      if (out['style'] === '') delete out['style']
    }
    if (this.classes) {
      out['class'] = this.classes.join(' ')
      if (out['class'] === '') delete out['class']
    }
    if (this.other) {
      out = FramelixObjectUtils.merge(out, this.other)
    }
    let str = []
    for (let key in out) {
      str.push(key + '="' + FramelixStringUtils.htmlEscape(out[key]) + '"')
    }
    return str.join(' ')
  }

  /**
   * Add a class
   * @param {string} className
   */
  addClass (className) {
    if (!this.classes) this.classes = []
    if (this.classes.indexOf(className) === -1) this.classes.push(className)
  }

  /**
   * Remove a class
   * @param {string} className
   */
  removeClass (className) {
    if (!this.classes) return
    const index = this.classes.indexOf(className)
    if (index > -1) this.classes.splice(index)
  }

  /**
   * Set a style attribute
   * @param {string} key
   * @param {string|null} value Null will delete the style
   */
  setStyle (key, value) {
    if (!this.styles) this.styles = {}
    if (value === null) {
      delete this.styles[key]
      return
    }
    this.styles[key] = value
  }

  /**
   * Get a style attribute
   * @param {string} key
   * @return {string|null}
   */
  getStyle (key) {
    return this.styles ? this.styles[key] : null
  }

  /**
   * Set an attribute
   * @param {string} key
   * @param {string|null} value Null will delete the key
   */
  set (key, value) {
    if (!this.other) this.other = {}
    if (value === null) {
      delete this.other[key]
      return
    }
    this.other[key] = value

  }

  /**
   * Get an attribute
   * @param {string} key
   * @return {string|null}
   */
  get (key) {
    return this.other ? this.other[key] : null
  }
}