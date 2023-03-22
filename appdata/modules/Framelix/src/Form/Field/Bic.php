<?php

namespace Framelix\Framelix\Form\Field;

/**
 * A BIC field (Bank Identifier Code)
 */
class Bic extends Text
{
    public int|string|null $maxWidth = 200;
}