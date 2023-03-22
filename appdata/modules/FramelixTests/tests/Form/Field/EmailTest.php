<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Lang;
use Framelix\FramelixTests\TestCase;

final class EmailTest extends TestCase
{
    public function tests(): void
    {
        $field = new Email();
        $field->name = $field::class;
        $field->required = true;
        $this->callFormFieldDefaultMethods($field);

        $this->setSimulatedPostData([$field->name => "#aaaaaa"]);
        $this->assertSame(Lang::get('__framelix_form_validation_email__'), $field->validate());
        $this->setSimulatedPostData([$field->name => "brain@test.de"]);
        $this->assertTrue($field->validate());
    }
}
