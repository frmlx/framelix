<?php

namespace Framelix\Framelix\View\Backend\UserProfile;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Html\TypeDefs\ElementColor;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Cookie;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserWebAuthn;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use Throwable;

use function base64_decode;
use function base64_encode;
use function json_decode;
use function json_encode;
use function preg_replace;
use function substr;

class Fido2 extends View
{
    protected string|bool $accessRole = true;
    private UserWebAuthn $storable;
    private \Framelix\Framelix\StorableMeta\UserWebAuthn $meta;

    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'createargs':
                $webAuthn = self::getWebAuthnInstance();
                $user = User::get();
                $createArgs = $webAuthn->getCreateArgs(
                    (string)$user->id,
                    $user->email,
                    $user->email
                );
                Cookie::set('fido2-create-challenge', (string)$webAuthn->getChallenge(), encrypted: true);
                $jsCall->result = ['createArgs' => (array)$createArgs];
                break;
            case 'processargs':
                $user = User::get();
                $webAuthn = self::getWebAuthnInstance();
                try {
                    $data = $webAuthn->processCreate(
                        base64_decode($jsCall->parameters["clientData"] ?? ''),
                        base64_decode($jsCall->parameters["attestationObject"] ?? ''),
                        ByteBuffer::fromHex(Cookie::get('fido2-create-challenge', encrypted: true) ?? '')
                    );
                    $data->credentialId = base64_encode($data->credentialId);
                    $data->AAGUID = base64_encode($data->AAGUID);
                    $data = json_decode(json_encode($data), true);
                    $userWebAuthn = new UserWebAuthn();
                    $userWebAuthn->deviceName = substr($jsCall->parameters['deviceName'] ?? "", 0, 191);
                    $userWebAuthn->user = $user;
                    $userWebAuthn->authData = $data;
                    $userWebAuthn->store();
                    $jsCall->result = true;
                    Toast::success('__framelix_view_backend_userprofile_fido2_success__');
                } catch (Throwable $e) {
                    $jsCall->result = Lang::get(
                            '__framelix_view_backend_userprofile_fido2_webauthn_error__'
                        ) . ": " . $e->getMessage();
                }
                break;
        }
    }

    /**
     * Get web authn instance
     * @param string|null $hostname If not set, it will get it by current host
     * @return WebAuthn
     */
    public static function getWebAuthnInstance(?string $hostname = null): WebAuthn
    {
        $host = $hostname ?? Url::getBrowserUrl()->getHost();
        $host = preg_replace("~\:[0-9]+~", '', $host);
        return new WebAuthn(
            $host,
            $host
        );
    }

    public function onRequest(): void
    {
        $this->storable = UserWebAuthn::getByIdOrNew(Request::getGet('editWebAuthn'));
        if ($this->storable->user !== User::get()) {
            $this->storable = new UserWebAuthn();
        }
        if (!$this->storable->id) {
            $this->storable->user = User::get();
        }
        $this->meta = new \Framelix\Framelix\StorableMeta\UserWebAuthn($this->storable);
        if (Cookie::get(__CLASS__ . "-pw-verified", encrypted: true)) {
            if (Form::isFormSubmitted($this->meta->getEditFormId())) {
                $form = $this->meta->getEditForm();
                $form->validate();
                $form->setStorableValues($this->storable);
                $this->storable->store();
                Toast::success('__framelix_saved__');
                Url::getBrowserUrl()->redirect();
            }
        } elseif (Form::isFormSubmitted('pw-verify-fido')) {
            if (!$this->storable->user->passwordVerify(Request::getPost('password'))) {
                Response::stopWithFormValidationResponse(['password' => '__framelix_password_incorrect__']);
            }
            Cookie::set(__CLASS__ . "-pw-verified", true, encrypted: true);
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        if (Url::getBrowserUrl()->getHost() === '127.0.0.1') {
            echo '<framelix-alert theme="error">' . Lang::get(
                    '__framelix_view_backend_userprofile_fido2_domainunsupported__',
                    ['domain' => Url::getBrowserUrl()->getHost(), 'use' => 'localhost']
                ) . '</framelix-alert>';
            return;
        }
        if (!Cookie::get(__CLASS__ . "-pw-verified", encrypted: true)) {
            $form = $this->getPasswordVerifyForm();
            $form->addSubmitButton('verify', '__framelix_goahead__');
            $form->show();
            return;
        }
        ?>
        <framelix-alert theme="primary">__framelix_view_backend_userprofile_fido2_info__</framelix-alert>
        <framelix-alert theme="primary">__framelix_view_backend_userprofile_fido2_2fa_info__</framelix-alert>
        <?php
        $form = $this->meta->getEditForm();
        if (!$this->storable->id) {
            $form->buttons = [];
            $form->addButton(
                'enable',
                '__framelix_view_backend_userprofile_fido2_enable__',
                buttonColor: ElementColor::THEME_PRIMARY
            );
        }
        $form->show();

        $authns = UserWebAuthn::getByCondition('user = {0}', [User::get()]);
        if ($authns) {
            $this->meta->getTable($authns)->show();
        }

        ?>
        <script>
          (async function () {
            const form = FramelixForm.getById('<?=$form->id?>')
            await form.rendered
            const enableBtn = form.container.find('framelix-button[data-action=\'enable\']')
            if (FramelixLocalStorage.get('webauthn')) {
              const disableBtn = $(`<framelix-button theme="primary">__framelix_view_backend_userprofile_fido2_disable__</framelix-button>`)
              enableBtn.after(disableBtn)
              enableBtn.after(`<framelix-alert theme="success">__framelix_view_backend_userprofile_fido2_already_enabled__</framelix-alert>`)
              enableBtn.remove()
              disableBtn.on('click', function () {
                FramelixLocalStorage.remove('webauthn')
                window.location.reload()
              })
            } else {
              enableBtn.on('click', async function () {
                if (typeof navigator.credentials === 'undefined' || typeof navigator.credentials.create === 'undefined') {
                  FramelixToast.error('__framelix_view_backend_userprofile_fido2_unsupported__')
                  return
                }
                if (!await form.validate()) return

                let createArgsServerData = await FramelixRequest.jsCall('<?=JsCall::getSignedUrl(
                    [self::class, "onJsCall"],
                    'createargs'
                )?>').getResponseData()
                let createArgs = createArgsServerData.createArgs
                Framelix.recursiveBase64StrToArrayBuffer(createArgs)
                navigator.credentials.create(createArgs).then(async function (createArgsClientData) {
                  if (!createArgsClientData) {
                    FramelixToast.error('__framelix_view_backend_userprofile_fido2_error__')
                    return
                  }
                  const values = form.getValues()
                  let processArgsParams = {
                    'deviceName': values.deviceName,
                    'clientData': Framelix.arrayBufferToBase64(createArgsClientData.response.clientDataJSON),
                    'attestationObject': Framelix.arrayBufferToBase64(createArgsClientData.response.attestationObject)
                  }
                  let processArgsResult = await FramelixRequest.jsCall('<?=JsCall::getSignedUrl(
                      [self::class, "onJsCall"],
                      'processargs'
                  )?>', processArgsParams).getResponseData()
                  if (processArgsResult === true) {
                    FramelixLocalStorage.set('webauthn', true)
                    Framelix.redirect(window.location.href)
                  } else {
                    FramelixToast.error(processArgsResult)
                  }
                }).catch(async function (e) {
                  FramelixToast.error(await FramelixLang.get('__framelix_view_backend_userprofile_fido2_error__', [e.message]), -1)
                })
              })
            }
          })()
        </script>
        <?php
    }

    public function getPasswordVerifyForm(): Form
    {
        $form = new Form();
        $form->id = "pw-verify-fido";

        $field = new \Framelix\Framelix\Form\Field\Password();
        $field->name = "password";
        $field->label = '__framelix_password_verify__';
        $form->addField($field);

        return $form;
    }
}