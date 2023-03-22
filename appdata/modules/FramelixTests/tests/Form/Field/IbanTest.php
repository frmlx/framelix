<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Iban;
use Framelix\FramelixTests\TestCase;

final class IbanTest extends TestCase
{
    public function tests(): void
    {
        $field = new Iban();
        $field->name = $field::class;
        $this->callFormFieldDefaultMethods($field);
    }
}
