<?php

namespace Network;

use Framelix\Framelix\Exception\StopExecution;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;
use Framelix\FramelixTests\TestCase;

final class JsCallTest extends TestCase
{

    public static function onJsCallInvalid(JsCall $jsCall, $tooMuchParametersHere): void
    {
    }

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'echo') {
            echo 123;
        } elseif ($jsCall->action === 'return') {
            $jsCall->result = 123;
        } elseif ($jsCall->action === 'both') {
            $jsCall->result = 123;
            echo 123;
        }
    }

    public function tests(): void
    {
        // output buffer
        $jsCall = new JsCall('echo', null);
        $this->assertSame("123", $jsCall->call(__CLASS__));

        // explicit return value in result
        $jsCall = new JsCall('return', null);
        $this->assertSame(123, $jsCall->call(__CLASS__));

        // testing url invoke for jscall
        $this->setSimulatedUrl(JsCall::getUrl(__CLASS__, 'echo'));
        Buffer::start();
        try {
            View::findViewForUrl(Url::create())->onRequest();
        } catch (StopExecution) {
            $this->assertSame(JsonUtils::encode("123"), Buffer::get());
        }

        // error when mixing output buffer and explicit result
        $this->assertExceptionOnCall(function () {
            $jsCall = new JsCall('both', null);
            $jsCall->call(__CLASS__);
        });

        // calling no valid jscall method
        $this->assertExceptionOnCall(function () {
            $jsCall = new JsCall('both', null);
            $jsCall->call(__CLASS__ . "::onJsCallInvalid");
        });
    }
}
