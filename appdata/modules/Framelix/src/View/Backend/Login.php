<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Enums\ButtonColor;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Field\TwoFactorCode;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Cookie;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\BruteForceProtection;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Storable\UserWebAuthn;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\UserProfile\Fido2;
use lbuchs\WebAuthn\Binary\ByteBuffer;

use function base64_decode;

class Login extends View
{

    public static string $imgBgDark = __DIR__ . "/../../../public/img/bg-login-dark.webp";

    public static string $imgBgLight = __DIR__ . "/../../../public/img/bg-login-light.webp";

    protected string|bool $accessRole = "*";

    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'webauthn-getargs':
                $webAuthn = Fido2::getWebAuthnInstance();
                $user = User::getByEmail((string)($jsCall->parameters['email'] ?? null));
                $userWebAuthns = $user ? UserWebAuthn::getByCondition('user = {0}', [$user]) : null;
                if (!$user || !$userWebAuthns) {
                    $jsCall->result = '__framelix_login_invalid_fido2__';
                    return;
                }
                $credentialIds = [];
                foreach ($userWebAuthns as $userWebAuthn) {
                    $credentialId = $userWebAuthn->authData['credentialId'] ?? null;
                    if ($credentialId) {
                        $credentialIds[] = base64_decode($credentialId);
                    }
                }
                $jsCall->result = ['getArgs' => (array)$webAuthn->getGetArgs($credentialIds)];
                Cookie::set('fido2-login-challenge', (string)$webAuthn->getChallenge(), encrypted: true);
                break;
            case 'webauthn-login':
                $webAuthn = Fido2::getWebAuthnInstance();
                $user = User::getByEmail((string)($jsCall->parameters['formValues']['email'] ?? null));
                $userWebAuthns = $user ? UserWebAuthn::getByCondition('user = {0}', [$user]) : null;
                if (!$user || !$userWebAuthns) {
                    $jsCall->result = '__framelix_login_invalid_fido2__';
                    return;
                }
                $data = null;
                foreach ($userWebAuthns as $userWebAuthn) {
                    $credentialId = $userWebAuthn->authData['credentialId'] ?? null;
                    if (!$credentialId) {
                        continue;
                    }
                    if ($credentialId === ($jsCall->parameters['credentialId'] ?? '')) {
                        $data = $webAuthn->processGet(
                            base64_decode($jsCall->parameters["clientData"] ?? ''),
                            base64_decode($jsCall->parameters["authenticatorData"] ?? ''),
                            base64_decode($jsCall->parameters["signature"] ?? ''),
                            $userWebAuthn->authData['credentialPublicKey'],
                            ByteBuffer::fromHex(Cookie::get('fido2-login-challenge', encrypted: true) ?? ''),
                        );
                        break;
                    }
                }

