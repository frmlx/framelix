class FramelixTypeDefPopupShowOptions extends FramelixBaseTypeDef {
    /**
     * Close self when user click outside of the popup
     */
    static CLOSEMETHODS_CLICK_OUTSIDE = "click-outside"

    /**
     * Close self when user click inside the popup
     */
    static CLOSEMETHODS_CLICK_INSIDE = "click-inside"

    /**
     * Close self when user click anywhere on the page
     */
    static CLOSEMETHODS_CLICK = "click"

    /**
     * Closes when user leave target element with mouse (also implicit using "click" on it because usually this lead to some other content modification)
     */
    static CLOSEMETHODS_MOUSE_LEAVE_TARGET = "mouseleave-target"

    /**
     * Closes when user has focused popup and then leaves the popup focus
     */
    static CLOSEMETHODS_FOCUSOUT_POPUP = "focusout-popup"

    /**
     * Can only be closed programmatically with FramelixPopup.destroyInstance()
     */
    static CLOSEMETHODS_MANUAL = "manual"

    /**
    * @param {FramelixTypeDefPopupShowOptions|Object} data
    * @return {string}
    */
    static toAttrValue (data) { return super.toAttrValue(data) }

    /**
    * @param {string} str
    * @return {FramelixTypeDefPopupShowOptions|Object|null}
    */
    static fromAttrValue (str) { return super.fromAttrValue(str) }

    /**
     * Where to place the popup beside the target, https://popper.js.org/docs/v2/constructors/#options
     * @type  {string}
     */
    placement = "top"

    /**
     * Stick in viewport so it always is visible, even if target is out of screen
     * @type  {boolean}
     */
    stickInViewport = false

    /**
     * How the popup should be closed
     * Require any of the class constants starting with CLOSEMETHODS_
     * @type  {("click-outside", "click-inside", "click", "mouseleave-target", "focusout-popup", "manual")}
     */
    closeMethods = "click-outside"

    /**
     * Popup color
     * default is dark
     * primary|error|warning|success
     * or HexColor starting with #
     * or a css variable starting with --
     * or element to copy background and text color from
     * @type  {string|HTMLElement|Cash}
     */
    color = "default"

    /**
     * The group id, one target can have one popup of one group
     * @type  {string}
     */
    group = "popup"

    /**
     * Offset the popup from the target (X,Y)
     * @type  {number[]}
     */
    offset = [0,5]

    /**
     * Css padding of popup container
     * @type  {string}
     */
    padding = "5px 15px"

    /**
     * Offset X by given mouse event, so popup is centered where the cursor is
     * @type  {MouseEvent}
     */
    offsetByMouseEvent = null

    /**
     * Where this popup should be appended to
     * @type  {string|Cash}
     */
    appendTo = "body"

    /**
     * Any data to pass to the instance for later reference
     * @type  {Object|null}
     */
    data = null

}