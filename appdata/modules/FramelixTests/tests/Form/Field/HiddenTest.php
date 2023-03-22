<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Hidden;
use Framelix\FramelixTests\TestCase;

final class HiddenTest extends TestCase
{
    public function tests(): void
    {
        $field = new Hidden();
        $field->name = $field::class;
        $this->callFormFieldDefaultMethods($field);
    }
}
