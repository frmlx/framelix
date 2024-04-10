<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserVerificationToken;
use Framelix\Framelix\Url;

class ForgotPassword extends View
{
    protected string|bool $accessRole = "*";
    private ?UserVerificationToken $token = null;

    public function onRequest(): void
    {
        if (User::get()) {
            Login::redirectToDefaultUrl();
        }
        if ($tokenStr = Request::getGet('token')) {
            $this->token = UserVerificationToken::getForToken((string)$tokenStr);
            if ($this->token && $this->token->createTime < DateTime::create("now - 1 hour")) {
                $this->token->delete(true);
                $this->token = null;
            }
            if (!$this->token) {
                Toast::error('__framelix_view_backend_forgotpassword_token_invalid__');
            }
        }
        if ($this->token && Form::isFormSubmitted('reset')) {
            $form = $this->getFormNewPassword();
            $form->validate();
            if (Request::getPost('password') !== Request::getPost('password2')) {
                Response::stopWithFormValidationResponse('__framelix_password_notmatch__');
            }
            $this->token->user->setPassword(Request::getPost('password'));
            $this->token->user->store();
            Toast::success('__framelix_view_backend_forgotpassword_resetdone__');
            $this->token->delete(true);
            \Framelix\Framelix\View::getUrl(Login::class)->redirect();
        }
        if (Form::isFormSubmitted('forgot')) {
            $form = $this->getFormSendMail();
            $form->validate();
            $email = (string)Request::getPost('email');
            $user = User::getByEmail($email);
            if ($user) {
                $verificationToken = UserVerificationToken::create(
                    $user,
                    UserVerificationToken::CATEGORY_FORGOT_PASSWORD
                );
                $url = $this->getSelfUrl();
                $url->setParameter('token', $verificationToken->token);
                $body = Lang::get(
                    '__framelix_view_backend_forgotpassword_mailbody__',
                    [Url::getApplicationUrl()->getUrlAsString()]
                );
                $body .= "<br/><br/>";
                $body .= '<a href="' . $url . '">' . $url . '</a>';
                \Framelix\Framelix\Utils\Email::send(
                    '__framelix_view_backend_forgotpassword__',
                    $body,
                    $user
                );
            }
            Toast::success(Lang::get('__framelix_view_backend_forgotpassword_sentmail__', [$email]));
            Url::getBrowserUrl()->redirect();
        }
        $this->sidebarClosedInitially = true;
        $this->layout = self::LAYOUT_SMALL_CENTERED;
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        if ($this->token) {
            $form = $this->getFormNewPassword();
            $form->addSubmitButton('reset');
        } else {
            $form = $this->getFormSendMail();
            $form->addSubmitButton('send', '__framelix_view_backend_forgotpassword_sendmail__');
        }
        $form->show();
    }

    public function getFormNewPassword(): Form
    {
        $form = new Form();
        $form->id = "reset";
        $form->submitWithEnter = true;

        $field = new Password();
        $field->name = "password";
        $field->label = "__framelix_password__";
        $field->minLength = 8;
        $form->addField($field);

        $field = new Password();
        $field->name = "password2";
        $field->label = "__framelix_password_repeat__";
        $field->minLength = 8;
        $form->addField($field);

        return $form;
    }

    public function getFormSendMail(): Form
    {
        $form = new Form();
        $form->id = "forgot";
        $form->submitWithEnter = true;

        $field = new Email();
        $field->name = "email";
        $field->label = "__framelix_email__";
        $field->required = true;
        $form->addField($field);

        if (Config::$backendAuthCaptcha) {
            $field = new Captcha();
            $field->name = "captcha";
            $field->required = true;
            $field->trackingAction = "framelix_backend_forgot_password";
            $field->type = Config::$backendAuthCaptcha;
            $form->addField($field);
        }

        return $form;
    }
}