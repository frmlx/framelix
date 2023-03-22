<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Editor;
use Framelix\FramelixTests\TestCase;

final class EditorTest extends TestCase
{
    public function tests(): void
    {
        $field = new Editor();
        $field->name = $field::class;
        $this->callFormFieldDefaultMethods($field);
        $this->setSimulatedPostData([$field->name => 'Test123']);

        $field->minLength = 2;
        $field->maxLength = 8;
        $this->assertTrue($field->validate());

        $field->minLength = 2;
        $field->maxLength = 3;
        $this->assertIsString($field->validate());

        $field->minLength = 8;
        $field->maxLength = 15;
        $this->assertIsString($field->validate());
    }
}
