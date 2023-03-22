<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Textarea;
use Framelix\FramelixTests\TestCase;

final class TextareaTest extends TestCase
{
    public function tests(): void
    {
        $field = new Textarea();
        $field->name = $field::class;
        $field->required = true;
        $this->callFormFieldDefaultMethods($field);

        $this->setSimulatedPostData([$field->name => "#aaaaaa"]);
        $this->assertTrue($field->validate());

        // update name to prevent caching of converted submitted values
        $field->name .= "1";
        $this->setSimulatedPostData([$field->name => "12.10.2020"]);
        $this->assertSame("12.10.2020", $field->getConvertedSubmittedValue());
        $this->assertTrue($field->validate());

        // validators
        $field->name .= "1";
        $field->minLength = 5;
        $this->setSimulatedPostData([$field->name => "6666"]);
        $this->assertIsString($field->validate());

        $field->name .= "1";
        $field->maxLength = 6;
        $field->minLength = null;
        $this->setSimulatedPostData([$field->name => "12.10.2020"]);
        $this->assertIsString($field->validate());
    }
}
