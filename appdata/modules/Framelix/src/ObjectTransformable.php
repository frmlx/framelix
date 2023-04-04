<?php

namespace Framelix\Framelix;

use JsonSerializable;
use Stringable;

/**
 * An interface to provide some framework object transform functions
 */
interface ObjectTransformable extends JsonSerializable, Stringable
{
    /**
     * Get the database value that is to be stored in database when calling store()
     * This is always the actual value that represent to current database value of the property
     * @return mixed
     */
    public function getDbValue(): mixed;

    /**
     * Get a human-readable html representation of this instance
     * Warning: You need to properly html-escape any user input. This value is used as is in the frontend (HTML allowed)
     * @return string
     */
    public function getHtmlString(): string;

    /**
     * Get a value that is explicitely used when displayed inside a html table
     * Warning: You need to properly html-escape any user input. This value is used as is in the frontend (HTML allowed)
     * @return mixed
     */
    public function getHtmlTableValue(): mixed;

    /**
     * Get a human-readable raw text representation of this instance
     * This value is used for spreadsheet values, json serialize, logs, etc...
     * You don't need to escape this value for html usage, as it is never used in HTML it is done automatically in the corresponding context
     * @return string
     */
    public function getRawTextString(): string;

    /**
     * Get a value that can be used in sort functions
     * @return mixed
     */
    public function getSortableValue(): mixed;

}