<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Time;
use Framelix\Framelix\Lang;
use Framelix\FramelixTests\TestCase;

final class TimeTest extends TestCase
{
    public function tests(): void
    {
        $field = new Time();
        $field->name = $field::class;
        $field->required = true;
        $this->callFormFieldDefaultMethods($field);

        $this->assertSame(Lang::get('__framelix_form_validation_required__'), $field->validate());

        // update name to prevent caching of converted submitted values
        $field->name .= "1";
        $this->setSimulatedPostData([$field->name => "10:00"]);
        $this->assertSame(36000, $field->getConvertedSubmittedValue());
        $this->assertTrue($field->validate());

        // validators
        $field->name .= "1";
        $field->minTime = \Framelix\Framelix\Time::create("12:00");
        $this->setSimulatedPostData([$field->name => "11:59"]);
        $this->assertIsString($field->validate());

        $field->name .= "1";
        $field->maxTime = \Framelix\Framelix\Time::create("12:00");
        $field->minTime = null;
        $this->setSimulatedPostData([$field->name => "12:01"]);
        $this->assertIsString($field->validate());
    }
}