                if (!$data) {
                    $jsCall->result = '__framelix_view_backend_userprofile_fido2_webauthn_error__';
                    return;
                }
                $token = UserToken::create($user);
                UserToken::setCookieValue(
                    $token->token,
                    ($jsCall->parameters['formValues']['stay'] ?? null) ? 60 * 86400 : null
                );
                // create system event logs
                $logCategory = SystemEventLog::CATEGORY_LOGIN_SUCCESS;
                if ((Config::$enabledBuiltInSystemEventLogs[$logCategory] ?? null)) {
                    SystemEventLog::create($logCategory, null, ['email' => $user->email]);
                }
                $url = Url::getBrowserUrl();
                $jsCall->result = ['url' => $url->getParameter('redirect') ?? Url::getApplicationUrl()];
                break;
        }
    }

    public function onRequest(): void
    {
        if (User::get()) {
            if (Request::getGet('redirect')) {
                Url::create(Request::getGet('redirect'))->redirect();
            }
            if (Config::$backendDefaultView) {
                \Framelix\Framelix\View::getUrl(Config::$backendDefaultView)->redirect();
            }
            Url::getApplicationUrl()->redirect();
        }
        if (Form::isFormSubmitted('login')) {
            $form = $this->getForm();
            $form->validate();
            $email = (string)Request::getPost('email');
            $user = User::getByEmail($email);
            if (BruteForceProtection::isBlocked('backend-login')) {
                Url::getBrowserUrl()->redirect();
            }
            BruteForceProtection::countUp('backend-login');
            if ($user && $user->passwordVerify(Request::getPost('password'))) {
                if ($user->twoFactorSecret) {
                    // if 2fa, redirect to 2fa verify page
                    Cookie::set(TwoFactorCode::COOKIE_NAME_USERID, $user->id, encrypted: true);
                    Cookie::set(TwoFactorCode::COOKIE_NAME_USERSTAY, Request::getPost('stay'), encrypted: true);
                    Cookie::set(TwoFactorCode::COOKIE_NAME_SECRET, $user->twoFactorSecret, encrypted: true);
                    Cookie::set(TwoFactorCode::COOKIE_NAME_BACKUPCODES, $user->twoFactorBackupCodes, encrypted: true);
                    \Framelix\Framelix\View::getUrl(Login2FA::class)->setParameter(
                        'redirect',
                        Request::getGet('redirect')
                    )->redirect();
                }

                $token = UserToken::create($user);
                UserToken::setCookieValue($token->token, Request::getPost('stay') ? 60 * 86400 : null);
                // create system event logs
                $logCategory = SystemEventLog::CATEGORY_LOGIN_SUCCESS;
                if ((Config::$enabledBuiltInSystemEventLogs[$logCategory] ?? null)) {
                    SystemEventLog::create($logCategory, null, ['email' => $email]);
                }
                BruteForceProtection::reset('backend-login');
                if (Request::getGet('redirect')) {
                    Url::create(Request::getGet('redirect'))->redirect();
                }
                Url::getApplicationUrl()->redirect();
            } else {
                // create system event logs
                $logCategory = SystemEventLog::CATEGORY_LOGIN_FAILED;
                if ((Config::$enabledBuiltInSystemEventLogs[$logCategory] ?? null)) {
                    SystemEventLog::create($logCategory, null, ['email' => $email]);
                }
                Response::stopWithFormValidationResponse('__framelix_login_invalid_user__');
            }
        }
        $this->sidebarClosedInitially = true;
        $this->layout = self::LAYOUT_SMALL_CENTERED;
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = $this->getForm();
        $form->addSubmitButton('login', '__framelix_login_submit__');
        $form->addButton('fido2', '__framelix_login_fido2__', buttonColor: ButtonColor::PRIMARY);
        $form->show();
        ?>
      <style>
        framelix-button:not(.fido2-enabled)[data-action='fido2'] {
          display: none;
        }
        html[data-layout="2"][data-color-scheme=dark] .framelix-page {
          background: url(<?=Url::getUrlToPublicFile(self::$imgBgDark)?>) center no-repeat;
          background-size: cover;
        }
        html[data-layout="2"][data-color-scheme=dark] .framelix-content-inner-inner {
          backdrop-filter: blur(12px);
          background: rgba(0, 0, 0, 0.7);
        }
        html[data-layout="2"][data-color-scheme=light] .framelix-page {
          background: url(<?=Url::getUrlToPublicFile(self::$imgBgLight)?>) center no-repeat;
          background-size: cover;
        }
        html[data-layout="2"][data-color-scheme=light] .framelix-content-inner-inner {
          backdrop-filter: blur(12px);
          background: rgba(255, 255, 255, 0.6);
        }
      </style>
      <script>
        (async function () {
          const form = FramelixForm.getById('<?=$form->id?>')
          await form.rendered
          form.container.on('change', function () {
            const email = form.fields['email'].getValue()
            FramelixLocalStorage.set('login-email', email)
          })
          const storedEmail = FramelixLocalStorage.get('login-email')
          if (storedEmail) {
            form.fields['email'].setValue(storedEmail)
            form.fields['password'].container.find('input').trigger('focus')
          } else {
            form.fields['email'].container.find('input').trigger('focus')
          }
          if (FramelixLocalStorage.get('webauthn')) {
            const fidoButton = $('framelix-button[data-action=\'fido2\']')
            fidoButton.addClass('fido2-enabled')
            fidoButton.on('click', async function () {
              let getArgsServerData = await FramelixRequest.jsCall('<?=JsCall::getUrl(
                  __CLASS__,
                  'webauthn-getargs'
              )?>', form.getValues()).getResponseData()
              if (typeof getArgsServerData === 'string') {
                FramelixToast.error(getArgsServerData)
                return
              }
              let getArgs = getArgsServerData.getArgs
              Framelix.recursiveBase64StrToArrayBuffer(getArgs)
              navigator.credentials.get(getArgs).then(async function (getArgsClientData) {
                let loginArgsParams = {
                  'formValues': form?.getValues(),
                  'credentialId': Framelix.arrayBufferToBase64(getArgsClientData.rawId),
                  'clientData': Framelix.arrayBufferToBase64(getArgsClientData.response.clientDataJSON),
                  'authenticatorData': Framelix.arrayBufferToBase64(getArgsClientData.response.authenticatorData),
                  'signature': Framelix.arrayBufferToBase64(getArgsClientData.response.signature),
                }
                let loginResult = await FramelixRequest.jsCall('<?=JsCall::getUrl(
                    __CLASS__,
                    'webauthn-login'
                )?>', loginArgsParams).getResponseData()
                if (loginResult.url) {
                  Framelix.redirect(loginResult.url)
                } else {
                  FramelixToast.error(loginResult)
                }
              })
            })
          }
        })()
      </script>
        <?php
    }

    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "login";
        $form->submitWithEnter = true;

        $field = new Email();
        $field->name = "email";
        $field->label = "__framelix_email__";
        $field->required = true;
        $field->maxWidth = null;
        $form->addField($field);

        $field = new Password();
        $field->name = "password";
        $field->label = "__framelix_password__";
        $field->maxWidth = null;
        $field->required = true;
        $form->addField($field);

        if (Config::$backendLoginCaptcha) {
            $field = new Captcha();
            $field->name = "captcha";
            $field->required = true;
            $field->trackingAction = "framelix_backend_login";
            $form->addField($field);
        }

        $field = new Toggle();
        $field->name = "stay";
        $field->label = "__framelix_stay_logged_in__";
        $form->addField($field);

        if (\Framelix\Framelix\Utils\Email::isAvailable()) {
            $field = new Html();
            $field->name = "forgot";
            $field->defaultValue = '<a href="' . \Framelix\Framelix\View::getUrl(
                    ForgotPassword::class
                ) . '">' . Lang::get('__framelix_forgotpassword__') . '</a>';
            $form->addField($field);
        }

        return $form;
    }

}