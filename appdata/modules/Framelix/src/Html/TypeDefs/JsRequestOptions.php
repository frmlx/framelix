<?php

namespace Framelix\Framelix\Html\TypeDefs;

use Framelix\Framelix\Url;
use JetBrains\PhpStorm\ExpectedValues;

/**
 * An object that pass options to create and make a FramelixRequest in the frontned
 */
class JsRequestOptions extends BaseTypeDef
{

    /**
     * Render into new modal with default options
     */
    public const string RENDER_TARGET_MODAL_NEW = "modalnew";

    /**
     * Render into popup attached to the called element
     */
    public const string RENDER_TARGET_POPUP = "popup";

    /**
     * Render into the best matching current context
     * If called from within a modal, it is rendered into the existing modal
     * If called from within a inline popup, it is rendered into the existing popup
     * If called from within a table <td> cell, it is rendered into that table cell
     * If it is called from with a tab, it is rendered into the existing tab container
     * Otherwise it acts same as "RENDER_TARGET_MODAL_NEW"
     */
    public const string RENDER_TARGET_CURRENT_CONTEXT = "currentcontext";

    public function __construct(
        /**
         * The url to load for the request
         * @var string|Url
         * @jstype string|FramelixRequest
         */
        public string|Url $url = '',

        /**
         * The render target for the request
         * If null, it will make the request but not render it anywhere
         * @var JsRenderTarget|string|null
         * @jstype FramelixTypeDefJsRenderTarget|Object|null
         * @jslistconstants RENDER_TARGET_
         */
        #[ExpectedValues(values: [
            JsRenderTarget::class,
            self::RENDER_TARGET_MODAL_NEW,
            self::RENDER_TARGET_CURRENT_CONTEXT,
            self::RENDER_TARGET_POPUP,
            null,
        ])]
        public JsRenderTarget|string|null $renderTarget = null,
    ) {}

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        if ($data['url'] instanceof Url) {
            $data['url'] = $data['url']->getUrlAsString(false);
        }
        return $data;
    }

    /**
     * Returns a request-options=attributeValue html string
     * @return string
     */
    public function toDefaultAttrStr(): string
    {
        return $this->toAttrValue('request-options');
    }

}