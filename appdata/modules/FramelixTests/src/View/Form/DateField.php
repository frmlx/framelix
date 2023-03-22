<?php

namespace Framelix\FramelixTests\View\Form;

use Framelix\Framelix\Form\Field\Date;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\View;

class DateField extends View\Backend\View
{
    protected string|bool $accessRole = "*";

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = new Form();
        $form->id = "test";

        $field = new Date();
        $field->name = "field1";
        $form->addField($field);

        $field = new Date();
        $field->name = "field2_minDate";
        $field->defaultValue = "2022-01-01";
        $field->minDate = \Framelix\Framelix\Date::create("2022-01-01");
        $field->maxDate = \Framelix\Framelix\Date::create("2022-01-12");
        $form->addField($field);

        $form->addSubmitButton();

        $form->show();
    }
}