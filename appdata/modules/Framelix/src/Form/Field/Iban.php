<?php

namespace Framelix\Framelix\Form\Field;

/**
 * An IBAN field - International Bank Account Number
 */
class Iban extends Text
{
    public int|string|null $maxWidth = 300;

}