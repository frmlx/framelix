<?php

namespace Framelix\Framelix\Html\TypeDefs;

/**
 * Render target options for a FramelixRequest
 */
class JsRenderTarget extends BaseTypeDef
{

    public function __construct(
        /**
         * Modal options
         * If set, it will render into a modals body content
         * @var ModalShowOptions|null
         * @jstype FramelixTypeDefModalShowOptions|Object
         */
        public ModalShowOptions|null $modalOptions = null,
        /**
         * Popup options
         * If set, it will render into a popup attached to the element from where the request is called
         * @var PopupShowOptions|null
         * @jstype FramelixTypeDefPopupShowOptions|Object
         */
        public PopupShowOptions|null $popupOptions = null,
        /**
         * Render into given element selector (Can be a html element, selector or cash instance)
         * @var string|null
         * @jstype string|HTMLElement|Cash|null
         */
        public string|null $elementSelector = null,
        /**
         * Will just redirect the browser to new url (Same behaviour as a default link)
         * @var bool
         */
        public bool $selfTab = false,

        /**
         * Will just redirect the browser to new url in a new tab(Same behaviour as a default link with target=_blank)
         * @var bool
         */
        public bool $newTab = false
    ) {}

}