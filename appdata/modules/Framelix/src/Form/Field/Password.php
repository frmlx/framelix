<?php

namespace Framelix\Framelix\Form\Field;

/**
 * A field to enter password with a visible toggle button
 */
class Password extends Text
{
    public int|string|null $maxWidth = 400;

    /**
     * Type for this input field
     * @var string
     */
    public string $type = "password";
}