<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Html\TypeDefs\ElementColor;
use Framelix\Framelix\Html\TypeDefs\JsRequestOptions;
use Framelix\Framelix\Url;
use JsonSerializable;

/**
 * Html Table Cell
 * Used to show some special contents
 * Default values like strings should be used natively without this class
 */
class TableCell implements JsonSerializable
{

    /**
     * String value
     * @var mixed
     */
    public mixed $stringValue = null;

    /**
     * Sort value
     * @var mixed
     */
    public mixed $sortValue = null;

    /**
     * Cell will be a fully filled button
     * @var bool
     */
    public bool $button = false;

    /**
     * Icon, see <framelix-icon> for more information
     * @var string|null
     */
    public ?string $buttonIcon = null;

    /**
     * The button text
     * @var string|null
     */
    public ?string $buttonText = null;

    /**
     * The button color definition
     * @var ElementColor|null
     */
    public ?ElementColor $buttonColor = null;

    /**
     * Icon tooltip
     * @var string|null
     */
    public ?string $buttonTooltip = null;

    /**
     * Icon url to redirect on click
     * @var Url|string|null
     */
    public Url|string|null $buttonHref = null;

    /**
     * The target to render to response to
     * @var string|null
     */
    public ?string $buttonTarget = null;

    /**
     * If set, given confirm message will appear before jscall will be executed
     * @var string|null
     */
    public ?string $buttonConfirmMessage = null;

    /**
     * Additional icon attributes
     * @var HtmlAttributes|null
     */
    public ?HtmlAttributes $buttonAttributes = null;

    /**
     * The request should be made when clicking the button
     * @var JsRequestOptions|null
     */
    public ?JsRequestOptions $buttonRequestOptions = null;

    /**
     * Json serialize
     * @return PhpToJsData
     */
    public function jsonSerialize(): PhpToJsData
    {
        return new PhpToJsData((array)$this, $this, 'FramelixTableCell');
    }

}