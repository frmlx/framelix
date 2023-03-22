<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Lang;
use Framelix\FramelixTests\TestCase;

final class ToggleTest extends TestCase
{
    public function tests(): void
    {
        $field = new Toggle();
        $field->name = $field::class;
        $field->required = true;
        $this->callFormFieldDefaultMethods($field);

        $this->assertSame(Lang::get('__framelix_form_validation_required__'), $field->validate());

        // update name to prevent caching of converted submitted values
        $field->name .= "1";
        $this->setSimulatedPostData([$field->name => "1"]);
        $this->assertSame(true, $field->getConvertedSubmittedValue());
    }
}
