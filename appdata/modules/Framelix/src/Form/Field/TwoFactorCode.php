<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Cookie;
use RobThree\Auth\TwoFactorAuth;

use function in_array;
use function strlen;

/**
 * A field to enter and validate a TOTP two-factor code
 */
class TwoFactorCode extends Field
{
    public const COOKIE_NAME_SECRET = 'framelix-2fa-secret';
    public const COOKIE_NAME_BACKUPCODES = 'framelix-2fa-backupcodes';
    public const COOKIE_NAME_USERID = 'framelix-2fa-userid';
    public const COOKIE_NAME_USERSTAY = 'framelix-2fa-stay';

    /**
     * Auto submit the form containing this field after user has entered 6-digits
     * @var bool
     */
    public bool $formAutoSubmit = true;

    /**
     * Validate
     * Return error message on error or true on success
     * @return string|bool
     */
    public function validate(): string|bool
    {
        if (!$this->isVisible()) {
            return true;
        }
        $parentValidation = parent::validate();
        if ($parentValidation !== true) {
            return $parentValidation;
        }
        $value = (string)$this->getConvertedSubmittedValue();
        $valid = false;
        $secret = Cookie::get(self::COOKIE_NAME_SECRET, encrypted: true);
        if ($secret && strlen($value) === 6) {
            $tfa = new TwoFactorAuth();
            $result = $tfa->verifyCode($secret, $value);
            if ($result) {
                $valid = true;
            }
        }
        $backupCodes = Cookie::get(self::COOKIE_NAME_BACKUPCODES, encrypted: true);
        if ($backupCodes && strlen($value) === 10) {
            if (in_array($value, $backupCodes, true)) {
                $valid = true;
            }
        }
        if (!$valid) {
            return Lang::get('__framelix_form_validation_twofactor__');
        }
        return true;
    }
}