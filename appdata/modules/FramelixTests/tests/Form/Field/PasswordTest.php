<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Password;
use Framelix\FramelixTests\TestCase;

final class PasswordTest extends TestCase
{
    public function tests(): void
    {
        $field = new Password();
        $field->name = $field::class;
        $this->callFormFieldDefaultMethods($field);
    }
}
