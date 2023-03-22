<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Bic;
use Framelix\FramelixTests\TestCase;

final class BicTest extends TestCase
{
    public function tests(): void
    {
        $field = new Bic();
        $field->name = $field::class;
        $this->callFormFieldDefaultMethods($field);
    }
}
