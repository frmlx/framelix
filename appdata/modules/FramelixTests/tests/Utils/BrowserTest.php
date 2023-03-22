<?php

namespace Utils;

use Framelix\Framelix\Config;
use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\JsonUtils;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function unlink;

final class BrowserTest extends TestCase
{

    public function tests(): void
    {
        // simulate php unit runner by setting the corresponding constant
        $file = Config::getUserConfigFilePath("01-core", "Framelix");
        file_put_contents($file, '<?php Framelix\Framelix\Config::$appSetupDone = true;Framelix\Framelix\Config::$salts["default"] = "0";');
        $browser = Browser::create();
        $browser->url = 'https://127.0.0.1:443/browsertestview';
        $browser->validateSsl = false;
        $browser->requestBody = "foobar";
        $browser->userPwd = "test:foo";
        $this->assertSame(0, $browser->getResponseCode());
        $this->assertSame('', $browser->getResponseText());
        $this->assertSame('', $browser->getResponseJson());
        $browser->sendRequest();
        $this->assertSame('{"get":[],"post":[],"body":null}', $browser->getResponseText());
        $this->assertSame(JsonUtils::decode('{"get":[],"post":[],"body":null}'), $browser->getResponseJson());
        $this->assertSame(200, $browser->getResponseCode());
        $this->assertSame('Basic dGVzdDpmb28=', $browser->responseHeaders['x-auth'] ?? null);
        unlink($file);
    }
}
