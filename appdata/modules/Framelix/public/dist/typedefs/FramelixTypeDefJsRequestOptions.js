class FramelixTypeDefJsRequestOptions extends FramelixBaseTypeDef {
    /**
     * Render into new modal with default options
     */
    static RENDER_TARGET_MODAL_NEW = "modalnew"

    /**
     * Render into popup attached to the called element
     */
    static RENDER_TARGET_POPUP = "popup"

    /**
     * Render into the best matching current context
     * If called from within a modal, it is rendered into the existing modal
     * If called from within a inline popup, it is rendered into the existing popup
     * If called from within a table <td> cell, it is rendered into that table cell
     * If it is called from with a tab, it is rendered into the existing tab container
     * Otherwise it acts same as "RENDER_TARGET_MODAL_NEW"
     */
    static RENDER_TARGET_CURRENT_CONTEXT = "currentcontext"

    /**
    * @param {FramelixTypeDefJsRequestOptions|Object} data
    * @return {string}
    */
    static toAttrValue (data) { return super.toAttrValue(data) }

    /**
    * @param {string} str
    * @return {FramelixTypeDefJsRequestOptions|Object|null}
    */
    static fromAttrValue (str) { return super.fromAttrValue(str) }

    /**
     * The url to load for the request
     * @type  {string|FramelixRequest}
     */
    url = ""

    /**
     * The render target for the request
     * If null, it will make the request but not render it anywhere
     * Require any of the class constants starting with RENDER_TARGET_
     * @type  {FramelixTypeDefJsRenderTarget|Object|null|("modalnew", "popup", "currentcontext")}
     */
    renderTarget = null

}