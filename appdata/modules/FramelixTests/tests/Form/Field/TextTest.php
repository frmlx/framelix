<?php

namespace Form\Field;

use Framelix\Framelix\Date;
use Framelix\Framelix\Form\Field\Text;
use Framelix\FramelixTests\TestCase;

final class TextTest extends TestCase
{
    public function tests(): void
    {
        $field = new Text();
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

        $field->setConverterStorable(null);
        $field->setConverterStorableInterface(null);
        $field->setConverterStorableInterface(Date::class);

        $this->setSimulatedPostData([$field->name => '2022-01-01 00:00:00']);
        $this->assertSame('2022-01-01', (string)$field->getConvertedSubmittedValue());
    }
}
