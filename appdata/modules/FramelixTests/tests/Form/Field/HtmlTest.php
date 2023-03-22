<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Html;
use Framelix\FramelixTests\TestCase;

final class HtmlTest extends TestCase
{
    public function tests(): void
    {
        $field = new Html();
        $field->name = $field::class;
        $field->required = true;
        $field->defaultValue = "123";

        $this->assertTrue($field->validate());
        $this->assertNull($field->getSubmittedValue());
        $this->assertTrue(isset($field->jsonSerialize()->properties['defaultValue']));
    }
}
