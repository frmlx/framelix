<?php

namespace Utils;

use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View;
use Framelix\FramelixTests\View\BrowserTestView;
use PHPUnit\Framework\TestCase;

use function strtoupper;

final class BrowserTest extends TestCase
{

    public function tests(): void
    {
        $browser = new Browser();
        $browser->url = View::getUrl(BrowserTestView::class)->getUrlAsString();
        $browser->validateSsl = false;
        $browser->requestBody = "foobar";
        $browser->userPwd = "test:foo";
        $this->assertSame(0, $browser->getResponseCode());
        $this->assertSame('', $browser->getResponseText());
        $this->assertSame('', $browser->getResponseJson());
        $browser->sendRequest();
        $this->assertSame('{"get":[],"post":[],"body":null,"method":"GET"}', $browser->getResponseText());
        $this->assertSame(
            JsonUtils::decode('{"get":[],"post":[],"body":null,"method":"GET"}'),
            $browser->getResponseJson()
        );
        $this->assertSame(200, $browser->getResponseCode());
        $this->assertSame('Basic dGVzdDpmb28=', $browser->responseHeaders['x-auth'] ?? null);

        $testMethods = [
            'get',
            'post',
            'delete',
            'put',
            'patch',
            'options',
        ];
        foreach ($testMethods as $testMethod) {
            $browser->requestMethod = $testMethod;
            $browser->sendRequest();
            $this->assertSame(
                JsonUtils::decode('{"get":[],"post":[],"body":null,"method":"' . strtoupper($testMethod) . '"}'),
                $browser->getResponseJson()
            );
        }
    }
}
