<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\Dev\Debug;
use Framelix\Framelix\Form\Field\Date;
use Framelix\Framelix\Form\Field\Editor;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Url;
use Framelix\FramelixDocs\View\View;

class Forms extends View
{
    protected string $pageTitle = 'Form Generation';

    public static function basicForm(): void
    {
        if (Form::isFormSubmitted('demo')) {
            echo "Great. Your form has been submitted with the following raw values.";
            Debug::dump($_POST);
            Response::stopWithFormValidationResponse();
        }

        $form = new Form();
        $form->id = 'demo';
        $form->submitUrl = Url::create(); // this will submit the form to the invisible ajax request url that created this modal
        // default submit url is the url you see in the browser url bar
        // it is not required to set this in your code probably, it is just for this complex demo showcases

        $field = new Email();
        $field->name = 'email';
        $field->label = "Gimmi your email";
        $field->required = true;
        $form->addField($field);

        $form->addSubmitButton();
        $form->show();
    }

    public static function advancedForm(): void
    {
        $form = new Form();
        $form->id = 'demo';
        $form->submitUrl = Url::create(); // this will submit the form to the invisible ajax request url that created this modal
        // default submit url is the url you see in the browser url bar
        // it is not required to set this in your code probably, it is just for this complex demo showcases

        $field = new Email();
        $field->name = 'email';
        $field->label = "Gimmi your email";
        $field->required = true;
        $form->addField($field);

        $field = new Password();
        $field->name = 'password1';
        $field->label = "Choose your password";
        $field->required = true;
        $form->addField($field);

        $field = new Password();
        $field->name = 'password2';
        $field->label = "Repeat your password";
        $field->required = true;
        $field->getVisibilityCondition()->notEmpty('password1'); // show only when password1 has a value
        $form->addField($field);

        $field = new Editor();
        $field->name = 'profile';
        $field->label = "Talk a bit more about you";
        $field->minHeight = 300;
        $form->addField($field);

        $field = new Date();
        $field->name = 'today';
        $field->label = "Pick a date";
        $form->addField($field);

        $field = new Select();
        $field->name = 'select';
        $field->label = "Choose at least 2 things";
        $field->minSelectedItems = 2;
        $field->multiple = true;
        $field->addOption('1', 'Value 1');
        $field->addOption('2', 'Value 2');
        $field->addOption('3', 'Value 3');
        $form->addField($field);

        if (Form::isFormSubmitted('demo')) {
            // backend validation
            // does not proceed when validation errors have been found
            // (implicitely calls Response::stopWithFormValidationResponse on error)
            $form->validate();
            if (Request::getPost('password1') !== Request::getPost('password2')) {
                Response::stopWithFormValidationResponse([
                    'password1' => 'Passwords do not match',
                    'password2' => 'Passwords do not match'
                ]);
            }
            Toast::success('Great. Your form has been submitted with the following raw values.');
            Debug::dump($_POST);
            Response::stopWithFormValidationResponse();
        }

        $form->addSubmitButton();
        $form->show();
    }


    public function showContent(): void
    {
        ?>
        <p>
            Form Generation is another powerful part of Framelix, as it combines many features to one solid
            piece to handle your application data.
            With the forms you are able to create advanced user input forms that than can insert and modify data in your
            database with ease.
        </p>
        <p>
            Form generation comes with dynamic features, like visibility changes on user input, form validation, async
            submit, file uploads, live search and many other fields and features.
        </p>
        <p>
            It is not easy to document all the possible ways of using forms because the possibilities are endless, but
            we try to show you the basics here, to give you an idea to work with forms.
        </p>
        <p>
            You can read out more features, fields, flags and functions directly from source folders in
            <code>Framelix/vendor-frontend/js/form</code> and <code>Framelix/src/Html/Form</code>.
        </p>
        <?php

        $this->addPhpExecutableMethod([__CLASS__, "basicForm"], "Basic Form",
            "Pretty simple form with a few fields. Nothing special.");
        $this->addPhpExecutableMethod([__CLASS__, "advancedForm"], "Advanced Form",
            "A more advanced form, with a way more fields, validations and techniques used.");
        $this->showPhpExecutableMethodsCodeBlock();
    }
}