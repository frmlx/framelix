<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Url;
use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;

/**
 * Html Table Cell
 * Used to show some special contents
 * Default values like strings should be used natively without this class
 */
class TableCell implements JsonSerializable
{
    /**
     * Just do the request but not showing actual results anywhere
     */
    public const int JSCALLMODE_INVISIBLE = 0;

    /**
     * Display contents inside a modal
     */
    public const int JSCALLMODE_MODAL = 1;

    /**
     * Display contents inside a tooltip popup attached to the clicked icon
     */
    public const int JSCALLMODE_POPUP = 2;

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
     * Icon theme, primary, success, warning, error, light
     * @var string|null
     */
    public ?string $buttonText = null;

    /**
     * Icon theme, primary, success, warning, error, light
     * @var string|null
     */
    #[ExpectedValues(values: ['primary', 'success', 'warning', 'error', 'light', 'transparent'])]
    public ?string $buttonTheme = null;

    /**
     * Icon background color, any css value is allowed
     * @var string|null
     */
    public ?string $buttonBgColor = null;

    /**
     * Icon text color, any css value is allowed
     * @var string|null
     */
    public ?string $buttonTextColor = null;

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
     * Open the icon url in a new tab
     * @var string|null
     */
    public ?string $buttonTarget = null;

    /**
     * If set, this jscall request will be made
     * @var string|null
     */
    public ?string $buttonJscallUrl = null;

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
     * Json serialize
     * @return PhpToJsData
     */
    public function jsonSerialize(): PhpToJsData
    {
        return new PhpToJsData((array)$this, $this, 'FramelixTableCell');
    }
}