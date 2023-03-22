<?php

namespace Network;

use Framelix\Framelix\Config;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Network\Request;
use Framelix\FramelixTests\TestCase;

final class RequestTest extends TestCase
{

    public function tests(): void
    {
        // invalid ip test
        Config::$clientIpOverride = '127.0.0.1-1';
        $this->assertSame('127.0.0.11', Request::getClientIp());

        Config::$clientIpOverride = '127.0.0.1';
        $this->assertSame('127.0.0.1', Request::getClientIp());

        $this->setSimulatedBodyData(['foo' => ['bar' => '123']]);
        $this->assertSame('123', Request::getBody('foo[bar]'));
        $this->assertSame(['foo' => ['bar' => '123']], Request::getBody());

        $this->setSimulatedPostData(['foo' => ['bar' => '123']]);
        $this->assertSame('123', Request::getPost('foo[bar]'));

        $this->setSimulatedGetData(['foo' => ['bar' => '123']]);
        $this->assertSame('123', Request::getGet('foo[bar]'));

        // notice automatically uppercase in getter
        $this->setSimulatedHeader('HTTP_X_BROWSER_URL', 'foobar');
        $this->assertSame('foobar', Request::getHeader('http_x_browser_url'));

        $this->setSimulatedHeader('HTTP_X_FORWARDED_PROTO', 'https');
        $this->assertTrue(Request::isHttps());

        $this->assertFalse(Request::isAsync());
        $this->assertTrue(Framelix::isCli());
    }
}
