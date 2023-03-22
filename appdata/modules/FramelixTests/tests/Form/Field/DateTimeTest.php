<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\DateTime;
use Framelix\Framelix\Lang;
use Framelix\FramelixTests\TestCase;

final class DateTimeTest extends TestCase
{
    public function tests(): void
    {
        $field = new DateTime();
        $field->name = $field::class;
        $field->required = true;
        $this->callFormFieldDefaultMethods($field);

        $this->setSimulatedPostData([$field->name => "#aaaaaa"]);
        $this->assertSame(Lang::get('__framelix_form_validation_required__'), $field->validate());

        // update name to prevent caching of converted submitted values
        $field->name .= "1";
        $this->setSimulatedPostData([$field->name => "12.10.2020"]);
        $this->assertInstanceOf(\Framelix\Framelix\DateTime::class, $field->getConvertedSubmittedValue());
        $this->assertTrue($field->validate());

        // validators
        $field->name .= "1";
        $field->minDateTime = \Framelix\Framelix\DateTime::create("13.10.2020");
        $this->setSimulatedPostData([$field->name => "12.10.2020"]);
        $this->assertIsString($field->validate());

        $field->name .= "1";
        $field->maxDateTime = \Framelix\Framelix\DateTime::create("11.10.2020");
        $field->minDateTime = null;
        $this->setSimulatedPostData([$field->name => "12.10.2020"]);
        $this->assertIsString($field->validate());
    }
}
