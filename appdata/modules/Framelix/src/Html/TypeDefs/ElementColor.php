<?php

namespace Framelix\Framelix\Html\TypeDefs;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * Color definitions for the frontend renderer
 */
class ElementColor extends BaseTypeDef
{

    /**
     * Default color theme, a somewhat bg/text
     */
    public const string THEME_DEFAULT = 'default';

    /**
     * Primary color, a blue-ish color
     */
    public const string THEME_PRIMARY = 'primary';

    /**
     * Success color, a green-ish color
     */
    public const string THEME_SUCCESS = 'success';

    /**
     * Warning color, a orange/brown-sh color
     */
    public const string THEME_WARNING = 'warning';

    /**
     * Error color, a red color
     */
    public const string THEME_ERROR = 'error';

    public function __construct(
        /**
         * Predefined color theme for action colors like error, success, etc...
         * @var string
         * @jslistconstants THEME_
         */
        #[ExpectedValues(valuesFromClass: self::class)]
        public string $theme = self::THEME_DEFAULT,
        /**
         * Background color in HSL range to override
         * If string, it will use that css color, including var() support
         * If array of max 4 numeric values, where only the first is required
         * 0 = Hue between 0-360
         * 1 = Saturation between 0-100 (Percent) (If not set, it uses saturation depending on dark/light mode)
         * 2 = Lightness between 0-100 (Percent) (If not set, it uses darkness depending on dark/light mode)
         * 3 = Alpha opacity between 0-1 (0=Invisible, If not set, it is fully visible)
         * If any of the array values is null, it uses defaults same if as if not set
         * @var array|string
         * @jstype number[]|null
         */
        public array|null $bgColor = null,
        /**
         * Text color in HSL range to override
         * If string, it will use that css color, including var() support
         * If given a string "invert" then it inverts the text color to white/black based on the best contrast with background
         * If array of max 4 numeric values, where only the first is required
         * 0 = Hue between 0-360
         * 1 = Saturation between 0-100 (Percent) (If not set, it uses saturation depending on dark/light mode)
         * 2 = Lightness between 0-100 (Percent) (If not set, it uses darkness depending on dark/light mode)
         * 3 = Alpha opacity between 0-1 (0=Invisible, If not set, it is fully visible)
         * If any of the array values is null, it uses defaults same if as if not set
         * @var array|string
         * @jstype number[]|string|null
         */
        public array|string|null $textColor = null,
    ) {}

}