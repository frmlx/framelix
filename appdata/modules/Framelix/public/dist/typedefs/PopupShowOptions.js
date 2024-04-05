/**
 * @typedef {Object} FramelixHtmlTypeDefsPopupShowOptions
 * @property {string} [placement="top"] Where to place the popup beside the target, https://popper.js.org/docs/v2/constructors/#options
 * @property {boolean} [stickInViewport=false] Stick in viewport so it always is visible, even if target is out of screen
 * @property {("click-outside", "click-inside", "click", "mouseleave-target", "focusout-popup", "manual")} [closeMethods="click-outside"] How the popup should be closed
 * @property {string} [color="default"] Popup color, default is dark, primary|error|warning|success, or HexColor starting with #, or a css variable starting with --, or element to copy background and text color from
 * @property {string} [group="popup"] The group id, one target can have one popup of one group
 * @property {number[]} [offset=[0,5]] Offset the popup from the target (X,Y)
 * @property {string} [padding="5px 15px"] Css padding of popup container
 * @property {MouseEvent} [offsetByMouseEvent=null] Offset X by given mouse event, so popup is centered where the cursor is
 * @property {string|Cash=} [appendTo="body"] Where this popup should be appended to
 * @property {Object=} [data=null] Any data to pass to the instance for later reference
*/