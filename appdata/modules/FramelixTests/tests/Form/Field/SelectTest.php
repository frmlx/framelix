<?php

namespace Form\Field;

use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Lang;
use Framelix\FramelixTests\Storable\TestStorable2;
use Framelix\FramelixTests\TestCase;

final class SelectTest extends TestCase
{
    public function tests(): void
    {
        $field = new Select();
        $field->name = $field::class;
        $field->required = true;
        $this->callFormFieldDefaultMethods($field);

        $this->assertSame(Lang::get('__framelix_form_validation_required__'), $field->validate());
        $field->addOptions(['123' => 'label', 'storable' => new TestStorable2()]);
        $field->addOptionsByStorables([new TestStorable2()]);
        $this->assertSame('label', $field->getOptionLabel('123'));
        $this->assertNull($field->getOptionLabel('notexist'));
        $field->removeOptions(['123']);
        $this->assertCount(2, $field->getOptions());

        // update name to prevent caching of converted submitted values
        $field->name .= "1";
        $this->setSimulatedPostData([$field->name => "12.10.2020"]);
        $this->assertSame("12.10.2020", $field->getConvertedSubmittedValue());
        $this->assertTrue($field->validate());

        $field->multiple = true;

        $field->name .= "1";
        $this->setSimulatedPostData([$field->name => ["12.10.2020"]]);
        $this->assertSame(["12.10.2020"], $field->getConvertedSubmittedValue());
        $this->assertTrue($field->validate());

        // validators
        $field->name .= "1";
        $field->minSelectedItems = 2;
        $this->setSimulatedPostData([$field->name => ["12.10.2020"]]);
        $this->assertIsString($field->validate());

        $field->name .= "1";
        $field->maxSelectedItems = 1;
        $field->minSelectedItems = null;
        $this->setSimulatedPostData([$field->name => ["12.10.2020", "12.10.2020"]]);
        $this->assertIsString($field->validate());
    }
}
