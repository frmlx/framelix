<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\TwoFactorCode;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Cookie;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\BruteForceProtection;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;

use function array_values;
use function strlen;

class Login2FA extends View
{
    protected string|bool $accessRole = "*";
    protected ?User $user = null;

    public function onRequest(): void
    {
        if (User::get()) {
            Url::getApplicationUrl()->redirect();
        }
        $this->user = User::getById(Cookie::get(TwoFactorCode::COOKIE_NAME_USERID, encrypted: true));
        if (!$this->user || $this->user->twoFactorSecret !== Cookie::get(
                TwoFactorCode::COOKIE_NAME_SECRET,
                encrypted: true
            )) {
            \Framelix\Framelix\View::getUrl(Login::class)->redirect();
        }
        if (Form::isFormSubmitted('twofa')) {
            $form = $this->getForm();
            $form->validate();

            $token = UserToken::create($this->user);
            UserToken::setCookieValue(
                $token->token,
                Cookie::get(TwoFactorCode::COOKIE_NAME_USERSTAY, encrypted: true) ? 60 * 86400 : null
            );

            // create system event logs
            $logCategory = SystemEventLog::CATEGORY_LOGIN_SUCCESS;
            if ((Config::$enabledBuiltInSystemEventLogs[$logCategory] ?? null)) {
                SystemEventLog::create($logCategory, null, ['email' => $this->user->email]);
            }

            $code = Request::getPost('code');
            if (strlen($code) === 10 && $this->user->twoFactorBackupCodes) {
                Toast::warning('__framelix_form_2fa_backup_code_used__');
                $backupCodes = $this->user->twoFactorBackupCodes;
                foreach ($backupCodes as $key => $backupCode) {
                    if ($backupCode === $code) {
                        unset($backupCodes[$key]);
                        break;
                    }
                }
                $this->user->twoFactorBackupCodes = array_values($backupCodes);
                $this->user->store();
            }
            BruteForceProtection::reset('backend-login');
            Login::redirectToDefaultUrl();
        }

        $this->sidebarClosedInitially = true;
        $this->layout = self::LAYOUT_SMALL_CENTERED;
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        $form = $this->getForm();
        $form->show();
        ?>
        <a href="<?= \Framelix\Framelix\View::getUrl(Login::class)->setParameter(
            'redirect',
            Request::getGet('redirect')
        ) ?>"><?= Lang::get('__framelix_view_backend_login2fa_back__') ?></a>
        <?php
    }

    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "twofa";

        $field = new TwoFactorCode();
        $field->name = "code";
        $form->addField($field);

        return $form;
    }
}