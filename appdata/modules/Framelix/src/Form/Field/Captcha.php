<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Config;
use Framelix\Framelix\Exception\FatalError;
use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Html\PhpToJsData;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\CryptoUtils;
use Framelix\Framelix\Utils\JsonUtils;

use function explode;
use function file_get_contents;
use function http_build_query;
use function stream_context_create;

/**
 * A captcha field to provide captcha validation
 */
class Captcha extends Field
{
    public const TYPE_RECAPTCHA_V2 = 'recaptchav2';
    public const TYPE_RECAPTCHA_V3 = 'recaptchav3';

    /**
     * The type of the captcha
     * @see self::TYPE_*
     * @var string|null
     */
    public ?string $type = null;

    /**
     * Some captcha solutions (recaptcha) does allow setting a category for action tracking
     * @var string
     */
    public string $trackingAction = 'framelix';

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'verify':
                $type = $jsCall->parameters['type'] ?? null;
                switch ($type) {
                    case self::TYPE_RECAPTCHA_V2:
                    case self::TYPE_RECAPTCHA_V3:
                        $token = (string)($jsCall->parameters['token'] ?? null);
                        $responseData = self::recaptchaValidationRequest(
                            $token,
                            Config::$captchaKeys[$type]['privateKey']
                        );
                        if ($type === self::TYPE_RECAPTCHA_V3) {
                            $success = ($responseData['success'] ?? null) && ($responseData['score'] ?? 0) >= Config::$recaptchaV3Treshold;
                        } else {
                            $success = (bool)($responseData['success'] ?? null);
                        }
                        $jsCall->result = ['hash' => $success ? CryptoUtils::hash($token) : null];
                        break;
                }
                break;
        }
    }

    /**
     * Recaptcha validation request
     * @param string $token
     * @param string $privateKey
     * @return mixed
     */
    public static function recaptchaValidationRequest(string $token, string $privateKey): mixed
    {
        $browser = Browser::create();
        $browser->url = 'https://www.google.com/recaptcha/api/siteverify';
        $browser->requestMethod = 'post';
        $browser->requestBody = [
            'secret' => $privateKey,
            'response' => $token
        ];
        $browser->sendRequest();
        return $browser->getResponseJson();
    }

    public function __construct()
    {
        $this->type = Config::$captchaType;
    }

    public function jsonSerialize(): PhpToJsData
    {
        if (!$this->type) {
            throw new FatalError("Missing 'type' for " . __CLASS__);
        }
        $data = parent::jsonSerialize();
        $keys = [
            self::TYPE_RECAPTCHA_V2,
            self::TYPE_RECAPTCHA_V3
        ];
        foreach ($keys as $key) {
            $data->properties['publicKeys'][$key] = Config::$captchaKeys[$key]['publicKey'];
        }
        $data->properties['signedUrlVerifyToken'] = JsCall::getUrl(
            Captcha::class,
            'verify'
        );
        return $data;
    }

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
        $value = (string)$this->getSubmittedValue();
        if ($this->required) {
            $value = explode(":", $value);
            if ($this->type === self::TYPE_RECAPTCHA_V2 || $this->type === self::TYPE_RECAPTCHA_V3) {
                if (CryptoUtils::hash($value[0]) !== ($value[1] ?? null)) {
                    return Lang::get('__framelix_form_validation_captcha_invalid__');
                }
            }
        }
        return true;
    }

}