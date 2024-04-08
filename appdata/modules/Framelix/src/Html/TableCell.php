<?php

namespace Framelix\Framelix\Html;

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
     * Create an instance
     * @param mixed $stringValue
     * @param mixed|null $sortValue
     * @return TableCell
     */
    public static function create(mixed $stringValue, mixed $sortValue = null): TableCell
    {
        $cell = new self();
        $cell->stringValue = $stringValue;
        $cell->sortValue = $sortValue;
        return $cell;
    }

    /**
     * Json serialize
     * @return PhpToJsData
     */
    public function jsonSerialize(): PhpToJsData
    {
        return new PhpToJsData((array)$this, $this, 'FramelixTableCell');
    }

}