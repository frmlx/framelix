<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Color;
use Framelix\FramelixTests\TestCase;

final class ColorTest extends TestCase
{
    public function tests(): void
    {
        $field = new Color();
        $field->name = $field::class;
        $this->callFormFieldDefaultMethods($field);

        $this->setSimulatedPostData([$field->name => "#aaaaaa"]);
        $this->assertSame("#aaaaaa", $field->getSubmittedValue());
        $this->assertSame("#AAAAAA", $field->getConvertedSubmittedValue());
    }
}
