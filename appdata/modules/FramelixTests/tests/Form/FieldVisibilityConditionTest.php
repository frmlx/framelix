<?php

namespace Form;

use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\FramelixTests\TestCase;

final class FieldVisibilityConditionTest extends TestCase
{
    public function tests(): void
    {
        $form = $this->getForm();

        $name = "test1";
        $field = $form->fields[$name];
        $this->assertTrue($field->isVisible());

        $name = "test2";
        $field = $form->fields[$name];
        $this->assertFalse($field->isVisible());
        $this->setSimulatedPostData([$name => 'FOO1']);
        $this->assertTrue($field->isVisible());
        $this->setSimulatedPostData([$name => 'FOO']);
        $this->assertTrue($field->isVisible());

        $name = "test3";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => 'FOO', "test2" => 'FOO']);
        $this->assertTrue($field->isVisible());
        $this->setSimulatedPostData([$name => 'FOO1', "test2" => 'FOO']);
        $this->assertFalse($field->isVisible());

        $name = "test4";
        $field = $form->fields[$name];
        $this->assertTrue($field->isVisible());

        $name = "test5";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => 'FOO']);
        $this->assertTrue($field->isVisible());

        $name = "test6";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => 'its BLAB ']);
        $this->assertTrue($field->isVisible());

        $name = "test7";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => 'its BLA ']);
        $this->assertTrue($field->isVisible());

        $name = "test8";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => '1']);
        $this->assertFalse($field->isVisible());
        $this->setSimulatedPostData([$name => '2']);
        $this->assertTrue($field->isVisible());

        $name = "test9";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => '0']);
        $this->assertFalse($field->isVisible());
        $this->setSimulatedPostData([$name => '1']);
        $this->assertTrue($field->isVisible());

        $name = "test10";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => '1']);
        $this->assertFalse($field->isVisible());
        $this->setSimulatedPostData([$name => '0']);
        $this->assertTrue($field->isVisible());

        $name = "test11";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => '2']);
        $this->assertFalse($field->isVisible());
        $this->setSimulatedPostData([$name => '1']);
        $this->assertTrue($field->isVisible());

        $name = "test12";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => ['3', '1']]);
        $this->assertTrue($field->isVisible());

        $name = "test13";
        $field = $form->fields[$name];
        $this->setSimulatedPostData([$name => 'FOOs']);
        $this->assertTrue($field->isVisible());

        $this->assertInstanceOf(PhpToJsData::class, $field->getVisibilityCondition()->jsonSerialize());
        $this->assertTrue($field->hasVisibilityCondition());
        $field->getVisibilityCondition()->clear();
        $this->assertFalse($field->hasVisibilityCondition());

        $this->assertExceptionOnCall(function () use ($field) {
            $field->getVisibilityCondition()->empty('test')->empty('test')->jsonSerialize();
        });
    }

    /**
     * @return Form
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->id = 'test';

        $field = new Text();
        $field->name = "test1";
        $form->addField($field);

        $field = new Text();
        $field->name = "test2";
        $field->getVisibilityCondition()->equal($field->name, "FOO")->or()->equal($field->name, "FOO1");
        $form->addField($field);

        $field = new Text();
        $field->name = "test3";
        $field->getVisibilityCondition()->equal($field->name, "FOO")->and()->equal("test2", "FOO");
        $form->addField($field);

        $field = new Text();
        $field->name = "test4";
        $field->getVisibilityCondition()->empty($field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = "test5";
        $field->getVisibilityCondition()->notEmpty($field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = "test6";
        $field->getVisibilityCondition()->like($field->name, "BLAB");
        $form->addField($field);

        $field = new Text();
        $field->name = "test7";
        $field->getVisibilityCondition()->notLike($field->name, "BLAB");
        $form->addField($field);

        $field = new Number();
        $field->name = "test8";
        $field->getVisibilityCondition()->greatherThan($field->name, 1);
        $form->addField($field);

        $field = new Number();
        $field->name = "test9";
        $field->getVisibilityCondition()->greatherThanEqual($field->name, 1);
        $form->addField($field);

        $field = new Number();
        $field->name = "test10";
        $field->getVisibilityCondition()->lowerThan($field->name, 1);
        $form->addField($field);

        $field = new Number();
        $field->name = "test11";
        $field->getVisibilityCondition()->lowerThanEqual($field->name, 1);
        $form->addField($field);

        $field = new Select();
        $field->name = "test12";
        $field->multiple = true;
        $field->addOption(1, 1);
        $field->addOption(2, 2);
        $field->getVisibilityCondition()->equal($field->name, 1);
        $form->addField($field);

        $field = new Text();
        $field->name = "test13";
        $field->getVisibilityCondition()->notEqual($field->name, "FOO");
        $form->addField($field);

        return $form;
    }
}
