<?php

use Framelix\Framelix\Config;
use Framelix\Framelix\Exception\Redirect;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\FramelixTests\TestCase;

final class UrlTest extends TestCase
{
    public function tests(): void
    {
        Config::$applicationHost = "localhost";
        Config::$applicationUrlPrefix = '';

        // tests with global vars and generic stuff
        $indexPhp = __DIR__ . "/../public/index.php";
        $fakeUrlStr = 'https://localhost/foobar?param=zar&car=nar';
        $_GET['car'] = "nar";
        $fakeUrl = Url::create($fakeUrlStr);
        $this->setSimulatedUrl($fakeUrl);
        $this->assertTrue($fakeUrl->hasParameterWithValue('zar'));
        $this->assertFalse($fakeUrl->hasParameterWithValue(''));
        $this->assertSame($fakeUrlStr, Url::create()->jsonSerialize());
        $this->assertSame($fakeUrlStr, Url::create($fakeUrl)->jsonSerialize());
        $this->assertSame($fakeUrlStr, Url::getBrowserUrl()->jsonSerialize());

        // test getUrlToPublicFile
        $this->assertSame(
            null,
            Url::getUrlToPublicFile("notexist")
        );
        $this->assertSame(
            null,
            Url::getUrlToPublicFile('')
        );
        $this->assertSame(
            'https://localhost/_FramelixTests/index.php',
            (string)Url::getUrlToPublicFile($indexPhp, false)
        );
        $this->assertSame(
            'https://localhost/_Framelix/img/logo.svg',
            (string)Url::getUrlToPublicFile(__DIR__ . "/../../Framelix/public/img/logo.svg", false)
        );
        $this->assertStringStartsWith(
            'https://localhost/_FramelixTests/index.php?t=',
            (string)Url::getUrlToPublicFile($indexPhp)
        );
        // test remove parameter
        $fakeUrl = Url::create($fakeUrlStr);
        $fakeUrl->removeParameterByValue('zar');
        $fakeUrl->removeParameterByValue('');
        $this->assertSame(
            'https://localhost/foobar?car=nar',
            (string)$fakeUrl
        );

        // test url updating
        $fakeUrlStr = 'https://user:pass@localhost:4430/foobar?param=zar&car=nar#hash';
        $this->setSimulatedUrl($fakeUrlStr);
        $url = Url::create();
        $this->assertSame($fakeUrlStr, (string)$url);
        $url->update("https://test/balance?nothing");
        $this->assertSame("https://user:pass@test:4430/balance?param=zar&car=nar&nothing=#hash", (string)$url);
        $url->update($fakeUrlStr, true);
        $this->assertSame($fakeUrlStr, (string)$url);

        // test getter/setter
        $this->assertSame(4430, $url->getPort());
        $url->setPort(222);
        $this->assertSame(222, $url->getPort());

        $this->assertSame('https', $url->getScheme());
        $url->setScheme('bla');
        $this->assertSame('bla', $url->getScheme());

        $this->assertSame('localhost', $url->getHost());
        $url->setHost('bla');
        $this->assertSame('bla', $url->getHost());

        $this->assertSame('user', $url->getUsername());
        $url->setUsername('bla');
        $this->assertSame('bla', $url->getUsername());

        $this->assertSame('pass', $url->getPassword());
        $url->setPassword('bla');
        $this->assertSame('bla', $url->getPassword());

        $this->assertSame('hash', $url->getHash());
        $url->setHash('123');
        $this->assertSame('123', $url->getHash());
        $url->setHash(null);
        $this->assertSame(null, $url->getHash());

        $url = Url::create('https://localhost');
        $url->setParameter('foo', ['bar' => 'waröäüß@😊']);
        $this->assertSame('https://localhost?foo%5Bbar%5D=war' . urlencode("öäüß@😊"), (string)$url);
        $this->assertSame(["foo" => ['bar' => 'waröäüß@😊']], $url->getParameters());

        // test language in url
        $fakeUrlStr = 'https://localhost/en/bla';
        $this->setSimulatedUrl($fakeUrlStr);
        $url = Url::create();
        $this->assertFalse($url->hasParameterWithValue(''));
        $this->assertSame('en', $url->getLanguage());
        $url->replaceLanguage("de");
        $this->assertSame('de', $url->getLanguage());
        $this->assertSame('https://localhost/de/bla', (string)$url);

        // test sign/verify
        $url->sign();
        $this->assertNotNull($url->getParameter('__s'));
        $this->assertNotNull($url->getParameter('__expires'));
        $this->assertTrue($url->verify());


        $s = $url->getParameter('__s');
        $url->setParameter('__s', $s . "1");
        $this->assertFalse($url->verify());
        $url->setParameter('__s', $s);

        $url = Url::create();
        $url->sign(true, 1);
        sleep(2);
        $this->assertFalse($url->verify());

        $url->removeParameter("__s");
        $this->assertFalse($url->verify());

        $this->assertExceptionOnCall(function () {
            Url::create()->redirect();
        }, [], Redirect::class);

        FileUtils::getUserdataFilepath("test", true);
        $this->assertInstanceOf(
            Url::class,
            Url::getUrlToPublicFile(FRAMELIX_USERDATA_FOLDER . "/FramelixTests/public")
        );
        $this->assertInstanceOf(
            Url::class,
            Url::getUrlToPublicFile(__DIR__ . "/../../Framelix/public/img/logo.svg")
        );
        $this->assertInstanceOf(
            Url::class,
            Url::getUrlToPublicFile(__DIR__ . "/../../Framelix/lang/en.json")
        );
    }
}
