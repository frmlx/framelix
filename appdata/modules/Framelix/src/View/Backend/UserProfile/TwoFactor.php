<?php

namespace Framelix\Framelix\View\Backend\UserProfile;

use Framelix\Framelix\Form\Field\TwoFactorCode;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Cookie;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\View\Backend\View;
use RobThree\Auth\TwoFactorAuth;

use function implode;

use const FRAMELIX_MODULE;

class TwoFactor extends View
{
    protected string|bool $accessRole = true;
    private User $storable;

    public static function onJsCall(JsCall $jsCall): void
    {
        if (!Cookie::get(__CLASS__ . "-pw-verified", encrypted: true)) {
            return;
        }
        switch ($jsCall->action) {
            case 'enable':
                $form = self::getEnableForm();
                /** @var TwoFactorCode $field */
                $field = $form->fields['code'];
                $tfa = new TwoFactorAuth($jsCall->parameters['name'] ?? '');
                $secret = $tfa->createSecret();
                $codes = [];
                for ($i = 1; $i <= 10; $i++) {
                    $codes[] = RandomGenerator::getRandomString(
                        10,
                        null,
                        RandomGenerator::CHARSET_ALPHANUMERIC_READABILITY
                    );
                }
                Cookie::set(TwoFactorCode::COOKIE_NAME_SECRET, $secret, encrypted: true);
                Cookie::set(TwoFactorCode::COOKIE_NAME_BACKUPCODES, $codes, encrypted: true);
                ?>
                <div style="text-align: center">
                    <div><?= Lang::get('__framelix_view_backend_userprofile_2fa_enable_info__') ?></div>
                    <div class="framelix-spacer-x2"></div>
                    <framelix-button theme="primary" data-action="getcodes">
                        __framelix_view_backend_userprofile_2fa_download_codes__
                    </framelix-button>
                    <div class="framelix-spacer-x2"></div>
                    <div id="qrcode"></div>
                    <div class="framelix-spacer-x2"></div>
                    <?= $secret ?>
                    <div class="framelix-spacer-x2"></div>
                    <div><?= Lang::get('__framelix_view_backend_userprofile_2fa_enable_enter__') ?></div>
                    <div class="framelix-spacer-x2"></div>
                    <?php
                    $form->show();
                    ?>
                </div>
                <script>
                  (async function () {
                    const container = $('#qrcode')
                    new QRCode(container[0], {
                      text: <?=JsonUtils::encode($tfa->getQRText(User::get()->email, $secret))?>,
                      width: Math.min(container.width(), 250),
                      height: Math.min(container.width(), 250),
                      colorDark: '#000',
                      colorLight: '#fff',
                      correctLevel: QRCode.CorrectLevel.H
                    })
                    setTimeout(function () {
                      container.find('img').removeAttr('style').css('max-width', '100%')
                    }, 10)
                  })()
                </script>
                <?php
                break;
            case 'test':
                ?>
                <div style="text-align: center">
                    <?php
                    $form = self::getTestForm();
                    $form->show();
                    ?>
                </div>
                <?php
                break;
            case 'disable':
                $user = User::get();
                $user->twoFactorSecret = null;
                $user->twoFactorBackupCodes = null;
                $user->store();
                Toast::success('__framelix_view_backend_userprofile_2fa_disabled__');
                Url::getBrowserUrl()->redirect();
            case 'getcodes':
                Response::download(
                    "@" . implode("\n", Cookie::get(TwoFactorCode::COOKIE_NAME_BACKUPCODES, encrypted: true)),
                    "backup-codes.txt"
                );
            case 'regenerate':
                $codes = [];
                for ($i = 1; $i <= 10; $i++) {
                    $codes[] = RandomGenerator::getRandomString(
                        10,
                        null,
                        RandomGenerator::CHARSET_ALPHANUMERIC_READABILITY
                    );
                }
                $user = User::get();
                $user->twoFactorBackupCodes = $codes;
                $user->store();
                Response::download("@" . implode("\n", $codes), "backup-codes.txt");
        }
    }

    public static function getEnableForm(): Form
    {
        $form = new Form();
        $form->id = "twofa-enable";
        $form->submitUrl = \Framelix\Framelix\View::getUrl(TwoFactor::class);

        $field = new TwoFactorCode();
        $field->name = "code";
        $form->addField($field);

        return $form;
    }

    public static function getTestForm(): Form
    {
        $form = new Form();
        $form->id = "twofa-test";
        $form->submitUrl = \Framelix\Framelix\View::getUrl(TwoFactor::class);

        $field = new TwoFactorCode();
        $field->name = "code";
        $form->addField($field);

        return $form;
    }

