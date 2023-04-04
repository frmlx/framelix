<?php

namespace Framelix\FramelixDocs\View\Features;

use Framelix\Framelix\Dev\Debug;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Form;
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
            You can read out more features, flags and functions directly from source folders in
            <code>Framelix/js/form</code> and <code>Framelix/src/Html/Form</code>.
        </p>
        <?php

        $this->addPhpExecutableMethod([__CLASS__, "basicForm"], "Basic Form",
            "Pretty simple form with a few fields. Nothing special.");
        $this->showPhpExecutableMethodsCodeBlock();
    }
}