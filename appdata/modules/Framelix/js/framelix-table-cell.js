/**
 * Table cell class to hold some more specific values for a table cell
 * Used to display icons nicely, for example
 */
class FramelixTableCell {

  /**
   * String value
   * @type {string|null}
   */
  stringValue = null

  /**
   * Sort value
   * @type {*|null}
   */
  sortValue = null

  /**
   * Cell will be a fully filled button
   * @type {boolean}
   */
  button = false

  /**
   * @type {string|null}
   */
  buttonIcon = null

  /**
   * @type {string|null}
   */
  buttonText = null

  /**
   * @type {string|null}
   */
  buttonTheme = null

  /**
   * @type {string|null}
   */
  buttonBgColor = null

  /**
   * @type {string|null}
   */
  buttonTextColor = null

  /**
   * @type {string|null}
   */
  buttonTooltip = null

  /**
   * @type {string|null}
   */
  buttonHref = null

  /**
   * @type {string|null}
   */
  buttonTarget = null

  /**
   * @type {string|null}
   */
  buttonConfirmMessage = null

  /**
   * The request should be made when clicking the button
   * @type {FramelixTypeDefJsRequestOptions|null}
   */
  buttonRequestOptions = null

  /**
   * Additional button attributes
   * @type {FramelixHtmlAttributes|null}
   */
  buttonAttributes = null

  /**
   * Get html string for this table cell
   * @return {string}
   */
  getHtmlString () {
    if (this.button) {
      let buttonAttr = this.buttonAttributes || new FramelixHtmlAttributes()
      if (this.buttonIcon) {
        buttonAttr.set('icon', this.buttonIcon)
      }
      if (this.buttonTheme) {
        buttonAttr.set('theme', this.buttonTheme)
      }
      if (this.buttonTextColor) {
        buttonAttr.set('textcolor', this.buttonTextColor)
      }
      if (this.buttonBgColor) {
        buttonAttr.set('bgcolor', this.buttonBgColor)
      }
      if (this.buttonTooltip) {
        buttonAttr.set('title', this.buttonTooltip)
      }
      if (this.buttonHref) {
        buttonAttr.set('href', this.buttonHref)
      }
      if (this.buttonTarget) {
        buttonAttr.set('target', this.buttonTarget)
      }
      if (this.buttonConfirmMessage) {
        buttonAttr.set('confirm-message', this.buttonConfirmMessage)
      }
      if (this.buttonRequestOptions) {
        buttonAttr.set('request-options', FramelixTypeDefJsRequestOptions.toAttrValue(this.buttonRequestOptions))
      }
      return '<framelix-button ' + buttonAttr.toString() + '>' + (this.buttonText || '') + '</framelix-button>'
    } else {
      return this.stringValue
    }
  }
}