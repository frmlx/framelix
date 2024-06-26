<?php

namespace Framelix\Framelix\View\Backend\UserProfile;

use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserVerificationToken;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

use function strtolower;

class Email extends View
{
    protected string|bool $accessRole = "admin,myprofile";
    private User $storable;

    public function onRequest(): void
    {
        $this->storable = User::get();
        if (Form::isFormSubmitted("changeemail")) {
            $form = $this->getForm();
            $form->validate();
            $emailNew = strtolower(Request::getPost('emailnew'));
            if ($emailNew === strtolower($this->storable->email)) {
                Response::stopWithFormValidationResponse('__framelix_view_backend_userprofile_email_equal__');
            }
            if (\Framelix\Framelix\Utils\Email::isAvailable()) {
                $token1 = UserVerificationToken::create(
                    $this->storable,
                    UserVerificationToken::CATEGORY_CHANGE_EMAIL_OLD,
                    $emailNew
                );
                $token2 = UserVerificationToken::create(
                    $this->storable,
                    UserVerificationToken::CATEGORY_CHANGE_EMAIL_NEW,
                    $emailNew
                );

                $url = \Framelix\Framelix\View::getUrl(EmailVerification::class);
                $url->setParameter('token', $token1->token);

                $body = Lang::get(
                    '__framelix_view_backend_userprofile_email_mail_body__',
                    [Url::getApplicationUrl()->getUrlAsString()]
                );
                $body .= "<br/><br/>";
                $body .= '<a href="' . $url . '">' . $url . '</a>';
                \Framelix\Framelix\Utils\Email::send(
                    '__framelix_view_backend_userprofile_email_mail_title__',
                    $body,
                    $this->storable->email
                );

                $url = \Framelix\Framelix\View::getUrl(EmailVerification::class);
                $url->setParameter('token', $token2->token);

                $body = Lang::get(
                    '__framelix_view_backend_userprofile_email_mail_body__',
                    [Url::getApplicationUrl()->getUrlAsString()]
                );
                $body .= "<br/><br/>";
                $body .= '<a href="' . $url . '">' . $url . '</a>';
                \Framelix\Framelix\Utils\Email::send(
                    '__framelix_view_backend_userprofile_email_mail_title__',
                    $body,
                    $emailNew
                );
                Toast::success('__framelix_view_backend_userprofile_email_mail_sent__');
            } else {
                $user = User::get();
                $user->email = $emailNew;
                $user->store();
                Toast::success('__framelix_view_backend_userprofile_email_mail_changed__');
            }

            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        if (\Framelix\Framelix\Utils\Email::isAvailable()) {
            ?>
            <framelix-alert theme="primary">__framelix_view_backend_userprofile_email_info__</framelix-alert>
            <?php
        }
        $form = $this->getForm();
        $form->addSubmitButton();
        $form->show();
    }

    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "changeemail";

        $field = new Html();
        $field->name = "email";
        $field->label = "__framelix_email__";
        $field->defaultValue = $this->storable->email;
        $form->addField($field);

        $field = new \Framelix\Framelix\Form\Field\Email();
        $field->name = "emailnew";
        $field->label = "__framelix_emailnew__";
        $field->required = true;
        $form->addField($field);

        return $form;
    }
}