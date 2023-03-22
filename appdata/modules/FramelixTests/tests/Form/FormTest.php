<?php

namespace Form;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Bic;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Date;
use Framelix\Framelix\Form\Field\DateTime;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Iban;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Search;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Field\Time;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Field\TwoFactorCode;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\FramelixTests\Storable\TestStorable1;
use Framelix\FramelixTests\Storable\TestStorable2;
use Framelix\FramelixTests\TestCase;
use Framelix\FramelixTests\View\TestView;

use function str_repeat;

final class FormTest extends TestCase
{
    public function tests(): void
    {
        $this->setupDatabase(true);

        // we have no objects in DB, so this does create a new entry
        $storable = TestStorable1::getByIdOrNew(1);
        $this->assertNull($storable->id);
        $storable->name = "foobar@dev.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = \Framelix\Framelix\DateTime::create('now');
        $storable->date = \Framelix\Framelix\Date::create('now');
        $storable->store();
        $storableReference = $storable;

        $storable = new TestStorable2();
        // modified timestamp is null for new objects
        $this->assertNull($storable->getModifiedTimestampTableCell());
        $storable->name = "foobar@test2.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->longTextLazy = str_repeat("foo", 1000);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = new \Framelix\Framelix\DateTime("2000-01-01 12:23:44");
        $storable->date = \Framelix\Framelix\Date::create("2000-01-01");
        $storable->otherReferenceOptional = $storableReference;
        $storable->otherReferenceArrayOptional = [$storableReference];
        $storable->typedIntArray = [1, 3, 5];
        $storable->typedBoolArray = [true, false, true];
        $storable->typedStringArray = ["yes", "baby", "yes"];
        $storable->typedFloatArray = [1.2, 1.6, 1.7];
        $storable->typedDateArray = [
            \Framelix\Framelix\DateTime::create("2000-01-01 12:23:44"),
            \Framelix\Framelix\DateTime::create("2000-01-01 12:23:44 + 10 days"),
            \Framelix\Framelix\DateTime::create("2000-01-01 12:23:44 + 1 year")
        ];
        $storable->time = \Framelix\Framelix\Time::create("12:00:01");
        $storable->updateTime = \Framelix\Framelix\DateTime::create('now - 10 seconds');
        $storable->store();

        $form = $this->getFormWithAllFields();

        $this->assertFalse(Form::isFormSubmitted($form->id));
        $this->setSimulatedGetData(["framelix-form-" . $form->id => '1']);
        $this->assertTrue(Form::isFormSubmitted($form->id));
        $this->setSimulatedPostData(["framelix-form-" . $form->id => '1']);
        $this->assertTrue(Form::isFormSubmitted($form->id));

        Buffer::start();
        $form->submitAsync = false;
        $form->show();
        $this->assertStringContainsString("FramelixObjectUtils.phpJsonToJs", Buffer::get());

        $this->assertStringContainsString("FramelixObjectUtils.phpJsonToJs", $form->getHtml());

        $form->submitUrl = new TestView();
        $this->assertStringContainsString("FramelixObjectUtils.phpJsonToJs", $form->getHtml());

        $this->assertInstanceOf(HtmlAttributes::class, $form->getHtmlAttributes());
        $form->removeField(Text::class);

        $form->addButton('test1', 'test1');
        $form->addSubmitButton('test2', 'test2');
        $form->addLoadUrlButton(Url::create(), 'test3', 'test3');

        $this->assertNotEmpty($form->getSubmittedValues());
        $this->assertNotEmpty($form->getConvertedSubmittedValues());

        $this->setSimulatedPostData(
            [
                $form->id => '1',
                'name' => '10.12.2020',
                'floatNumber' => '10,22',
                'jsonData' => ['rows' => [['text' => '1'], ['text' => '2']]],
                'date' => '10.12.2020',
                'dateTime' => '10.12.2020',
                'typedDateArray' => ['10.12.2020', '11.12.2020'],
                'otherReferenceOptional' => $storableReference->id
            ]
        );
        $form->setStorableValues($storable);
        $form->store(new TestStorable2());

        $this->assertStringContainsString("FramelixObjectUtils.phpJsonToJs", $form->fields[Toggle::class]->getHtml());
    }

    /**
     * @return Form
     */
    private function getFormWithAllFields(): Form
    {
        $form = new Form();
        $form->id = 'test';

        $field = new Bic();
        $field->name = $field::class;
        $form->addField($field);
        $form->removeField($field->name);
        $form->addField($field);

        Config::addCaptchaKey(Captcha::TYPE_RECAPTCHA_V2, 'test', 'test');
        $field = new Captcha();
        $field->type = $field::TYPE_RECAPTCHA_V2;
        $field->name = $field::class;
        $form->addField($field);

        $field = new Color();
        $field->name = $field::class;
        $form->addField($field);

        $field = new Date();
        $field->name = 'date';
        $field->setFieldOptionsForStorable(new TestStorable2(), $field->name);
        $form->addField($field);

        $field = new DateTime();
        $field->name = 'dateTime';
        $field->setFieldOptionsForStorable(new TestStorable2(), $field->name);
        $form->addField($field);

        $field = new Email();
        $field->name = $field::class;
        $form->addField($field);

        $field = new File();
        $field->name = $field::class;
        $form->addField($field);

        $field = new Hidden();
        $field->name = $field::class;
        $form->addField($field);

        $field = new Html();
        $field->name = $field::class;
        $form->addField($field);

        $field = new Iban();
        $field->name = $field::class;
        $form->addField($field);
        $fieldPrev = $field;

        $field = new Number();
        $field->name = 'int';
        $field->setFieldOptionsForStorable(new TestStorable2(), $field->name);
        $field->setPositionInForm(Iban::class);
        $field->setPositionInForm($fieldPrev);
        $field->setPositionInForm(null);
        $form->addField($field);

        $field = new Number();
        $field->name = 'floatNumber';
        $field->setFieldOptionsForStorable(new TestStorable2(), $field->name);
        $form->addField($field);

        $field = new Password();
        $field->name = $field::class;
        $form->addField($field);

        $field = new Search();
        $field->name = $field::class;
        $field->setSearchMethod(__CLASS__, 'test');
        $form->addField($field);

        $field = new Select();
        $field->name = $field::class;
        $form->addField($field);

        $field = new Text();
        $field->name = 'name';
        $field->setFieldOptionsForStorable(new TestStorable2(), $field->name);
        $form->addField($field);

        $field = new Text();
        $field->name = 'otherReferenceOptional';
        $field->setFieldOptionsForStorable(new TestStorable2(), $field->name);
        $form->addField($field);

        $field = new Select();
        $field->multiple = true;
        $field->name = 'typedDateArray';
        $field->setFieldOptionsForStorable(new TestStorable2(), $field->name);
        $form->addField($field);

        $field = new Textarea();
        $field->name = $field::class;
        $form->addField($field);

        $field = new Time();
        $field->name = $field::class;
        $form->addField($field);

        $field = new Toggle();
        $field->name = $field::class;
        $form->addField($field);

        $field = new TwoFactorCode();
        $field->name = $field::class;
        $form->addField($field);

        $field = new Textarea();
        $field->name = 'jsonData';
        $field->setFieldOptionsForStorable(new TestStorable2(), $field->name);
        $form->addField($field);

        $form->addFieldGroup('test', 'Test', [Toggle::class, TwoFactorCode::class]);
        $form->removeFieldGroup('test');

        return $form;
    }
}