    public function onRequest(): void
    {
        $this->storable = User::get();
        if (Cookie::get(__CLASS__ . "-pw-verified", encrypted: true)) {
            if (Form::isFormSubmitted('twofa-enable')) {
                $form = self::getEnableForm();
                $form->validate();
                $this->storable->twoFactorSecret = Cookie::get(TwoFactorCode::COOKIE_NAME_SECRET, encrypted: true);
                $this->storable->twoFactorBackupCodes = Cookie::get(
                    TwoFactorCode::COOKIE_NAME_BACKUPCODES,
                    encrypted: true
                );
                $this->storable->store();
                Cookie::set(TwoFactorCode::COOKIE_NAME_SECRET, null, encrypted: true);
                Cookie::set(TwoFactorCode::COOKIE_NAME_BACKUPCODES, null, encrypted: true);
                Toast::success('__framelix_view_backend_userprofile_2fa_enabled__');
                Url::getBrowserUrl()->redirect();
            }
            if (Form::isFormSubmitted('twofa-test')) {
                $form = self::getEnableForm();
                Cookie::set(TwoFactorCode::COOKIE_NAME_SECRET, $this->storable->twoFactorSecret, encrypted: true);
                Cookie::set(
                    TwoFactorCode::COOKIE_NAME_BACKUPCODES,
                    $this->storable->twoFactorBackupCodes,
                    encrypted: true
                );
                $form->validate();

                $code = Request::getPost('code');
                if (strlen($code) === 10 && $this->storable->twoFactorBackupCodes) {
                    $backupCodes = $this->storable->twoFactorBackupCodes;
                    foreach ($backupCodes as $key => $backupCode) {
                        if ($backupCode === $code) {
                            unset($backupCodes[$key]);
                            break;
                        }
                    }
                    $this->storable->twoFactorBackupCodes = array_values($backupCodes);
                    $this->storable->store();
                    Toast::success('__framelix_form_2fa_backup_code_used__');
                } else {
                    Toast::success('__framelix_view_backend_userprofile_2fa_test_success__');
                }
                Url::getBrowserUrl()->redirect();
            }
        } elseif (Form::isFormSubmitted('pw-verify-2fa')) {
            if (!$this->storable->passwordVerify(Request::getPost('password'))) {
                Response::stopWithFormValidationResponse(['password' => '__framelix_password_incorrect__']);
            }
            Cookie::set(__CLASS__ . "-pw-verified", true, encrypted: true);
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        if (!Cookie::get(__CLASS__ . "-pw-verified", encrypted: true)) {
            $form = $this->getPasswordVerifyForm();
            $form->addSubmitButton('verify', '__framelix_goahead__');
            $form->show();
            return;
        }
        ?>
        <framelix-alert>__framelix_view_backend_userprofile_2fa_info__</framelix-alert>
        <?php
        if ($this->storable->twoFactorSecret) {
            ?>
            <framelix-alert>__framelix_view_backend_userprofile_2fa_disable_info__</framelix-alert>
            <div class="framelix-responsive-grid-3">
                <framelix-button theme="success"
                                 data-action="disable"
                                 icon="789">__framelix_view_backend_userprofile_2fa_disable__
                </framelix-button>
                <framelix-button theme="primary"
                                 data-action="test"
                                 icon="75a">__framelix_view_backend_userprofile_2fa_test__
                </framelix-button>
                <framelix-button theme="error"
                                 data-action="regenerate"
                                 icon="78a">__framelix_view_backend_userprofile_2fa_regenerate_codes__
                </framelix-button>
            </div>
            <?php
        } else {
            ?>
            <framelix-button theme="success" data-action="enable">__framelix_view_backend_userprofile_2fa_enable__
            </framelix-button>
            <?php
        }
        ?>
        <script>
          (function () {
            $(document).on('click', 'framelix-button[data-action]', async function () {
              switch ($(this).attr('data-action')) {
                case 'enable':
                  await FramelixDom.includeCompiledFile('Framelix', 'js', 'qrcodejs', 'QRCode')
                  let name = await FramelixModal.prompt(await FramelixLang.get('__framelix_view_backend_userprofile_2fa_enable_name__'), '<?=FRAMELIX_MODULE?>').promptResult
                  await FramelixModal.show({
                    bodyContent: FramelixRequest.jsCall('<?=JsCall::getUrl(__CLASS__, 'enable')?>', { 'name': name })
                  })
                  break
                case 'test':
                  await FramelixModal.show({
                    bodyContent: FramelixRequest.jsCall('<?=JsCall::getUrl(__CLASS__, 'test')?>')
                  })
                  break
                case 'disable':
                  if (await FramelixModal.confirm(await FramelixLang.get('__framelix_view_backend_userprofile_2fa_disable_warning__')).confirmed) {
                    await FramelixModal.show({
                      bodyContent: FramelixRequest.jsCall('<?=JsCall::getUrl(__CLASS__, 'disable')?>')
                    })
                  }
                  break
                case 'getcodes':
                  await FramelixRequest.jsCall('<?=JsCall::getUrl(
                      __CLASS__,
                      'getcodes'
                  )?>').getResponseData()
                  break
                case 'regenerate':
                  if (await FramelixModal.confirm(await FramelixLang.get('__framelix_view_backend_userprofile_2fa_regenerate_codes_warning__')).confirmed) {
                    await FramelixRequest.jsCall('<?=JsCall::getUrl(
                        __CLASS__,
                        'regenerate'
                    )?>').getResponseData()
                  }
                  break
              }
            })
          })()
        </script>
        <?php
    }

    public function getPasswordVerifyForm(): Form
    {
        $form = new Form();
        $form->id = "pw-verify-2fa";

        $field = new \Framelix\Framelix\Form\Field\Password();
        $field->name = "password";
        $field->label = '__framelix_password_verify__';
        $form->addField($field);

        return $form;
    }
}