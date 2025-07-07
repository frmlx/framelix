<?php

namespace Framelix\Framelix\Utils;

use avadim\FastExcelWriter\Style;
use JetBrains\PhpStorm\ExpectedValues;

class FastExcelCell implements \JsonSerializable
{

    public bool $hasRelativeReference = false;

    /**
     * Merge styles, so the new array contains all of both styles
     * @param array|null $style
     * @param array $addStyle
     * @return array
     */
    public static function mergeStyle(?array $style, array $addStyle): array
    {
        $style = $style ?? [];
        $rec = function ($addStyle, &$ref) use (&$rec) {
            foreach ($addStyle as $key => $value) {
                if (is_array($value)) {
                    if (!isset($ref[$key])) {
                        $ref[$key] = [];
                    }
                    $rec($value, $ref[$key]);
                } else {
                    $ref[$key] = $value;
                }
            }
        };
        $rec($addStyle, $style);
        return $style;
    }

    /**
     * @param mixed $cellValue
     * @param array|null $style
     */
    public function __construct(public mixed $cellValue, public ?array $style = null) {}

    public static function create(mixed $cellValue): self
    {
        return new self($cellValue);
    }

    /**
     * Get a special string that resolves to the relative cell coordinates of this cell when the excel is built with FastExcel::setFromArray
     * @param int $relativeRow
     * @param int $relativeCell
     * @return string
     */
    public function getRelativeCellRef(int $relativeRow = 0, int $relativeCell = 0): string
    {
        $this->hasRelativeReference = true;
        return '$$$_(' . $relativeRow . ',' . $relativeCell . ')';
    }

    /**
     * Set border styles
     * @param string|true|null $borders Set border directions, T = top, R = right, B = bottom, L = left
     *  If true = All borders
     *  If null = No borders
     *  Example: TB = Top and Bottom
     *  Example: TRBL = All around
     * @param string|null $style
     * @param string|null $color Border color in hex
     * @return self
     */
    public function setBorderStyle(
        string|true|null $borders,
        #[ExpectedValues([
            Style::BORDER_STYLE_THIN,
            Style::BORDER_STYLE_MEDIUM,
            Style::BORDER_STYLE_THICK,
            Style::BORDER_STYLE_DASH_DOT,
            Style::BORDER_STYLE_DASH_DOT_DOT,
            Style::BORDER_STYLE_DASHED,
            Style::BORDER_STYLE_DOTTED,
            Style::BORDER_STYLE_DOUBLE,
            Style::BORDER_STYLE_HAIR,
            Style::BORDER_STYLE_MEDIUM_DASH_DOT,
            Style::BORDER_STYLE_MEDIUM_DASH_DOT_DOT,
            Style::BORDER_STYLE_MEDIUM_DASHED,
            Style::BORDER_STYLE_SLANT_DASH_DOT,
        ])] ?string $style = null,
        ?string $color = null
    ): self {
        if (!$borders) {
            unset($this->style[Style::BORDER]);
            return $this;
        }
        $borders = is_string($borders) ? strtoupper($borders) : $borders;
        $borderSum = 0;
        if ($borders === true || str_contains($borders, 'T')) {
            $borderSum += Style::BORDER_TOP;
        }
        if ($borders === true || str_contains($borders, 'R')) {
            $borderSum += Style::BORDER_RIGHT;
        }
        if ($borders === true || str_contains($borders, 'B')) {
            $borderSum += Style::BORDER_BOTTOM;
        }
        if ($borders === true || str_contains($borders, 'B')) {
            $borderSum += Style::BORDER_LEFT;
        }
        if (!$borderSum) {
            unset($this->style[Style::BORDER]);
            return $this;
        }
        $this->style[Style::BORDER] = [$borderSum => [Style::BORDER_STYLE => $style, Style::BORDER_COLOR => $color]];
        return $this;
    }

    /**
     * Set cell/text alignment
     * @param string|false|null $horizontalAlign False will reset to default, null will not touch the style
     * @param string|false|null $verticalAlign False will reset to default, null will not touch the style
     * @return self
     */
    public function setAlignment(
        #[ExpectedValues([
            Style::TEXT_ALIGN_LEFT,
            Style::TEXT_ALIGN_CENTER,
            Style::TEXT_ALIGN_RIGHT,
            false,
            null
        ])]
        string|false|null $horizontalAlign = null,
        #[ExpectedValues(['bottom', 'center', 'distributed', 'top', false, null])] string|false|null $verticalAlign = null,
    ): self {
        if ($horizontalAlign === false) {
            unset($this->style[Style::FORMAT][Style::TEXT_ALIGN]);
        } elseif ($horizontalAlign) {
            $this->style[Style::FORMAT][Style::TEXT_ALIGN] = $horizontalAlign;
            $this->style[Style::FORMAT][Style::VERTICAL_ALIGN] = "center";
        }
        if ($verticalAlign === false) {
            unset($this->style[Style::FORMAT][Style::VERTICAL_ALIGN]);
        } elseif ($verticalAlign) {
            $this->style[Style::FORMAT][Style::VERTICAL_ALIGN] = $verticalAlign;
        }
        return $this;
    }

    /**
     * Set font styling
     * @param int|false|null $fontSize False will reset to default, null will not touch the style
     * @param string|false|null $fontFamily False will reset to default, null will not touch the style
     * @param string|false|null $style False will reset to default, null will not touch the style
     * @param string|false|null $color False will reset to default, null will not touch the style
     * @return $this
     */
    public function setFontStyle(
        int|false|null $fontSize = null,
        string|false|null $fontFamily = null,
        #[ExpectedValues([
            Style::FONT_STYLE_BOLD,
            Style::FONT_STYLE_ITALIC,
            Style::FONT_STYLE_UNDERLINE,
            Style::FONT_STYLE_STRIKETHROUGH,
            false,
            null
        ])] string|false|null $style = null,
        string|false|null $color = null
    ): self {
        if ($fontSize === false) {
            unset($this->style[Style::FONT][Style::FONT_SIZE]);
        } elseif ($fontSize) {
            $this->style[Style::FONT][Style::FONT_SIZE] = $fontSize;
        }
        if ($style === false) {
            unset($this->style[Style::FONT][Style::FONT_STYLE]);
        } elseif ($style) {
            $this->style[Style::FONT][Style::FONT_STYLE] = $style;
        }
        if ($color === false) {
            unset($this->style[Style::FONT][Style::FONT_COLOR]);
        } elseif ($color) {
            $this->style[Style::FONT][Style::FONT_COLOR] = $color;
        }
        if ($fontFamily === false) {
            unset($this->style[Style::FONT][Style::FONT_NAME]);
        } elseif ($fontFamily) {
            $this->style[Style::FONT][Style::FONT_NAME] = $fontFamily;
        }
        return $this;
    }

    public function setFillColor(?string $color): self
    {
        if (!$color) {
            unset($this->style[Style::FILL_COLOR]);
        } else {
            $this->style[Style::FILL_COLOR] = $color;
        }
        return $this;
    }

    public function setTextWrap(bool $flag): self
    {
        $this->style[Style::FORMAT][Style::TEXT_WRAP] = $flag;
        return $this;
    }

    /**
     * Add style to the current style list
     * @param array $style
     * @return $this
     * @see Style
     */
    public function addStyle(array $style): self
    {
        $this->style = self::mergeStyle($this->style, $style);
        return $this;
    }

    /**
     * Remove all styles
     * @return $this
     */
    public function removeAllStyles(): self
    {
        $this->style = null;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return ["FastExcelCell", (array)$this];
    }

}