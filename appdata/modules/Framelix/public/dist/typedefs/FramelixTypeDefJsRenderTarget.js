class FramelixTypeDefJsRenderTarget extends FramelixBaseTypeDef {
    /**
    * @param {FramelixTypeDefJsRenderTarget|Object} data
    * @return {string}
    */
    static toAttrValue (data) { return super.toAttrValue(data) }

    /**
    * @param {string} str
    * @return {FramelixTypeDefJsRenderTarget|Object|null}
    */
    static fromAttrValue (str) { return super.fromAttrValue(str) }

    /**
     * Modal options
     * If set, it will render into a modals body content
     * @type  {FramelixTypeDefModalShowOptions|Object}
     */
    modalOptions = null

    /**
     * Popup options
     * If set, it will render into a popup attached to the element from where the request is called
     * @type  {FramelixTypeDefPopupShowOptions|Object}
     */
    popupOptions = null

    /**
     * Render into given element selector (Can be a html element, selector or cash instance)
     * @type  {string|HTMLElement|Cash|null}
     */
    elementSelector = null

    /**
     * Will just redirect the browser to new url (Same behaviour as a default link)
     * @type  {boolean}
     */
    selfTab = false

    /**
     * Will just redirect the browser to new url in a new tab(Same behaviour as a default link with target=_blank)
     * @type  {boolean}
     */
    newTab = false

}