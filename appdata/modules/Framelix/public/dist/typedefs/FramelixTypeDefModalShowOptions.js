class FramelixTypeDefModalShowOptions extends FramelixBaseTypeDef {
    /**
    * @param {FramelixTypeDefModalShowOptions|Object} data
    * @return {string}
    */
    static toAttrValue (data) { return super.toAttrValue(data) }

    /**
    * @param {string} str
    * @return {FramelixTypeDefModalShowOptions|Object|null}
    */
    static fromAttrValue (str) { return super.fromAttrValue(str) }

    /**
     * The body content to render
     * @type  {string|Cash|FramelixRequest}
     */
    bodyContent = null

    /**
     * The fixed header content which can be optional
     * @type  {string|Cash|FramelixRequest|null}
     */
    headerContent = null

    /**
     * The fixed footer content which can be optional
     * @type  {string|Cash|FramelixRequest|null}
     */
    footerContent = null

    /**
     * Max width of modal
     * @type  {string|number|null}
     */
    maxWidth = null

    /**
     * The modal color, success, warning, error, primary
     * @type  {string|null}
     */
    color = null

    /**
     * Reuse the given modal instance instead of creating a new
     * @type  {FramelixModal|null}
     */
    instance = null

    /**
     * Any data to pass to the instance for later reference
     * @type  {Object|null}
     */
    data = null

}