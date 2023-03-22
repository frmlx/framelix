<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Number;
use Framelix\FramelixTests\TestCase;

final class NumberTest extends TestCase
{
    public function tests(): void
    {
        $field = new Number();
        $field->name = $field::class;
        $field->required = true;
        $field->setIntegerOnly();
        $this->callFormFieldDefaultMethods($field);

        $this->setSimulatedPostData([$field->name => "#aaaaaa"]);
        $this->assertTrue($field->validate());
        $this->assertSame("#aaaaaa", $field->getSubmittedValue());

        // update name to prevent caching of converted submitted values
        $field->name .= "1";
        $this->setSimulatedPostData([$field->name => "122,22"]);
        $this->assertSame(122, $field->getConvertedSubmittedValue());
        $this->assertTrue($field->validate());

        $field->name .= "1";
        $field->decimals = 2;
        $this->setSimulatedPostData([$field->name => "122,22"]);
        $this->assertSame(122.22, $field->getConvertedSubmittedValue());
        $this->assertTrue($field->validate());

        // validators
        $field->name .= "1";
        $field->min = 1;
        $this->setSimulatedPostData([$field->name => "0"]);
        $this->assertIsString($field->validate());

        $field->name .= "1";
        $field->max = 3;
        $field->min = null;
        $this->setSimulatedPostData([$field->name => "4"]);
        $this->assertIsString($field->validate());
    }
}
