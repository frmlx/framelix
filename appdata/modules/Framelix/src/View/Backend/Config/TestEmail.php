<?php

namespace Framelix\Framelix\View\Backend\Config;

use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

class TestEmail extends View
{
    protected string|bool $accessRole = "admin";

    public function onRequest(): void
    {
        if (Form::isFormSubmitted("testemail")) {
            \Framelix\Framelix\Utils\Email::send(
                Request::getPost('testEmailSubject'),
                Request::getPost('testEmailBody'),
                Request::getPost('testEmailTo')
            );
            Toast::success('__framelix_ok__');
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = $this->getForm();
        $form->addSubmitButton();
        $form->show();
    }

    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "testemail";

        $field = new Email();
        $field->name = "testEmailTo";
        $field->label = '__framelix_config_testemailto_label__';
        $field->required = true;
        $form->addField($field);

        $field = new Text();
        $field->name = "testEmailSubject";
        $field->label = '__framelix_config_testemailsubject_label__';
        $field->required = true;
        $form->addField($field);

        $field = new Textarea();
        $field->name = "testEmailBody";
        $field->label = '__framelix_config_testemailbody_label__';
        $field->required = true;
        $form->addField($field);

        return $form;
    }
}