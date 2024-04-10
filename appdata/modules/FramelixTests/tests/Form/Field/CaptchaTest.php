<?php

namespace Form\Field;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\FramelixTests\TestCase;

final class CaptchaTest extends TestCase
{
    public function tests(): void
    {
        Config::addCaptchaKey(Captcha::TYPE_RECAPTCHA_V2, 'test', 'test');
        Config::addCaptchaKey(Captcha::TYPE_RECAPTCHA_V3, 'test', 'test');
        $field = new Captcha();
        $field->type = $field::TYPE_RECAPTCHA_V2;
        $field->name = $field::class;
        $field->required = true;
        $this->setSimulatedPostData([$field->name => "Foo"]);

        $jsCall = new JsCall('verify', ['type' => $field->type]);
        $jsCall->call([Captcha::class, "onJsCall"]);
        $this->assertTrue(ArrayUtils::keyExists($jsCall->result, 'hash'));

        $field->type = $field::TYPE_RECAPTCHA_V3;
        $jsCall = new JsCall('verify', ['type' => $field->type]);
        $jsCall->call([Captcha::class, "onJsCall"]);
        $this->assertTrue(ArrayUtils::keyExists($jsCall->result, 'hash'));

        $this->assertIsString($field->validate());

        $this->assertTrue(ArrayUtils::keyExists($field->jsonSerialize()->properties, 'signedUrlVerifyToken'));

        $this->assertExceptionOnCall(function () use ($field) {
            $field->type = null;
            $field->jsonSerialize();
        });
    }
}
