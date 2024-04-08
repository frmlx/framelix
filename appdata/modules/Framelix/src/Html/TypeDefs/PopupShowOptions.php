<?php

namespace Framelix\Framelix\Html\TypeDefs;

use JetBrains\PhpStorm\ExpectedValues;

class PopupShowOptions extends BaseTypeDef
{
    /**
     * Close self when user click outside of the popup
     */
    public const string CLOSEMETHODS_CLICK_OUTSIDE = 'click-outside';

    /**
     * Close self when user click inside the popup
     */
    public const string CLOSEMETHODS_CLICK_INSIDE = 'click-inside';

    /**
     * Close self when user click anywhere on the page
     */
    public const string CLOSEMETHODS_CLICK = 'click';

    /**
     * Closes when user leave target element with mouse (also implicit using "click" on it because usually this lead to some other content modification)
     */
    public const string CLOSEMETHODS_MOUSE_LEAVE_TARGET = 'mouseleave-target';

    /**
     * Closes when user has focused popup and then leaves the popup focus
     */
    public const string CLOSEMETHODS_FOCUSOUT_POPUP = 'focusout-popup';

    /**
     * Can only be closed programmatically with FramelixPopup.destroyInstance()
     */
    public const string CLOSEMETHODS_MANUAL = 'manual';

    public function __construct(
        /**
         * Where to place the popup beside the target, https://popper.js.org/docs/v2/constructors/#options
         * @var string
         */
        public string $placement = 'top',
        /**
         * Stick in viewport so it always is visible, even if target is out of screen
         * @var bool
         */
        public bool $stickInViewport = false,
        /**
         * How the popup should be closed
         * @var string|array
         * @jslistconstants CLOSEMETHODS_
         */
        #[ExpectedValues(values: [
            self::CLOSEMETHODS_CLICK_OUTSIDE,
            self::CLOSEMETHODS_CLICK_INSIDE,
            self::CLOSEMETHODS_CLICK,
            self::CLOSEMETHODS_MOUSE_LEAVE_TARGET,
            self::CLOSEMETHODS_FOCUSOUT_POPUP,
            self::CLOSEMETHODS_MANUAL
        ])]
        public string|array $closeMethods = self::CLOSEMETHODS_CLICK_OUTSIDE,
        /**
         * Popup color
         * @var string|ElementColor
         * @jstype string|FramelixTypeDefElementColor
         */
        public string $color = 'dark',
        /**
         * The group id, one target can have one popup of one group
         * @var string
         */
        public string $group = 'popup',
        /**
         * Offset the popup from the target (X,Y)
         * @var int[]
         */
        public array $offset = [0, 5],
        /**
         * Css padding of popup container
         * @var string
         */
        public string $padding = '5px 15px',
        /**
         * The fixed with for the popup
         * @var string|null
         */
        public ?string $width = null,
        /**
         * Offset X by given mouse event, so popup is centered where the cursor is
         * @var mixed
         * @jstype MouseEvent
         */
        public mixed $offsetByMouseEvent = null,
        /**
         * Where this popup should be appended to
         * @var string
         * @jstype string|Cash
         */
        public string $appendTo = 'body',
        /**
         * Any data to pass to the instance for later reference
         * @var array|null
         * @jstype Object|null
         */
        public ?array $data = null,
    ) {
    }
}